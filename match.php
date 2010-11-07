<?php

require_once 'common.php';

require_once 'include/classes/MatchBoard/A3/AAR/AARMatchBoard.php';

$match = new AARPDOMatchBoard($pdo, $request->sanitizeInteger('game'), $request->sanitizeInteger('match') );

$match->setUpMatch();
//$match->precacheZones();

header('content-type: text/plain; charset=utf8');

$match->storeZones(new MatchZoneStreamStorer(fopen('php://output', 'w+')));