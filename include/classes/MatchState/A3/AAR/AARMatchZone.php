<?php
class AARMatchZonePDOFactory extends A3MatchZonePDOFactory
{
	protected function createObject( array $data )
	{
		return new AARMatchZone( $this->m_match, $data );
	}
	
	/** Supports type mdofication for teched units
	 * (non-PHPdoc)
	 * @see include/classes/MatchState/MatchZonePDOFactory::loadPieces()
	 */
	protected function loadPieces( $zone_id )
	{
		$this->m_loadPiecesSingleGameZone->bindValue( ':zone_id', $zone_id, PDO::PARAM_INT );
		$this->m_loadPiecesSingleGameZone->execute( );
		$pieces = array( );
		while ( $row = $this->m_loadPiecesSingleGameZone->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			// if the nations player has tech researched we will try to create 
			// tech-modified versions of the types.
			if ( $this->m_match->getPlayer( $row['nation'] )->hasTechnologyResearched( ) )
			{
				$row['type'] = $this->m_match->addTechedType( $row['type'], $row['nation'] );
			}
			$pieces[$row['nation']][$row['type']] = $row['count'];
		}
		return $pieces;
	}
}

class AARMatchZone extends A3MatchZone
{
	
}