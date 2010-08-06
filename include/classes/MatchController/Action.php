<?php
class StateAction
{
	protected $m_command;
	public function __construct( Command $command )
	{
		$this->m_command = $command;
	}
}