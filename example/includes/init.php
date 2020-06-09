<?php
use Jgauthi\Component\Database\Db;

// In this example, the vendor folder is located in "example/"
require_once __DIR__.'/../vendor/autoload.php';

//-- Configuration (edit here) -------
define('DB_SERVER', 'localhost');
define('DB_LOGIN', 'root');
define('DB_PASS', 'root');
define('DB_DATABASE', 'dbname');
define('DB_PORT', 3306); // optional
//------------------------------------

function d(string $value, string $title = ''): void
{
    echo '<h3>'.$title.'</h3>';
    var_dump($value);
}

// Creates the instance
$db = new db(DB_SERVER, DB_LOGIN, DB_PASS, DB_DATABASE, DB_PORT);
