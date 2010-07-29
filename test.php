<?php

require_once 'config.php';
require_once 'include/classes/GameState/A3MatchZone.php';

try
{
	$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password );
}
catch (PDOException $e)
{
	die($e->getMessage());
}
if ( ! $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) || $pdo->getAttribute(PDO::ATTR_ERRMODE) != PDO::ERRMODE_EXCEPTION)
{
	die ('Error Mode change failed!');
}

A3MatchZoneRegistry::initializeRegistry( new A3MatchZonePDOFactory( $pdo, 1 ) );
A3GameTypeRegistry::initializeRegistry( new A3GameTypePDOFactory( $pdo, 1 ) );

$reg = A3MatchZoneRegistry::getInstance( );

$archangel = $reg->getElement( 'Archangel' );

$archangel->hasConnection( 'Belarus' ) or die( 'Should have connection' );

$archangel->countPieces( 'Russia', 'infantry' ) == 5 or print( 'Wrong number of infantry' );
$archangel->countPieces( 'Russia', 'tank' ) == 2 or print( 'Wrong number of tanks' );

A3GameTypeRegistry::getElement( 'infantry')->attack == 1 or print( 'Infantry attack wrong' );
A3GameTypeRegistry::getElement( 'tank')->attack == 3 or print( 'Tank attack wrong' );
A3GameTypeRegistry::getElement( 'infantry')->defense == 2 or print( 'Infantry defense wrong' );
A3GameTypeRegistry::getElement( 'tank')->defense == 3 or print( 'Tank defense wrong' );
