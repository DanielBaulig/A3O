<?php

require_once dirname(__FILE__) . '/../Registry.php';
require_once dirname(__FILE__) . '/../Storers.php';
require_once dirname(__FILE__) . '/GameType.php';
require_once dirname(__FILE__) . '/MatchZone.php';
require_once dirname(__FILE__) . '/GameNation.php';
require_once dirname(__FILE__) . '/GameAlliance.php';
require_once dirname(__FILE__) . '/MatchPlayer.php';

abstract class MatchState
{
	protected $m_zoneRegistry;
	protected $m_playerRegistry;
	protected $m_nationRegistry;
	protected $m_typeRegistry;
	protected $m_allianceRegistry;	

	protected $m_matchId;
	protected $m_gameId;
	
	abstract public function __construct( $game_id, $match_id );
	
	public function apply( IChange $change )
	{
		$change->applyTo( $this );
	}
	
	public function getMatchId( )
	{
		return $this->m_matchId;
	}
	
	public function getGameId( )
	{
		return $this->m_gameId;
	}
	
	public function getZone( $zone )
	{
		return $this->m_zoneRegistry->getElement( $zone );
	}
	public function getPlayer( $nation )
	{
		return $this->m_playerRegistry->getElement( $nation );
	}
	public function getNation( $nation )
	{
		return $this->m_nationRegistry->getElement( $nation );
	}
	public function getType( $type )
	{
		return $this->m_typeRegistry->getElement( $type );
	}
	public function getAlliance( $alliance )
	{
		return $this->m_allianceRegistry->getElement( $alliance );
	}
}