<?php
interface IStateController
{
	/**
	 * 
	 * @param $action
	 * @return IStateController
	 */
	public function doAction( $action );
	/**
	 * @return IStateController
	 */
	public function doEnter( );
	
	public function __construct( $game );
}