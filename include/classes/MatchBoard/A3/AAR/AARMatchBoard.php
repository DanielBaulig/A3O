<?php
require_once dirname(__FILE__) . '/../A3MatchBoard.php';
require_once dirname(__FILE__) . '/AARMatchPlayer.php';
require_once dirname(__FILE__) . '/AARMatchZone.php';
require_once dirname(__FILE__) . '/AARGameType.php';

class AARPDOMatchBoard extends A3PDOMatchBoard
{
	const TECH_JETFIGHTER = 'jetfighter';
	const TECH_HEAVYBOMBER = 'heavybomber';
	const TECH_LONGRANGEAIRCRAFT = 'longrangeaircraft';
	const TECH_SUPERSUBS = 'supersubs';
	const TECH_COMBINEDBOMBARDMENT = 'combinedbombardment';
	const TECH_ROCKETS = 'rockets';
	
	public function __construct(PDO $pdo, $game_id, $match_id)
	{
		$this->m_matchId = $match_id;
		$this->m_gameId = $game_id;
		
		$this->m_zoneRegistry = new BaseRegistry( new AARMatchZonePDOFactory($pdo, $this) );
		$this->m_playerRegistry = new BaseRegistry( new AARMatchPlayerPDOFactory($pdo, $this) );
		$this->m_nationRegistry = new BaseRegistry( new A3GameNationPDOFactory($pdo, $this) );
		$this->m_allianceRegistry = new BaseRegistry( new A3GameAlliancePDOFactory($pdo, $this) );
		
		// exchange type registry for AAR type registry which supports type modification 
		$this->m_typeRegistry = new AARGameTypeRegistry( new AARGameTypePDOFactory($pdo, $this) );
	}
	
	/** Adds a teched (modified by researched techology) type to the registry
	 * if it isn't present yet and returns it's name to identify it (regardless
	 * if it was present or not).
	 * 
	 * @param string $basetype
	 * @param string $nation
	 */
	public function getTechedType( $basetype, $nation )
	{
		$modification = array( );
		$type = $this->m_typeRegistry->getElement( $basetype );
		$player = $this->getPlayer( $nation );

		// if its not a bomber and we have JetFigters researched
		if ( $type->isTechableJetFighter( ) && $player->hasJetFightersResearched( ) )
		{
			// apply jet fighter modifications
			AARGameType::applyJetFighter( $modification );
		}
		// if its a bomber and we have heavy bombers researched
		if ( $type->isTechableHeavyBomber( ) && $player->hasHeavyBombersResearched( ) )
		{
			AARGameType::applyHeavyBomber( $modification );
		}	
			
		// if we have long range aircraft researched
		if ( $type->isTechableLongRangeAirCraft( ) && $player->hasLongRangeAircraftResearched( ) )
		{
			// apply long range aircraft modifications
			AARGameType::applyLongRangeAircraft( $modification );
		}
		
		// if it is a submarine and we have super subs researched
		if( $type->isTechableSuperSubs( ) && $player->hasSuperSubsResearched( ) )
		{
			// apply super sub modifications
			AARGameType::applySuperSubs( $modification );
		}
		// if it's a destroyer and we have combined bombardment reseachred
		if( $type->isTechableCombinedBombardment( ) && $player->hasCombinedBombardmentResearched( ) )
		{
			// apply combined bombardment modifications
			AARGameType::applyCombinedBombardment( $modification );
		}
		// if its an aagun and we have rockets researched
		if( $type->isTechableRockets( ) && $player->hasRocketsResearched( ) )
		{
			// apply rocket modifications
			AARGameType::applyRockets( $modification );
		}
		// add it to the registry and return it's name. addModifiedType takes care to not 
		// add a duplicate of the teched type.	
		return $this->m_typeRegistry->getModifiedType( $basetype,  $modification[GameType::NAME] . $basetype, $modification ); 
	}
}