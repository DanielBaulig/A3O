<?php

abstract class A3GameZoneRegistry
{
	private static $instance = null;
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
}

class A3GameZone
{
	protected $m_name;	
	protected $m_connections = array( );
	/** Associative Array of Pieces (Units)
	 * Piecetype => Remaining Movement => Count
	 * 
	 * eg $this->pieces['infantry'][1] = 10;
	 * -> 10 pieces of infantry that have 1 remaining movement
	 * if 3 move 1 field from this to other
	 * -> $this->pieces['infantry'][1] -= 3;
	 * -> $other->pieces['infantry'][0] += 3;
	 * 
	 * I should implement something like piecesCount( $type ), eg.
	 * 
	 * for($i = 0; $i <= getTypeAttributes( $type )->maximumMovement; $i++)
	 * {
	 * 		$sum += $this->pieces[$type][$i];
	 * }
	 * return $sum;
	 * 
	 * @var unknown_type
	 */
	protected $m_pieces = array( );
	protected $m_owner = null;
	protected $m_water = false;
	protected $m_production = 0;

	/** Returns the number of pieces of the given type
	 * 
	 * @param string $type
	 */
	public function countPieces( $type, $minimumRemainingMovement = 0 )
	{
		$piecesCount = 0;
		if ( array_key_exists( $this->m_pieces, $type ) )
		{
			for( $i=$minimumRemainingMovement; $i <= getTypeAttributes( $type )->maximumMovement; $i++ )
			{
				$piecesCount += $this->m_pieces[$type][$i];
			}
		}
		return $piecesCount;
	}
	
	public function canMovePieces( $count, $type, $distance )
	{
		return countPieces( $type, $distance ) >= $count;
	}
	
	/** Checks if this zone has a connection to the given zone.
	 * 
	 * @param string $zone
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
	 * @param array $path
	 * @param boolean $water (optional)
	 * @return boolean
	 */
	public function isValidPath( array $path, $water = null )
	{
		$zone = $this;
		foreach( $path as $step )
		{
			if ( !$zone->hasConnection( $step ) )
			{
				return false;
			}
			if ( $water !== null && $water !== $zone->m_water )
			{
				return false;
			}
			$zone = A3GameZoneRegistry::getInstance( )->getElement( $step );
		}
		if ( $water !== null && $water !== $zone->m_water )
		{
			return false;
		}
		return true;
	}
	
	public function __construct( $name )
	{
		$this->m_name = $name;
	}
}