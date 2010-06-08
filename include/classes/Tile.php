<?php

define('TILE_TYPE_LAND', 0);
define('TILE_TYPE_SEA',  1);

define('TABLE_VERTICES', 'vertices');

class Tile
{
	protected $m_pdo;
	
	//////////////////////////
	//  Logical Attributes  //
	//////////////////////////
	/** Array of neighbouring tiles
	 * 
	 * @var array
	 */	
	private $neighbours;
	
	/** Production poitns generated by this tile
	 * 
	 * @var int
	 */
	private $production;
	
	/** Current controller of this tile
	 * 
	 * @var Nation
	 */
	private $controller;
	
	/** Type of th tile (sea or land)
	 * 
	 * @var TILE_TYPE_LAND or TILE_TYPE_SEA
	 */
	private $type;
	
	///////////////////////////
	// Structural Attributes //
	///////////////////////////
	private $name;
	/** The tile polygon's center vertex
	 * 
	 * @var array of int
	 */
	private $center;
	
	/** The tile polygon's vertices
	 * 
	 * @var array of array of int
	 */
	private $m_vertices;
	private $m_verticesJsonCache = NULL;
	
	protected $m_loadVerticesPdoStatement = NULL;
	
	/** The position of text, units, etc ??? NOT SURE !!!
	 * 
	 * @var array of array of int
	 */
	private $places;
	
	private $id;
	
	///////////////////////////
	// Methods and Functions //
	///////////////////////////
	
	public function __construct(PDO $database)
	{
		$this->m_pdo = $database;
		// DEBUG HACK
		$this->id = 1;
		
		//die('SELECT x, y FROM ' . TABLE_VERTICES . ' WHERE vertex_tile_id = :vertex_tile_id');
		
		$this->m_loadVerticesPdoStatement = $this->m_pdo->prepare('SELECT x, y FROM ' . TABLE_VERTICES . ' WHERE vertex_tile_id = :vertex_tile_id;', array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$this->m_loadVerticesPdoStatement->bindParam(':vertex_tile_id', $this->id, PDO::PARAM_INT);
	}
	
	/** Calculates the path to another tile using A*
	 * 
	 * @param Tile $other
	 * @returns array of Tile
	 */
	public function &pathTo(Tile &$other)
	{
		// do A*
		return $other;
	}
	
	protected function buildVerticesJSONCache()
	{
		$json = '[ ';	
		foreach($this->m_vertices as &$vertex)
		{
			$json .= '[ ' . $vertex[0] . ', ' . $vertex[1] . ' ], ';					
		}
		$json = trim($json, ',');
		$json .= ']';
		$this->m_verticesJsonCache = $json;
	}
	
	public function &getVerticesAsJSON()
	{
		if ($this->m_verticesJsonCache === NULL)
		{
			$this->buildVerticesJSONCache();
		}
		return $this->m_verticesJsonCache;
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
		}
		catch(PDOException $e)
		{
			die($e->getMessage());
		}
	}
}

?>