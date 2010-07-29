<?php
interface IFactory
{
	public function createSingleProduct( $key );
	public function createAllProducts( );
}