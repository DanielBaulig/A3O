<?php
require_once dirname(__FILE__) . '/A3SetupState.php';
require_once dirname(__FILE__) . '/A3BidState.php';

require_once dirname(__FILE__) . '/TurnPhases/A3TurnPhases.php';


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
	
	abstract public function buildSetup( );
	abstract public function buildGameOver( );
	
	public function createNewMatchMachine( )
	{
		$this->setup = null;
		$this->gameOver = null;
	}
	
	public function getMatchMachine(  )
	{
		$this->setup->setUp( $this->gameOver );
		$this->bidMachineBuildDirector->createStateMachine( $this->turnPhasesBuildDirector->createStateMachine( $this->gameOver ) );
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
		if ( $state = $this->turnPhaseMachineBuildDirector->getStateSavedIn( $stateBuffer ) )
		{
			return $state;
		}
		return null;
	}
}