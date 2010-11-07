<?php
/** MatchZonePDOFactory takes an PDO object and the id to a specific
 * match and handles creation of MatchZone objects for that match by
 * loading all neccessary data from the PDO instance and creating the
 * objects from it. It implements the Factory pattern.
 * 
 * @author Daniel Baulig
 */
class MatchZonePDOFactory implements IFactory
{
	protected $m_pdo;
	protected $m_match;
	
	protected $m_loadBaseSingleGameZone;
	protected $m_loadConnectionsSingleGameZone;
	protected $m_loadPiecesSingleGameZone;
	protected $m_loadOptionsSingleMatchZone;
	protected $m_loadbasezonePiecesSingleGameZone;
	
	protected $m_loadBaseAllGameZones;
	protected $m_loadBasezoneAllGameZones;

	/** Constructs the MatchZone factory, setting up the queries
	 * and the PDOStatement objects.
	 * 
	 * @param PDO $pdo
	 * @param int $match_id
	 */
	public function __construct( PDO $pdo, MatchBoard $match )
	{
		$this->m_pdo = $pdo;
		$this->m_match = $match;
		
		$base_sql = 		
			'SELECT z.zone_id AS id, bz.basezone_name AS name, n.nation_name AS owner, bz.basezone_id AS basezone'
			. ' FROM a3o_zones AS z INNER JOIN a3o_basezones AS bz ON z.zone_basezone = bz.basezone_id'
			. ' INNER JOIN a3o_matches AS m ON z.zone_match = m.match_id INNER JOIN a3o_games AS g'
			. ' ON m.match_game = g.game_id LEFT JOIN a3o_nations AS n ON g.game_id = n.nation_game'
			. ' AND z.zone_owner = n.nation_id WHERE z.zone_match = :match_id AND bz.basezone_name = :zone LIMIT 1;'; 
						
		$this->m_loadBaseSingleGameZone = $this->m_pdo->prepare( $base_sql );	
		$this->m_loadBaseSingleGameZone->bindValue( ':match_id', $this->m_match->getMatchId( ), PDO::PARAM_INT );
		
		$connections_sql =
			'SELECT bz2.basezone_name AS zone FROM a3o_basezones AS bz INNER JOIN a3o_connections AS c ON'
			. ' c.connection_firstzone = bz.basezone_id INNER JOIN a3o_basezones AS bz2'
			. ' ON bz2.basezone_id = c.connection_secondzone WHERE bz.basezone_id = :basezone_id;';
			
		$this->m_loadConnectionsSingleGameZone = $this->m_pdo->prepare( $connections_sql );

		$pieces_sql =
			'SELECT t.type_name AS type, n.nation_name AS nation, p.pieces_count AS count'
			. ' FROM a3o_pieces AS p INNER JOIN a3o_types AS t ON t.type_id = p.pieces_type'
			. ' INNER JOIN a3o_nations AS n ON n.nation_id = p.pieces_nation WHERE p.pieces_zone = :zone_id;'; 
		
		$this->m_loadPiecesSingleGameZone = $this->m_pdo->prepare( $pieces_sql );
		
		$basezone_pieces_sql =
			'SELECT t.type_name AS type, n.nation_name AS nation, p.pieces_count AS count'
			. ' FROM a3o_basezonepieces AS p INNER JOIN a3o_types AS t ON t.type_id = p.basezonepieces_type'
			. ' INNER JOIN a3o_nations AS n ON n.nation_id = p.basezonepieces_nation'
			. ' WHERE p.basezonepieces_basezone = :basezone_id;';
		
		$this->m_loadBasezonePiecesSingleGameZone = $this->m_pdo->prepare( $basezone_pieces_sql );
		
		$options_sql =
			'SELECT bzo.basezoneoption_name AS name, bzo.basezoneoption_value AS value FROM a3o_basezones AS bz INNER JOIN'
			. ' a3o_basezoneoptions AS bzo ON bzo.basezoneoption_basezone = bz.basezone_id'
			. ' WHERE bz.basezone_id = :basezone_id;';
			
		$this->m_loadOptionsSingleMatchZone = $this->m_pdo->prepare( $options_sql );
		
		
		$all_base_sql = 
			'SELECT z.zone_id AS id, bz.basezone_name AS name, n.nation_name AS owner, bz.basezone_id AS basezone'
			. ' FROM a3o_zones AS z INNER JOIN a3o_basezones AS bz ON z.zone_basezone = bz.basezone_id'
			. ' INNER JOIN a3o_matches AS m ON z.zone_match = m.match_id INNER JOIN a3o_games AS g'
			. ' ON m.match_game = g.game_id LEFT JOIN a3o_nations AS n ON g.game_id = n.nation_game'
			. ' AND z.zone_owner = n.nation_id WHERE z.zone_match = :match_id;';
			
		$all_basezone_sql = 
			'SELECT bz.basezone_name AS name, n.nation_name AS owner, bz.basezone_id AS basezone FROM a3o_basezones AS bz'
			. ' LEFT JOIN a3o_nations AS n ON bz.basezone_game = n.nation_game'
			. ' AND bz.basezone_owner = n.nation_id WHERE bz.basezone_game = :game_id;';
			
		$this->m_loadBaseAllGameZones = $this->m_pdo->prepare( $all_base_sql );
		$this->m_loadBaseAllGameZones->bindValue( ':match_id', $this->m_match->getMatchId( ), PDO::PARAM_INT );
		
		$this->m_loadBasezoneAllGameZones = $this->m_pdo->prepare( $all_basezone_sql );
		$this->m_loadBasezoneAllGameZones->bindValue( ':game_id', $this->m_match->getGameId(), PDO::PARAM_INT );
	}
	
