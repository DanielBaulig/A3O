<?php
require_once dirname(__FILE__) . '/A3SetupState.php';
require_once dirname(__FILE__) . '/A3BidState.php';
require_once dirname(__FILE__) . '/A3GameOver.php';

require_once dirname(__FILE__) . '/TurnPhases/A3TurnPhases.php';

class  A3MatchMachineBuildDirector implements IStateMachineFactory
{
	private $matchMachineBuilder = null;
	
	public function setMatchMachineBuilder( A3MatchMachineBuilder $matchMachineBuilder )
	{
		$this->matchMachineBuilder = $matchMachineBuilder;
	}
	
	public function createStateMachine( IState $exitPoint )
	{
		$this->matchMachineBuilder->createNewMatchMachine();
		
		$this->matchMachineBuilder->buildSetup( 'Setup' );
		$this->matchMachineBuilder->buildGameOver( 'Game Over' );
		
		return $this->matchMachineBuilder->getMatchMachine( $exitPoint );
	}
	
	public function getStateSavedIn( $stateBuffer )
	{
		return $this->matchMachineBuilder->getStateSavedIn( $stateBuffer );
	}
}


abstract class A3MatchMachineBuilder implements IStateLoader
{
	protected $match;
	
	protected $gameOver;
	protected $setup;
	
	private $turnPhasesBuildDirector;
	private $bidMachineBuildDirector;
	
	public function __construct( MatchBoard $match, 
		IStateMachineFactory $turnPhasesBuildDirector, 
		IStateMachineFactory $bidMachineBuildDirector )
	{
		$this->match = $match;
		
		$this->bidMachineBuildDirector = $bidMachineBuildDirector;
		$this->turnPhasesBuildDirector = $turnPhasesBuildDirector;
	}
	
	abstract public function buildSetup( $name );
	abstract public function buildGameOver( $name );
	
	public function createNewMatchMachine( )
	{
		$this->setup = null;
		$this->gameOver = null;
	}
	
	public function getMatchMachine(  )
	{
		$bid = $this->bidMachineBuildDirector->createStateMachine( $this->turnPhasesBuildDirector->createStateMachine( $this->gameOver ) );
		$this->setup->setUp( $bid );
		return $this->setup;
	}
	
	public function getStateSavedIn( $stateBuffer )
	{
		if ( $this->setup->isSavedIn( $stateBuffer ) )
		{
			return $this->setup;
		}
		if ( $this->gameOver->isSavedIn( $stateBuffer ) )
		{
			return $this->gameOver;
		}
		if ( $state = $this->bidMachineBuildDirector->getStateSavedIn( $stateBuffer ) )
		{
			return $state;
		}
		if ( $state = $this->turnPhasesBuildDirector->getStateSavedIn( $stateBuffer ) )
		{
			return $state;
		}
		return null;
	}
}