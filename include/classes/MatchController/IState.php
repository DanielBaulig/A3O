<?php
/**
 * Implements a basic state. Can save itself into a variable
 * and can determine if it self was saved in the variable.
 * 
 * @author Daniel Baulig
 */
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

/**
 * Represents a state with no function that can be used to
 * skip over certain parts in the state machine that are
 * not yet implemented.
 * 
 * Calling onEnter will yield to a onEnter of the next state.
 * 
 * Calling doAction will trigger an exception.
 * 
 * @author Daniel Baulig
 *
 */
class UnimplementedState extends BaseState
{
	private $m_nextState;
	
	public function setUp( IState $nextState )
	{
		$this->m_nextState = $nextState;
	}
	
	public function doEnter( )
	{
		return $this->m_nextState->doEnter( );
	}
	
	public function doAction( Action $action )
	{
		// this should never be called!
		throw new Exception( 'State not implemented!' );
	}
	
	public function __construct( $name )
	{
		parent::__construct( $name );
	}
}

/**
 * Interface for state loading classes. A class implementing 
 * the IStateLoader interface must be able to return the BaseState
 * implementation which is saved in the $statebuffer, if it knows 
 * it.
 * 
 * If the state is not known to the IStateLoader then I may yield
 * this search to any other IStateLoader it knows of. However, care
 * not to get into a recursive loop when searching for a state
 *  
 * @author Daniel Baulig
 */
interface IStateLoader
{
	public function getStateSavedIn( $stateBuffer );
}

/**
 * Interface for a statemachine factory. A call to createStateMachine
 * returns an entire, setup and ready to use statemachine. Because
 * IStateMachineFactory extends IStateLoader you can also pass it
 * a statebufer and get the entire statemachine connected to the 
 * BaseState represented by the statebuffer.
 * 
 * @author Daniel Baulig
 */
interface IStateMachineFactory extends IStateLoader
{
	/** 
	 * @param IState $exitPoint
	 * @return IState
	 */
	public function createStateMachine( IState $exitPoint );
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