<?php
/*class TurnSequence extends BaseState
{
	protected $m_turnOrder;
	protected $m_playerWonExit = null;
	protected $m_nextTurnExit = null;
	protected $m_currentIndex;
	protected $m_playerWon = false;
	
	public function __construct( $name, array $turnOrder )
	{
		parent::__construct( $name );
		$this->m_turnOrder = $turnOrder;
		$this->m_phaseSequenceMachine = $phaseSequenceMachine;
		$this->m_gameOverState = $gameOverState;
		$this->m_currentIndex = 0;	
	}
	
	public function setUp( IState $playerWonExit, IState $nextTurnExit )
	{
		if ( $this->playerWonExit !== null || $this->nextTurnExit !== null )
		{
			throw new Exception( 'TurnSequence already set up.' );
		}
		$this->m_playerWonExit = $playerWonExit;
		$this->m_nextTurnExit = $nextTurnExit;		
	}
	
	protected function doPlayerWonExit( )
	{
		return $this->m_playerWonExit->doEnter( );
	}
	
	protected function doNextPlayerExit( )
	{
		return $this->m_nextTurnExit->doEnter( );
	}
	
	public function doEnter( )
	{
		if ( $this->m_playerWon )
		{
			return $this->doPlayerWonExit( );
		}
		else 
		{
			$currentNation = $this->m_turnOrder[$m_currentIndex];
			$this->m_currentIndex = ( ++$this->m_currentIndex ) % count($this->m_turnOrder);
			return $this->doNextPlayerExit( );	
		}		
	}
	
	public function doAction( Action $action )
	{
		throw new Exception( 'TurnSequence cannot handle actions.' );
	}
}  */