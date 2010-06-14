<?php

define('TABLE_UNIT_TYPES', 'unit_types');

class UnitTypeFactory
{
	private $m_pdo;
	private $m_game;
	
	private $m_unitTypeCache = array();
	
	protected $m_loadSingleUnitTypePdoStatement = NULL;
	protected $m_loadSingleUnitTypeByNamePdoStatement = NULL;
	protected $m_loadAllUnitTypesPdoStatement = NULL;
	protected $m_saveUnitTypePdoStatement = NULL;
	protected $m_deleteUnitTypePdoStatement = NULL;
	
	public function __construct( PDO $pdo, $game )
	{
		$this->m_pdo = $pdo;
		$this->m_game = $game;
		
		$columns = 'unit_type_id, unit_type_name, unit_type_attack, unit_type_defense, unit_type_movement' 
			. ', unit_type_carrier_cost, unit_type_carrier_capacity, unit_type_transport_cost, unit_type_transport_capacity'
			. ', unit_type_can_blitz, unit_type_can_bombard, unit_type_is_air, unit_type_is_sea, unit_type_is_factory'
			. ', unit_type_is_destroyer, unit_type_is_antiair, unit_type_is_sub, unit_type_is_twohit, unit_type_is_strategic_bomber'
			. ', unit_type_is_artillery_supportable, unit_type_is_artillery';	
			
		$onUpdateColumns = '';
		$valuesNameList = '';
		$columnList = explode(', ', $columns);
		foreach ($columnList as $column)
		{
			// skip id
			if ( $column == 'unit_type_id' )
			{
				continue;
			}
			$onUpdateColumns .= $column . '=VALUES(' . $column . '), ';
			$valuesNameList .= ':' . $column . ', ';
		}
		$onUpdateColumns = trim( $on_update_columns, ', ' );
		$valuesNameList = trim( $valuesList, ', ' );
			
		
		$this->m_loadSingleUnitTypeByIdPdoStatement = $this->m_pdo->prepare('SELECT ' . $columns . ' FROM ' . TABLE_UNIT_TYPES . ' WHERE unit_type_id = :unit_type_id LIMIT 1');
		
		$this->m_loadSingleUnitTypeByNamePdoStatement = $this->m_pdo->prepare('SELECT ' . $columns . ' FROM ' . TABLE_UNIT_TYPES . ' WHERE unit_type_name LIKE :unit_type_name AND unit_type_game_id = :game_id LIMIT 1');
		$this->m_loadSingleUnitTypeByNamePdoStatement->bindParam(':game_id', $this->m_game, PDO::PARAM_INT);
		
		$this->m_loadAllUnitTypesPdoStament = $this->m_pdo->prepare('SELECT ' . $columns . ' FROM ' . TABLE_UNIT_TYPES . ' WHERE unit_type_game_id = :game_id');
		$this->m_loadAllUnitTypesPdoStament->bindParam(':game_id', $this->m_game, PDO::PARAM_INT);
		
		$this->m_saveUnitTypePdoStatement = $this->m_pdo->prepare('INSERT INTO ' . TABLE_UNIT_TYPES . ' ( unit_type_game_id, ' . $columns . ') VALUES ( :game_id, 0, ' . $valuesNameList . ' ) ON DUPLICATE KEY UPDATE ' . $onUpdateColumns );
		$this->m_saveUnitTypePdoStatement->bindParam( ':game_id', $this->m_game, PDO::PARAM_INT );
		
		$this->m_deleteUnitTypePdoStatement = $this->m_pdo->prepare('DELETE FROM ' . TABLE_UNIT_TYPES . ' WHERE unit_type_name LIKE :unit_type_name AND unit_type_game_id = :game_id');
		$this->m_deleteUnitTypePdoStatement->bindParam( ':game_id', $this->m_game, PDO::PARAM_INT );
	}
	
