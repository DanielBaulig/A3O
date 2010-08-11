<?php
class A3CollectIncome implements IState
{
	protected $m_match;
	protected $m_nextPhase;
	
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
		// collect income
		return $this->doNextPhaseExit( );
	}
	
	public function doAction( Action $action )
	{
		return $this;
	}
}