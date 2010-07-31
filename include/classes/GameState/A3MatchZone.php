<?php

require_once dirname(__FILE__).'/../Registry.php';
require_once dirname(__FILE__).'/A3GameType.php';

/** A3MatchZonePDOFactory takes an PDO object and the id to a specific
 * match and handles creation of A3MatchZone objects for that match by
 * loading all neccessary data from the PDO instance and creating the
 * objects from it. It implements the Factory pattern.
 * 
 * @author Daniel Baulig
 */
class A3MatchZonePDOFactory implements IFactory
{
	private $m_pdo;
	
	private $m_loadBaseSingleGameZone;
	private $m_loadConnectionsSingleGameZone;
	private $m_loadPiecesSingleGameZone;
	private $m_loadOptionsSingleMatchZone;
	
	private $m_loadBaseAllGameZones;

	/** Constructs the A3MatchZone factory, setting up the queries
	 * and the PDOStatement objects.
	 * 
	 * @param PDO $pdo
	 * @param int $match_id
	 */
	public function __construct( PDO $pdo, $match_id )
	{
		$this->m_pdo = $pdo;
		
		$base_sql = 		
			'SELECT z.zone_id AS id, bz.basezone_name AS name, n.nation_name AS owner,'
			. ' bz.basezone_water AS water, bz.basezone_production AS production'
			. ' FROM a3o_zones AS z INNER JOIN a3o_basezones AS bz ON z.zone_basezone = bz.basezone_id'
			. ' INNER JOIN a3o_matches AS m ON z.zone_match = m.match_id INNER JOIN a3o_games AS g'
			. ' ON m.match_game = g.game_id LEFT JOIN a3o_nations AS n ON g.game_id = n.nation_game'
			. ' AND z.zone_owner = n.nation_id WHERE z.zone_match = :match_id AND bz.basezone_name = :zone LIMIT 1;'; 
						
		$this->m_loadBaseSingleGameZone = $this->m_pdo->prepare( $base_sql );	
		$this->m_loadBaseSingleGameZone->bindValue( ':match_id', $match_id, PDO::PARAM_INT );

		
		$connections_sql =
			'SELECT bz2.basezone_name AS zone FROM a3o_zones AS z INNER JOIN a3o_basezones AS bz'
			. ' ON bz.basezone_id = z.zone_basezone INNER JOIN a3o_connections AS c ON'
			. ' c.connection_firstzone = bz.basezone_id INNER JOIN a3o_basezones AS bz2'
			. ' ON bz2.basezone_id = c.connection_secondzone WHERE z.zone_id = :zone_id;';
			
		$this->m_loadConnectionsSingleGameZone = $this->m_pdo->prepare( $connections_sql );
			
		// COALESCE will return the first non NULL value in its parameter list
		$pieces_sql =
			'SELECT t.type_name AS type, COALESCE(o.typeoption_value, \'0\') AS movement, n.nation_name AS nation, p.pieces_count AS count'
			. ' FROM a3o_pieces AS p INNER JOIN a3o_types AS t ON t.type_id = p.pieces_type'
			. ' INNER JOIN a3o_nations AS n ON n.nation_id = p.pieces_nation LEFT JOIN a3o_typeoptions AS o'
			. ' ON o.typeoption_type = t.type_id AND o.typeoption_name = \'movement\' WHERE p.pieces_zone = :zone_id;'; 
		
		$this->m_loadPiecesSingleGameZone = $this->m_pdo->prepare( $pieces_sql );
		
		
		$options_sql =
			'SELECT bzo.basezoneoption_name AS name, bzo.basezoneoption_value AS value FROM a3o_zones AS z'
			. ' INNER JOIN a3o_basezones AS bz ON bz.basezone_id = z.zone_basezone INNER JOIN' 
			. ' a3o_basezoneoptions AS bzo ON bzo.basezoneoption_basezone = bz.basezone_id WHERE z.zone_id = :zone_id;';
			
		$this->m_loadOptionsSingleMatchZone = $this->m_pdo->prepare( $options_sql );
		
		
		$all_base_sql = 
			'SELECT z.zone_id AS id, bz.basezone_name AS name, n.nation_name AS owner,'
			. ' bz.basezone_water AS water, bz.basezone_production AS production'
			. ' FROM a3o_zones AS z INNER JOIN a3o_basezones AS bz ON z.zone_basezone = bz.basezone_id'
			. ' INNER JOIN a3o_matches AS m ON z.zone_match = m.match_id INNER JOIN a3o_games AS g'
			. ' ON m.match_game = g.game_id LEFT JOIN a3o_nations AS n ON g.game_id = n.nation_game'
			. ' AND z.zone_owner = n.nation_id WHERE z.zone_match = :match_id;';
			
		$this->m_loadBaseAllGameZones = $this->m_pdo->prepare( $all_base_sql );
		$this->m_loadBaseAllGameZones->bindValue( 'match_id', $match_id, PDO::PARAM_INT );
	}
	
