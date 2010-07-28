<?php

require_once 'Logger.php';

class ObjectRelationalMapping
{
	private $m_ormMapping;

	public function setMapping( $fieldName, $dbName, $dbType )
	{
		
	}
	
	public function buildColumnList( )
	{
		$result = '';
		foreach ( $this->m_ormMapping as $member => $column )
		{
			if ( $column != NULL )
			{
				$result .= $column['name'] . ', ';
			}
		}
		$result = trim( $result, ', ' );
		return $result;
	}
	
	public function bindColumns( PDOStatement $statement, $object )
	{
		try
		{
			foreach ( $this->m_ormMappingh as $member => $column )
			{
				if ( $column == NULL )
				{
					throw new Exception( 'ORM not properly initialized.' );
				}
				@ $statement->bindColumn( $column['name'], $object->$member, $column['type'] );
			}
		}
		catch( PDOException $e )
		{
			Logger::getLogger( )->logException( $e );
		}
	}
	
	public function bindValues( $object )
	{
		try
		{
			foreach ( $this->m_ormMapping as $member => $column )
			{
				if ( $column == NULL )
				{
					throw new Exception( 'ORM not properly initialized.' );
				}
				@ $statement->bindValue( ':' . $column['name'], $object->$member, $column['type'] );
			}
		}
		catch( PDOException $e )
		{
			Logger::getLogger( )->logException( $e );
		}
	}
}