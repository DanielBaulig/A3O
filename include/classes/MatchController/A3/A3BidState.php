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
		$bid = new A3BidState( 'bid' );
		$bid->setUp( $exitPoint );
		return $bid;
	}
	
	public function getStateSavedIn( $stateBuffer )
	{
		return null;
	}
}