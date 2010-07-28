<?php

class Attachment
{
	private $attachment_rules_id;
	private $attachment_name;
	private $attachment_type;
	
	private $m_options = array( );
	
	public function __contrsuct( )
	{		
	}
	
	public function attachTo( IAttachable $attachable )
	{
		$attachable->registerAttachment( $this, $this->attachment_name );
	}
	
	public function detachFrom( IAttachable $attachable )
	{
		$attachable->unregisterAttachment( $this->attachment_name );
	}
}

interface IAttachable
{
	public function registerAttachment( Attachment $attachment, $name );
	public function unregisterAttachment( $name );
	
	public function getAttachement( $name );
	public function hasAttachment( $name );
}