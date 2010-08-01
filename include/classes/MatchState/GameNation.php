<?php

/** This class implements the Factory pattern and is used
 * to build GameNation objects for aspecific game using
 * a PDO connection supplied to the constructor. 
 * 
 * @author Daniel Baulig
 */
class GameNationPDOFactory implements IFactory
{
	protected $m_loadAllGameNations;
	protected $m_loadSingleGameNation;
	protected $m_loadAlliancesSingleNation;
	
	/** Loads all alliances belonging to a nation specified by it's id.
	 * 
	 * @param int $nation_id
	 * @return array
	 */
	protected function loadAlliances( $nation_id )
	{
		$alliances = array( );

		$this->m_loadAlliancesSingleNation->bindValue( ':nation_id', $nation_id, PDO::PARAM_INT );
		$this->m_loadAlliancesSingleNation->execute( );
		
		while( $alliance = $this->m_loadAlliancesSingleNation->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$alliances[ $alliance[GameAlliance::NAME] ] = true;
		}
		return $alliances;
	}
	
	/** Loads all of this games nations and creates GameNation objects and returns them in an array.
	 * 
	 * (non-PHPdoc)
	 * @see include/classes/IFactory::createAllProducts()
	 * @return array
	 */
	public function createAllProducts( )
	{
		$nations = array( );
		$this->m_loadAllGameNations->execute( );
		while( $nation = $this->m_loadAllGameNations->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$nation[GameNation::ALLIANCES] = $this->loadAlliances( $nation['id'] );
			unset( $nation['id'] );
			$nations[ $nation[GameNation::NAME] ] = new GameNation( $nation );
		}
		return $nations;
	}
	
	/** Loads a single nation for this game specified by $key and returns it as GameNation object.
	 * 
	 * If key is not a valid reference to a nation in this game the method will throw
	 * a DomainException exception.
	 * 
	 * (non-PHPdoc)
	 * @see include/classes/IFactory::createSingleProduct()
	 * @return GameNation
	 * @throws DomainException
	 */
	public function createSingleProduct( $key )
	{
		$this->m_loadSingleGameNation->bindValue( ':nation', $key, PDO::PARAM_STR );
		$this->m_loadSingleGameNation->execute( );
		
		if ( $nation = $this->m_loadSingleGameNation->fetch( PDO::FETCH_ASSOC ) )
		{
			$nation[GameNation::ALLIANCES] = $this->loadAlliances( $nation['id'] );
			unset( $nation['id'] );
			return new GameNation( $nation );
		}
		else
		{
			throw new DomainException( 'Specified name ' . $key . ' not valid.' );
		}		
	}
	
	/** Initialzes the factory and sets up the sql queries and PDOStatements

	 * @param PDO $pdo
	 * @param int $game_id
	 */
	public function __construct( PDO $pdo, $game_id )
	{
		$this->m_pdo = $pdo;
		
		$sql_nations = 'SELECT n.nation_id AS id, n.nation_name AS name FROM a3o_nations AS n ' 
			. 'WHERE n.nation_game = :game_id;';
		
		$this->m_loadAllGameNations = $this->m_pdo->prepare( $sql_nations );
		$this->m_loadAllGameNations->bindValue( ':game_id', $game_id, PDO::PARAM_INT );
		
		$sql_nation = 'SELECT n.nation_id AS id, n.nation_name AS name FROM a3o_nations AS n'
			. ' WHERE n.nation_name = :nation LIMIT 1;';
		
		$this->m_loadSingleGameNation = $this->m_pdo->prepare( $sql_nation );
		
		$sql_alliances = 
			'SELECT a.alliance_name AS name FROM a3o_alliances AS a INNER JOIN a3o_alliancenations AS an ON'
			. ' an.alliancenation_alliance = a.alliance_id INNER JOIN a3o_nations AS n ON'
			. ' n.nation_id = an.alliancenation_nation WHERE n.nation_id = :nation_id;';
		
		$this->m_loadAlliancesSingleNation = $this->m_pdo->prepare( $sql_alliances );
		
	}
}
/** The GameNationRegistry implements the Registry design pattern and
 * the Singleton creational pattern. It is a key => value store where key
 * is a string (name) and value possibly all GameNation objects associated
 * with a specific game.
 * 
 * @author Daniel Baulig
 * @see BaseRegistry
 * @see MatchZoneRegistry::
 */
class GameNationRegistry extends BaseRegistry
{
	private static $instance = null;

	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance !== null )
		{
			throw new Exception( 'GameNationRegistry already initialized.' );
		}
		self::$instance = new GameNationRegistry( $factory );
	}
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception('GameNationRegistry not initialized.');
		}
		return self::$instance;
	}
	public static function getNation( $key )
	{
		return self::$instance->getElement( $key );
	}
}

/** Represents a nation within a specific game
 * 
 * @author Daniel Baulig
 */
class GameNation
{
	protected $m_data;
	
	const NAME = 'name';
	const ALLIANCES = 'alliances';
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
	}
	
	/** Returns true if this nation is an ally of $nation
	 * 
	 * @param string $nation
	 * @return boolean
	 */
	public function isAllyOf( $nation )
	{
		$nation =  GameNationRegistry::getNation( $nation );
		foreach( $this->m_data[GameNation::ALLIANCES] as $alliance => $ignore )
		{
			if ( $nation->isInAlliance( $alliance ) )
			{
				return true;
			}
		}
		return false;
	}
	
	/** Returns true if this nation is in the alliance $alliance
	 * 
	 * @param string $alliance
	 * @return boolean
	 */
	public function isInAlliance( $alliance )
	{
		return array_key_exists( $alliance, $this->m_data[GameNation::ALLIANCES] );
	}
} 
