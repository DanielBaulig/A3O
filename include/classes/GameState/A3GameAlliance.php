<?php

class A3GameAlliance
{
	protected $m_data;
	
	const NAME = 'name';
	const NATIONS = 'nations';
	
	public function __construct( $data )
	{
		$this->m_data = $data;
	}
	
	public function isMember( $nation )
	{
		return array_key_exists( $nation, $this->m_data[self::NATIONS] );
	}
	
	public function forEachMember( $callback )
	{
		foreach( $this->m_data[self::NATIONS] as $nation => $ignore )
		{
			call_user_func( $callback, $nation );
		}
	}
}