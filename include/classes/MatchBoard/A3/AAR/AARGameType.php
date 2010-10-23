<?php
class AARGameTypePDOFactory extends A3GameTypePDOFactory
{
	private $m_modification = array( );
	
	/** Creates a type object. Considers modification and can produce a special custom
	 * type based upon data, but modified according to whatever is found in 
	 * modification.
	 * 
	 * (non-PHPdoc)
	 * @see include/classes/MatchBoard/A3/A3GameTypePDOFactory::createObject()
	 */
	protected function createObject( array $data )
	{
		// apply modifications
		$data = AARGameType::applyModification( $data, $this->m_modification );
		// reset modifications, so next type will not be affected by them
		$this->m_modification = array( );
		return new AARGameType( $this->m_match, $data );
	}
	
	/** Adds modifications to the next created object.
	 * 
	 * Note that addModification is cumulative. Successive calls
	 * will continuesly add more modifications until an object was
	 * created consuming the modifications.
	 * 
	 * @param array $modification
	 */
	public function addModification( array $modification = array( ) )
	{
		$this->m_modification = AARGameType::applyModification( $this->m_modification, $modification );
	}
}

class AARGameTypeRegistry extends BaseRegistry
{
	/** Adds a modified type to the registry. Takes a $basetype and applies $modifications to it.
	 * $name is used for indexing.
	 * 
	 * @param string $basetype
	 * @param string $name
	 * @param array $modification
	 */
	public function getModifiedType( $basetype, $name, array $modification )
	{
		if (!array_key_exists( $name , $this->m_elements ) )
		{
			$this->m_factory->addModification( $modification );
			$type = $this->m_factory->createSingleProduct( $basetype );
			$this->m_elements[$name] = $type; 
		}
		return $name;
	}
}

class AARGameType extends A3GameType
{
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
		if ( array_key_exists( GameType::NAME, $modification ) )
		{
			$data[GameType::NAME] = $modification[GameType::NAME] . $data[GameType::NAME];	
		}
		
		// are there any options in the modification?
		if ( array_key_exists( GameType::OPTIONS, $modification ) )
		{
			// get each option from the modification
			foreach( $modification[GameType::OPTIONS] as $key => $value )
			{
				// if there are no options in $data...
				if (! array_key_exists( GameType::OPTIONS, $data ) )
				{
					// ... create them
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
	
	public static function applyJetFighter( &$data )
	{
		self::applyModification( $data, self::$m_techTypeModifiers['jetfighter'] );
	}
	
	public static function applyHeavyBomber( &$data )
	{
		self::applyModification( $data, self::$m_techTypeModifiers['heavybomber'] );
	}
	
	public static function applyLongRangeAircraft( &$data )
	{
		self::applyModification( $data, self::$m_techTypeModifiers['longrangeaircraft'] );
	}
	
	public static function applySuperSubs( &$data )
	{
		self::applyModification( $data, self::$m_techTypeModifiers['supersubs'] );
	}
	
	public static function applyCombinedBombardment( &$data )
	{
		self::applyModification( $data, self::$m_techTypeModifiers['combinedbombardment'] );
	}
	
	public static function applyRockets( &$data )
	{
		self::applyModification( $data, self::$m_techTypeModifiers['rockets'] );
	}
	
	public function isTechableJetFighter( )
	{
		return $this->m_data[self::NAME] == 'fighter';
	}
	
	public function isTechableHeavyBomber( )
	{
		return $this->m_data[self::NAME] == 'bomber';
	}
	
	public function isTechableLongRangeAircraft( )
	{
		return $this->isAir( );
	}
	
	public function isTechableSuperSubs( )
	{
		return $this->isSubmerged( );
	}
	
	public function isTechableCombinedBombardment( )
	{
		return $this->m_data[self::NAME] == 'destroyer';
	}
	
	public function isTechableRockets( )
	{
		return $this->isAntiAir( );
	}
}