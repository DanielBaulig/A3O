<?php

require_once dirname(__FILE__).'/../Registry.php';

class A3GameTypePDOFactory implements IFactory
{
	private $m_pdo;
	
	/** PDO statement to load all game types 
	 * 
	 * No additional binds needed.
	 * 
	 * @var PDOStatement
	 */
	private $m_loadAllGameTypes;
	
	/** PDO statement to load options for a type specified by it's id
	 *
	 * type id must be bound to :type_id
	 *
	 * @var PDOStatement
	 */	
	private $m_loadOptionsByGameTypeId;
	
	/** PDO statement to load options for a type specified by it's name
	 *
	 * type name mist be bound to :type
	 *
	 * @var PDOStatement
	 */
	private $m_loadOptionsByGameTypeName;
	
	/** Creates a type factory for the specified game.
	 *
	 * @param PDO $pdo
	 * @param int $game
	 */
	public function __construct( PDO $pdo, $game )
	{
		$this->m_pdo = $pdo;
		
		$sql_types = 'SELECT type_id AS id, type_name AS name FROM a3o_types WHERE type_game = :game_id;';
		
		$this->m_loadAllGameTypes = $this->m_pdo->prepare( $sql_types );
		$this->m_loadAllGameTypes->bindValue( ':game_id', $game, PDO::PARAM_INT );
		
		$sql_options_id = 'SELECT typeoption_name AS name, typeoption_value AS value FROM a3o_typeoptions'
			. ' WHERE typeoption_type = :type_id;';
			
		$this->m_loadOptionsByGameTypeId = $this->m_pdo->prepare( $sql_options_id );
		
		$sql_options_name = 'SELECT typeoption_name AS name, typeoption_value AS value FROM a3o_typeoptions'
			. ' INNER JOIN a3o_types ON type_id = typeoption_type WHERE type_name = :type;';
			
		$this->m_loadOptionsByGameTypeName = $this->m_pdo->prepare( $sql_options_name );
	}
	
	/** Creates a type object from key
	 * 
	 * @see include/classes/IFactory::createSingleProduct()
	 * 
	 * @return A3GameType
	 */
	public function createSingleProduct( $key )
	{
		$type = array ( A3GameType::NAME => $key );
		$this->m_loadOptionsByGameTypeName->bindValue( ':type', $key, PDO::PARAM_STR );
		$this->m_loadOptionsByGameTypeName->execute( );
		
		$options = array( );
		
		while ( $row = $this->m_loadOptionsByGameTypeName->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$options[$row['name']] = $row['value'];
		}
		
		if ( count( $options ) <= 0 )
		{
			throw new Exception('Invalid type key!');
		}
		
		$type[A3GameType::OPTIONS] = $options;
		
		return new A3GameType( $type );
	}
	
	public function createAllProducts( )
	{
		$types = array ( );
		$this->m_loadAllGameTypes->execute( );
		
		while( $type = $this->m_loadAllGameTypes->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$this->m_loadOptionsByGameTypeId->bindValue( ':type_id', $type['id'], PDO::PARAM_STR );
			$this->m_loadOptionsByGameTypeId->execute( );
			
			$options = array( );
			
			while ( $option = $this->m_loadOptionsByGameTypeId->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
			{
				$options[$option['name']] = $option['value'];
			}
			$type[A3GameType::OPTIONS] = $options;
			unset( $type['id'] );
			$types[ $type[A3GameType::NAME ] ] = new A3GameType( $type );
		}
		
		return $types;
	}
}

class A3GameTypeRegistry extends BaseRegistry
{
	private static $instance = null;
	
	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance !== null )
		{
			throw new Exception('Registry already initialized.');
		}
		self::$instance = new A3GameTypeRegistry( $factory );
	}
	
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception('Registry must be initialized first.');
		}
		return self::$instance;
	}
	public static function getType( $key )
	{
		return self::$instance->getElement( $key );
	}
}

class A3GameType
{
	protected $m_data;
	protected $m_name;
	protected $m_options;
	
	const NAME = 'name';
	const OPTIONS = 'options';
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
		$this->m_name = $data['name'];
		$this->m_options = $data['options'];
	}
	
	public function __isset( $option )
	{
		return array_key_exists( $option, $this->m_data[A3GameType::OPTIONS] );
	}
	
	public function __get( $option )
	{
		if( $this->__isset( $option ) )
		{
			return $this->m_data[A3GameType::OPTIONS][$option];
		}
		else
		{
			return 0;
		}
	}
}