<?php

define('AJAX_FUNCTION_KEY', 'call');
define('AJAX_SECURITY_SETTIGNS', 0);
define('AJAX_FUNCTION_REFERENCE', 1);
define('AJAX_SECURITY_SETTINGS_INPUTTYPE', 0);
define('AJAX_SECURITY_SETTINGS_FILTER', 1);
define('AJAX_SECURITY_SETTINGS_FILTEROPTIONS', 2);

interface JSON 
{
	public function toJSON( );	
}

class AjaxSecurity
{
	/** 
	 * 
	 * $m_validParams = array ( $paramName => array (  ) )
	 * 
	 * @var unknown_type
	 */
	private $m_validParams = NULL;
	private $m_requiredClearanceLevel = NULL;
	private $m_locked = false;
	
	public function __construct($clearanceLevel, array $validParams = NULL)
	{
		$this->m_validParams = $validParams;
		$this->m_requiredClearanceLevel = $clearanceLevel;
	}
	
	public function filterInput($param)
	{
		if (array_key_exists($param, $this->m_validParams))
		{
			return filter_input($this->m_validParams[$param][AJAX_SECURITY_SETTINGS_INPUTTYPE], $param, $this->m_validParams[$param][AJAX_SECURITY_SETTINGS_FILTER], $this->m_validParams[$param][AJAX_SECURITY_SETTINGS_FILTEROPTIONS]);	
		}
		return NULL;
	}
	
	public function clearanceGranted(SecurityToken $token)
	{
		if($token->getClearanceLevel() >= $this->requiredClearanceLevel)
		{
			return true;
		}
		return false;
	}
	
	public function addValidParam($name, $inputType, $filter, $filterOptions)
	{
		if (!$this->m_locked)
		{
			$this->m_validParams[$name] = array( AJAX_SECURITY_SETTINGS_INPUTTYPE => $inputType, AJAX_SECURITY_SETTINGS_FILTER => $filter, AJAX_SECURITY_SETTINGS_FILTEROPTIONS => $filterOptions );
		}
		else
		{
			die('Cannot add Params: AjaxSecurity locked.');
		}
	}
	
	public function lock()
	{
		$this->m_locked = true;
	}
}

class AjaxResponder
{
	private $m_functions = array();
	public function __construct(){}
	
	public function registerFunction($name, AjaxSecurity $security, $func)
	{
		if (! array_key_exists($name, $this->m_functions))
		{
			$security->lock();
			$this->m_functions[$name][AJAX_SECURITY_SETTIGNS] = $security;
			$this->m_functions[$name][AJAX_FUNCTION_REFERENCE] = $func;
		}
		else
		{
			die('Function already registered in AjaxResponder');
		}
	}
	
	public function respond(SecurityToken $securityToken)
	{
		$filtered_function_key = NULL;
		if (filter_has_var(INPUT_GET, AJAX_FUNCTION_KEY))
		{
			$filtered_function_key = filter_input(INPUT_GET, AJAX_FUNCTION_KEY, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		}
		else if (filter_has_var(INPUT_POST, AJAX_FUNCTION_KEY))
		{
			$filtered_function_key = filter_input(INPUT_POST, AJAX_FUNCTION_KEY, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		}
		if ($filtered_function_key !== NULL && array_key_exists($filtered_function_key, $this->m_functions))
		{
			if ($this->m_functions[$filtered_function_key][AJAX_SECURITY_SETTIGNS]->clearanceGranted($securityToken))
			{
				$this->m_functions[$filtered_function_key][AJAX_FUNCTION_REFERENCE]($this->m_functions[$filtered_function_key][AJAX_SECURITY_SETTIGNS], $securityToken);
			}
		}
		
	}
}

?>