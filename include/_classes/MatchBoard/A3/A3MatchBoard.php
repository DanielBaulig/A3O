<?php
require_once dirname(__FILE__) . '/../MatchBoard.php';

require_once dirname(__FILE__) . '/A3GameType.php';
require_once dirname(__FILE__) . '/A3MatchZone.php';
require_once dirname(__FILE__) . '/A3MatchPlayer.php';
require_once dirname(__FILE__) . '/A3GameNation.php';
require_once dirname(__FILE__) . '/A3GameAlliance.php';      

// TODO: Refactor all SQL Queries to use A3ClassName::CONST for their AS names
// TODO: Refactor all SQL Queries to use a variable prefix instead of a3o_

class A3PDOMatchBoard extends MatchBoard
{
	public function __construct(PDO $pdo, $game_id, $match_id)
	{
		$this->m_matchId = $match_id;
		$this->m_gameId = $game_id;
		
		$this->m_zoneRegistry = new BaseRegistry( new A3MatchZonePDOFactory($pdo, $this) );
		$this->m_playerRegistry = new BaseRegistry( new A3MatchPlayerPDOFactory($pdo, $this) );
		$this->m_nationRegistry = new BaseRegistry( new A3GameNationPDOFactory($pdo, $this) );
		$this->m_typeRegistry = new BaseRegistry( new A3GameTypePDOFactory($pdo, $this) );
		$this->m_allianceRegistry = new BaseRegistry( new A3GameAlliancePDOFactory($pdo, $this) );			
	}
}