	/** Loads all connections belonging to to $zone_id and returns them as an array.
	 * 
	 * Note, that "belonging to" means that the zone is listed in the
	 * firstzone field. If it is only listed in the secondzone field
	 * it will not be returned. As such each entry in the connection
	 * tables is a directed edge.
	 * That means that you eithr need two entries for bidirectional
	 * connections or implement the hasConnection method to actually
	 * check in both directions. This is the current implementation.
	 * 
	 * @param int $zone_id
	 * @return array
	 */
	protected function loadConnections( $zone_id )
	{
		$this->m_loadConnectionsSingleGameZone->bindValue( ':zone_id', $zone_id, PDO::PARAM_INT );
		$this->m_loadConnectionsSingleGameZone->execute( );
		$connections = array ( );
		while ( $connection = $this->m_loadConnectionsSingleGameZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$connections[$connection['zone']] = true;
		}
		return $connections;
	}
	
	/** Loads all pieces in the zone referenced by $zone_id and returns them as an array.
	 * 
	 * @param int $zone_id
	 * @return array
	 */
	protected function loadPieces( $zone_id )
	{
		$this->m_loadPiecesSingleGameZone->bindValue( ':zone_id', $zone_id, PDO::PARAM_INT );
		$this->m_loadPiecesSingleGameZone->execute( );
		$pieces = array( );
		while ( $row = $this->m_loadPiecesSingleGameZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			for( $i = 0; $i < $row['movement']; $i++ )
			{
				$pieces[$row['nation']][$row['type']][$i] = 0;
			}
			$pieces[$row['nation']][$row['type']][$row['movement']] = $row['count'];
		}
		return $pieces;
	}
	
	//TODO: Profile performance boost zone_id vs basezone_id
	/** Loads all options belonging to the basezone to which $zone_id belongs and returns them as an array
	 * 
	 * Regarding todo: the options loaded aren't actually direcly connected to the zone,
	 * but rather to the basezone of the zone. Since there is no direct instanciation of
	 * basezones (there is basicly no need) the loading is placed in the A3MatchZone factory
	 * and the options are stored in the A3MatchZone objects. However, the loading is for conveinience
	 * reasons based on the $zone_id and not the basezone_id, although basezone_id could be 
	 * taken from a previous query while loading the zone and could be used to load the options
	 * faster. Maybe this should be changed to take basezone_id instead of zone_id.
	 * 
	 * @param int $zone_id
	 * @return array
	 */
	protected function loadOptions( $zone_id )
	{
		$this->m_loadOptionsSingleMatchZone->bindValue( ':zone_id', $zone_id, PDO::PARAM_INT );
		$this->m_loadOptionsSingleMatchZone->execute( );
		
		$options = array( );
		
		while( $option = $this->m_loadOptionsSingleMatchZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
		//TODO: Implement A3MatchZone->loadOptions  	
		}
	}
	
	/** Creates a single A3MatchZone object from key and returns it.
	 * 
	 * If there is no zone with the specified key found in the database
	 * the method will throw a DomainException exception.
	 * 
	 * @see include/classes/IFactory::createSingleProduct()
	 * @param string $key
	 * @return A3MatchZone
	 * @throws DomainException
	 */
	public function createSingleProduct( $key )
	{
		$this->m_loadBaseSingleGameZone->bindValue( ':zone', $key, PDO::PARAM_STR );
		$this->m_loadBaseSingleGameZone->execute( );
		$zone = $this->m_loadBaseSingleGameZone->fetch( PDO::FETCH_ASSOC );
		
		if ( !$zone )
		{
			// $key must have been sanitized, in case this message is displayed to a user!
			//TODO: Sanitize all incoming keys (type-, zone-, nation- and alliance names) upon first touch!
			throw new DomainException('Specified zone ' . $key . ' not valid.');
		}

		$connections = $this->loadConnections( $zone['id'] );		
		$pieces = $this->loadPieces( $zone['id'] );
		$options = $this->loadOptions( $zone['id'] );
		
		$zone[A3MatchZone::CONNECTIONS] = $connections;
		$zone[A3MatchZone::PIECES] = $pieces;
		$zone[A3MatchZone::OPTIONS] = $options;
		unset($zone['id']);
		return new A3MatchZone( $zone );
	}
	
