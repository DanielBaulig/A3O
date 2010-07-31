<?php

class A3GameNationPDOFactory implements IFactory
{
	protected $m_loadAllGameNations;
	protected $m_loadSingleGameNation;
	protected $m_loadAlliancesSingleNation;
	
	protected function loadAlliances( $nation_id )
	{
		$alliances = array( );

		$this->m_loadAlliancesSingleNation->bindValue( ':nation_id', $nation_id, PDO::PARAM_INT );
		$this->m_loadAlliancesSingleNation->execute( );
		
		while( $alliance = $this->m_loadAlliancesSingleNation->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$alliances[ $alliance[A3GameAlliance::NAME] ] = true;
		}
		return $alliances;
	}
	
	public function createAllProducts( )
	{
		$nations = array( );
		$this->m_loadAllGameNations->execute( );
		while( $nation = $this->m_loadAllGameNations->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$nation[A3GameNation::ALLIANCES] = $this->loadAlliances( $nation['id'] );
			unset( $nation['id'] );
			$nations[ $nation[A3GameNation::NAME] ] = new A3GameNation( $nation );
		}
		return $nations;
	}
	
	public function createSingleProduct( $key )
	{
		$this->m_loadSingleGameNation->bindValue( ':nation', $key, PDO::PARAM_STR );
		$this->m_loadSingleGameNation->execute( );
		
		if ( $nation = $this->m_loadSingleGameNation->fetch( PDO::FETCH_ASSOC ) )
		{
			$nation[A3GameNation::ALLIANCES] = $this->loadAlliances( $nation['id'] );
			unset( $nation['id'] );
			return new A3GameNation( $nation );
		}
		else
		{
			throw new DomainException( 'Specified name ' . $key . ' not valid.' );
		}		
	}
	
	public function __construct( PDO $pdo, $game )
	{
		$this->m_pdo = $pdo;
		
		$sql_nations = 'SELECT n.nation_id AS id, n.nation_name AS name FROM a3o_nations AS n ' 
			. 'WHERE n.nation_game = :game_id;';
		
		$this->m_loadAllGameNations = $this->m_pdo->prepare( $sql_nations );
		$this->m_loadAllGameNations->bindValue( ':game_id', $game, PDO::PARAM_INT );
		
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

class A3GameNationRegistry extends BaseRegistry
{
	private static $instance = null;

	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance !== null )
		{
			throw new Exception( 'A3GameNationRegistry already initialized.' );
		}
		self::$instance = new A3GameNationRegistry( $factory );
	}
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception('A3GameNationRegistry not initialized.');
		}
		return self::$instance;
	}
	public static function getNation( $key )
	{
		return self::$instance->getElement( $key );
	}
}

class A3GameNation
{
	protected $m_data;
	
	const NAME = 'name';
	const ALLIANCES = 'alliances';
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
	}
	
	public function isAllyOf( $nation )
	{
		$nation =  A3GameNationRegistry::getNation( $nation );
		foreach( $this->m_data[A3GameNation::ALLIANCES] as $alliance => $ignore )
		{
			if ( $nation->isInAlliance( $alliance ) )
			{
				return true;
			}
		}
		return false;
	}
	
	public function isInAlliance( $alliance )
	{
		return array_key_exists( $alliance, $this->m_data[A3GameNation::ALLIANCES] );
	}
} 
