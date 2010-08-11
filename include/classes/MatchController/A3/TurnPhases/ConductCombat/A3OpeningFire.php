<?php 
class A3OpeningFire extends BaseState
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
	
	public function doEnter( )
	{
		//TODO: FIRE!
		return $this->doNextPhaseExit( );
	}

	protected function doNextPhaseExit( )
	{
		return $this->m_nextPhase->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		return $this;
	}
}