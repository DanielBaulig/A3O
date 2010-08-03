<?php

interface IChange
{
	public function apply( );
}

class ChangeSet implements IChange
{
	private $m_changes;
	
	public function addChange( IChange $change )
	{
		$this->m_changes[] = $change;
	}
	
	public function __construct( )
	{
		$this->m_changes = array( );
	}
	
	public function apply( )
	{
		foreach( $this->m_changes as $change )
		{
			$change->apply( );
		}
	}
}

class MovePiecesChange implements IChange
{
	private $type;
	private $nation;
	private $count;
	private $sourceZone;
	private $destinationZone;
	private $distance;
	
	public function apply( )
	{
		MatchZoneRegistry::getZone( $this->sourceZone )->movePieces( 
			$this->count, $this->nation, $this->type, $this->distance, $this->destinationZone 
		);
	}
}

/** DiceResults are not really a change and that's why they do not do anything
 * in their apply method. However, to correctly replay past turns and matches
 * the game engine needs to know the resutls of rolled dices. DiceResults
 * is used to store those results in the change queue.
 * 
 * @author Daniel Baulig
 */
class DiceResults implements IChange
{
	private $results;

	public function addResult( $result )
	{
		$results[] = $result;
	}
	
	public function getNext( )
	{
		$value = current( $this->results );
		next( $this->results );
		return $value;
	}
	
	public function apply( )
	{
		// do nothing
	}
}

class AddPiecesChange implements IChange
{
	public function apply( )
	{
		// do nothing
	}
}

class RemovePieces implements IChange
{
	public function apply( )
	{
		// do nothing
	}
}

class SetPlayerOption implements IChange
{
	public function apply( )
	{
		// do nothing
	}
}

class UnsetPlayerOption implements IChange
{
	public function apply( )
	{
		// do nothing
	}
}

class ChangeOwner implements IChange
{	public function apply( )
	{
		// do nothing
	}
}