<?php
/** Implements the Factory pattern and creates GameType objects
 * for a specific game.
 * 
 * Takes a PDO object and the id to a specific game and creates
 * GameType objects from the PDO belonging to that specific
 * game.
 * 
 * @author Daniel Baulig
 */
class GameTypePDOFactory implements IFactory
{
	private $m_pdo;
	protected $m_match;

	/** PDO statement to load all game types
	 *
	 * No additional binds needed after construction.
	 *
	 * @var PDOStatement
	 */
	private $m_loadAllGameTypes;

	/** PDO statement to load options for a type specified by it's id
	 *
	 * type id must be bound to :type_id
	 *
	 * @var PDOStatement
	 */
	private $m_loadOptionsByGameTypeId;

	/** PDO statement to load options for a type specified by it's name
	 *
	 * type name must be bound to :type
	 *
	 * @var PDOStatement
	 */
	private $m_loadOptionsByGameTypeName;

	/** Creates a type factory for the specified game.
	 *
	 * @param PDO $pdo
	 * @param int $game
	 */
	public function __construct( PDO $pdo, MatchState $match )
	{
		//TODO: Refactor SQL to not get options by name, but to get id from a single option
		// this may be of lower performance but is the better design approach imo
		$this->m_pdo = $pdo;
		$this->m_match = $match;
		$this->m_specialFactory;

		$sql_types = 'SELECT type_id AS id, type_name AS name FROM a3o_types WHERE type_game = :game_id;';

		$this->m_loadAllGameTypes = $this->m_pdo->prepare( $sql_types );
		$this->m_loadAllGameTypes->bindValue( ':game_id', $this->m_match->getGameId( ), PDO::PARAM_INT );

		$sql_options_id = 'SELECT typeoption_name AS name, typeoption_value AS value FROM a3o_typeoptions'
		. ' WHERE typeoption_type = :type_id;';
			
		$this->m_loadOptionsByGameTypeId = $this->m_pdo->prepare( $sql_options_id );

		$sql_options_name = 'SELECT typeoption_name AS name, typeoption_value AS value FROM a3o_typeoptions'
		. ' INNER JOIN a3o_types ON type_id = typeoption_type WHERE type_name = :type;';
			
		$this->m_loadOptionsByGameTypeName = $this->m_pdo->prepare( $sql_options_name );
	}

	/** This is so descendent class can override this method to change the type
	 * of object beeing returned to a child type of GameType
	 * 
	 * @param array $data
	 * @return GameType
	 */
	protected function createObject( array $data )
	{
		return new GameType( $this->m_match, $data );
	}
	
	/** Creates a type object from key
	 *
	 * @see include/classes/IFactory::createSingleProduct()
	 * @param string $key
	 * @return GameType
	 */
	public function createSingleProduct( $key )
	{
		$type = array ( GameType::NAME => $key );
		$this->m_loadOptionsByGameTypeName->bindValue( ':type', $key, PDO::PARAM_STR );
		$this->m_loadOptionsByGameTypeName->execute( );

		$options = array( );

		while ( $row = $this->m_loadOptionsByGameTypeName->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$options[$row['name']] = $row['value'];
		}
		// check if there was any option found
		if ( count( $options ) <= 0 )
		{
			// types can indeed exist in the database without any options attached
			// but in reality this does not make any logical sense, so even if there
			// is a possibility, that the type does exist in the database but has no
			// options attached, we will treat the type as if it does not exist.
			throw new DomainException( 'Specified type ' . $key . ' not valid.' );
		}

		$type[GameType::OPTIONS] = $options;

		return $this->createObject( $type );
	}

	/** Loads all types for this game from the database and returns them as an array
	 * 
	 * @see include/classes/IFactory::createAllProducts()
	 * @return array
	 */
	public function createAllProducts( )
	{
		$types = array ( );
		$this->m_loadAllGameTypes->execute( );

		while( $type = $this->m_loadAllGameTypes->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$this->m_loadOptionsByGameTypeId->bindValue( ':type_id', $type['id'], PDO::PARAM_STR );
			$this->m_loadOptionsByGameTypeId->execute( );
				
			$options = array( );
				
			while ( $option = $this->m_loadOptionsByGameTypeId->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
			{
				$options[ $option[GameType::OPTION_NAME] ] = $option[GameType::OPTION_VALUE];
			}
			$type[GameType::OPTIONS] = $options;
			unset( $type['id'] );
			$types[ $type[GameType::NAME] ] = $this->createObject( $type );
		}

		return $types;
	}
}

class GameType
{
	protected $m_data;
	protected $m_state;

	const NAME = 'name';
	const OPTIONS = 'options';
	const OPTION_NAME = 'name';
	const OPTION_VALUE = 'value';

	public function __construct( MatchState $state, array $data )
	{
		$this->m_data = $data;
		$this->m_state = $state;
	}

	protected function getOption( $name )
	{
		if( array_key_exists( $name, $this->m_data[GameType::OPTIONS] ) )
		{
			return $this->m_data[GameType::OPTIONS][$name];
		}
		else
		{
			return 0;
		}
	}
	
}