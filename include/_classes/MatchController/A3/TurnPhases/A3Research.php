<?php
class A3Research implements IState
{
	protected $m_match;
	protected $m_nextPhase;
	
	const BUY_DICE = 'buydice';
	const END_RESEARCH = 'endresearch';
	
	public function __construct( MatchBoard $match )
	{
		$this->m_match = $match;
	}
	
	public function setUp( IState $nextPhase )
	{
		$this->m_nextPhase = $nextPhase;
	}
	
	protected function doNextPhaseExit( )
	{
		return $this->m_nextPhase->doEnter( );
	}
	
	public function doEnter( )
	{
		if ( $this->cannotResearch )
		{
			return $this->doNextPhaseExit( );
		}
		else
		{
			return $this;
		}
	}
	
	public function doAction(Action $action)
	{
		if ( $action->isCommand( self::BUY_DICE ) )
		{
			//TODO: buy dice & check research
			$this->cannotResearch = true;
		}
		
		if ( $action->isCommand( self::END_RESEARCH ) )
		{
			return $this->doNextPhaseExit( );
		}
		
		return $this->doEnter( );
	}
}