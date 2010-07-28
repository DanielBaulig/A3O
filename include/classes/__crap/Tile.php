<?php

define('TILE_TYPE_LAND', 0);
define('TILE_TYPE_SEA',  1);

define('TABLE_TILE_VERTICES', 'tile_vertices');
define('TABLE_BASETILES', 'base_tiles');
define('TABLE_TILE_CONNECTIONS', 'tile_connections');

require_once 'ObjectRelationalMapping.php';
require_once 'Registry.php';

//TODO: introduce tile_vertex_json_cache_timestamp on base_tiles and vertex_timestamp on tile_vertices to recognize changes
  
class BaseTileFactory implements IFactory
{
	public function createProduct( PDOStatement $statement = NULL )
	{
		return BaseTile::buildFromStatement( $statement );
	}
}

class BaseTileRegistry extends BaseRegistry
{
	private $m_tileCache = NULL;
	private $m_tilesPrecached = false;
	
	private $m_loadSingleTilePdoStatement;
	private $m_loadAllTilesPdoStatement;
	private $m_loadAllNeighboursPdoStatement;
	
	public function __construct( PDO $pdo, $game )
	{
		parent::__construct( $pdo, $game, new BaseTileFactory( ) );
		
		$ormMapping = & BaseTile::getOrmMapping( ); 
		$ormMapping['m_id'] = array ( 'name' => 'tile_id', 'type' => PDO::PARAM_INT );
		$ormMapping['m_water'] = array ( 'name' => 'tile_type', 'type' => PDO::PARAM_INT );
		$ormMapping['m_name'] = array ( 'name' => 'tile_name', 'type' => PDO::PARAM_STR );
		$ormMapping['m_production'] = array ( 'name' => 'tile_production', 'type' => PDO::PARAM_INT );
		$ormMapping['m_centerX'] = array ( 'name' => 'tile_center_x', 'type' => PDO::PARAM_INT );
		$ormMapping['m_centerY'] = array ( 'name' => 'tile_center_y', 'type' => PDO::PARAM_INT );
		$ormMapping['m_verticesJsonCache'] = array ( 'name' => 'tile_vertices_json_cache', 'type' => PDO::PARAM_INT );
		
		$columns = BaseTile::buildColumnList( );
		
		$this->m_loadSingleElementByIdStatement = $this->m_pdo->prepare( 'SELECT ' . $columns . ' FROM ' . TABLE_BASETILES . ' WHERE tile_id = :id LIMIT 1' );
		
		$this->m_loadSingleTilePdoStatement = $this->m_pdo->prepare('SELECT ' . $columns . ' FROM ' . TABLE_BASETILES . ' WHERE tile_name LIKE :name AND tile_gamd_id = :game_id LIMIT 1');
		$this->m_loadSingleTilePdoStatement->bindParam(':game_id', $this->m_game, PDO::PARAM_INT);
	
		$this->m_loadAllElementsPdoStatement = $this->m_pdo->prepare('SELECT ' . $columns . ' FROM ' . TABLE_BASETILES . ' WHERE tile_game_id = :game_id');
		$this->m_loadAllElementsPdoStatement->bindParam(':game_id', $this->m_game, PDO::PARAM_INT);
		
		$this->m_loadAllNeighboursPdoStatement = $this->m_pdo->prepare('SELECT f.tile_name AS first_tile_name, s.tile_name AS second_tile_name FROM ' . TABLE_TILE_CONNECTIONS . ' AS tc INNER JOIN ' . TABLE_BASETILES . ' AS f ON tc.tile_connection_first_tile_id = f.tile_id INNER JOIN ' . TABLE_BASETILES . ' AS s ON tc.tile_connection_second_tile_id = s.tile_id');
	}
	
	public function precacheNeighbours()
	{
		if (! $this->m_tilesPrecached )
		{
			die('Precache tiles first!');
		}
		try
		{
			$this->m_loadAllNeighboursPdoStatement->execute();
			while ($row = $this->m_loadAllNeighboursPdoStatement->fetch(PDO::FETCH_ASSOC))
			{
				$this->m_tileCache[$row['first_tile_name']]->knockAtNeighbour($this->m_tileCache[$row['second_tile_name']]);
			}
		}
		catch(PDOException $e)
		{
			die($e->getMessage());
		}
	}
}

