<?php

require_once 'include/classes/Logger.php';

error_reporting( E_ALL | E_STRICT );
 
function my_assert_handler($file, $line, $code)
{
    echo "<hr>Assertion Failed:
        File '$file'<br />
        Line '$line'<br />
        Code '$code'<br /><hr />";
}

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);
assert_options(ASSERT_CALLBACK, 'my_assert_handler');


//Logger::hookErrorHandler( );
//Logger::hookExceptionHandler( );
Logger::getLogger( )->createEchoLogger( Logger::LOG_ALL );

$sql_host = 'localhost';
$sql_username = 'root';
$sql_password = '';
$sql_database = 'a3o';
 
?>