<?php
class A3CollectIncome extends BaseState
{
	protected $m_match;
	protected $m_nextPhase;
	
	public function __construct( $name, MatchBoard $match )
	{
		parent::__construct( $name );
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