	public function getUnitType( $name, $forceReload = false )
	{
		if ( !$forceReload && array_key_exists( $name, $this->m_unitTypeCache ) )
		{
			return $this->m_unitTypeCache[$name];
		}
		else
		{
			try
			{
				$this->m_loadSingleUnitTypeByNamePdoStatement->bindValue( ':unit_type_name', $name, PDO::PARAM_STR );
				$this->m_loadSingleUnitTypeByNamePdoStatement->execute( );
				
				if ( $this->m_loadSingleUnitTypeByNamePdoStatement->rowCount( ) != 1 )
				{
					die( $this->m_loadSingleUnitTypeByNamePdoStatement->queryString );
					$this->m_loadSingleUnitTypeByNamePdoStatement->closeCursor( );
					$this->m_unitTypeCache[$name] = NULL;
				}
				else
				{
					/*$result = $this->m_loadSingleUnitTypeByNamePdoStatement->fetch( PDO::FETCH_ASSOC );
					return $this->m_unitTypeCache[$name] = new UnitType ( 
						$result['unit_type_id'],
						$result['unit_type_name'],
						$result['unit_type_attack'],
						$result['unit_type_defense'],
						$result['unit_type_movement'],
						$result['unit_type_carrier_cost'],
						$result['unit_type_transport_cost'],
						$result['unit_type_carrier_capacity'],
						$result['unit_type_transport_capacity'] ,
						$result['unit_type_can_blitz'],
						$result['unit_type_can_bombard'],
						$result['unit_type_is_air'],
						$result['unit_type_is_sea'],
						$result['unit_type_is_factory'],
						$result['unit_type_is_destroyer'],
						$result['unit_type_is_antiair'],
						$result['unit_type_is_sub'],
						$result['unit_type_is_twohit'],
						$result['unit_type_is_strategic_bomber'],
						$result['unit_type_is_artillery_supportable'],
						$result['unit_type_is_artillery']               
					);*/
					$unitType = new UnitType( );
					$unitType->fetch( $this->m_loadSingleUnitTypeByNamePdoStatement );
					return $unitType;
				}
			}
			catch( PDOException $e )
			{
				die( $e->getMessage( ) );
			}
		}
	}
	
	public function precacheUnitTypes( )
	{
		$this->m_unitTypesCache = array( );
		try
		{
			$this->m_loadAllUnitTypesPdoStatement->execute( );
			while ( $result = $this->m_loadAllUnitTypesPdoStatement->fetch( PDO::FETCH_ASSOC ) )
			{
				$this->m_unitTypeCache[$name] = new UnitType ( 
					$result['unit_type_id'],
					$result['unit_type_name'],
					$result['unit_type_attack'],
					$result['unit_type_defense'],
					$result['unit_type_movement'],
					$result['unit_type_carrier_cost'],
					$result['unit_type_transport_cost'],
					$result['unit_type_carrier_capacity'],
					$result['unit_type_transport_capacity'],
					$result['unit_type_can_blitz'],
					$result['unit_type_can_bombard'],
					$result['unit_type_is_air'],
					$result['unit_type_is_sea'],
					$result['unit_type_is_factory'],
					$result['unit_type_is_destroyer'],
					$result['unit_type_is_antiair'],
					$result['unit_type_is_sub'],
					$result['unit_type_is_twohit'],
					$result['unit_type_is_strategic_bomber'],
					$result['unit_type_is_artillery_supportable'],
					$result['unit_type_is_artillery']               
				);
			}
		}
		catch ( PDOException $e )
		{
			die( $e->getMessage( ) );
		}
	}
	
	public function &getUnitTypeCache( )
	{
		return $this->m_unitTypeCache;
	}
	
	public function deleteUnitType( $name )
	{
		if ( array_key_exists( $name, $this->unitTypeCache ) )
		{
			$this->m_deleteUnitTypePdoStatement>bindValue( ':unit_type_name', $name );
			$this->m_deleteUnitTypePdoStatement->execute( );
			$this->unitTypeCache[$name] = NULL;
			unset( $this->unitTypeCache[$name] );
		}
	}
	
