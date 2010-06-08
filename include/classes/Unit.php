<?php

define('UNIT_TYPE_LAND',  0);
define('UNIT_TYPE_NAVAL', 1);
define('UNIT_TYPE_AIR',   2);

class Unit
{
	private $movement;
	private $attack;
	private $defense;
	
	private $position;
	private $type;
	
	public function move(Tile &$tile)
	{
		
	}
}

?>