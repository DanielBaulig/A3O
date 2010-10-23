<?php
interface IStoreable
{
	public function storeData( Storer $storer );
}

/**
 * Implementation of half of the Memento pattern. A storer takes
 * an object implementing the IStoreable interface and stores it's
 * state in any location that is implemented for the storer.  
 * 
 * The storer calls the storeData method of the IStoreable object
 * and passes itself as parameter. If IStoreable is implemented
 * correctly the IStoreable will call the storers takeData method 
 * passing an array containing all data to be stored. 
 * 
 * Some media require a seperator between objects (eg in a stream). If
 * your storer does need a seperator reimplement the next( ) method to 
 * generate the seperator. 
 * When using a Storer to store multiple objects you should make sure to 
 * call next( ) between each of the objects.
 * 
 * @author Daniel Baulig
 */
abstract class Storer
{
	private $m_dataBuffer;
	public function takeData( array $data )
	{
		$this->m_dataBuffer = $data;
	}
	protected function getStoreableData( IStoreable $storeable )
	{
		$storeable->storeData( $this );
		return $this->m_dataBuffer;
	}
	
	abstract public function store( IStoreable $storeable );
	public function next( ) 
	{
	}
}