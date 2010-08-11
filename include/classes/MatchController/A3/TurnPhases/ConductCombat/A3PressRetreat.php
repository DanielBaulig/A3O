<?php 
class A3PressRetreat extends BaseState
{
	protected $m_match;
	protected $m_concludeCombat;
	protected $m_pressAttack;
	
	
	const RETREAT = 'retreat';
	const PRESS_ATTACK = 'press';
	const SUBMERGE = 'submerge';
	
	public function __construct( $name, MatchBoard $match )
	{
		parent::__construct( $name );
		$this->m_match = $match;	
		$this->attackers = 4;
		$this->defenders = 4;
	}
	
	public function setUp( IState $concludeCombat, IState $pressAttack )
	{
		$this->m_concludeCombat = $concludeCombat;
		$this->m_pressAttack = $pressAttack;
	}
	
	public function doEnter( )
	{
		if ( ! ($this->attackers > 0 && $this->defenders > 0 ) )
		{
			return $this->doConcludeCombatExit( )->doEnter( );
		}
		else 
		{
			return $this;
		}
	}

	protected function doConcludeCombatExit( )
	{
		return $this->m_concludeCombat->doEnter( );
	}
	
	protected function doPressAttackExit( )
	{
		return $this->m_pressAttack->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		if ( $action->isCommand( self::RETREAT ) )
		{
			$this->attackers = 0;
		}
		
		if ( $action->isCommand( self::SUBMERGE ) )
		{
			// submerge a submarine
		}
		
		if ( $action->isCommand( self::PRESS_ATTACK ) )
		{
			return $this->doPressAttackExit( );
		}
		
		return $this->doEnter( );
	}
}