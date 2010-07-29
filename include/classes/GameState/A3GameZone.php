<?php

require_once dirname(__FILE__).'/../Registry.php';

class A3GameZoneRegistry
{
	private static $instance = null;
	
	/**
	 * 
	 * @param IFactory $factory
	 * @throws Exception
	 */
	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance === null )
		{
			self::$instance = new A3GameZone( $factory );
		} 
		else
		{
			throw new Exception('Registry already initialized.');
		}
	}
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception('Registry must be initialized.');
		}
		return self::$instance;
	}
	public static function getElement( $key )
	{
		return self::getInstance( )->getElement( $key );
	}
}

class A3GameZone
{
	protected $m_name;	
	protected $m_connections = array( );
	/** Associative Array of Pieces (Units)
	 * Nation => Piecetype => Remaining Movement => Count
	 * 
	 * eg $this->m_pieces['russia']['infantry'][1] = 10;
	 * -> 10 pieces of infantry that have 1 remaining movement
	 * if 3 move 1 field from this to other
	 * -> $this->m_pieces['russia']['infantry'][1] -= 3;
	 * -> $other->m_pieces['russia']['infantry'][0] += 3;
	 * 
	 * @var array $pieces
	 * @access protected
	 */
	protected $m_pieces = array( );
	protected $m_owner = null;
	protected $m_water = false;
	protected $m_production = 0;

	/** Returns the number of pieces of the given type
	 * 
	 * @param string $type
	 * @return int 
	 */
	public function countPieces( $nation, $type, $minimumRemainingMovement = 0 )
	{
		$piecesCount = 0;
		if ( array_key_exists( $this->m_pieces, $nation ) && array_key_exists( $this->m_pieces[$nation], $type ) )
		{
			for( $i=$minimumRemainingMovement; $i <= A3GameTypeRegistry::getElement( $type )->maximumMovement; $i++ )
			{
				$piecesCount += $this->m_pieces[$nation][$type][$i];
			}
		}
		return $piecesCount;
	}
	
	/** Moves up to $count pieces of nation $nation and type $type to zone $target draining $distance movement.
	 * 
	 * Returns true if transfer was successfull and false if there was a problem (eg. 
	 * less pieces available than specified or target zone not valid).
	 * Even if returning false the method will transfer as many pieces as possible.
	 * Use {@link canMovePieces} and {@link isPathValid} to check if a given move is valid 
	 * entirely without moving any pieces.
	 * 
	 * @param int $count
	 * @param string $nation
	 * @param string $type
	 * @param int $distance
	 * @param string $target
	 * @return boolean
	 */
	public function movePieces( $count, $nation, $type, $distance, $target )
	{
		if ( array_key_exists( $this->m_pieces, $nation ) && array_key_exists( $this->m_pieces[$nation], $type ) )
		{
			$total = 0;
			$target = A3GameZoneRegistry::getElement( $target );
			if ( $target !== null )
			{
				for( $i = $distance; $i <= A3GameTypeRegistry::getElement( $type )->maximumMovement; $i++ )
				{
					$moved = $count > $this->m_pieces[$nation][$type][$i] ? $this->m_pieces[$nation][$type][$i] : $count;
					$count = $count - $moved;
					$target->m_pieces[$nation][$type][$i - $distance] += $moved;
					$this->m_pieces[$nation][$type][$i] -= $moved;
					$total += $moved;
				}
				if ( $total === $count )
				{
					return true;
				}
			}
		}
		return false;
	}
	
	/** Returns if the amount of pieces of $nation nation and $type type can be moved 
	 * (transfered) from this zone to the given zone.
	 * 
	 * @param int $count
	 * @param string $nation
	 * @param string $type
	 * @param int $distance
	 * @return boolean
	 */
	public function canMovePieces( $count, $nation, $type, $distance )
	{
		return $this->countPieces( $nation, $type, $distance ) >= $count;
	}
	
	/** Checks if this zone has a connection to the given zone.
	 * 
	 * @param string $zone
	 * @return boolean
	 */
	public function hasConnection( $zone )
	{
		return array_key_exists( $zone, $this->m_connections );
	}
	
	/** Checks if a path starting from here is valid.
	 * 
	 * The optional parameter $water specifies if
	 * isValidPath should check if the path is entirely
	 * on water (true) or on land (false). If it is not
	 * specified or set to null, isValidPath will allow
	 * water aswell as land zones on the path.
	 * 
	 * The optional parameter $alliance specifies if the
	 * zones are checked against ownership and only a path
	 * controlled by the specified alliance may be traversed.
	 * 
	 * The optional parameter $combat speciyfies if the
	 * movement may end in an enemy zone, thus triggering
	 * combat. Defaults to false.
	 * 
	 * @param array $path
	 * @param boolean $water (optional)
	 * @param string $alliance (optional)
	 * @param boolean $combat (optional)
	 * @return boolean
	 */
	public function isValidPath( array $path, $water = null, $alliance = null, $combat = false )
	{
		$zone = $this;
		$alliance = A3GameAllianceRegistry::getElement( $alliance );
		
		// get zone where movement ends
		$endZone = end( $path );
		
		foreach( $path as $step )
		{
			// dont allow to enter a zone that is not connected
			if ( !$zone->hasConnection( $step ) )
			{
				return false;
			}
			
			// create/get A3GameZone object for the upcoming zone
			$zone = A3GameZoneRegistry::getElement( $step );
			
			// if water is null (no terrain checking) simply skip
			// if water is true, only allow to enter water
			// if water is false, only allow to enter land
			if ( $water !== null && $water !== $zone->m_water )
			{
				return false;
			}
			// if alliance checking is enabled only allow to enter zones controlled by given alliance
			if( $alliance !== null && ! $alliance->isAllied( $zone->m_owner ) )
			{
				// however only, if this is not the last zone and triggering combat is not allowed
				if ( !($step === $endZone && $combat) )
				{
					return false;
				}				
			}
		}
		return true;
	}
	
	public function __construct( $name )
	{
		$this->m_name = $name;
	}
}