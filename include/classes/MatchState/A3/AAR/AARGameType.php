<?php
class AARGameTypePDOFactory extends A3GameTypePDOFactory
{
	private $m_modification = array( );
	
	/** Creates a type object. Considers modification and can produce a special custom
	 * type based upon data, but modified according to whatever is found in 
	 * modification.
	 * 
	 * (non-PHPdoc)
	 * @see include/classes/MatchState/A3/A3GameTypePDOFactory::createObject()
	 */
	protected function createObject( array $data )
	{
		// apply modifications
		$data = AARPDOMatchState::applyModification( $data, $this->m_modification );
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
		$this->m_modification = AARPDOMatchState::applyModification( $this->m_modification, $modification );
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
	public function addModifiedType( $basetype, $name, array $modification )
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
}