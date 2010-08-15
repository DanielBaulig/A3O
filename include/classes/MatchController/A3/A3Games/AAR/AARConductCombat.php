<?php
class AARConductCombatBuilder extends A3ConductCombatBuilder
{
	protected $autoRemoveCasualties;
	public function buildOpeningFire( $name )
	{
		$this->openingFire = new A3OpeningFire( $name , $this->match );
	} 
	public function buildOpeningFireRemoveCasualties( $name )
	{
		$this->openFireRemoveCasualties = $this->autoRemoveCasualties ? 
			new A3AutoRemoveCasualties( $name, $this->match ) : new A3RemoveCasualties( $name, $this->match );
	}	
	public function buildAttackersFire( $name )
	{
		$this->attackersFire = new A3AttackersFire( $name, $this->match );
	}
	public function buildDefendersFire( $name )
	{
		$this->defendersFire = new A3DefendersFire( $name, $this->match );
	}
	public function buildCombatRemoveCasualties( $name )
	{
		$this->combatRemoveCasualties = $this->autoRemoveCasualties ?
			new A3AutoRemoveCasualties( $name, $this->match ) : new A3RemoveCasualties( $name, $this->match );
	}
	public function buildPressRetreat( $name )
	{
		$this->pressRetreat = new A3PressRetreat( $name, $this->match );
	}
	public function buildConcludeCombat( $name )
	{
		$this->concludeCombat = new A3ConcludeCombat( $name, $this->match );
	}
	
	public function __construct( MatchBoard $match, $autoRemoveCasualties )
	{
		parent::__construct( $match );
		$this->autoRemoveCasualties = $autoRemoveCasualties;
	}
}