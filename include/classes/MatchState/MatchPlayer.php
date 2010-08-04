<?php
class MatchPlayerPDOFactory implements IFactory
{
	protected $m_pdo;
	
	protected $m_loadSingleMatchPlayer;
	protected $m_loadSingleMatchPlayerOptions;
	protected $m_loadAllMatchPlayers;
	
	public function __construct( PDO $pdo, $match_id )
	{
		$this->m_pdo = $pdo;
		
		$sql_single =
			'SELECT p.player_id AS id, n.nation_name AS nation, p.player_user AS user FROM a3o_players AS p'
			. ' INNER JOIN a3o_nations AS n ON p.player_nation = n.nation_id WHERE p.player_match = :match_id'
			. ' AND n.nation_name = :nation LIMIT 1;';
			
		$this->m_loadSingleMatchPlayer = $this->m_pdo->prepare( $sql_single );
		$this->m_loadSingleMatchPlayer->bindValue( ':match_id' , $match_id, PDO::PARAM_INT );
			
		$sql_options =
			'SELECT o.playeroption_name AS name, o.playeroption_value AS value FROM a3o_playeroptions AS o'
			. ' WHERE o.playeroption_player = :player_id;';
		
		$this->m_loadSingleMatchPlayerOptions = $this->m_pdo->prepare( $sql_options );
			
		$sql_all =
			'SELECT p.player_id AS id, n.nation_name AS nation, p.player_user AS user FROM a3o_players AS p'
			. ' INNER JOIN a3o_nations AS n ON n.nation_id = p.player_nation WHERE p.player_match = :match_id;';
			
		$this->m_loadAllMatchPlayers = $this->m_pdo->prepare( $sql_all );
		$this->m_loadAllMatchPlayers->bindValue( ':match_id', $match_id, PDO::PARAM_INT );
	}
	
	protected function createObject( array $data )
	{
		return new MatchPlayer( $data );
	}
	
	protected function loadOptions( $player_id )
	{
		$this->m_loadSingleMatchPlayerOptions->bindValue( ':player_id', $player_id, PDO::PARAM_INT );
		$this->m_loadSingleMatchPlayerOptions->execute( );
		
		$options = array( );
		
		while( $option = $this->m_loadSingleMatchPlayerOptions->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$options[$option['name']] = $option['value'];
		}
		
		return $options;
	}
	
	public function createSingleProduct( $key )
	{
		$this->m_loadSingleMatchPlayer->bindValue( ':nation', $key, PDO::PARAM_STR );
		$this->m_loadSingleMatchPlayer->execute( );
		
		if ( $player = $this->m_loadSingleMatchPlayer->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$player[MatchPlayer::OPTIONS] = $this->loadOptions( $player['id'] );

			unset( $player['id'] );
			
			return $this->createObject( $player );
		}
		else
		{
			throw new DomainException( 'Specified nation ' . $key . ' not valid.' );
		}
	}
	
	public function createAllProducts( )
	{
		$this->m_loadAllMatchPlayers->execute( );
		
		$players = array( );
		
		while( $player = $this->m_loadAllMatchPlayers->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$player[MatchPlayer::OPTIONS] = $this->loadOptions( $player['id'] );
			
			unset( $player['id'] );
			$players[$player[MatchPlayer::NATION]] = $this->createObject( $player );
		}
		
		return $players;
	}
}

class MatchPlayerPDOStorer extends Storer
{
	protected $m_savePlayerStatement;
	protected $m_savePlayerOptions;
	protected $m_clearMatchOptions;
	protected $m_pdo;
	
