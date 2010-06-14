<?php

class TripleAGameXMLImporter
{
	private $xml = NULL;
	private $turnOrder = array();
	private $players = array();
	public $territories = array();
	
	function __construct( )
	{
		$this->xml = new XMLReader( );
	}
	
	function parse( $xml )
	{
		if ( !$this->xml->open( $xml ) )
		{
			return false;
		}

		// read expected doctype
		$this->xml->read();
		if ( $this->xml->nodeType != XMLReader::DOC_TYPE && $this->xml->name != 'game')
		{
			return false;
		}
		// read game information
		while ( $this->xml->read( ) )
		{
			// first node should be <game>
			if ($this->xml->name != 'game')
			{
				$this->xml->next( );
				echo 'nexting for game - SHOULD NOT HAPPEN<br/>';
			}
			else
			{
				echo 'found game; node-type: ' . $this->xml->nodeType . '<br/>';
		
				// get next block
				while ( $this->xml->read( ) )
				{
					if ( $this->xml->name == 'info' && ( $this->xml->nodeType == XMLReader::ELEMENT ) && $this->xml->hasAttributes )
					{
						$this->version = $this->xml->getAttribute('version');
						$this->name = $this->xml->getAttribute('name');
						echo 'name: ' . $this->name . '<br/>';
						echo 'version: ' .  $this->version . '<br/>';
					}
					elseif ( $this->xml->name == 'map' && ( $this->xml->nodeType == XMLReader::ELEMENT ) )
					{
						echo 'found map<br/>';
						while ( $this->xml->read( ) )
						{
							if ( ( $this->xml->name == 'map' ) && ( $this->xml->nodeType == XMLReader::END_ELEMENT ) )
							{
								echo 'found map end<br/>';
								break;
							} 
							elseif ( $this->xml->name == 'territory' && $this->xml->hasAttributes && ( $this->xml->nodeType == XMLReader::ELEMENT ) ) 
							{
								echo 'found territory<br/>';
								if ( ( $name = $this->xml->getAttribute( 'name' ) ) != '' )
								{
									$this->territories[ $name ][ 'water' ] = $this->xml->getAttribute( 'water' ) === 'true' ? true : false;
									echo 'name: ' . $name . '<br/>';
									echo 'water?: ' . $this->territories[$name]['water'] . '<br/>';
								}
							}
							elseif ( $this->xml->name == 'connection' && $this->xml->hasAttributes && ( $this->xml->nodeType == XMLReader::ELEMENT ) ) 
							{
								echo 'found connection<br/>';
								if ( ( $first = $this->xml->getAttribute( 't1' ) ) != '' && ( $second = $this->xml->getAttribute( 't2' ) ) != '')
								{
									$this->terriroties[ $first ][ 'neighbours' ][ $second ] = true;
									echo 'first: ' . $first . '<br/>';
									echo 'second: ' . $second . '<br/>'; 
								}
							}
						};
					}
					elseif ( $this->xml->name == 'resourceList' && ( $this->xml->nodeType == XMLReader::ELEMENT ) )
					{
						echo 'resource list found<br/>';
						while ( $this->xml->read( ) )
						{
							if ( $this->xml->name == 'resourceList' && ( $this->xml->nodeType == XMLReader::END_ELEMENT ) )
							{
								echo 'found resource list end<br/>';
								break;
							}	
							elseif ( $this->xml->name == 'resource' && ( $this->xml->nodeType == XMLReader::ELEMENT ) && $this->xml->hasAttributes )
							{
								if ( ( $name = $this->xml->getAttribute('name') ) != '' )
								{
									echo 'found resource ' . $name . '<br/>';
									$this->resouceList[$name] = true;
								}
							}
						}						
					}
					elseif ( $this->xml->name == 'playerList' && ( $this->xml->nodeType == XMLReader::ELEMENT ) )
					{
						echo 'player list found<br/>';
						while ( $this->xml->read( ) )
						{
							if ( $this->xml->name == 'playerList' && ( $this->xml->nodeType == XMLReader::END_ELEMENT ) )
							{
								echo 'found player list end<br/>';
								break;
							} 
							elseif ( $this->xml->name == 'player' && ( $this->xml->nodeType == XMLReader::ELEMENT ) && $this->xml->hasAttributes )
							{
								if ( ( $name = $this->xml->getAttribute('name') ) != '' && !array_key_exists( $name, $this->players ) )
								{			
									$this->turnOrder[] = $name;
									$this->players[$name]['optional'] = $this->xml->getAttribute('optional') === 'true' ? true : false;
									echo 'found player ' . $name . ( $this->players[$name]['optional'] ? ' (optional)' : '' ) . '<br/>';
								}
							}
							elseif ( $this->xml->name == 'alliance' && ( $this->xml->nodeType == XMLReader::ELEMENT ) && $this->xml->hasAttributes )
							{
								if ( ( $player = $this->xml->getAttribute('player') ) != '' && array_key_exists( $player, $this->players ) && ( $alliance = $this->xml->getAttribute('alliance') ) != '' )
								{			
									$this->players[$player]['alliance'] = $alliance;
									echo 'found alliance/player ' . $alliance . ' (' .$player . ')<br/>';
								}
							}	
						}
					}
					elseif ( $this->xml->name == 'initialize' && ( $this->xml->nodeType == XMLReader::ELEMENT ) )
					{
						echo 'found initialize<br/>';
						while ( $this->xml->read( ) )
						{
							if ( $this->xml->name == 'initialize' && ( $this->xml->nodeType == XMLReader::END_ELEMENT ) )
							{
								echo 'found initialize end<br/>';
								break;
							}
							elseif ( $this->xml->name == 'territoryOwner' && ( $this->xml->nodeType == XMLReader::ELEMENT ) && $this->xml->hasAttributes )
							{
								if ( ($owner = $this->xml->getAttribute('owner')) != '' && ($territory = $this->xml->getAttribute('territory')) != '' && array_key_exists( $territory, $this->territories ) && array_key_exists ( $owner, $this->players ) )	
								{
									$this->territories[$territory]['startowner'] = $owner;
									echo 'found owner. ' . $owner . ' owns ' . $territory .  '<br/>';
								}							
							}
						}
					}
				}
			}
			
			// while there could possibly exist more than 1 game config 
			// in the file we will skip any additional configs for
			// conveinience
			return true; 
		}		
	}
}

$importer = new TripleAGameXMLImporter( );
$importer->parse('big_world_1942.xml');

require_once '../config.php';
 
try
{
	$pdo = new PDO('mysql:host=' . $sql_host . ';dbname='. $sql_database, $sql_username, $sql_password);
}
catch (PDOException $e)
{
	die($e->getMessage());
}

$statement = $pdo->prepare('UPDATE base_tiles, nations SET tile_startowner_id = nation_id WHERE tile_name LIKE :tile_name AND nation_name LIKE :startowner');

foreach ( $importer->territories as $name => &$territory  )
{
	$statement->bindValue(':startowner', $territory['startowner'], PDO::PARAM_STR);
	$statement->bindValue(':tile_name', $name, PDO::PARAM_STR);
	$statement->execute( );
	echo $statement->queryString . '<br/>';
}

?>