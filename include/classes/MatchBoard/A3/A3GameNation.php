<?php
// this indirection/abstraction layer is only for the case I will later need it
class A3GameNationPDOFactory extends GameNationPDOFactory
{
	protected function createObject( array $data )
	{
		return new A3GameNation( $this->m_match, $data );
	}
}

class A3GameNation extends GameNation
{
	
}