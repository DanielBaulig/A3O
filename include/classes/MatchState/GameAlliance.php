<?php

/** This class implements the Factory pattern and is capable
 * of loading alliances for a specified game from the database
 * and building GameAlliance objects for each of them.
 * 
 * @author Daniel Baulig
 */
class GameAlliancePDOFactory implements IFactory
{
	protected $m_pdo;
	protected $m_loadSingleAlliance;
	protected $m_loadSingleAllianceNations;
	protected $m_loadAllGameAliances;
	
	/** Initializes the factory and sets up the queries and the PDOStatements
	 * 
	 * @param PDO $pdo
	 * @param int $game_id
	 */
	public function __construct( PDO $pdo, $game_id )
	{
		$this->m_pdo = $pdo;
		
		$sql_alliances = 
			'SELECT a.alliance_id AS id, a.alliance_name AS name FROM a3o_alliances AS a WHERE a.alliance_game = :game_id;';
		
		$this->m_loadAllGameAliances = $this->m_pdo->prepare( $sql_alliances );
		$this->m_loadAllGameAliances->bindValue( ':game_id', $game_id );
		
		
		$sql_alliance_nations =
			'SELECT n.nation_name AS name FROM a3o_nations AS n INNER JOIN a3o_alliancenations AS an ON'
			. ' an.alliancenation_nation = n.nation_id WHERE an.alliancenation_alliance = :alliance_id;';
			
		$this->m_loadSingleAllianceNations = $this->m_pdo->prepare( $sql_alliance_nations );
		
		
		$sql_alliance = 
			'SELECT a.alliance_id AS id, a.alliance_name AS name FROM a3o_alliances AS a WHERE'
			. ' a.alliance_game = :game_id AND a.alliance_name = :alliance LIMIT 1;';
			
		$this->m_loadSingleAlliance = $this->m_pdo->prepare( $sql_alliance );
		$this->m_loadSingleAlliance->bindValue( ':game_id', $game_id );
	}	
	
	/** Loads all alliances from the database, creates GameAlliance from them and returns them in an array
	 * 
	 * (non-PHPdoc)
	 * @see include/classes/IFactory::createAllProducts()
	 * @return array
	 */
	public function createAllProducts( )
	{
		$this->m_loadAllGameAliances->execute( );
		$alliances = array( );
		
		while( $alliance = $this->m_loadAllGameAliances->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) ) 
		{
			$alliance[GameAlliance::NATIONS] = $this->loadNations( $alliance['id'] );
			unset( $alliance['id'] );
			$alliances[$alliance[GameAlliance::NAME]] = new GameAlliance( $alliance );
		}
		
		return $alliances;
	}
	
	/** Loads all nations belonging to a specified alliance
	 *
	 * @param int $alliance_id
	 */
	protected function loadNations( $alliance_id )
	{
		$this->m_loadSingleAllianceNations->bindValue( ':alliance_id', $alliance_id );
		$this->m_loadSingleAllianceNations->execute( );
		
		$nations = array( );
		
		while( $nation = $this->m_loadSingleAllianceNations->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$nations[$nation[GameNation::NAME]] = true;
		}
		
		return $nations;
	} 
	
	/** Creates a single GameAlliance object for an alliance specified by $key.
	 * 
	 * If $key is not found in the database throws a DomainException exception.
	 * 
	 * (non-PHPdoc)
	 * @see include/classes/IFactory::createSingleProduct()
	 * @return GameAlliance
	 * @throws DomainException
	 */
	public function createSingleProduct( $key )
	{
		$this->m_loadSingleAlliance->bindValue( ':alliance', $key );
		$this->m_loadSingleAlliance->execute( );
		
		if ( $alliance = $this->m_loadSingleAlliance->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$alliance[GameAlliance::NATIONS] = $this->loadNations( $alliance['id'] );
			unset( $alliance['id'] );

			return new GameAlliance( $alliance );
		}
		else
		{
			throw new DomainException( 'Specified alliance ' . $key . ' not valid.' );
		}
	}
}

/** Implements the Registry pattern and the Singleton creational pattern.
 * Is basicly a key => value store where key is a string (name) and value
 * are possibly all GameAlliance objects for a single game.
 * 
 * @author Daniel Baulig
 * @see BaseRegistry
 * @see MatchZoneRegistry
 */
class GameAllianceRegistry extends BaseRegistry
{
	private static $instance = null;
	
	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance !== null )
		{
			throw new Exception( 'Registry already initialized.' );
		}
		self::$instance = new GameAllianceRegistry( $factory );
	}
	
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception( 'Registry must be initialized first.' );
		}
		return self::$instance;
	}
	
	public static function getAlliance( $key )
	{
		return self::$instance->getElement( $key );
	}
}

/** I think a am repeating myself. Look at the other GameState classes.
 * 
 * @author Daniel Baulig
 */
class GameAlliance
{
	protected $m_data;
	
	const NAME = 'name';
	const NATIONS = 'nations';
	
	public function __construct( $data )
	{
		$this->m_data = $data;
	}
	
	public function hasMember( $nation )
	{
		return array_key_exists( $nation, $this->m_data[self::NATIONS] );
	}
	
	public function forEachMember( $callback )
	{
		foreach( $this->m_data[self::NATIONS] as $nation => $ignore )
		{
			call_user_func( $callback, $nation );
		}
	}
}