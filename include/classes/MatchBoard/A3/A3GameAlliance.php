<?php
// this indirection/abstraction layer is only for the case I will later need it
class A3GameAlliancePDOFactory extends GameAlliancePDOFactory
{
	protected function createObject( array $data )
	{
		return new A3GameAlliance( $this->m_match, $data );
	}
}

class A3GameAlliance extends GameAlliance
{
	
}