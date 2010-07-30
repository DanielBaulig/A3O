<?php
class A3GameNation
{
	protected $m_data;
	
	const NAME = 'name';
	const ALLIANCES = 'alliances';
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
	}
	
	public function isAllyOf( $nation )
	{
		$nation =  A3GameNatoinRegistry::getElement( $nation );
		foreach( $this->m_data[A3GameNation::ALLIANCES] as $alliance => $ignore )
		{
			if ( $nation->isInAlliance( $alliance ) )
			{
				return true;
			}
		}
		return false;
	}
	
	public function isInAlliance( $alliance )
	{
		return array_key_exists( $alliance, $this->m_data[A3GameNation::ALLIANCES] );
	}
}
