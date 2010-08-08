<?php
class A3Reinforcements implements IState
{
	protected $m_nextPhase;
	protected $m_match;

        const BUY_PIECES = 'buy';
	const END_REINFORCEMENTS = 'endreinforcements';
	
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
		if ( $action->isCommand( self::BUY_PIECES ) )
		{
			// buy pieces
		}
		
		if( $action->isCommand( self::END_REINFORCEMENTS ) )
		{
			return $this->doNextPhaseExit( );
		}
		
		return $this;
	}
}