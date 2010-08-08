<?php
class A3Combat implements IState
{
	protected $m_match;
	protected $m_nextPhase;
	protected $m_conductCombat;
	
	const CONDUCT_COMBAT = 'conduct';
	const CONDUCT_ALL = 'conductall';
	
	public function __construct( MatchBoard $match )
	{
		$this->m_match = $match;	
		$this->conflictsLeft = 5;
	}
	
	public function setUp( IState $nextPhase, IState $conductCombat )
	{
		$this->m_nextPhase = $nextPhase;
		$this->m_conductCombat = $conductCombat;
	}
	
	public function doEnter( )
	{
		if( !$this->conflictsLeft )
		{
			return $this->doNextPhaseExit( );
		}
		else
		{
			return $this;
		}
	}
	
	protected function doConductCombatExit( )
	{
		return $this->m_conductCombat->doEnter( );
	}
	
	protected function doNextPhaseExit( )
	{
		return $this->m_nextPhase->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		if ( $action->isCommand( self::CONDUCT_COMBAT ) )
		{
			// if zone is conflict?
			$this->conflictsLeft -= 1;
			return $this->doConductCombatExit( );
		}
		
		if( $action->isCommand( self::CONDUCT_ALL ) )
		{
			// for each conflict zone
			$this->conflictsLeft = 0;
		}
		return $this->doEnter( );
	}
}