	/** Loads all connections belonging to to basezone and returns them as an array.
	 * 
	 * Note, that "belonging to" means that the zone is listed in the
	 * firstzone field. If it is only listed in the secondzone field
	 * it will not be returned. As such each entry in the connection
	 * tables is a directed edge.
	 * That means that you eithr need two entries for bidirectional
	 * connections or implement the hasConnection method to actually
	 * check in both directions or override loadConnections to load 
	 * connections "from" both fields. This is the current implementation.
	 * 
	 * @param int $zone_id
	 * @return array
	 */
	protected function loadConnections( $basezone_id )
	{
		$this->m_loadConnectionsSingleGameZone->bindValue( ':basezone_id', $basezone_id, PDO::PARAM_INT );
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
			$pieces[$row['nation']][$row['type']] = $row['count'];
		}
		return $pieces;
	}
	
	protected function loadBasezonePieces( $basezone_id )
	{
		$this->m_loadBasezonePiecesSingleGameZone->bindValue( ':basezone_id', $basezone_id, PDO::PARAM_INT );
		$this->m_loadBasezonePiecesSingleGameZone->execute( );
		$pieces = array( );
		while ( $row = $this->m_loadBasezonePiecesSingleGameZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$pieces[$row['nation']][$row['type']] = $row['count'];
		}
		return $pieces;
	}
	
   /** Loads all options belonging to the basezone and returns them as an array
	 * 
	 * @param int $basezone_id
	 * @return array
	 */
	protected function loadOptions( $basezone_id )
	{
		$this->m_loadOptionsSingleMatchZone->bindValue( ':basezone_id', $basezone_id, PDO::PARAM_INT );
		$this->m_loadOptionsSingleMatchZone->execute( );
		
		$options = array( );
		
		while( $option = $this->m_loadOptionsSingleMatchZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$options[$option['name']] = $option['value'];
		}
		
		return $options;
	}
	
	protected function createObject( array $data )
	{
		return new MatchZone( $this->m_match, $data );
	}
	
	/** Creates a single MatchZone object from key and returns it.
	 * 
	 * If there is no zone with the specified key found in the database
	 * the method will throw a DomainException exception.
	 * 
	 * @see include/classes/IFactory::createSingleProduct()
	 * @param string $key
	 * @return MatchZone
	 * @throws DomainException
	 */
	public function createSingleProduct( $key )
	{
		$this->m_loadBaseSingleGameZone->bindValue( ':zone', $key, PDO::PARAM_STR );
		$this->m_loadBaseSingleGameZone->execute( );
		$zone = $this->m_loadBaseSingleGameZone->fetch( PDO::FETCH_ASSOC );
		
		if ( !$zone )
		{
			// WARHNING! $key must have been sanitized, in case this message is displayed to a user!
			//TODO: Sanitize all incoming keys (type-, zone-, nation- and alliance names) upon first touch!
			throw new DomainException('Specified zone ' . $key . ' not valid.');
		}

		$connections = $this->loadConnections( $zone['basezone'] );		
		$pieces = $this->loadPieces( $zone['id'] );
		$options = $this->loadOptions( $zone['basezone'] );
		
		$zone[MatchZone::CONNECTIONS] = $connections;
		$zone[MatchZone::PIECES] = $pieces;
		$zone[MatchZone::OPTIONS] = $options;
		unset($zone['id']);
		return $this->createObject( $zone );
	}
	
	/** Loads each MatchZone for this match and returns them in an array
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
			$connections = $this->loadConnections( $zone['basezone'] );			
			$pieces = $this->loadPieces( $zone['id'] );
			$options = $this->loadOptions( $zone['basezone'] );
			
			$zone[MatchZone::PIECES] = $pieces;
			$zone[MatchZone::CONNECTIONS] = $connections;
			$zone[MatchZone::OPTIONS] = $options;
			
			unset($zone['id']);
			$zones[ $zone[MatchZone::NAME] ] = $this->createObject( $zone );
		}
		
		return $zones;
	}
	
	public function createAllProductsFromBasezone( )
	{
		$zones = array( );
		$this->m_loadBasezoneAllGameZones->execute( );
		while ( $zone = $this->m_loadBasezoneAllGameZones->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$connections = $this->loadConnections( $zone['basezone'] );			
			$pieces = $this->loadBasezonePieces( $zone['basezone'] );
			$options = $this->loadOptions( $zone['basezone'] );
			
			$zone[MatchZone::PIECES] = $pieces;
			$zone[MatchZone::CONNECTIONS] = $connections;
			$zone[MatchZone::OPTIONS] = $options;
			
			$zones[ $zone[MatchZone::NAME] ] = $this->createObject( $zone );
		}
		
		return $zones;
	}
}

// Note to myself: Following Storer is just a concept.
class MatchZoneStreamStorer extends Storer
{
	protected $m_stream;
	public function __construct( $stream, $named = true )
	{
		$this->m_stream = $stream;
		$this->m_named = $named;
	}
	public function store( IStoreable $zone )
	{
		$data = $this->getStoreableData( $zone );
		
		fwrite($this->m_stream, ($this->m_named ? '"'.$data[MatchZone::NAME].'":' : '') . json_encode( $data ) );
	}
}

class MatchZonePDOStorer extends Storer
{
	protected $m_pdo;
	
	protected $m_saveZoneOwner;
	protected $m_saveZonePieces;
	protected $m_clearMatchPieces;
	
	public function __construct( PDO $pdo, $match_id )
	{
		$this->m_pdo = $pdo;
		
		$sql_owner =
			'UPDATE a3o_zones AS z INNER JOIN a3o_basezones AS bz ON bz.basezone_id = z.zone_basezone'
			. ' INNER JOIN a3o_matches m ON z.zone_match = m.match_id'
			. ' INNER JOIN a3o_games g ON g.game_id = m.match_game' 
			. ' INNER JOIN a3o_nations AS n ON n.nation_game = g.game_id SET z.zone_owner = n.nation_id'
			. ' WHERE bz.basezone_name = :zone AND m.match_id = :match_id AND n.nation_name = :nation;';
			
		$this->m_saveZoneOwner = $this->m_pdo->prepare( $sql_owner );
		$this->m_saveZoneOwner->bindValue( ':match_id' , $match_id, PDO::PARAM_INT );
			
		$sql_pieces =
			'INSERT INTO a3o_pieces (pieces_zone, pieces_nation, pieces_type, pieces_count)'
			. ' SELECT z.zone_id, n.nation_id, t.type_id, :insert_count FROM a3o_zones AS z' 
			. ' INNER JOIN a3o_basezones AS bz ON bz.basezone_id = z.zone_basezone'
			. ' INNER JOIN a3o_nations AS n ON n.nation_game = bz.basezone_game' 
			. ' INNER JOIN a3o_types AS t ON t.type_game = n.nation_game'
        	. ' INNER JOIN a3o_matches AS m ON m.match_game = t.type_game'
			. ' WHERE n.nation_name = :nation AND t.type_name = :type AND bz.basezone_name = :zone'
			. ' AND m.match_id = :match_id ON DUPLICATE KEY UPDATE pieces_count = :update_count;';
			
		$this->m_saveZonePieces = $this->m_pdo->prepare( $sql_pieces );
		$this->m_saveZonePieces->bindValue( ':match_id' , $match_id );
		
		$sql_clear_pieces =
			'DELETE FROM a3o_pieces AS p INNER JOIN a3o_zones AS z ON p.pieces_zone = z.zone_id'
			. ' WHERE pieces_count = 0 AND z.zone_match = :match_id;';
		
		$this->m_clearMatchPieces = $this->m_pdo->prepare( $sql_clear_pieces );
		$this->m_clearMatchPieces->bindValue( ':match_id' , $match_id );
	}
	
	/** Stores the given zone.
	 * 
	 * Note that although MatchZone is not hinted due to lack of stricter
	 * typing on inhertied methods this class will only handle MatchZones
	 * correctly.
	 * 
	 * @param MatchZone $zone
	 */
	public function store( IStoreable $zone )
	{
		$data = $this->getStoreableData( $zone );
		
		$this->m_pdo->beginTransaction( );
		try 
		{
			$this->m_saveZoneOwner->bindValue( ':nation' , $data[MatchZone::OWNER], PDO::PARAM_STR );
			$this->m_saveZoneOwner->bindValue( ':zone' , $data[MatchZone::NAME], PDO::PARAM_STR );	
			$this->m_saveZoneOwner->execute( );
			
			$this->m_saveZonePieces->bindValue( ':zone' , $data[MatchZone::NAME], PDO::PARAM_STR );
			foreach( $data[MatchZone::PIECES] as $nation => $pieces )
			{
				$this->m_saveZonePieces->bindValue( ':nation' , $nation, PDO::PARAM_STR );
				foreach( $pieces as $type => $count ) 
				{
					$this->m_saveZonePieces->bindValue( ':type' , $type, PDO::PARAM_STR );
					$this->m_saveZonePieces->bindValue( ':update_count' , $count, PDO::PARAM_INT );
					$this->m_saveZonePieces->bindValue( ':insert_count' , $count, PDO::PARAM_INT );
					
					$this->m_saveZonePieces->execute( );
				}
			}
			
			//$this->
			
			$this->m_pdo->commit( );
		}
		catch( Exception $e )
		{
			$this->m_pdo->rollBack( );
			throw $e;
		}
	}
	
	public function clearMatchPieces( )
	{
		$this->m_clearMatchPieces->execute( ); 
	}
}


