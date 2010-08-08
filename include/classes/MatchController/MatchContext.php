<?php
class MatchContext
{
	private $m_currentController;

	public function startup( IStateController $initialController )
	{
		$this->m_currentController = $initialController->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		$this->m_currentController = $this->m_currentController->doAction( $action );
	}
	
	public function __construct( IStateController $startController = NULL )
	{
		$this->m_currentController = $startController;
	}
}