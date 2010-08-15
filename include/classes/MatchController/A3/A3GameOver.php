<?php
class A3GameOver extends BaseState
{
	protected $match;
	
	public function doEnter( )
	{
		return $this;
	}
	public function doAction( Action $action )
	{
		return $this;
	}
	public function __construct( $name, MatchBoard $match )
	{
		parent::__construct( $name );
		$this->match = $match;
	}
}