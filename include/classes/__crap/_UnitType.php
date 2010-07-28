<?php
class UnitType implements IAttachable
{
	private $m_attachments = array( );
	
	public function registerAttachment( Attachment $attachment, $name )
	{
		$this->m_attachments[$name] = $attachment;
	}	
	public function unregisterAttachment( $name )
	{
		$this->m_attachments[$name] = NULL;
		unset( $this->m_attachments[$name] );
	}	
	public function getAttachement( $name )
	{
		return $this->m_attachments[$name];
	}	
	public function hasAttachment( $name )
	{
		return array_key_exists( $name, $this->m_attachments );
	}
}