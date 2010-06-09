<?php

require_once 'config.php';
require_once 'include/classes/Tile.php';
require_once 'include/classes/Ajax.php';
require_once 'include/classes/Security.php';

try
{
	$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password);
}
catch (PDOException $e)
{
	die($e->getMessage());
}

function ajax_suggestTile(AjaxSecurity $ajaxSecurity, SecurityToken $securityToken)
{
	global $pdo;
	
	$filtered_x = $ajaxSecurity->filterInput('x');
	$filtered_y = $ajaxSecurity->filterInput('y');
	
	$statement = $pdo->prepare('SELECT vertex_tile_id FROM (SELECT MAX(x) AS max, MIN(x) AS mix, MAX(y) AS may, MIN(y) AS miy, vertex_tile_id FROM ' . TABLE_TILE_VERTICES . ' GROUP BY vertex_tile_id) sub_table WHERE max > :x AND mix < :x AND may > :y AND miy < :y');
	$statement->bindValue(':x', $filtered_x, PDO::PARAM_INT);
	$statement->bindValue(':y', $filtered_y, PDO::PARAM_INT);
	
	$statement->execute();
	
	$json = '[ ';
	
	while ($row = $statement->fetch(PDO::FETCH_ASSOC))
	{
		$json .= '{"id":' . $row['vertex_tile_id'] . '},';
	}
	$json = trim($json, ',') . ']';
	header('content-type: application/json');
	echo $json;
}

$as = new AjaxSecurity(0);
$as->addValidParam('x', INPUT_GET, FILTER_SANITIZE_NUMBER_INT, NULL);
$as->addValidParam('y', INPUT_GET, FILTER_SANITIZE_NUMBER_INT, NULL);
$ar = new AjaxResponder();
$ar->registerFunction('suggestTile', $as, ajax_suggestTile);
$ar->respond(new SecurityToken(10));

?>