	public function __construct( PDO $pdo, $match_id )
	{
		$this->m_pdo = $pdo;
		
		$sql =
			'INSERT INTO a3o_players (player_nation, player_user, player_match)'
			. ' SELECT n.nation_id, :select_user_id, :select_match_id FROM a3o_nations AS n INNER JOIN a3o_matches AS m'
			. ' ON n.nation_game = m.match_game WHERE m.match_id = :where_match_id AND n.nation_name = :nation'
			. ' ON DUPLICATE KEY UPDATE player_user = :update_user_id;';
		
		$this->m_savePlayerStatement = $this->m_pdo->prepare( $sql );
		$this->m_savePlayerStatement->bindValue( ':select_match_id' , $match_id, PDO::PARAM_INT );
		$this->m_savePlayerStatement->bindValue( ':where_match_id' , $match_id, PDO::PARAM_INT );
		
		$sql_option =
			'INSERT INTO a3o_playeroptions ( playeroption_player, playeroption_name, playeroption_value )'
			. ' VALUES ( :player_id, :name, :insert_value ) ON DUPLICATE KEY UPDATE'
			. ' playeroption_value = :update_value;' ;
			
		$this->m_savePlayerOptions = $this->m_pdo->prepare( $sql_option );
		
		$sql_clear_options =
			'DELETE o FROM a3o_playeroptions AS o INNER JOIN a3o_players AS p ON o.playeroption_player = p.player_id'
			. ' WHERE ( o.playeroption_value = \'0\' OR o.playeroption_value = \'\' ) AND p.player_match = :match_id;';
		$this->m_clearMatchOptions = $this->m_pdo->prepare( $sql_clear_options );
		$this->m_clearMatchOptions->bindValue( ':match_id' , $match_id, PDO::PARAM_INT );
	}
	
	protected function saveOptions( $player_id, array $options )
	{
		//TODO: check performance improvement for ad-hoc multi row insert
		foreach( $options as $name => $value )
		{
			$this->m_savePlayerOptions->bindValue( ':player_id', $player_id, PDO::PARAM_INT );
			$this->m_savePlayerOptions->bindValue( ':name', $name, PDO::PARAM_STR );
			$this->m_savePlayerOptions->bindValue( ':insert_value', $value, PDO::PARAM_STR );
			$this->m_savePlayerOptions->bindValue( ':update_value', $value, PDO::PARAM_STR );
			
			$this->m_savePlayerOptions->execute( );
		}
	}
	
	public function store( IStoreable $player  )
	{
		$data = $this->getStoreableData( $player );
		$this->m_pdo->beginTransaction( );
		try
		{
			$this->m_savePlayerStatement->bindValue( ':nation' , $data[MatchPlayer::NATION], PDO::PARAM_STR );
			$this->m_savePlayerStatement->bindValue( ':select_user_id' , $data[MatchPlayer::USER], PDO::PARAM_INT );
			$this->m_savePlayerStatement->bindValue( ':update_user_id' , $data[MatchPlayer::USER], PDO::PARAM_INT );
		
			$this->m_savePlayerStatement->execute( );
		
			$this->saveOptions( $data[MatchPlayer::USER], $data[MatchPlayer::OPTIONS] );
			
			$this->m_pdo->commit( );
		}
		catch( Exception $e )
		{
			$this->m_pdo->rollBack( );
			throw $e;
		}
	}

	public function clearMatchPlayerOptions( )
	{
		$this->m_clearMatchOptions->execute( );
	}
}

class MatchPlayerRegistry extends BaseRegistry
{
	private static $instance = null;
	private $m_factory;
	
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception( 'Registry must be intialized first.' );
		}
		return self::$instance;
	}
	
	public static function initializeRegistry( IFactory $factory )
	{
		if( self::$instance !== null )
		{
			throw new Exception( 'Registry already initialized.' );
		}
		self::$instance = new MatchPlayerRegistry( $factory );
	}
	
	public function swapFactory( IFactory $factory )
	{
		$old = $this->m_factory;
		$this->m_factory = $factory;
		$this->m_elements = array( );
		return $old;
	}
	
	public static function getPlayer( $nation )
	{
		return self::$instance->getElement( $nation );
	}
}

class MatchPlayer implements IStoreable
{
	protected $m_data;
	
	const NATION = 'nation';
	const USER = 'user';
	const OPTIONS = 'options';
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
	}
	
	public function isUser( $user )
	{
		return $this->m_data[self::USER] == $user;
	}
	
	protected function getOption( $name )
	{
		if ( array_key_exists( $name, $this->m_data[self::OPTIONS] ) )
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
}