class Vertex extends ObjectRelationalMapping
{
	public static function & getOrmMapping( )
	{
		if ( self::$ormMapping === NULL)
		{
			self::$ormMapping = array 
			(
				'm_id' => NULL,
				'm_tile_id' => NULL,
				'm_x' => NULL,
				'm_y' => NULL
			);
		}
		return UnitType::$ormMapping;
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
}

class Connection extends ObjectRelationalMapping
{
	public static function & getOrmMapping( )
	{
		if ( self::$ormMapping === NULL)
		{
			self::$ormMapping = array 
			(
				'm_first_tile' => NULL,
				'm_second_tile' => NULL
			);
		}
		return UnitType::$ormMapping;
	}
	
	public static function buildFromStatement( ) 
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
	
	public function __construct( )
	{		
	}
	
	public function isConnecting( BaseTile $first, BaseTile $second )
	{
		return $this->connectsFrom( $first ) && $this->connectsTo( $second );
	}
	
	public function connectsFrom( BaseTile $from )
	{
		return $this->m_first_tile == $from->getId( );
	}
	
	public function connectsTo( BaseTile $to )
	{
		return $this->m_second_tile == $to->getId( );
	}
	
	public function getId( )
	{
		return $this->m_first_tile . '->' .  $this->m_second_tile;
	}
	
	protected $m_first_tile;
	protected $m_second_tile;
}

class BaseTile extends ObjectRelationalMapping
{
	private static $ormMapping = NULL;
	public static function & getOrmMapping( )
	{
		if ( self::$ormMapping === NULL)
		{
			self::$ormMapping = array 
			(
				'm_id' => NULL,
				'm_name' => NULL,
				'm_controllingNation' => NULL,
				'm_production' => NULL,
				'm_water' => NULL,
				'm_centerX' => NULL,
				'm_centerY' => NULL,
				'm_verticesJsonCache' => NULL
			);
		}
		return UnitType::$ormMapping;
	}
	
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
		
	public function fetchVertices( PDOStatement $statement )
	{
		$this->m_vertices = array( );
		while ( $vertex = Vertex::buildFromStatement( $statement ) )
		{
			$this->m_vertices[] = $vertex;
		}
	}
	
	public function fetchConnections( PDOStatement $statement )
	{
		$this->m_connections = array( );
		while ( $connection = Vertex::buildFromStatement( $statement ) )
		{
			$this->m_connections[] = $connection;
		}
	}
	
	private function hasConnectionTo( BaseTile $tile )
	{
		return $this->m_connections[$tile->getId( )]->connectsTo( $tile );	
	}	
	public function isConnectedTo( BaseTile $tile )
	{
		return $this->hasConnectionTo( $tile ) || $tile->hasConnectionTo( $this );
	}
	
	protected $m_id;
	protected $m_name;
	protected $m_production;
	protected $m_water;
	protected $m_centerX;
	protected $m_centerY;
	protected $m_verticesJsonCache;
	protected $m_connections = array( );
	protected $m_vertices = array( );	
	
	public function getId( )
	{
		return $m_id;	
	}
}

class Tile
{
	protected $m_pdo;
	
	//////////////////////////
	//  Logical Attributes  //
	//////////////////////////
	private $m_name = NULL;
	
	/** Array of neighbouring tiles
	 * 
	 * @var array
	 */	
	private $m_neighbours = NULL;
	
	/** Production poitns generated by this tile
	 * 
	 * @var int
	 */
	private $m_production = NULL;
	
	/** Current controller of this tile
	 * 
	 * @var Nation
	 */
	private $m_owner = NULL;
	
	/** Is this a water tile?
	 * 
	 * @var Boolean
	 */
	private $m_water = NULL;
	
	///////////////////////////
	// Structural Attributes //
	///////////////////////////
	/** The tile polygon's center vertex
	 * 
	 * @var array of int
	 */
	private $m_center = NULL;
	
	/** The tile polygon's vertices
	 * 
	 * @var array of array of int
	 */
	private $m_vertices = NULL;
	private $m_verticesJsonCache = NULL;
	
	protected $m_loadVerticesPdoStatement = NULL;
	protected $m_saveVerticesJsonCachePdoStatement = NULL;
	protected $m_updateVerticesMinMaxCachePdoStatement = NULL;
	
	/** The position of text, units, etc ??? NOT SURE !!!
	 * 
	 * @var array of array of int
	 */
	private $m_places = NULL;
	
	private $m_verticesBoundingBoxUpperLeft = NULL;
	private $m_verticesBoundingBoxLowerRight = NULL;
	
	private $m_id = NULL;
	
	///////////////////////////
	// Methods and Functions //
	///////////////////////////
	
	public function getCenterX( )
	{
		return $this->m_center[0];
	}
	
	public function getCenterY( )
	{
		return $this->m_center[1];
	}
	
