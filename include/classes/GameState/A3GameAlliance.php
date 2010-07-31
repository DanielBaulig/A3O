<?php

class A3GameAlliancePDOFactory implements IFactory
{
	protected $m_pdo;
	protected $m_loadSingleAlliance;
	protected $m_loadSingleAllianceNations;
	protected $m_loadAllGameAliances;
	
	public function __construct( PDO $pdo, $game )
	{
		$this->m_pdo = $pdo;
		
		$sql_alliances = 
			'SELECT a.alliance_id AS id, a.alliance_name AS name FROM a3o_alliances AS a WHERE a.alliance_game = :game_id;';
		
		$this->m_loadAllGameAliances = $this->m_pdo->prepare( $sql_alliances );
		$this->m_loadAllGameAliances->bindValue( ':game_id', $game );
		
		
		$sql_alliance_nations =
			'SELECT n.nation_name AS name FROM a3o_nations AS n INNER JOIN a3o_alliancenations AS an ON'
			. ' an.alliancenation_nation = n.nation_id WHERE an.alliancenation_alliance = :alliance_id;';
			
		$this->m_loadSingleAllianceNations = $this->m_pdo->prepare( $sql_alliance_nations );
		
		
		$sql_alliance = 
			'SELECT a.alliance_id AS id, a.alliance_name AS name FROM a3o_alliances AS a WHERE'
			. ' a.alliance_game = :game_id AND a.alliance_name = :alliance LIMIT 1;';
			
		$this->m_loadSingleAlliance = $this->m_pdo->prepare( $sql_alliance );
		$this->m_loadSingleAlliance->bindValue( ':game_id', $game );
	}	
	
	public function createAllProducts( )
	{
		$this->m_loadAllGameAliances->execute( );
		$alliances = array( );
		
		while( $alliance = $this->m_loadAllGameAliances->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) ) 
		{
			$alliance[A3GameAlliance::NATIONS] = $this->loadNations( $alliance['id'] );
			unset( $alliance['id'] );
			$alliances[$alliance[A3GameAlliance::NAME]] = new A3GameAlliance( $alliance );
		}
		
		return $alliances;
	}
	
	protected function loadNations( $alliance_id )
	{
		$this->m_loadSingleAllianceNations->bindValue( ':alliance_id', $alliance_id );
		$this->m_loadSingleAllianceNations->execute( );
		
		$nations = array( );
		
		while( $nation = $this->m_loadSingleAllianceNations->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$nations[$nation[A3GameNation::NAME]] = true;
		}
		
		return $nations;
	} 
	
	public function createSingleProduct( $key )
	{
		$this->m_loadSingleAlliance->bindValue( ':alliance', $key );
		$this->m_loadSingleAlliance->execute( );
		
		if ( $alliance = $this->m_loadSingleAlliance->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$alliance[A3GameAlliance::NATIONS] = $this->loadNations( $alliance['id'] );
			unset( $alliance['id'] );

			return new A3GameAlliance( $alliance );
		}
		else
		{
			throw new DomainException( 'Specified alliance ' . $key . ' not valid.' );
		}
	}
}

class A3GameAllianceRegistry extends BaseRegistry
{
	private static $instance = null;
	
	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance !== null )
		{
			throw new Exception( 'Registry already initialized.' );
		}
		self::$instance = new A3GameAllianceRegistry( $factory );
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

class A3GameAlliance
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