<?php

require_once 'Logger.php';
require_once 'Registry.php';
require_once 'ObjectRelationalMapping.php';

define('TABLE_UNIT_TYPES', 'unit_types');

class UnitTypeFactory implements IFactory
{
	public function createProduct( PDOStatement $statement = NULL )
	{
		return UnitType::buildFromStatement( $statement );
	}
}

class UnitTypeRegistry extends BaseRegistry
{
	protected $m_loadSingleUnitTypeByNamePdoStatement = NULL;
	protected $m_saveUnitTypePdoStatement = NULL;
	protected $m_deleteUnitTypePdoStatement = NULL;
	
	public function __construct( PDO $pdo, $game )
	{
		parent::__construct( $pdo, $game, new UnitTypeFactory( ) );
		
		$ormMapping = & UnitType::getOrmMapping( ); 
		$ormMapping['m_id'] = array ( 'name' => 'unit_type_id', 'type' => PDO::PARAM_INT );
		$ormMapping['m_name'] = array ( 'name' => 'unit_type_name', 'type' => PDO::PARAM_STR );
		$ormMapping['m_attack'] = array ( 'name' => 'unit_type_attack', 'type' => PDO::PARAM_INT );
		$ormMapping['m_defense'] = array ( 'name' => 'unit_type_defense', 'type' => PDO::PARAM_INT );
		$ormMapping['m_movement'] = array ( 'name' => 'unit_type_movement', 'type' => PDO::PARAM_INT );
		$ormMapping['m_carrierCost'] = array ( 'name' => 'unit_type_carrier_cost', 'type' => PDO::PARAM_INT );
		$ormMapping['m_carrierCapacity'] = array ( 'name' => 'unit_type_carrier_capacity', 'type' => PDO::PARAM_INT );
		$ormMapping['m_transportCost'] = array ( 'name' => 'unit_type_transport_cost', 'type' => PDO::PARAM_INT );
		$ormMapping['m_transportCapacity'] = array ( 'name' => 'unit_type_transport_capacity', 'type' => PDO::PARAM_INT );
		$ormMapping['m_canBlitz'] = array ( 'name' => 'unit_type_can_blitz', 'type' => PDO::PARAM_INT );
		$ormMapping['m_canBombard'] = array ( 'name' => 'unit_type_can_bombard', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isAir'] = array ( 'name' => 'unit_type_is_air', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isSea'] = array ( 'name' => 'unit_type_is_sea', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isFactory'] = array ( 'name' => 'unit_type_is_factory', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isDestroyer'] = array ( 'name' => 'unit_type_is_destroyer', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isAntiAir'] = array ( 'name' => 'unit_type_is_antiair', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isSub'] = array ( 'name' => 'unit_type_is_sub', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isTwoHit'] = array ( 'name' => 'unit_type_is_twohit', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isStrategicBomber'] = array ( 'name' => 'unit_type_is_strategic_bomber', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isArtillerySupportable'] = array ( 'name' => 'unit_type_is_artillery_supportable', 'type' => PDO::PARAM_INT );
		$ormMapping['m_isArtillery'] = array ( 'name' => 'unit_type_is_artillery', 'type' => PDO::PARAM_INT );
	
		// TODO: Check performance against $columns = '*';		
		$columns = UnitType::buildColumnList( );
		
		$this->m_loadSingleElementByIdPdoStatement = $this->m_pdo->prepare( 'SELECT ' . $columns . ' FROM ' . TABLE_UNIT_TYPES . ' WHERE unit_type_id = :id LIMIT 1' );
		
		$this->m_loadSingleUnitTypeByNamePdoStatement = $this->m_pdo->prepare('SELECT ' . $columns . ' FROM ' . TABLE_UNIT_TYPES . ' WHERE unit_type_name LIKE :unit_type_name AND unit_type_game_id = :game_id LIMIT 1');
		$this->m_loadSingleUnitTypeByNamePdoStatement->bindParam(':game_id', $this->m_game, PDO::PARAM_INT);
		
		$this->m_loadAllElementsPdoStament = $this->m_pdo->prepare( 'SELECT ' . $columns . ' FROM ' . TABLE_UNIT_TYPES . ' WHERE unit_type_game_id = :game_id' );
		$this->m_loadAllElementsPdoStament->bindParam( ':game_id', $this->m_game, PDO::PARAM_INT );
	}
}

class UnitType extends ObjectRelationalMapping
{
	private static $ormMapping = NULL;
	public static function & getOrmMapping( )
	{
		if ( UnitType::$ormMapping === NULL)
		{
			UnitType::$ormMapping = array 
			(
				'm_id' => NULL,
				'm_name' => NULL,
				'm_attack' => NULL,
				'm_defense' => NULL,
				'm_movement' => NULL,
				'm_carrierCost' => NULL,
				'm_carrierCapacity' => NULL,
				'm_transportCost' => NULL,
				'm_transportCapacity' => NULL,
				'm_canBlitz' => NULL,
				'm_canBombard' => NULL,
				'm_isAir' => NULL,
				'm_isSea' => NULL,
				'm_isFactory' => NULL,
				'm_isDestroyer' => NULL,
				'm_isAntiAir' => NULL,
				'm_isSub' => NULL,
				'm_isTwoHit' => NULL,
				'm_isStrategicBomber' => NULL,
				'm_isArtillerySupportable' => NULL,
				'm_isArtillery' => NULL
			);
		}
		return UnitType::$ormMapping;
	}
	
