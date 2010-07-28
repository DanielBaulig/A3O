<?php
abstract class Command
{
	protected abstract function getUnsafeParameter( $paramName );
	protected abstract function getUnsafeCommandString( );
	public function getCommandString( )
	{
		return preg_filter( '/[a-zA-Z_]+/', '$0', getUnsafeCommandString( ) );
	}
	public function getFilteredParameter( $paramName, $filter, $filterFlags )
	{
		return filter_var( $this->getUnsafeParameter( $paramName ), $filter, $filterFlags );
	}
	public function getCleanedStringIdentifier( $paramName )
	{
		return preg_filter( '/[a-zA-Z_]+/', '$0', getUnsafeParameter( $paramName ) );
	}	
}