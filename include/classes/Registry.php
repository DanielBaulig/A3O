<?php
 
require_once dirname(__FILE__).'/Logger.php';

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

/** Implementation of the Registry pattern
 * 
 * Should be encapsulated to implement the singleton pattern. 
 * Sadly, due to how PHP handles class variables in inheritence 
 * the singleton pattern cannot be implemented here.
 * 
 * @author Daniel Baulig
 */
class BaseRegistry
{
	/** The factory providing the factory methods to instanciate 
	 * elements contained in the registry.
	 * 
	 * @var IFactory
	 */
	private $m_factory;
	/** The elements contained in the registry.
	 * 
	 * @var array
	 */
	private $m_elements = array( );
	
	/** Instanciates the Registry.
	 * 
	 * The factory provides the methods to instanciate elements
	 * by their key.
	 * 
	 * @param IFactory $factory
	 */
	protected function __construct( IFactory $factory )
	{
		$this->m_factory = $factory;
	}
	
	/** Returns an element contained in the registry.
	 * 
	 * If $forceReload is specified and true or $key is no yet
	 * found in the registry cache, getElement will (re-)load
	 * the element referenced by $key using the methods provided
	 * by the factory.
	 * 
	 * @param mixed $key
	 * @param boolean $forceReload (optional)
	 * @return mixed
	 */
	public function getElement( $key, $forceReload = false )
	{
		if ($forceReload || !array_key_exists($key, $this->m_elements))
		{
			try
			{
				$element = $this->m_factory->createSingleProduct( $key );
				if ( $element != null )
				{
					$this->m_elements[$key] = $element;	
				}
			}
			catch(PDOException $e)
			{
				Logger::getLogger( )->logException( $e );
			}
		}
		// From http://www.php.net/manual/en/language.types.array.php
		// Note: Attempting to access an array key which has not been defined is the same as 
		// accessing any other undefined variable: an E_NOTICE-level error message will be 
		// issued, and the result will be NULL. 
		return @$this->m_elements[$key];
	}	
	
	/** Returns a reference to the entire collection of elements allready loaded in the registry.
	 * 
	 * @return &array
	 */
	public function &getAllElements( )
	{
		return $this->m_elements;
	}	
	
	/** Loads all elements into the registry cache. 
	 *
	 * This is especially useful if getting the entire collection of elements
	 * is (much) quicker than getting each element one by one and use of a wide
	 * range of elements is anticipated. 
	 */
	public function precacheElements( )
	{
		$this->m_elements = $this->m_factory->createAllProducts( );
	}
}