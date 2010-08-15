<?php
require_once dirname( __FILE__ ) . '/A3OpeningFire.php';
require_once dirname( __FILE__ ) . '/A3RemoveCasualties.php';
require_once dirname( __FILE__ ) . '/A3AttackersFire.php';
require_once dirname( __FILE__ ) . '/A3DefendersFire.php';
require_once dirname( __FILE__ ) . '/A3PressRetreat.php';
require_once dirname( __FILE__ ) . '/A3ConcludeCombat.php';


abstract class A3ConductCombatBuilder implements IStateLoader
{
	protected $match; 
	
	protected $openingFire;
	protected $openFireRemoveCasualties;
	protected $attackersFire;
	protected $defendersFire;
	protected $combatRemoveCasualties;
	protected $pressRetreat;
	protected $concludeCombat;
	
	public function __construct( MatchBoard $match )
	{
		$this->match = $match;
	}
	
	abstract public function buildOpeningFire( $name );
	abstract public function buildOpeningFireRemoveCasualties( $name );
	abstract public function buildAttackersFire( $name );
	abstract public function buildDefendersFire( $name );
	abstract public function buildCombatRemoveCasualties( $name );
	abstract public function buildPressRetreat( $name );
	abstract public function buildConcludeCombat( $name );
	
	public function createNewConductCombatMachine( )
	{
		$this->openingFire = null;
		$this->openFireRemoveCasualties = null;
		$this->attackersFire = null;
		$this->defendersFire = null;
		$this->combatRemoveCasualties = null;
		$this->pressRetreat = null;
		$this->concludeCombat = null;
	}
	
	public function getConductCombatMachine( IState $exitPoint )
	{
		$this->openingFire->setUp( $this->openFireRemoveCasualties );
		$this->openFireRemoveCasualties->setUp( $this->attackersFire );
		$this->attackersFire->setUp( $this->defendersFire );
		$this->defendersFire->setUp( $this->combatRemoveCasualties );
		$this->combatRemoveCasualties->setUp( $this->pressRetreat );
		$this->pressRetreat->setUp( $this->concludeCombat, $this->attackersFire );
		$this->concludeCombat->setUp( $exitPoint );
		
		return $this->openingFire;
	}
	
	public function getStateSavedIn( $stateBuffer )
	{
		if( $this->openingFire->isSavedIn( $stateBuffer ) )
		{
			return $this->openingFire;
		}
		if( $this->openFireRemoveCasualties->isSavedIn( $stateBuffer ) )
		{
			return $this->combatRemoveCasualties;
		}
		if( $this->attackersFire->isSavedIn( $stateBuffer ) )
		{
			return $this->attackersFire;
		}
		if( $this->defendersFire->isSavedIn( $stateBuffer ) )
		{
			return $this->defendersFire;
		}
		if( $this->combatRemoveCasualties->isSavedIn( $stateBuffer ) )
		{
			return $this->combatRemoveCasualties;
		}
		if( $this->pressRetreat->isSavedIn( $stateBuffer ) )
		{
			return $this->pressRetreat;
		}
		if( $this->concludeCombat->isSavedIn( $stateBuffer ) )
		{
			return $this->concludeCombat;
		}
		return null;
	}
}

class A3ConductCombatBuildDirector implements IStateMachineFactory
{
	private $conductCombatBuilder = null;
	
	public function setConductCombatBuilder( A3ConductCombatBuilder $conductCombatBuilder )
	{
		$this->conductCombatBuilder = $conductCombatBuilder;
	}
	
	public function createStateMachine( IState $exitPoint )
	{
		return $this->createConductCombat( $exitPoint );
	}
	
	public function createConductCombat( IState $exitPoint )
	{
		$this->conductCombatBuilder->createNewConductCombatMachine( );
		$this->conductCombatBuilder->buildOpeningFire( 'Opening Fire' );
		$this->conductCombatBuilder->buildOpeningFireRemoveCasualties( 'Opening Fire Remove Casualties' );
		$this->conductCombatBuilder->buildAttackersFire( 'Attackers Fire' );
		$this->conductCombatBuilder->buildDefendersFire( 'Defenders Fire' );
		$this->conductCombatBuilder->buildCombatRemoveCasualties( 'Remove Casualties' );
		$this->conductCombatBuilder->buildPressRetreat( 'Press or Retreat' );
		$this->conductCombatBuilder->buildConcludeCombat( 'Conclude Combat' );
		
		return $this->conductCombatBuilder->getConductCombatMachine( $exitPoint );
	}
	
	public function getStateSavedIn( $stateBuffer )
	{
		return $this->conductCombatBuilder->getStateSavedIn( $stateBuffer );
	}
}