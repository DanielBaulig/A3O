<?php

require_once 'common.php';

require_once 'include/classes/MatchBoard/A3/AAR/AARMatchBoard.php';

$match_id = $request->sanitizeInteger('match');
$game_id = $request->sanitizeInteger('game');

$match = new AARPDOMatchBoard($pdo, $game_id, $match_id );

//$fack = new AARMatchZonePDOFactory($pdo, $match);
//$zones  = $fack->createAllProductsFromBasezone();

$match->precacheZones();

header('content-type: application/json; charset=utf8');
echo '{';
$match->storeZones(new MatchZoneStreamStorer(fopen('php://output', 'w+')));
echo '}';