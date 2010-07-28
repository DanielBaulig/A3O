<?php
interface IGameState
{
	public function applyChange( Change $change );
	public function undoChange( Change $change );
	public function undoLastChange( );
}