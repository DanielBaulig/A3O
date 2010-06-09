<?php

require_once 'config.php';
require_once 'include/classes/Tile.php';

try
{
	$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password);
}
catch (PDOException $e)
{
	die($e->getMessage());
}

if(file_exists('polygons.txt'))
{
	$fh = fopen('polygons.txt', 'r');
	
	$tile_id_sql = 'SELECT tile_id FROM tiles WHERE tile_name LIKE :tile_name ;';
	$tile_id_statement = $pdo->prepare($tile_id_sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	
	$vertex_sql = 'INSERT INTO tile_vertices (vertex_tile_id, x, y) VALUES (:tile_id, :x, :y);';
	$vertex_statement = $pdo->prepare($vertex_sql);
	
	while ($s = fgets($fh))
	{
		preg_match('/([\w\s\'-]+)/', $s, $tile_name);

		$tile_name = $tile_name[1];
		$tile_name = trim($tile_name);
		
		try
		{
			$tile_id_statement->bindValue(':tile_name', $tile_name, PDO::PARAM_STR);
			if (!$tile_id_statement->execute())
			{
				$error = $tile_id_statement->errorInfo();
				die($error[2]);
			}

			if (! $row = $tile_id_statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT))
			{
				$tile_id_statement->debugDumpParams();
			}
		}
		catch (PDOException $e)
		{
			die($e->getMessage());	
		}
		$tile_id = $row['tile_id'];
		
		$vertex_statement->bindValue(':tile_id', $tile_id, PDO::PARAM_INT);
		
		preg_match_all('/\((\d+),(\d+)\)/', $s, &$vertices);
		print_r($vertices);

		for($i = 0; $i < count($vertices[1]); $i++)
		{
			$vertex_statement->bindValue(':x', $vertices[1][$i], PDO::PARAM_INT);
			$vertex_statement->bindValue(':y', $vertices[2][$i], PDO::PARAM_INT);
			$vertex_statement->execute();
		}
	}
}

?>