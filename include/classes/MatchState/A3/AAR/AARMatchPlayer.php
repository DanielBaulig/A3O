<?php
class AARMatchPlayerPDOFactory extends A3MatchPlayerPDOFactory
{
	protected function createObject( $data )
	{
		return new AARMatchPlayer( $this->m_match, $data );
	}
}

class AARMatchPlayer extends A3MatchPlayer
{
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
		return $this->getOption( 'technology_jetfighters' );
	}
	
	public function hasSuperSubsResearched( )
	{
		return $this->getOption( 'technology_supersubs' );
	}
	
	public function hasRocketsResearched( )
	{
		return $this->getOption( 'technology_rockets' );
	}
	
	public function hasLongRangeAircraftResearched( )
	{
		return $this->getOption( 'technlogy_longrangeaircraft');
	}
	
	public function hasCombinedBombardmentResearched( )
	{
		return $this->getOption( 'technology_combinedbombardment' );
	}	
}