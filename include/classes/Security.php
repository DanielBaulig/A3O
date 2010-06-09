<?php

class SecurityToken
{
	private $m_clearanceLevel;
	
	public function __construct($clearanceLevel)
	{
		$this->m_clearanceLevel = $clearanceLevel;
	}
	
	public function getClearanceLevel()
	{
		return $this->m_clearanceLevel;
	}
}

?>