class MatchZone implements IStoreable
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
	
	protected $m_state;
	
	const NAME = 'name';
	const PIECES = 'pieces';
	const CONNECTIONS = 'connections';
	const OPTIONS = 'options';
	const OWNER = 'owner';

	/** Returns the number of pieces of the given type in this zone
	 * 
	 * @param string $nation
	 * @param string $type
	 * @return int 
	 */
	public function countPieces( $nation, $type )
	{
		return @($this->m_data[self::PIECES][$nation][$type] ? $this->m_data[self::PIECES][$nation][$type] : 0);	
	}
	
	/** Checks if this zone has a connection to the given zone.
	 * 
	 * Note that this check is directional.
	 * 
	 * @param string $zone
	 * @return boolean
	 */
	public function hasConnection( $zone )
	{
		return array_key_exists( $zone, $this->m_data[self::CONNECTIONS] );
	}
	
	public function isOwnedBy( $nation )
	{
		return $nation === $this->m_data[self::OWNER];
	}
	
	public function isFriendlyTo( $nation )
	{
		if ( $this->m_data[self::OWNER] )
		{
			return $this->m_state->getNation( $this->m_data[self::OWNER] )->isAllyOf( $nation );
		}
		else 
		{
			return true;
		}
	}
	/** Moves $count $type pieces of nation $nation from this zone to $zone
	 * 
	 * Note that there is no checking for overflow, validity or similar. So moving
	 * negative numbers or more pieces than there are present IS possible.
	 * In fact this is used by some of the Changes to implement behaviour like
	 * reversing the change, creating pieces or removing them. 
	 * 
	 * @param string $zone
	 * @param string $nation
	 * @param string $type
	 * @param int $count
	 */
	public function movePiecesTo( $zone, $nation, $type, $count )
	{
		$this->m_data[self::PIECES][$nation][$type] -= $count;
		$this->m_match->getZone( $zone )->m_data[self::PIECES][$nation][$type] += $count;
	}
	
	/** Checks if a piece of an enemy of $nation is present
	 * 
	 * @param string $nation
	 * @return boolean
	 */
	public function isEnemyPresentOf( $nation )
	{
		$nation = $this->m_state->getNation( $nation );
		foreach( $this->m_data[self::PIECES] as $stackOwner => $nationStack )
		{
			if ( !$nation->isAllyOf( $stackOwner ) )
			{
				foreach( $this->m_data[self::PIECES][$stackOwner] as $count )
				{
					if( $count > 0 )
					{
						return true;
					}
				}
			}
		}
		return false;
	}
	
	protected function getOption( $name )
	{
		if( array_key_exists( $name , $this->m_data[self::OPTIONS] )  )
		{
			return $this->m_data[self::OPTIONS][$name];
		}
		else
		{
			return 0;
		}
	}
	
	public function storeData( Storer $storer )
	{
		$storer->takeData( $this->m_data );
	}
	
	public function __construct( MatchBoard $state, array $data )
	{
		$this->m_data = $data;
		$this->m_state = $state;
	}
}