<?php
header('content-type: application/json');	

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

$tile = new Tile($pdo);
$tile->loadVertices();

echo  $tile->getVerticesAsJSON();
?>