	/** Loads each A3MatchZone for this match and returns them in an array
	 * (non-PHPdoc)
	 * @see include/classes/IFactory::createAllProducts()
	 * @return array
	 */
	public function createAllProducts( )
	{
		$zones = array( );
		$this->m_loadBaseAllGameZones->execute( );
		while ( $zone = $this->m_loadBaseAllGameZones->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$connections = $this->loadConnections( $zone['id'] );			
			$pieces = $this->loadPieces( $zone['id'] );
			$options = $this->loadOptions( $zone['id'] );
			
			$zone[A3MatchZone::PIECES] = $pieces;
			$zone[A3MatchZone::CONNECTIONS] = $connections;
			$zone[A3MatchZone::OPTIONS] = $options;
			
			unset($zone['id']);
			$zones[ $zone[A3MatchZone::NAME] ] = new A3MatchZone( $zone );
		}
		
		return $zones;
	}
}

/** A3MatchZoneRegistry is a implementation of the Registry pattern and stores
 * key => value pairs where keys are strings (names) and the values are possibly
 * all A3MatchZone objects of a specific match.
 * 
 * The registry is able to either precache all the match's zones by calling the
 * precacheElements method or load zones ad-hoc from the database as they are
 * requested.
 * The registry uses a factory implementing the IFactory interface that it gets 
 * passed in the constructor to construct the A3MatchZone objects.
 * 
 * Note that it also implements the Signleton pattern and as such cannot be 
 * instanciated directly, but only by calling the initializeRegistry class
 * method.
 * After the Registry is initialized you can either use it's getInstance class
 * method to directly access the registry object or have the getZone class method
 * marshall your requests for you.
 * 
 * @author Daniel Baulig
 * @see BaseRegistry
 */
class A3MatchZoneRegistry extends BaseRegistry
{
	private static $instance = null;
	
	/** Initializes the registry
	 * 
	 * Throws an exception if the registry is already initialized.
	 * 
	 * @param IFactory $factory
	 * @throws Exception
	 */
	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance === null )
		{
			self::$instance = new A3MatchZoneRegistry( $factory );
		} 
		else
		{
			throw new Exception('Registry already initialized.');
		}
	}
	
	/** Returns an instance of the registry
	 * 
	 * Throws an exception if the registry is not initialized yet.
	 * 
	 * @throws Exception
	 */
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception('Registry must be initialized first.');
		}
		return self::$instance;
	}
	
	/** Returns the element specified by $key from the registry.
	 * 
	 * Note that this method does not check if the registry was already initialized.
	 * You are responsible to make sure the registry is ready and setup when calling
	 * this method.
	 * 
	 * @param mixed $key
	 */
	public static function getZone( $key )
	{
		return self::$instance->getElement( $key );
	}
}

class A3MatchZone
{
	/** Holds the game zones data
	 * 
	 * 'name' => string $zoneName
	 * 'connections' => string $otherZoneName => boolean true
	 * 'pieces' => string $nation => string $type => int $remainingMovement => int $count
	 * 'owner' => string $nation
	 * 'water' => boolean $water
	 * 'production' => int $production
	 * 
	 * See also the following class constants for a means of securing the array
	 * is adressed correctly even if keys may change in future implementations.
	 * 
	 * @var array
	 * @access protected
	 */
	protected $m_data;
	
	const NAME = 'name';
	const PIECES = 'pieces';
	const CONNECTIONS = 'connections';
	const OPTIONS = 'options';
	const OWNER = 'owner';
	const WATER = 'water';
	const PRODUCTION = 'production';

	/** Returns the number of pieces of the given type in this zone
	 * 
	 * @param string $type
	 * @return int 
	 */
	public function countPieces( $nation, $type, $minimumRemainingMovement = 0 )
	{
		$piecesCount = 0;
		if ( array_key_exists( $nation, $this->m_data[A3MatchZone::PIECES] ) && array_key_exists( $type, $this->m_data[A3MatchZone::PIECES][$nation] ) )
		{
			for( $i=$minimumRemainingMovement; $i <= A3GameTypeRegistry::getType( $type )->movement; $i++ )
			{
				$piecesCount += $this->m_data[A3MatchZone::PIECES][$nation][$type][$i];
			}
		}
		return $piecesCount;
	}
	
