<?php

abstract class BaseLogger
{
	const LOG_NONE = 0;
	const LOG_NOTICE = 1;
	const LOG_WARNING = 2;
	const LOG_ERROR = 4;
	const LOG_EXCEPTION = 8;
	const LOG_ALL = 15;
	
	private $verbosity = BaseLogger::LOG_NONE;
	
	public function __construct( $verbosity )
	{
		$this->verbosity = $verbosity;
	}
	
	abstract protected function log( $severity, $message, $file = false, $line = false, $backtrace = false );
	
	
	public function logNotice( $message )
	{
		if ( $this->verbosity & BaseLogger::LOG_NOTICE )
		{
			$this->log('NOTICE', $message );
		}	
	}
	public function logWarning( $message )
	{
		$trace = debug_backtrace( false );
		if ( $this->verbosity & BaseLogger::LOG_WARNING )
		{
			$this->log('WARNING', $message, $trace[0]['file'], $trace[0]['line'] );
		}
	}
	public function logError( $message, $die = false )
	{
		$trace = debug_backtrace( false );
		if ( $this->verbosity & BaseLogger::LOG_ERROR )
		{
			$this->log('ERROR', $message, $trace[0]['file'], $trace[0]['line'], $trace );
		}
		if ( $die )
		{
			die('Terminating.');
		}
	}
	public function logException( Exception $e, $die = false )
	{
		if ( $this->verbosity & BaseLogger::LOG_EXCEPTION )
		{
			$this->log('EXCEPTION', $e->getMessage( ), $e->getFile( ), $e->getLine( ), $e->getTrace( ) );
		}
		if ( $die )
		{
			die('Terminating.');
		}
	}
};

abstract class LineLogger extends BaseLogger
{
	abstract protected function writeLine( $output );
	
	protected function log( $severity, $message, $file = false, $line = false, $backtrace = false )
	{
		$output = $severity;
		if ( $file  !== false )
		{
			$output .= '(' . $file;
			if ( $line !== false )
			{
				$output .= ':' . $line;
			}
			$output .= ')';
		}
		$output .= ': ' . $message . "\n";
		$this->writeLine( $output );
		
		if ( $backtrace !== false )
		{
			$this->writeLine( 'BACKTRACE:' . "\t\n" );
			foreach( $backtrace as $number => $traceline )
			{
				$this->writeLine( "\t\t" . '[' . $number . '] ' . $traceline['function'] . ' called at [' . $traceline['file'] . ':' . $traceline['line'] . ']' . "\n");
			}
		}
	}
}

class EchoLogger extends LineLogger
{
	protected function writeLine( $output )
	{
		echo $output;
	}
}

class FileLogger extends LineLogger
{
	private $fileHandle = NULL;
	
	protected function writeLine( $output )
	{
		if ( ! fputs( $this->fileHandle, $output ) )
		{
			throw new Exception( 'Couldn\'t write to logfile.' );
		}
	}
	
	public function __contrstuct( $verbosity, $file ) 
	{
		parent::__construct( $verbosity );
		if ( ! is_writeable( $file ) )
		{
			throw new Exception ( 'File ' . $file . ' is not writeable for process.' );
		}				
		if ( ! $this->fileHandle = fopen($file, 'at') )
		{
			throw new Exceptino( 'File ' . $file . ' couldn\'t be opened for writing.');
		}
	}
	
	public function __destruct( )
	{
		fclose( $this->fileHandle );
	}
}

class Logger extends BaseLogger
{
	private static $errorNumberToString = array ( 
		E_ERROR => 'E_ERROR', 
		E_WARNING => 'E_WARNING', 
		E_NOTICE => 'E_NOTICE',
		E_USER_ERROR => 'E_USER_ERROR',
		E_USER_NOTICE => 'E_USER_NOTICE',
		E_USER_WARNING => 'E_USER_WARNING',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_STRICT => 'E_STRICT',
		E_DEPRECATED => 'E_DEPRECATED',
		E_USER_DEPRECATED => 'E_USER_DEPRECATED'
	);
	
	private static $instance;	
	public static function getLogger( )
	{
		if ( Logger::$instance == NULL )
		{
			Logger::$instance = new Logger( Logger::LOG_ALL );
		}
		return Logger::$instance;
	}
	
	public static function errorHandler( $errno, $errstr, $errfile, $errline, $errcontext )
	{
		if ( error_reporting( ) )
		{
			Logger::getLogger( )->log( 'PHP'.Logger::$errorNumberToString[$errno], $errstr, $errfile, $errline );
			switch ( $errno )
			{
				case E_ERROR:
				case E_USER_ERROR:
				case E_RECOVERABLE_ERROR:
					die('Terminating.');
			}
		}
		return true;
	}
	
	public static function exceptionHandler( Exception $e )
	{
		Logger::getLogger( )->logException( $e, true );
	} 
	
	public static function hookExceptionHandler ( $hook = true )
	{
		if ( $hook )
		{
			set_exception_handler( 'Logger::exceptionHandler' );
		}
		else
		{
			restore_exception_handler( );
		}
	}
	
	public static function hookErrorHandler( $hook = true)
	{
		if ( $hook === true )
		{
			set_error_handler( 'Logger::errorHandler' ) ;
		}
		else
		{
			restore_error_handler( );
		}
	}
	
	private $m_loggers = array();
	
	public function createEchoLogger( $verbosity )
	{
		$this->m_loggers[] = new EchoLogger( $verbosity );
	}
	
	public function createFileLogger( $verbosity, $file )
	{
		$this->m_loggers[] = new FileLogger( $file, $verbosity );
	}
	
	protected function log( $severity, $message, $file = false, $line = false, $backtrace = false )
	{
		foreach ( $this->m_loggers as $logger )
		{
			$logger->log( $severity, $message, $file, $line, $backtrace );
		}
	}
}

?>