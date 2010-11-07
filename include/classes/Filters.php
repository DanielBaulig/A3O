<?php
abstract class Filter
{
	const INTERNAL_ENCODING = 'UTF-8';
	const EREGEX_CLEAR_KEYSTRING = '[^a-zA-Z0-9_]';
	
	public function __construct()
	{
		mb_internal_encoding(self::INTERNAL_ENCODING);
	}
	
	protected function sanitizeInteger($value)
	{
		// we use utf8_decode on all our number inputs to make sure
		// no non-latin1 characters are accidentally interpreted
		// as ASCII numbers by filter_var.
		// I really hope PHP6 will fix the encoding hell PHP
		// currently is -.-
		// ^- this is not posible due to UTF-8 design! wooohooo!
		// 	  so i removed those nifty utf8_decodes again :)
		return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
	}
	
	protected function validateInteger($value, $default = false)
	{
		return filter_var($value, FILTER_VALIDATE_INT, array( 'options' => array( 'default' => $default ) ) );
	}
	
	protected function sanitizeFloat($value)
	{
		return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, array( 'flags' => FILTER_FLAG_ALLOW_FRACTION ) );
	}
	
	protected function validateFloat($value, $default = false)
	{
		return filter_var($value, FILTER_VALIDATE_FLOAT, array( 'options' => array( 'default' => $default ) ) );
	}
	
	protected function filterAsHTMLSafe($value)
	{
		return htmlspecialchars($value, ENT_COMPAT, self::INTERNAL_ENCODING);
	}
	
	protected function filterKeyString($value)
	{
		return mb_ereg_replace(self::EREGEX_CLEAR_KEYSTRING, '', $value);
	}
}

class RequestFilter extends Filter
{
	public function __construct( )
	{
		parent::__construct();
		
		$this->data['REQUEST'] = $GLOBALS['_REQUEST'];
		$this->data['GET'] = $GLOBALS['_GET'];
		$this->data['POST'] = $GLOBALS['_POST'];
		$this->data['SERVER'] = $GLOBALS['_SERVER'];
		$this->data['FILES'] = $GLOBALS['_FILES'];
		$this->data['SESSION'] = $GLOBALS['_SESSION'];
		$this->data['COOKIE'] = $GLOBALS['_COOKIE'];
		
		unset($GLOBALS['_COOKIE']);
		unset($GLOBALS['_SESSION']);
		unset($GLOBALS['_FILES']);
		unset($GLOBALS['_SERVER']);
		unset($GLOBALS['_POST']);
		unset($GLOBALS['_REQUEST']);
		unset($GLOBALS['_GET']);
	}
	
	public function sanitizeInteger($name, $source = 'REQUEST')
	{
		return parent::sanitizeInteger($this->data[$source][$name]);
	}
	
	public function validateInteger($name, $default = 0, $source = 'REQUEST')
	{
		return parent::validateInteger($this->data[$source][$name], $default);
	}
	
	public function sanitizeFloat($name, $source = 'REQUEST')
	{
		return parent::sanitizeFloat($this->data[$source][$name]);
	}
	
	public function validateFloat($name, $default = false, $source = 'REQUEST')
	{
		return parent::validateFloat($this->data[$source][$name], $default );
	}
	
	public function filterUnsafeRawWhatYouReallyDontWant_Seriously( $source, $name, $makeThisTheStringYES_I_WANT)
	{
		if ($makeThisTheStringYES_I_WANT != 'YES_I_WANT')
		{
			throw new Exception('I knew it!');
		}
		return filter_var($this->data[$source][$name], FILTER_UNSAFE_RAW);
	}
}