<?php
class AARTurnPhasesBuilder extends A3TurnPhasesBuilder
{
	public function buildResearch( $name )
	{
		$this->research = new A3Research( $name , $this->match );
	}
	public function buildReinforcements( $name )
	{
		$this->reinforcements = new A3Reinforcements( $name, $this->match );
	}
	public function buildCombatMovement( $name )
	{
		$this->combatMovement = new A3CombatMovement( $name, $this->match );
	}
	public function buildCombat( $name )
	{
		$this->combat = new A3Combat( $name, $this->match );
	}
	public function buildNonCombatMovement( $name )
	{
		$this->nonCombatMovement = new A3NonCombatMovement( $name, $this->match );
	}
	public function buildMobilize( $name )
	{
		$this->mobilize = new A3Mobilize( $name, $this->match );
	}
	public function buildCollectIncome( $name )
	{
		$this->collectIncome = new A3CollectIncome( $name, $this->match );
	}
	
	public function __construct( $match, $conductCombatBuildDirector )
	{
		parent::__construct( $match, $conductCombatBuildDirector );
	}
}