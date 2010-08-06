<?php
// this indirection/abstraction layer is only for the case I will later need it
class A3MatchPlayerPDOFactory extends MatchPlayerPDOFactory
{
	protected function createObject( $data )
	{
		return new A3MatchPlayer( $this->m_match, $data );
	}
}

class A3MatchPlayer extends MatchPlayer
{	
}