<?php 
class A3AutoRemoveCasualties implements IState
{
	protected $m_match;
	protected $m_nextPhase;
	
	public function __construct( MatchBoard $match )
	{
		$this->m_match = $match;	
	}
	
	public function setUp( IState $nextPhase )
	{
		$this->m_nextPhase = $nextPhase;
	}
	
	public function doEnter( )
	{
		// remove casualties
		return $this->doNextPhaseExit( );
	}

	protected function doNextPhaseExit( )
	{
		return $this->m_nextPhase->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		return $this;
	}
}

class A3RemoveCasualties implements IState
{
	protected $m_match;
	protected $m_nextPhase;
	
	const SELECT = 'select';
		
	public function __construct( MatchBoard $match )
	{
		$this->m_match = $match;	
	}
	
	public function setUp( IState $nextPhase )
	{
		$this->m_nextPhase = $nextPhase;
	}
	
	public function doEnter( )
	{
		if ( $this->noCasualtiesLeft )
		{
			return $this->doNextPhaseExit( );
		}
		else 
		{
			return $this;
		}
	}

	protected function doNextPhaseExit( )
	{
		return $this->m_nextPhase->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		if ( $action->isCommand( self::SELECT ) )
		{
			// remove casualties
			$this->noCasualtiesLeft = true;
		}
		
		return $this->doEnter( );
	}
}