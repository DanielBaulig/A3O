<?php

abstract class A3SetupController implements IStateController
{
	private $m_game;
	
	protected abstract function initializeBoard( );
	protected abstract function getBidController( );
	
	public function __construct( $game )
	{
		$this->m_game = $game;	
	}
	public function doEnter( )
	{
		$this->initializeBoard( );
		
		return getBidController( )->doEnter( );
	}
	public function doAction( $action )
	{
		// this should not  happen!
		return $this;
	}
}