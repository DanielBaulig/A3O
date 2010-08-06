<?php
class AARMatchPlayerPDOFactory extends A3MatchPlayerPDOFactory
{
	protected function createObject( $data )
	{
		return new AARMatchPlayer( $this->m_match, $data );
	}
}

class AARMatchPlayer extends A3MatchPlayer
{	
}