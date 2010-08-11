<?php
require_once dirname( __FILE__ ) . '/A3Research.php';
require_once dirname( __FILE__ ) . '/A3Reinforcements.php';
require_once dirname( __FILE__ ) . '/A3CombatMovement.php';
require_once dirname( __FILE__ ) . '/A3Combat.php';

require_once dirname( __FILE__ ) . '/ConductCombat/A3ConductCombat.php';

require_once dirname( __FILE__ ) . '/A3NonCombatMovement.php';
require_once dirname( __FILE__ ) . '/A3Mobilize.php';
require_once dirname( __FILE__ ) . '/A3CollectIncome.php';

abstract class A3TurnPhasesBuilder implements IStateLoader
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
		$this->combatMovement->setUp( $this->combat );
		$this->combat->setUp( $this->nonCombatMovement, $this->conductCombatBuildDirector->createStateMachine( $this->combat ) );
		$this->nonCombatMovement->setUp( $this->mobilize );
		$this->mobilize->setUp( $this->collectIncome );
		$this->collectIncome->setUp( $exitPoint );
		
		return $this->research;
	} 
	
	public function getStateSavedIn( $stateBuffer )
	{
		if ( $this->research->isSavedIn( $stateBuffer ) )
		{
			return $this->research;
		}		
		if ( $this->reinforcements->isSavedIn( $stateBuffer ) )
		{
			return $this->reinforcements;
		}
		if( $this->combatMovement->isSavedIn( $stateBuffer ) )
		{
			return $this->combatMovement;
		}
		if( $this->combat->isSavedIn( $stateBuffer ) )
		{
			return $this->combat;
		}
		if ( $this->nonCombatMovement->isSavedIn( $stateBuffer ) )
		{
			return $this->nonCombatMovement;
		}
		if( $this->mobilize->isSavedIn( $stateBuffer ) )
		{
			return $this->mobilize;
		}
		if( $this->collectIncome->isSavedIn( $stateBuffer ) )
		{
			return $this->collectIncome;
		}
		return $this->conductCombatBuildDirector->getStateSavedIn( $stateBuffer );
	}
}

class  A3TurnPhasesBuildDirector implements IStateMachineFactory
{
	private $turnPhaseBuilder = null;
	
	public function setTurnPhaseBuilder( A3TurnPhasesBuilder $turnPhaseBuilder )
	{
		$this->turnPhaseBuilder = $turnPhaseBuilder;
	}
	
	public function createStateMachine( IState $exitPoint )
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
	
	public function getStateSavedIn( $stateBuffer )
	{
		return $this->turnPhaseBuilder->getStateSavedIn( $stateBuffer );
	}
}