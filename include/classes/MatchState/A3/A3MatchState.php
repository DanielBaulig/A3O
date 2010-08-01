<?php
require_once dirname(__FILE__) . '/../../Registry.php';
require_once dirname(__FILE__) . '/../GameType.php';
require_once dirname(__FILE__) . '/../MatchZone.php';
require_once dirname(__FILE__) . '/../GameNation.php';
require_once dirname(__FILE__) . '/../GameAlliance.php';

require_once dirname(__FILE__) . '/A3GameType.php';
require_once dirname(__FILE__) . '/A3MatchZone.php'; 

// TODO: Refactor all SQL Queries to use A3ClassName::CONST for their AS names
// TODO: Refactor all SQL Queries to use a variable prefix instead of a3o_

class A3MatchState
{
	
}