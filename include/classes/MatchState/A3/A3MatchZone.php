<?php

class A3MatchZonePDOFactory extends MatchZonePDOFactory
{
	protected function createObject( array $data )
	{
		return new A3MatchZone( $data );
	}
}

class A3MatchZone extends MatchZone
{
	/** Checks if this zone has a connection to the given zone.
	 * 
	 * Note that the zone checks in both directions and thus presumes
	 * connections are always bidirectional.
	 * 
	 * @param string $zone
	 * @return boolean
	 */
	public function hasConnection( $zone )
	{
		return parent::hasConnection( $zone ) || 
			array_key_exists( $this->m_data[self::NAME], MatchZoneRegistry::getZone( $zone )->m_data[self::CONNECTIONS] );
	}
	
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
		$nation = GameNationRegistry::getNation( $nation );
		$type = GameTypeRegistry::getType( $type );
		foreach( $this->m_data[self::PIECES] as $stackOwner => $stack )
		{
			if ( !$nation->isAllyOf( $stackOwner ) )
			{
				foreach( $stack as $stack_type => $count )
				{
					if( $count > 0 )
					{
						$stack_type = GameTypeRegistry::getType( $stack_type );
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