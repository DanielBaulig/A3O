<?php
require_once dirname( __FILE__ ) . '/A3Research.php';
require_once dirname( __FILE__ ) . '/A3Reinforcements.php';
require_once dirname( __FILE__ ) . '/A3CombatMovement.php';
require_once dirname( __FILE__ ) . '/A3Combat.php';

require_once dirname( __FILE__ ) . '/ConductCombat/A3ConductCombat.php';

require_once dirname( __FILE__ ) . '/A3NonCombatMovement.php';
require_once dirname( __FILE__ ) . '/A3Mobilize.php';
require_once dirname( __FILE__ ) . '/A3CollectIncome.php';

abstract class A3TurnPhasesBuilder
{
	protected $match;
	
	protected $research;
	protected $reinforcements;
	protected $combatMovement;
	protected $combat;
	protected $nonCombatMovement;
	protected $mobilize;
	protected $collectIncome;

	private $conductCombatBuildDirector;
	
	public function __construct( MatchBoard $match, IStateMachineFactory $conductCombatBuildDirector )
	{
		$this->match = $match;
		$this->conductCombatBuildDirector = $conductCombatBuildDirector;
	}

	abstract public function buildResearch( );
	abstract public function buildReinforcements( );
	abstract public function buildCombatMovement( );
	abstract public function buildCombat( );
	abstract public function buildNonCombatMovement( );
	abstract public function buildMobilize( );
	abstract public function buildCollectIncome( );
	
	public function createNewTurnPhasesMachine( )
	{
		$this->collectIncome = null;
		$this->combat = null;
		$this->combatMovement = null;
		$this->match = null;
		$this->mobilize = null;
		$this->nonCombatMovement = null;
		$this->reinforcements = null;
		$this->research = null;
	}
	public function getTurnPhasesMachine( IState $exitPoint )
	{
		$this->research->setUp ( $this->reinforcements );
		$this->reinforcements->setUp( $this->combatMovement );
		$this->combatMovement->set( $this->combat );
		$this->combat->setUp( $this->nonCombatMovement, $this->conductCombatBuildDirector->createStateMachine( $this->combat ) );
		$this->nonCombatMovement->setUp( $this->mobilize );
		$this->mobilize->setUp( $this->collectIncome );
		$this->collectIncome->setUp( $exitPoint );
		
		return $this->research;
	} 
}

class  A3TurnPhasesBuildDirector implements IStateMachineFactory
{
	private $turnPhaseBuilder = null;
	
	public function setTurnPhaseBuilder( A3TurnPhasesBuilder $turnPhaseBuilder )
	{
		$this->turnPhaseBuilder = $turnPhaseBuilder;
	}
	
	public function createTurnPhases( IState $exitPoint )
	{
		$this->turnPhaseBuilder->createNewTurnMachine( );
		$this->turnPhaseBuilder->buildResearch( );
		$this->turnPhaseBuilder->buildReinforcements( );
		$this->turnPhaseBuilder->buildCombatMovement( );
		$this->turnPhaseBuilder->buildCombat( );
		$this->turnPhaseBuilder->buildNonCombatMovement( );
		$this->turnPhaseBuilder->buildMobilize( );
		$this->turnPhaseBuilder->buildCollectIncome( );
		
		return $this->turnPhaseBuilder->getTurnPhaseMachine( $exitPoint );
	}
	
	public function createStateMachine( IState $exitPoint )
	{
		return $this->createTurnPhases( $exitPoint );
	}
}