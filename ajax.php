<?php
	
require_once 'config.php';
require_once 'include/classes/Tile.php';

$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password);

$tile = new Tile($pdo);
$tile->loadVertices();

echo $tile->getVerticesAsJSON();
?>