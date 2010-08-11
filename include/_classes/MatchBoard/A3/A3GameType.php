<?php

class A3GameTypePDOFactory extends GameTypePDOFactory
{
	protected function createObject( array $data )
	{
		return new A3GameType( $this->m_match, $data );
	}
}

class A3GameType extends GameType
{
	public function isBlitz( )
	{
		return (boolean) $this->getOption( 'blitz' );
	}
	
	public function isBombardCoast( )
	{
		return (boolean) $this->getOption( 'bombardcoast' );
	}
	
	public function isNaval( )
	{
		return (boolean) $this->getOption( 'naval' );
	}
	
	public function isAir( )
	{
		return (boolean) $this->getOption( 'air' );
	}
	
	public function isAntiAir( )
	{
		return (boolean) $this->getOption( 'antiair' );
	}
	
	public function isSubmerged( )
	{
		return (boolean) $this->getOption( 'submerged' );
	}
	
	public function isDefensless( )
	{
		return !(boolean) $this->getOption( 'defense' );
	}
	
	public function isDetector( )
	{
		return (boolean) $this->getOption( 'detector' );
	}
	
	public function canTraversePath( array $path, $nation, $combatMovement )
	{
		// too far
		if( count($path) - 1 > $this->getOption( 'movement' ) )
		{
			return false;
		}
		
		// get key of last element
		end( $path );
		$last = key( $path );
		
		$currentZone = $this->m_state->getZone( reset( $path ) );
		
		while( next( $path ) && $currentZone->hasConnection( current( $path ) ) )
		{
			$currentZone = $this->m_state->getZone( current( $path ) );
			$isLastZone = key( $path ) === $last;

			// impassible
			if( $currentZone->isImpassible( ) )
			{
				return false;
			}
			
			// water
			if ( $currentZone->isSeaZone( ) )
			{
				// is this a water unit?
				if ( $this->isNaval( ) )
				{
					// is this water zone hostile?
					if ( $currentZone->isHostileTo( $nation, $this->m_data[A3GameType::NAME] ) )
					{
						// aint this the final move in a combat move?
						if ( !( isLastZone && $combatMovement ) )
						{
							return false;
						}
					}
				}
				// is this an air unit?
				else if ( $this->isAir( ) ) 
				{
					if ( $isLastZone )
					{
						if ( $currentZone->isHostileTo( $nation, $this->m_data[A3GameType::NAME] ) )
						{
							if ( !$combatMovement )
							{
								return false;
							}
						}
						else 
						{
							// check if a landing opportunity is within reach ( allied land or ac )
						}
					}
				}
				else
				{
					return false;	
				}
			}
			else 
			// land zone
			{
				// check for ownership
				if ( !$currentZone->isFriendlyTo( $nation ) )
				{					
					// is it not the last zone on the path?
					if ( !$isLastZone )
					{						
						// air units may always traverse enemy territory (even during non combat)
						if ( !$this->isAir( ) )
						{
							// blitz units may cross enemy territory during combat move and if there are no enemies present
							if ( !($this->isBlitz( ) && !$currentZone->isEnemyPresent( $nation ) && $combatMovement) )
							{
								return false;
							}
						}						
					}
					// it is the last zone
					else
					{
						// no land units may end their movement in enemy territory during non-combat moves
						if( !$combatMovement )
						{
							return false;
						}
						if ( $this->isAir( ) )
						{
							// check if a landing oppurtunity is witin reach (allied zone or ac)
						}
					}
				}
			}
		} 
		return true;
	}
}