	public function isWater( )
	{
		return $this->m_water;
	}
	
	public function getOwner( )
	{
		return $this->m_owner;
	}
	
	public function getName()
	{
		return $this->m_name;
	}
	
	protected function assignResult(array $assoc_row)
	{
		if (array_key_exists('tile_id', $assoc_row))
		{
			$this->m_id = $assoc_row['tile_id'];
		}
		if (array_key_exists('tile_name', $assoc_row))
		{
			$this->m_name = $assoc_row['tile_name'];
		}
		if (array_key_exists('tile_production', $assoc_row))
		{
			$this->m_production = $assoc_row['tile_production'];
		}
		if (array_key_exists('tile_type', $assoc_row))
		{
			$this->m_type = $assoc_row['tile_type'];
		}
		if (array_key_exists('tile_startowner_id', $assoc_row))
		{
			$this->m_owner = $assoc_row['tile_startowner_id'];
		}
		if (array_key_exists('tile_center_x', $assoc_row) && array_key_exists('tile_center_y', $assoc_row))
		{
			$this->m_center = array( $assoc_row['tile_center_x'],  $assoc_row['tile_center_y']);
		}
		if (array_key_exists('tile_vertices_max_x', $assoc_row) && array_key_exists('tile_vertices_max_y', $assoc_row))
		{
			$this->m_verticesBoundingBoxLowerRight = array( $assoc_row['tile_vertices_max_x'], $assoc_row['tile_vertices_max_y'] );
		}
		if (array_key_exists('tile_vertices_min_x', $assoc_row) && array_key_exists('tile_vertices_min_y', $assoc_row))
		{
			$this->m_verticesBoundingBoxUpperLeft = array( $assoc_row['tile_vertices_min_x'], $assoc_row['tile_vertices_min_y'] );
		}
		if (array_key_exists('tile_vertices_json_cache', $assoc_row))
		{
			// reular expression matches json [[123,456],[234,567],[345,678],...] with at least 3 [1,0] elements (polygon)
			if (preg_match('/\[(\[\d+,\d+\],?){3,}\]/', $assoc_row['tile_vertices_json_cache']))
			{
				$this->m_verticesJsonCache = $assoc_row['tile_vertices_json_cache'];
			}
		}
	}
	