	/** Moves up to $count pieces of nation $nation and type $type to zone $target draining $distance movement. 
	 * 
	 * Use {@link canMovePieces} and {@link isPathValid} to check if a given move is valid 
	 * entirely before calling movePieces. movePieces will move as many pieces as possible,
	 * even if the input values exceed the number of pieces present. Be aware that this happen
	 * due to an exploit attempt. Be sure to secure against such attempts.
	 * 
	 * Also $count values of less than 0 can be used to seriously exploit this method.
	 * Because of this the method will throw an UnexpectedValueException if $count has
	 * a value of below 0.
	 * 
	 * @param int $count
	 * @param string $nation
	 * @param string $type
	 * @param int $distance
	 * @param string $target
	 */
	public function movePieces( $count, $nation, $type, $distance, $target )
	{
		if ( $count < 0 )
		{
			throw new UnexpectedValueException( 'Possible attempt at exploiting.' );
		}
		if ( array_key_exists( $nation , $this->m_data[A3MatchZone::PIECES] ) && array_key_exists( $type, $this->m_data[A3MatchZone::PIECES][$nation] ) )
		{
			$total = 0;
			$target = A3GameZoneRegistry::getZone( $target );

			$typeMovement = A3GameTypeRegistry::getType( $type )->movement;
			
			for( $i = $distance; $i <= $typeMovement; $i++ )
			{
				$moved = $count > $this->m_data[A3MatchZone::PIECES][$nation][$type][$i] ? $this->m_data[A3MatchZone::PIECES][$nation][$type][$i] : $count;
				$count = $count - $moved;
				$target->m_data[A3MatchZone::PIECES][$nation][$type][$i - $distance] += $moved;
				$this->m_data[A3MatchZone::PIECES][$nation][$type][$i] -= $moved;
				$total += $moved;
			}
		}
	}
	
	/** Returns if the amount of pieces of $nation nation and $type type can be moved 
	 * (transfered) from this zone to the given zone.
	 * 
	 * Be aware that $count and $distance may not be negative!
	 * 
	 * @param int $count
	 * @param string $nation
	 * @param string $type
	 * @param int $distance
	 * @return boolean
	 */
	public function canMovePieces( $count, $nation, $type, $distance )
	{
		$moveable = $this->countPieces( $nation, $type, $distance );
		return $moveable !== 0 && $moveable >= $count;
	}
	
	//TODO: refactor this behaviour out of a baseclass
	/** Checks if this zone has a connection to the given zone.
	 * 
	 * Note that the zone checks in both directions and thus presumes
	 * connections are always bidirectional.
	 * 
	 * @param string $zone
	 * @return boolean
	 */
	public function hasConnection( $zone )
	{
		if ( array_key_exists( $zone, $this->m_data[A3MatchZone::CONNECTIONS] ) )
		{
			return true;
		}
		$zone = A3MatchZoneRegistry::getZone( $zone );
		return array_key_exists( $this->m_data[A3MatchZone::NAME], $zone->m_data[A3MatchZone::CONNECTIONS] );
	}
	
	/** Checks if a path starting from here is valid.
	 * 
	 * The optional parameter $water specifies if
	 * isValidPath should check if the path is entirely
	 * on water (true) or on land (false). If it is not
	 * specified or set to null, isValidPath will allow
	 * water aswell as land zones on the path.
	 * 
	 * The optional parameter $alliance specifies if the
	 * zones are checked against ownership and only a path
	 * controlled by the specified alliance may be traversed.
	 * 
	 * The optional parameter $combat speciyfies if the
	 * movement may end in an enemy zone, thus triggering
	 * combat. Defaults to false.
	 * 
	 * @param array $path
	 * @param boolean $water (optional)
	 * @param string $alliance (optional)
	 * @param boolean $combat (optional)
	 * @return boolean
	 */
	public function isValidPath( array $path, $water = null, $alliance = null, $combat = false )
	{
		$zone = $this;
		if ( $alliance !== null )
		{
			$alliance = A3GameAllianceRegistry::getAlliance( $alliance );
		}
		
		// get zone where movement ends
		$endZone = end( $path );
		
		foreach( $path as $step )
		{
			// TODO: Add checks if the zone has "impassible" flag (eg Sahara)
			// dont allow to enter a zone that is not connected
			if ( !$zone->hasConnection( $step ) )
			{
				return false;
			}
			
			// create/get A3GameZone object for the upcoming zone
			$zone = A3MatchZoneRegistry::getZone( $step );
			
			// if water is null (no terrain checking) simply skip
			// if water is true, only allow to enter water
			// if water is false, only allow to enter land
			if ( $water !== null && $water !== $zone->m_data[A3MatchZone::WATER] )
			{
				return false;
			}
			// if alliance checking is enabled only allow to enter zones controlled by given alliance
			if( $alliance !== null && ! $alliance->isAllied( $zone->m_data[A3MatchZone::OWNER] ) )
			{
				// however only, if this is not the last zone and triggering combat is not allowed
				if ( !($step === $endZone && $combat) )
				{
					return false;
				}				
			}
		}
		return true;
	}
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
	}
}