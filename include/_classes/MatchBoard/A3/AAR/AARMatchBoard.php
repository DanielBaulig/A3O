<?php
require_once dirname(__FILE__) . '/../A3MatchBoard.php';
require_once dirname(__FILE__) . '/AARMatchPlayer.php';
require_once dirname(__FILE__) . '/AARMatchZone.php';
require_once dirname(__FILE__) . '/AARGameType.php';

class AARPDOMatchBoard extends A3PDOMatchBoard
{
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
	
	/** Technology type modifiers are applied to the basetypes if the
	 * controlling player has reseached the apporpriate techs using
	 * applyModification.
	 * 
	 * Note that applyModification sums integer values and sets all other values,
	 * so you can either increase/decrease a value using integers or set them
	 * to a fixed value using strings (or other datatypes that might be supported). 
	 * 
	 * @var array
	 */
	private static $m_techTypeModifiers = array(
		'jetfighter' => array(
			GameType::NAME => 'jet_',
			GameType::OPTIONS => array( 'defense' => +1, 'dodgeaa' => '1' ),
		),	
		'heavybomber' => array(
			GameType::NAME => 'heavy_',
			GameType::OPTIONS => array( 'heavybomber' => '1' ),
		),
		'longrangeaircraft' => array(
			GameType::NAME => 'longrange_',
			GameType::OPTIONS => array( 'movement' => +2 ),
		),
		'supersubs' => array(
			GameType::NAME => 'super_',
			GameType::OPTIONS => array( 'attack' => +1, 'defense' => +1 ),
		),				
		'combinedbombardment' => array(
			GameType::NAME => 'combined_',
			GameType::OPTIONS => array( 'bombardcoast' => +1 ),
		),
		'rockets' => array(
			GameType::NAME => 'rocket_',
			GameType::OPTIONS => array( 'rocket' => '1' ),
		),
	);
	
	/** This is a helper function that merges $data with $modification
	 * allowing easy on the fly modifications on types and generally
	 * each match/game element.
	 * 
	 * @param array $data
	 * @param array $modification
	 */
	public static function applyModification ( array &$data, array $modification )
	{
		// if the modification contains a name we will concat
		// the modification name infront of the basename
		if (array_key_exists( GameType::NAME, $modification ) )
		{
			$data[GameType::NAME] = $modification[GameType::NAME] . $data[GameType::NAME];	
		}
		
		if ( array_key_exists( GameType::OPTIONS, $modification ) )
		{
			// get each option from the modification
			foreach( $modification[GameType::OPTIONS] as $key => $value )
			{
				if (! array_key_exists( GameType::OPTIONS, $data ) )
				{
					$data[GameType::OPTIONS] = array( );
				}
				// is there an entry in data with the same key?
				if( array_key_exists( $key, $data[GameType::OPTIONS] ) )
				{
					// if so and the value in modification array is an integer
					if( is_int( $modification[GameType::OPTIONS][$key] ) )
					{
						// add it to the value in data
						$data[GameType::OPTIONS][$key] += $modification[GameType::OPTIONS][$key];
					}
					else 
					{
						// else set the value in data to the value in modification
						$data[GameType::OPTIONS][$key] = $modification[GameType::OPTIONS][$key];
					}
				}
				else 
				{
					// if there was no key in data create it
					$data[GameType::OPTIONS][$key] = $modification[GameType::OPTIONS][$key];
				}
			}
		}
		return $data;
	}
	/** Adds a teched (modified by researched techology) type to the registry
	 * if it isn't present yet and returns it's name to identify it (regardless
	 * if it was present or not).
	 * 
	 * @param string $basetype
	 * @param string $nation
	 */
	public function addTechedType( $basetype, $nation )
	{
		$modification = array( );
		$player = $this->getPlayer( $nation );

		// if this is either a bomber or a fighter
		if ( $basetype == 'bomber' || $basetype == 'fighter' )
		{
			// if its a fighter and we have JetFigters researched
			if ( ($basetype == 'fighter') && $player->hasJetFightersResearched( ) )
			{
				// apply jet fighter modifications
				$modification = self::applyModification( $modification, self::$m_techTypeModifiers['jetfighter'] );
			}
			// if its a bomber and we have heavy bombers researched
			if ( $basetype == 'bomber' && $player->hasHeavyBombersResearched( ) )
			{
				// apply heavy bomber modifications
				$modification = self::applyModification( $modification, self::$m_techTypeModifiers['heavybombers'] );
			}	
			
			// if we have long range aircraft researched
			if ( $player->hasLongRangeAircraftResearched( ) )
			{
				// apply long range aircraft modifications
				$modification = self::applyModification( $modification, self::$m_techTypeModifiers['longrangeaircraft'] );
			}
		}
		// if it is a submarine and we have super subs researched
		if( $basetype == 'sub' && $player->hasSuperSubsResearched( ) )
		{
			// apply super sub modifications
			$modification = self::applyModification( $modification, self::$m_techTypeModifiers['supersubs'] );
		}
		// if it's a destroyer and we have combined bombardment reseachred
		if( $basetype == 'destroyer' && $player->hasCombinedBombardmentResearched( ) )
		{
			// apply combined bombardment modifications
			$modification = self::applyModification( $modification, self::$m_techTypeModifiers['combinedbombardment'] );
		}
		// if its an aagun and we have rockets researched
		if( $basetype == 'antiair' && $player->hasRocketsResearched( ) )
		{
			// apply rocket modifications
			$modification = self::applyModification( $modification, self::$m_techTypeModifiers['rockets'] );
		}
		// add it to the registry and return it's name. addModifiedType takes care to not 
		// add a duplicate of the teched type.	
		return $this->m_typeRegistry->addModifiedType( $basetype,  $modification[GameType::NAME] . $basetype, $modification ); 
	}
}