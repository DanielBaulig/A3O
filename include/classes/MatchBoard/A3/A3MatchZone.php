<?php

class A3MatchZonePDOFactory extends MatchZonePDOFactory
{
	protected $m_loadIncomingConnectionsSingleGameZone;
	public function __construct($pdo, $match)
	{
		parent::__construct($pdo, $match);
		
		$connections_sql =
			'SELECT bz2.basezone_name AS zone FROM a3o_zones AS z INNER JOIN a3o_basezones AS bz'
			. ' ON bz.basezone_id = z.zone_basezone INNER JOIN a3o_connections AS c ON'
			. ' c.connection_secondzone = bz.basezone_id INNER JOIN a3o_basezones AS bz2'
			. ' ON bz2.basezone_id = c.connection_firstzone WHERE z.zone_id = :zone_id;';
		
		$this->m_loadIncomingConnectionsSingleGameZone = $this->m_pdo->prepare( $connections_sql );
	}
	protected function createObject( array $data )
	{
		return new A3MatchZone( $this->m_match, $data );
	}
	
	protected function loadIncomingConnections($zone_id)
	{
		$this->m_loadIncomingConnectionsSingleGameZone->bindValue( ':zone_id', $zone_id, PDO::PARAM_INT );
		$this->m_loadIncomingConnectionsSingleGameZone->execute( );
		$connections = array ( );
		while ( $connection = $this->m_loadIncomingConnectionsSingleGameZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$connections[$connection['zone']] = true;
		}
		return $connections;
	}
	
	protected function loadOutgoingConnections($zone_id)
	{
		return parent::loadConnections($zone_id);
	}
	
	protected function loadConnections($zone_id) 
	{
		return array_merge( $this->loadIncomingConnections($zone_id), $this->loadOutgoingConnections($zone_id) );
	}
}

class A3MatchZone extends MatchZone
{
	public function isImpassible( ) 
	{
		return (boolean) $this->getOption( 'impassible' );
	}
	
	public function isSeaZone( )
	{
		return (boolean) $this->getOption( 'water' );
	}
	
	/** Similar to {@link isEnemyPresentOf} it checks if enemies are present,
	 * but takes into account specific options of those possible enemies that would
	 * let pieces of $nation pass through the territory nevertheless
	 *
	 * If $submerged is true only detectors count as hostile
	 * 
	 * @param string $nation
	 * @param boolean $submerged
	 * @return boolean
	 */
	public function isHostileTo( $nation, $type )
	{
		$nation = $this->m_state->getNation( $nation );
		$type = $this->m_state->getType( $type );
		foreach( $this->m_data[self::PIECES] as $stackOwner => $stack )
		{
			if ( !$nation->isAllyOf( $stackOwner ) )
			{
				foreach( $stack as $stack_type => $count )
				{
					if( $count > 0 )
					{
						$stack_type = $this->m_state->getType( $stack_type );
						if ( !$type->isSubmerged( ) || $stack_type->isDetector( ) )
						{
							if ( ! ( $stack_type->isDefenseless( ) || $stack_type->isSubmerged( ) ) )
							{							
								return true;	
							}							
						}
					}
				}
			}
		}
		return false;
	}
}