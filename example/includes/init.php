<?php
use Jgauthi\Component\Database\Db;

// In this example, the vendor folder is located in "example/"
require_once __DIR__.'/../vendor/autoload.php';

//-- Configuration (edit here) ------------------------
$dbhost = ((isset($dbhost)) ? $dbhost : 'localhost');
$dbuser = ((isset($dbuser)) ? $dbuser : 'root');
$dbpass = ((isset($dbpass)) ? $dbpass : 'root');
$dbname = ((isset($dbname)) ? $dbname : 'dbname');
$dbport = ((isset($dbport)) ? $dbport : 3306);
//-----------------------------------------------------

function d($value, $title = '')
{
    echo '<h3>'.$title.'</h3>';
    var_dump($value);
}

// Creates the instance
$db = db::init($dbhost, $dbuser, $dbpass, $dbname, $dbport);
