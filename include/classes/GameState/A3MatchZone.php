<?php

require_once dirname(__FILE__).'/../Registry.php';
require_once dirname(__FILE__).'/A3GameType.php';

class A3MatchZonePDOFactory implements IFactory
{
	private $m_pdo;
	
	private $m_loadBaseSingleGameZone;
	private $m_loadConnectionsSingleGameZone;
	private $m_loadPiecesSingleGameZone;
	private $m_loadOptionsSingleMatchZone;
	
	private $m_loadBaseAllGameZones;

	public function __construct( PDO $pdo, $match )
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
		$this->m_loadBaseSingleGameZone->bindValue( ':match_id', $match, PDO::PARAM_INT );

		
		$connections_sql =
			'SELECT bz2.basezone_name AS zone FROM a3o_zones AS z INNER JOIN a3o_basezones AS bz'
			. ' ON bz.basezone_id = z.zone_basezone INNER JOIN a3o_connections AS c ON'
			. ' c.connection_firstzone = bz.basezone_id INNER JOIN a3o_basezones AS bz2'
			. ' ON bz2.basezone_id = c.connection_secondzone WHERE z.zone_id = :zone_id;';
			
		$this->m_loadConnectionsSingleGameZone = $this->m_pdo->prepare( $connections_sql );
			
		
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
		$this->m_loadBaseAllGameZones->bindValue( 'match_id', $match, PDO::PARAM_INT );
	}
	
	protected function loadConnections( $zone_id )
	{
		$this->m_loadConnectionsSingleGameZone->bindValue( ':zone_id', $zone_id, PDO::PARAM_INT );
		$this->m_loadConnectionsSingleGameZone->execute( );
		$connections = array ( );
		while ( $row = $this->m_loadConnectionsSingleGameZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$connections[$row['zone']] = true;
		}
		return $connections;
	}
	
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
	
	//TODO: Think about if it is usefull to not load options by zone_id but by basezone_id, which will safe plenty of JOINs
	protected function loadOptions( $zone_id )
	{
		$this->m_loadOptionsSingleMatchZone->bindValue( ':zone_id', $zone_id, PDO::PARAM_INT );
		$this->m_loadOptionsSingleMatchZone->execute( );
		
		$options = array( );
		
		while( $option = $this->m_loadOptionsSingleMatchZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			
		}
	}
	
	public function createSingleProduct( $key )
	{
		$this->m_loadBaseSingleGameZone->bindValue( ':zone', $key, PDO::PARAM_STR );
		$this->m_loadBaseSingleGameZone->execute( );
		$zone = $this->m_loadBaseSingleGameZone->fetch( PDO::FETCH_ASSOC );
		
		if ( !$zone )
		{
			throw new Exception('Specified zone not found.');
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

	/** Returns the number of pieces of the given type
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
	 * Returns true if transfer was successfull and false if there was a problem (eg. 
	 * less pieces available than specified or target zone not valid).
	 * Even if returning false the method will transfer as many pieces as possible.
	 * Use {@link canMovePieces} and {@link isPathValid} to check if a given move is valid 
	 * entirely without moving any pieces.
	 * 
	 * @param int $count
	 * @param string $nation
	 * @param string $type
	 * @param int $distance
	 * @param string $target
	 * @return boolean
	 */
	public function movePieces( $count, $nation, $type, $distance, $target )
	{
		if ( array_key_exists( $nation , $this->m_data[A3MatchZone::PIECES] ) && array_key_exists( $type, $this->m_data[A3MatchZone::PIECES][$nation] ) )
		{
			$total = 0;
			$target = A3GameZoneRegistry::getZone( $target );
			if ( $target !== null )
			{
				for( $i = $distance; $i <= A3GameTypeRegistry::getType( $type )->movement; $i++ )
				{
					$moved = $count > $this->m_data[A3MatchZone::PIECES][$nation][$type][$i] ? $this->m_data[A3MatchZone::PIECES][$nation][$type][$i] : $count;
					$count = $count - $moved;
					$target->m_data[A3MatchZone::PIECES][$nation][$type][$i - $distance] += $moved;
					$this->m_data[A3MatchZone::PIECES][$nation][$type][$i] -= $moved;
					$total += $moved;
				}
				if ( $total === $count )
				{
					return true;
				}
			}
		}
		return false;
	}
	
	/** Returns if the amount of pieces of $nation nation and $type type can be moved 
	 * (transfered) from this zone to the given zone.
	 * 
	 * @param int $count
	 * @param string $nation
	 * @param string $type
	 * @param int $distance
	 * @return boolean
	 */
	public function canMovePieces( $count, $nation, $type, $distance )
	{
		return $this->countPieces( $nation, $type, $distance ) >= $count;
	}
	
	/** Checks if this zone has a connection to the given zone.
	 * 
	 * @param string $zone
	 * @return boolean
	 */
	public function hasConnection( $zone )
	{
		return array_key_exists( $zone, $this->m_data[A3MatchZone::CONNECTIONS] ) || 
			array_key_exists( $this->data[A3MatchZone::NAME], A3GameZoneRegistry::getZone( $zone )->data[A3MatchZone::CONNECTIONS] );
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
		$alliance = A3GameAllianceRegistry::getAlliance( $alliance );
		
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
			$zone = A3GameZoneRegistry::getElement( $step );
			
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
	
	public function isWater( )
	{
		return $this->m_data[A3MatchZone::OPTIONS]['water'];
	}
	
	public function getName( )
	{
		return $this->m_data[self::NAME];
	}
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
		$this->m_name = $data['name'];
		$this->m_owner = $data['owner'];
		//$this->m_production = $data['production'];
		//$this->m_water = $data['water'];

		// copy arrays
		$this->m_connections = $data['connections'];
		$this->m_pieces = $data['pieces'];
	}
}