<?php

require_once 'include/classes/Logger.php';
require_once 'include/classes/Filters.php';

error_reporting( (E_ALL | E_STRICT) & ~E_NOTICE );
 
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

require_once 'config.php';

$pdo = new PDO('mysql:host='.$sql_host.';dbname='.$sql_database.';charset=UTF-8', $sql_username, $sql_password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$request = new RequestFilter();