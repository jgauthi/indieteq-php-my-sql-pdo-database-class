<?php
use Jgauthi\Component\Database\Db;

// In this example, the vendor folder is located in "example/"
require_once __DIR__.'/../vendor/autoload.php';

function d($value, $title = '')
{
    echo '<h3>'.$title.'</h3>';
    var_dump($value);
}

// Creates the instance
// $db = db::init($dbhost, $dbuser, $dbpass, $dbname, $dbport);
$db = db::initByIni(__DIR__.'/database.ini');
