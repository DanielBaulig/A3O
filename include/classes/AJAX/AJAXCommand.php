<?php
class AJAXCommand extends Command
{
	protected function getUnsafeParameter( $paramName )
	{
		return $_REQUEST[$paramName];
	}
	protected abstract function getUnsafeCommandString( )
	{
		return $_REQUEST['command'];
	}
}