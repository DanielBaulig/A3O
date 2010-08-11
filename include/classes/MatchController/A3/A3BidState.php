<?php
//TODO: implement bidding
class A3BidState extends UnimplementedState
{
}

class A3BidStateBuilder
{
	
}

class A3BidMachineBuildDirector implements IStateMachineFactory
{
	public function createStateMachine( IState $exitPoint )
	{
		return new A3BidState( $exitPoint );
	}
	
	public function getStateSavedIn( $stateBuffer )
	{
		return null;
	}
}