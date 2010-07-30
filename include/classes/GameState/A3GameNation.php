<?php

class A3GameNationPDOFactory implements IFactory
{
	public function createAllProducts(){}
	public function createSingleProduct( $key ){}
}

class A3GameNationRegistry extends BaseRegistry
{
	private static $instance = null;
	public static function initializeRegistry( IFactory $factory )
	{
		if ( self::$instance !== null )
		{
			throw new Exception( 'A3GameNationRegistry already initialized.' );
		}
		self::$instance = new A3GameNationRegistry( $factory );
	}
	public static function getInstance( )
	{
		if ( self::$instance === null )
		{
			throw new Exception('A3GameNationRegistry not initialized.');
		}
		return self::$instance;
	}
	public static function getNation( $key )
	{
		return self::$instance->getElement( $key );
	}
}

class A3GameNation
{
	protected $m_data;
	
	const NAME = 'name';
	const ALLIANCES = 'alliances';
	
	public function __construct( array $data )
	{
		$this->m_data = $data;
	}
	
	public function isAllyOf( $nation )
	{
		$nation =  A3GameNatoinRegistry::getNation( $nation );
		foreach( $this->m_data[A3GameNation::ALLIANCES] as $alliance => $ignore )
		{
			if ( $nation->isInAlliance( $alliance ) )
			{
				return true;
			}
		}
		return false;
	}
	
	public function isInAlliance( $alliance )
	{
		return array_key_exists( $alliance, $this->m_data[A3GameNation::ALLIANCES] );
	}
} 
