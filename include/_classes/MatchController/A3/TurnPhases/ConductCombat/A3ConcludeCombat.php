<?php
class A3ConcludeCombat implements IState
{
	protected $m_nextPhase;
	protected $m_match;

	const END_COMBAT = 'endcombat';
	
	public function __construct( MatchBoard $match )
	{
		$this->m_match = $match;	
	}
	
	public function setUp( IState $nextPhase )
	{
		$this->m_nextPhase = $nextPhase;
	}
	
	public function doEnter( )
	{
		return $this;
	}
	
	protected function doNextPhaseExit( )
	{
		return $this->m_nextPhase->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		if ( $action->isCommand( self::END_COMBAT ) )
		{
			// change owner and stuff
			return $this->doNextPhaseExit( );
		}
		
		return $this;
	}
}