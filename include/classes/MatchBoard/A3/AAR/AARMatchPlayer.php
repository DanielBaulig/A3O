<?php
class AARMatchPlayerPDOFactory extends A3MatchPlayerPDOFactory
{
	protected function createObject( array $data )
	{
		return new AARMatchPlayer( $this->m_match, $data );
	}
}

class AARMatchPlayer extends A3MatchPlayer
{
	public function hasTechnologyResearched( )
	{
		return $this->hasCombinedBombardmentResearched( ) ||
			$this->hasHeavyBombersResearched( ) ||
			$this->hasJetFightersResearched( ) ||
			$this->hasLongRangeAircraftResearched( ) ||
			$this->hasRocketsResearched( ) ||
			$this->hasSuperSubsResearched( );
	}
	
	public function researchJetFighters( )
	{
		$this->setOption( 'technology_jetfighters' , 1 );
	}
	
	public function researchSuperSubs( )
	{
		$this->setOption( 'technology_supersubs', 1 );
	}
	
	public function researchRockets( )
	{
		$this->setOption( 'technology_rockets', 1 );
	}
	public function researchLongRangeAircraft( )
	{
		$this->setOption( 'technology_longrangeaircraft', 1 );
	}
	
	public function researchCombinedBombardment( )
	{
		$this->setOption( 'technology_combinedbombardment', 1 );
	}
	
	public function researchHeavyBombers( )
	{
		$this->setOption( 'technology_heavybombers', 1 );
	}
	
	public function hasJetFightersResearched( )
	{
		return (boolean) $this->getOption( 'technology_jetfighters' );
	}
	
	public function hasHeavyBombersResearched( )
	{
		return (boolean) $this->getOption( 'technology_heavybombers' );
	}
	
	public function hasSuperSubsResearched( )
	{
		return (boolean) $this->getOption( 'technology_supersubs' );
	}
	
	public function hasRocketsResearched( )
	{
		return (boolean) $this->getOption( 'technology_rockets' );
	}
	
	public function hasLongRangeAircraftResearched( )
	{
		return (boolean) $this->getOption( 'technology_longrangeaircraft');
	}
	
	public function hasCombinedBombardmentResearched( )
	{
		return (boolean) $this->getOption( 'technology_combinedbombardment' );
	}	
}