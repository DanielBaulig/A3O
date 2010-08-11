<?php
class Action
{
	protected $m_data;
	
	const SENDER = 'sender';
	const COMMAND = 'command';
	const PARAMS = 'params';
	
	public function getSender( )
	{
		return $this->m_data[self::SENDER];
	}
	
	public function isCommand( $command )
	{
		return $this->m_command === $command;
	}
	
	public function getParams( )
	{
		return $this->m_params;
	}
	
	public function __construct( $sender, $command, $params )
	{
		$this->m_sender = $sender;
		$this->m_command = $command;
		$this->m_params = $params;
	}
}