	private $unit_type_id;
	private $unit_type_name;
	
	/*// basic attack, defense & movement information
	private $m_attack = 0;
	private $m_defense = 0;
	private $m_movement = 0;
	
	// transportation information
	private $m_carrierCost = 0;
	private $m_transportCost = 0;	
	private $m_transportCapacity = 0;
	private $m_carrierCapacity = 0;
	
	// flags
	private $m_canBlitz = false;
	private $m_canBombard = false;	
	private $m_isAir = false;
	private $m_isSea = false;
	private $m_isFactory = false;
	private $m_isDestroyer = false;
	private $m_isAntiAir = false;
	private $m_isSub = false;
	private $m_isTwoHit = false;
	private $m_isStrategicBomber = false;
	private $m_isArtillerySupportable = false;
	private $m_isArtillery = false;*/
	
	public function __construct( )
	{	
	}
	
	public static function buildFromStatement( PDOStatement $statement )
	{
		$instance = new self( );
		$instance->bindColumns( $statement );
		if ( $statement->fetch( PDO::FETCH_BOUND ) )
		{
			return $instance;
		}
		else
		{
			return NULL;
		}
	}
	
	public function getId( )
	{
		return $this->m_id;
	}
	
	/*public function getName( )
	{
		return $this->m_name;
	}
	
	public function getAttack( )
	{
		return $this->m_attack;
	}	
	public function getDefense( )
	{
		return $this->m_defense;
	}	
	public function getMovement( )
	{
		return $this->m_movement;
	}
	
	public function setAttack( $value )
	{
		$this->m_attack = (int) $value;
	}	
	public function setDefense( $value )
	{
		$this->m_defense = (int) $value;
	}
	public function setMovement( $value )
	{
		$this->m_movement = (int) $value;
	}
	
	public function getCarrierCost( )
	{
		return $this->m_carrierCost;
	}
	public function getTransportCost( )
	{
		return $this->m_transportCost;
	}
	public function getTransportCapacity( )
	{
		return $this->m_transportCapacity;
	}
	public function getCarrierCapacity( )
	{
		return $this->m_carrierCapacity;
	}
	
	public function setCarrierCost( $value )
	{
		$this->m_carrierCost = (int) $value;
	}
	public function setTransportCost ( $value )
	{
		$this->m_transportCost = (int) $value;
	}
	public function setTransportCapacity( $value )
	{
		$this->m_transportCapacity = (int) $value;
	}
	public function setCarrierCapacity( $value )
	{
		$this->m_carrierCapacity = (int) $value;
	}
	
	public function canBlitz( )
	{
		return $this->m_canBlitz;
	}	
	public function canBombard( )
	{
		return $this->m_canBombard;
	}
	
	public function setBlitz( $value )
	{
		$this->m_canBlitz = (bool) $value;
	}
	public function setBombard( $value )
	{
		$this->m_canBombard = (bool) $value;
	}
	
	public function isAir( ) 
	{ 
		return $this->m_isAir; 
	}
	public function isSea( ) 
	{ 
		return $this->m_isSea; 
	}
	public function isFactory( ) 
	{ 
		return $this->m_isFactory; 
	}
	public function isDestroyer( ) 
	{ 
		return $this->m_isDestroyer; 
	}
	public function isAntiAir( ) 
	{ 
		return $this->m_isAntiAir; 
	}
	public function isSub( ) 
	{ 
		return $this->m_isSub; 
	}
	public function isTwoHit( ) 
	{ 
		return $this->m_isTwoHit; 
	}
	public function isStrategicBomber( ) 
	{ 
		return $this->m_isStrategicBomber; 
	}
	public function isArtillerySupportable( ) 
	{ 
		return $this->m_isArtillerySupportable; 
	}
	public function isArtillery( ) 
	{ 
		return $this->m_isArtillery; 
	}
	
	public function setAir( $value ) 
	{ 
		$this->m_isAir = (bool) $value; 
	}
	public function setSea( $value ) 
	{ 
		$this->m_isSea = (bool) $value; 
	}
	public function setFactory( $value ) 
	{ 
		$this->m_isFactory = (bool) $value; 
	}
	public function setDestroyer( $value ) 
	{ 
		$this->m_isDestroyer = (bool) $value; 
	}
	public function setAntiAir( $value ) 
	{ 
		$this->m_isAntiAir = (bool) $value; 
	}
	public function setSub( $value ) 
	{ 
		$this->m_isSub = (bool) $value; 
	}
	public function setTwoHit( $value ) 
	{ 
		$this->m_isTwoHit = (bool) $value; 
	}
	public function setStrategicBomber( $value ) 
	{ 
		$this->m_isStrategicBomber = (bool) $value; 
	}
	public function setArtillerySupportable( $value ) 
	{ 
		$this->m_isArtillerySupportable = (bool) $value; 
	}
	public function setArtillery( $value ) 
	{ 
		$this->m_isArtillery = (bool) $value; 
	}*/
}

?>