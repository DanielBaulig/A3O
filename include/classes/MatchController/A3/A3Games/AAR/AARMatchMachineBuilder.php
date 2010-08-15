<?php
class AARMatchMachineBuilder extends A3MatchMachineBuilder
{
	public function buildSetup( $name )
	{
		$this->setup = new A3SetupState( $name, $this->match );
	}
	public function buildGameOver( $name )
	{
		$this->gameOver = new A3GameOver( $name, $this->match );
	}
}