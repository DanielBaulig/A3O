<?php
class A3Mobilize implements IState
{
	protected $m_match;
	protected $m_nextPhase;
	
	const PLACE_PIECES = 'place';
	
	public function __construct( MatchBoard $match )
	{
		$this->m_match = $match;
		$this->unitsToPlace = 2;
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
		if ( $this->unitsToPlace > 0 )
		{
			return $this;
		}
		else
		{
			return $this->doNextPhaseExit( );
		}
	}
	
	public function doAction( Action $action )
	{
		if ( $action->isCommand( self::PLACE_PIECES ) )
		{
			// place pieces
			$this->unitsToPlace -= 1;
		}
		
		return $this->doEnter( );
	}
}