	public function storeUnitType( $name )
	{
		if ( array_key_exists( $name, $this->unitTypeCache ) )
		{
			if ( $this->unitTypeCache[$name] != NULL )
			{
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_name', $name, PDO::PARAM_STR );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_attack', $this->unitTypeCache[$name]->getAttack( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_defense', $this->unitTypeCache[$name]->getDefense( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_movement', $this->unitTypeCache[$name]->getMovement( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_carrier_cost', $this->unitTypeCache[$name]->getCarrierCost( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_transport_cost', $this->unitTypeCache[$name]->getTransportCost( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_carrier_capacity', $this->unitTypeCache[$name]->getCarrierCapacity( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_transport_capacity', $this->unitTypeCache[$name]->getTransportCapacity( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_can_blitz', $this->unitTypeCache[$name]->canBlitz( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_can_bombard', $this->unitTypeCache[$name]->canBombard( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_air', $this->unitTypeCache[$name]->isAir( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_sea', $this->unitTypeCache[$name]->isSea( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_factory', $this->unitTypeCache[$name]->isFactory( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_destroyer', $this->unitTypeCache[$name]->isDestroyer( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_antiair', $this->unitTypeCache[$name]->isAntiAir( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_sub', $this->unitTypeCache[$name]->isSub( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_twohit', $this->unitTypeCache[$name]->isTwoHit( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_strategic_bomber', $this->unitTypeCache[$name]->isStrategicBomber( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_artillery_supportable', $this->unitTypeCache[$name]->isArtillerySupportable( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->bindValue( ':unit_type_is_artillery', $this->unitTypeCache[$name]->isArtillery( ), PDO::PARAM_INT );
				$this->m_saveUnitTypePdoStatement->execute( );
				if ( $this->m_saveUnitTypePdoStatement->rowCount( ) == 1 ) // insert
				{
					$this->unitTypeCache[$name]->setId( $this->m_pdo->lastInsertId( ) );
				}
			}			
		}
	}
}

class UnitType
{
	public function fetch( PDOStatement $statement )
	{
		try
		{
			$statement->bindColumn( 'unit_type_id', $this->m_id, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_name', $this->m_name, PDO::PARAM_STR );
			
			$statement->bindColumn( 'unit_type_attack', $this->m_attack, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_defense', $this->m_defense, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_movement', $this->m_movement, PDO::PARAM_INT );
			
			$statement->bindColumn( 'unit_type_carrier_cost', $this->m_carrierCost, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_carrier_capacity', $this->m_carrierCapacity, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_transport_cost', $this->m_transportCost, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_transport_capacity', $this->m_transportCapacity, PDO::PARAM_INT );
			
			$statement->bindColumn( 'unit_type_can_blitz', $this->m_canBlitz, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_can_bombard', $this->m_canBombar, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_air', $this->m_isAir, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_sea', $this->m_isSea, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_factory', $this->m_isFactory, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_antiair', $this->m_isAntiAir, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_destroyer', $this->m_isDestroyer, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_twohit', $this->m_isTwoHit, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_strategic_bomber', $this->m_StrategicBomber, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_artillery_supportable', $this->m_isArtillerySupportable, PDO::PARAM_INT );
			$statement->bindColumn( 'unit_type_is_artillery', $this->m_isArtillery, PDO::PARAM_INT );
		}
		catch ( PDOException $e )
		{
			echo $e->getMessage( ) . '<br/>';
		}
		
		$statement->fetch( PDO::FETCH_BOUND );
	}
	
	public function store( PDOStatement $statement )
	{
		try
		{
			$statement->bindValue( ':unit_type_id', $this->m_id, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_name', $this->m_name, PDO::PARAM_STR );
			$statement->bindValue( ':unit_type_attack', $this->m_attack, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_defense', $this->m_defense, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_movement', $this->m_movement, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_carrier_cost', $this->m_carrierCost, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_transport_cost', $this->m_transportCost, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_carrier_capacity', $this->m_carrierCapacity, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_transport_capacity', $this->m_transportCapacity, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_can_blitz', $this->m_canBlitz, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_can_bombard', $this->m_canBombard, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_air', $this->m_isAir, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_sea', $this->m_isSea, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_factory', $this->m_isFactory, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_destroyer', $this->m_isDestroyer, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_antiair', $this->m_isAntiAir, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_sub', $this->m_isSub, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_twohit', $this->m_isTwoHit, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_strategic_bomber', $this->m_isStrategicBomber, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_artillery_supportable', $this->m_isArtillerySupportable, PDO::PARAM_INT );
			$statement->bindValue( ':unit_type_is_artillery', $this->m_isArtillery, PDO::PARAM_INT );
		}
		catch ( PDOException $e )
		{
			echo $e->getMessage( ) . '<br/>';
		}
		$statement->execute( );
	}
	
	private $m_name;
	private $m_id;
	
	// basic attack, defense & movement information
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
	private $m_isArtillery = false;
	
	public function __construct( )
	{	
	}
	
	public function getId( )
	{
		return $this->m_id;
	}
	
	public function getName( )
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
	}
}

?>