	public function __construct(PDO $database, array $assoc_row = NULL)
	{
		$this->m_pdo = $database;

		if ($assoc_row !== NULL)
		{
			$this->assignResult($assoc_row);
		}
		try
		{
			$this->m_loadVerticesPdoStatement = $this->m_pdo->prepare('SELECT x, y FROM ' . TABLE_TILE_VERTICES . ' WHERE vertex_tile_id = :tile_id;', array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$this->m_loadVerticesPdoStatement->bindParam(':tile_id', $this->m_id, PDO::PARAM_INT);
			
			$this->m_saveVerticesJsonCachePdoStatement = $this->m_pdo->prepare('UPDATE ' . TABLE_BASETILES . ' SET tile_vertices_json_cache = :vertices_json_cache WHERE tile_id = :tile_id');
			$this->m_saveVerticesJsonCachePdoStatement->bindParam(':tile_id', $this->m_id, PDO::PARAM_INT);
			$this->m_saveVerticesJsonCachePdoStatement->bindParam(':vertices_json_cache', $this->m_verticesJsonCache, PDO::PARAM_STR);
			
			$this->m_updateVerticesMinMaxCachePdoStatement = $this->m_pdo->prepare('UPDATE ' . TABLE_BASETILES . ' bt, (SELECT MAX(x) AS max, MIN(x) AS mix, MAX(y) AS may, MIN(y) AS miy, vertex_tile_id FROM ' . TABLE_TILE_VERTICES . ' GROUP BY vertex_tile_id) ss SET bt.tile_vertices_max_x=ss.max, bt.tile_vertices_min_x=ss.mix, bt.tile_vertices_max_y=ss.may, bt.tile_vertices_min_y=ss.miy WHERE bt.tile_id=ss.vertex_tile_id');
			
			$this->m_loadNeighboursPdoStatement = $this->m_pdo->prepare('SELECT f.tile_name AS first_tile_name, s.tile_name AS second_tile_name FROM ' . TABLE_TILE_CONNECTIONS . ' AS tc INNER JOIN ' . TABLE_BASETILES . ' AS f ON tc.tile_connection_first_tile_id = f.tile_id INNER JOIN ' . TABLE_BASETILES . ' AS s ON tc.tile_connection_second_tile_id = s.tile_id WHERE f.tile_id = :tile_id OR s.tile_id = :tile_id');
			$this->m_loadNeighboursPdoStatement->bindParam(':tile_id', $this->m_id, PDO::PARAM_INT); 
		}
		catch(PDOException $e)
		{
			die($e->getMessage());
		}
	}
	
	protected function buildVerticesJSONCache()
	{
		if ($this->m_vertices === NULL)
		{
			$this->loadVertices();
		}
		
		$json = '[';	
		foreach($this->m_vertices as &$vertex)
		{
			$json .= '[' . $vertex[0] . ',' . $vertex[1] . '],';					
		}
		$json = trim($json, ',');
		$json .= ']';
		$this->m_verticesJsonCache = $json;
		$this->m_pdo->beginTransaction();
		try
		{
			$this->m_saveVerticesJsonCachePdoStatement->execute();
			$this->m_updateVerticesMinMaxCachePdoStatement->execute();
			$this->m_pdo->commit();
		}
		catch(PDOException $e)
		{
			$this->m_pdo->rollBack();
			die($e->getMessage());
		}
	}
	
	public function getVerticesAsJson()
	{
		if ($this->m_verticesJsonCache === NULL)
		{
			$this->buildVerticesJSONCache();
		}
		return $this->m_verticesJsonCache;
	}
	
	public function getVerticesBoundingBoxAsJson()
	{
		return '{"upperleft":[' . $this->m_verticesBoundingBoxUpperLeft[0] . ',' . $this->m_verticesBoundingBoxUpperLeft[1] . '],"lowerright":[' . $this->m_verticesBoundingBoxLowerRight[0] . ','. $this->m_verticesBoundingBoxLowerRight[1] . ']}';
	}
	
	public function getCenterAsJson()
	{
		return '[' . $this->m_center[0] . ',' . $this->m_center[1] . ']';
	}
	
	public function getNeighboursAsJson()
	{
		$json = '[';
		if($this->m_neighbours != NULL)
		{
			foreach($this->m_neighbours as $neighbour_name => &$neigbour)
			{
				$json .= '"' . $neighbour_name . '",';
			}
		}
		return trim($json,',').']';
	}
	
	public function getTileAsJson()
	{		
		return '{"name":"' . $this->m_name . '"' .
				',"owner":' . ( $this->getOwner( ) ? $this->getOwner( ) : 0 ) . 
				',"vertices":' . $this->getVerticesAsJson() .		
				',"center":' . $this->getCenterAsJson() .
				',"type":' . $this->m_type .
				',"neighbours":' . $this->getNeighboursAsJson() .
				',"boundingbox":' . $this->getVerticesBoundingBoxAsJson() . '}';
	}
	
	public function loadVertices()
	{	
		try
		{
			$this->m_loadVerticesPdoStatement->execute();
			
			$this->m_vertices = array();
			
			while ($row = $this->m_loadVerticesPdoStatement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT))
			{
				$this->m_vertices[] = array($row['x'], $row['y']);
			}
			$this->m_loadVerticesPdoStatement->closeCursor();
		}
		catch(PDOException $e)
		{
			die($e->getMessage());
		}
	}
	
	public function loadNeighbouringTiles(TileFactory $factory)
	{
		$this->loadNeighbouringTileNames();
		foreach($this->m_neighbours as $neighbour_name => &$neighbour)
		{
			$neighbour = $factory->getTile($neighbour_name);
		}
	}
	
	public function loadNeighbouringTileNames()
	{
		try
		{
			$this->m_neighbours = array();
			$this->m_loadNeighboursPdoStatement->execute();
			
			while($row = $this->m_loadNeighboursPdoStatement->fetch(PDO::FETCH_ASSOC))
			{
				if ($row['first_tile_name'] === $this->m_name)
				{
					$this->m_neighbours[$row['second_tile_name']] = NULL;
				}
				else if ($row['second_tile_name'] === $this->m_name)
				{
					$this->m_neighbours[$row['first_tile_name']] = NULL;
				}
			}
		}
		catch(PDOException $e)
		{
			die($e->getMessage());
		}
	}
	
	private function openForNeighbour(Tile $neighbour)
	{
		if ($this->m_neighbours === NULL)
		{
			$this->m_neighbours = array();
		}
		$this->m_neighbours[$neighbour->m_name] = $neighbour;
	}
	
	public function knockAtNeighbour(Tile $neighbour)
	{
		if ($this->m_neighbours === NULL)
		{
			$this->m_neighbours = array();
		}
		$this->m_neighbours[$neighbour->m_name] = $neighbour;
		$neighbour->openForNeighbour($this);
	}
}

?>