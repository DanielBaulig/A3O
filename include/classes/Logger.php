<?php

class Logger
{
	private static $m_instance = NULL;
	public static function getInstance( )
	{
		if ( Logger::$m_instance == NULL )
		{
			Logger::$m_instance = new Logger( );
		}
		return Logger::$m_instance;
	}
	
	
	private $m_logVerbosity;
	private $m_echoVerbosity;
	
	public function __construct( )
	{
		
	}
}

?>