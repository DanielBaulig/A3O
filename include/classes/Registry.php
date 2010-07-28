<?php

require_once 'Logger.php';

interface IFactory
{
	/** Returns a product referenced by key
	 * 
	 * @param mixed $key
	 */
	public function createSingleProduct( $key );
	
	/** Returns an associative Array with $key => $product
	 * 
	 */
	public function createAllProducts( );
}

abstract class BaseRegistry
{
	private $m_factory;
	private $m_elements = array( );
	
	public function __construct( IFactory $factory )
	{
		$this->m_factory = $factory;
	}
	
	public function getElement( $key, $forceReload = false )
	{
		if ($forceReload || !array_key_exists($key, $this->m_elements))
		{
			try
			{
				$element = $this->m_factory->createProduct( $key );
				if ( $element != NULL )
				{
					$this->m_elements[$key] = $element;	
				}
			}
			catch(PDOException $e)
			{
				Logger::getLogger( )->logException( $e );
			}
		}
		return $this->m_elements[$key];
	}	
	
	public function &getAllElements( )
	{
		return $this->m_elements;
	}	
	
	public function precacheElements( )
	{
		$this->m_elements = $this->m_factory->createAllProducts( );
	}
}