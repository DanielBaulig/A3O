<?php
abstract class BaseState implements IState
{
	private $m_name;
	public function saveTo( &$stateBuffer )
	{
		$stateBuffer = $this->m_name;
	}
	public function isSavedIn( $stateBuffer )
	{
		return $stateBuffer == $this->m_name;
	}
	public function __construct( $name )
	{
		$this->m_name = $name;
	}
}

class UnimplementedState extends BaseState
{
	private $m_nextState;
	
	public function doEnter( )
	{
		return $this->m_nextState;
	}
	
	public function doAction( Action $action )
	{
		// this should never be called!
		throw new Exception( 'State not implemented!' );
	}
	
	public function __construct( $name, IState $nextState )
	{
		parent::__construct( $name );
		$this->m_nextState = $nextState;
	}
}

interface IStateMachineFactory extends IStateLoader
{
	/** 
	 * @param IState $exitPoint
	 * @return IState
	 */
	public function createStateMachine( IState $exitPoint );
}

interface IStateLoader
{
	public function getStateSavedIn( $stateBuffer );
}

interface IState
{
	/** This is called if the state should handle an action.
	 * If the state wants to retain control after it handled the
	 * message it should return $this, if it wants to pass 
	 * control to another state it must return the other state.
	 * 
	 * @param Action $action
	 * @return IState
	 */
	public function doAction( Action $action );
	
	/** If the states intents to handle messages it should return $this,
	 * if it just wants to hand control to another state after this method
	 * finishes it should return the other state.
	 * @return IState
	 */
	public function doEnter( );
}