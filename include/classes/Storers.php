<?php
interface IStoreable
{
	public function storeData( Storer $storer );
}

abstract class Storer
{
	private $m_storeable;
	private $m_dataBuffer;
	public function takeData( array $data )
	{
		$this->m_dataBuffer = $data;
	}
	protected function getData( )
	{
		$this->m_storeable->storeData( $this );
		return $this->m_dataBuffer;
	}
	
	public function __construct( IStoreable $storeable )
	{
		$this->m_storeable = $storeable;
	}
	
	abstract public function store( );
}

interface IStorerFactory
{	
	abstract public function createStorer( IStoreable $storeable );
}

class A3StorerPDOFactory implements IStorerFactory
{
	protected $m_pdo;
	
	public function createStorer( IStoreable $storeable )
	{
		switch( get_class( $storeable ) )
		{
			case 'A3MatchPlayer':
					return new MatchPlayerPDOStorer( $this->m_pdo, $storeable );
			case 'MatchZone':
					return new MatchZonePDOStorer( $this->m_pdo, $storeable );
			case 'A3MatchState':
					return new MatchStatePDOStorer( $this->m_pdo, $storeable );
			default:
				throw new InvalidArgumentException( 'Unknown IStoreable implementation. You are propably using the wrong StorerFactory.' );
		}
	}
	
	public function __construct( PDO $pdo )
	{
		$this->m_pdo = $pdo;
	}
}