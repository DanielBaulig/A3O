<?php
interface IStoreable
{
	public function storeData( Storer $storer );
}

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
}

interface IStorerFactory
{	
	abstract public function createStorer( IStoreable $storeable );
}

class A3StorerPDOFactory implements IStorerFactory
{
	protected $m_pdo;
	protected $m_match;
	
	public function createStorer( IStoreable $storeable )
	{
		switch( get_class( $storeable ) )
		{
			case 'A3MatchPlayer':
					return new MatchPlayerPDOStorer( $this->m_pdo, $this->m_match, $storeable );
			case 'MatchZone':
					return new MatchZonePDOStorer( $this->m_pdo, $this->m_match, $storeable );
			case 'A3MatchState':
					return new MatchStatePDOStorer( $this->m_pdo, $this->m_match, $storeable );
			default:
				throw new InvalidArgumentException( 'Unknown IStoreable implementation. You are propably using the wrong StorerFactory.' );
		}
	}
	
	public function __construct( PDO $pdo, $match_id )
	{
		$this->m_pdo = $pdo;
		$this->m_match = $match_id;
	}
}