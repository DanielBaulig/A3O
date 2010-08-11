<?php
class A3NonCombatMovement extends BaseState
{
	protected $m_match;
	protected $m_nextPhase;
	
	const MOVE_PIECES = 'move';
	const END_NONCOMBAT_MOVEMENT = 'endmove';
	
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
		return $this;
	}
	
	public function doAction( Action $action )
	{
		if ( $action->isCommand( self::MOVE_PIECES ) )
		{
			// move pieces
		}
		
		if( $action->isCommand( self::END_NONCOMBAT_MOVEMENT ) )
		{
			return $this->doNextPhaseExit( );
		}
		
		return $this;
	}
}