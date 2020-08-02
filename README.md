PDO Database Class
============================

A database class for PHP-MySQL which uses the PDO extension.

## To use the class
### 1. Install the library (PHP5.6 version)
Edit your [composer.json](https://getcomposer.org) (launch `composer update` after edit):
```json
{
  "repositories": [
    { "type": "git", "url": "git@github.com:jgauthi/indieteq-php-my-sql-pdo-database-class.git" }
  ],
  "require": {
    "jgauthi/indieteq-php-my-sql-pdo-database-class": "1.*"
  }
}
```

The PHP 7 version is available, [read more](https://github.com/jgauthi/indieteq-php-my-sql-pdo-database-class).


### 2. Require the class in your project and create the instance
```php
<?php
use Jgauthi\Component\Database\Db;

require('vendor/autoload.php');

// The instance
$db = db::init($dbhost, $dbuser, $dbpass, $dbname, $dbport = 3306);

// Or by PDO Instance
$db = new db($pdo);
```

### 3.  Display error
The original log class was removed and replace by php classic function: [trigger_error](http://www.php.net/manual/en/function.trigger-error.php). With this use, the error are log in the error_log system. You can active debug with `$db->setDebug(true);`, to display query request on error.

## Examples
Below some examples of the basic functions of the database class. I've included a SQL dump so you can easily test the database
class functions.
### The persons table
| id | firstname | lastname | sex | age
|:-----------:|:------------:|:------------:|:------------:|:------------:|
| 1       |        John |     Doe    | M | 19
| 2       |        Bob  |     Black    | M | 41
| 3       |        Zoe  |     Chan    | F | 20
| 4       |        Kona |     Khan    | M | 14
| 5       |        Kader|     Khan    | M | 56

### Fetching everything from the table
```php
<?php
// Fetch whole table
$persons = $db->query("SELECT * FROM persons");
$nb_persons = $db->numRows();
```
### Fetching with Bindings (ANTI-SQL-INJECTION):
Binding parameters is the best way to prevent SQL injection. The class prepares your SQL query and binds the parameters
afterwards.

There are three different ways to bind parameters.
```php
<?php
// 1. Read friendly method
$db->bind('id', 1);
$db->bind('firstname', 'John');
$person   =  $db->query("SELECT * FROM Persons WHERE firstname = :firstname AND id = :id");

// 2. Bind more parameters
$db->bindMore(['firstname' => 'John', 'id' => '1']);
$person   =  $db->query("SELECT * FROM Persons WHERE firstname = :firstname AND id = :id");

// 3. Or just give the parameters to the method
$person   =  $db->query("SELECT * FROM Persons WHERE firstname = :firstname AND id = :id", ['firstname' => 'John', 'id' => '1']);
```

More about SQL injection prevention : http://indieteq.com/index/readmore/how-to-prevent-sql-injection-in-php

### Fetching Row:
This method always returns only 1 row.
```php
<?php
// Fetch a row
$ages     =  $db->row("SELECT * FROM Persons WHERE  id = :id", ['id' => 1]);
```

Result

| id | firstname | lastname | sex | age
|:-----------:|:------------:|:------------:|:------------:|:------------:|
| 1       |        John |     Doe    | M | 19
### Fetching Single Value:
This method returns only one single value of a record.
```php
<?php
// Fetch one single value
$db->bind('id', 3);
$firstname = $db->single("SELECT firstname FROM Persons WHERE id = :id");
```
#### Result
|firstname
|:------------:
| Zoe

### Using the like keyword
```php
<?php
// Using Like
// Notice the wildcard at the end of the value!!
$like = $db->query("SELECT * FROM Persons WHERE Firstname LIKE :firstname ", ['firstname' => 'sekit%']);
```
#### Result
| id | firstname | lastname | sex | age
|:-----------:|:------------:|:------------:|:------------:|:------------:|
| 4       |        Sekito |     Khan | M | 19

### Fetching Column:
```php
<?php
// Fetch a column
$names    =  $db->column("SELECT Firstname FROM Persons");
```
#### Result
|firstname |
|:-----------:
|        John
|        Bob
|        Zoe
|        Kona
|        Kader
### Delete / Update / Insert
When executing the delete, update, or insert statement by using the query method the affected rows will be returned.
```php
<?php
// Delete
$delete   =  $db->query("DELETE FROM Persons WHERE Id = :id", ['id' => 1]);

// Update
$update   =  $db->query("UPDATE Persons SET firstname = :f WHERE Id = :id", ['id' => 32, 'f' => 'Jan', 'last_update' => new DateTime]);

// Insert
$insert   =  $db->query("INSERT INTO Persons(Firstname,Age) VALUES(:f,:age)", ['f' => 'Vivek', 'age' => 20]);

// Do something with the data
if($insert > 0 ) {
  return 'Succesfully created a new person !';
}
```

Note: On mysql request, the [datetime](https://www.php.net/manual/en/book.datetime.php) classes is supported for mysql fields: _VARCHAR, TEXT, DATE, DATETIME, etc_.


## Method parameters
Every method which executes a query has the optional parameter called bindings.

The _row_ and the _query_ method have a third optional parameter  which is the fetch style.
The default fetch style is _PDO::FETCH_ASSOC_ which returns an associative array.

Here an example :

```php
<?php
// Fetch style as third parameter
$person_num =     $db->row("SELECT * FROM Persons WHERE id = :id", ['id' => '1'], PDO::FETCH_NUM);

print_r($person_num);
// Array ( [0] => 1 [1] => Johny [2] => Doe [3] => M [4] => 19 )
```
More info about the PDO fetchstyle : http://php.net/manual/en/pdostatement.fetch.php


## Save a variable in database
Before use this method, please create the table:

```sql
CREATE TABLE IF NOT EXISTS `variable` (
  `name` varchar(100) NOT NULL,
  `value` text,
  `serialize` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `dateUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Various variables and options for the application';

ALTER TABLE `variable` ADD PRIMARY KEY (`name`);
```

You can save/get/delete any variables (text, int, array, object) in this table, with this method:

```php
<?php
$name = 'var_name';
$pdo->table['variable'] = 'variable'; // [Optionnal] You can change the table name

// Save
$value = ['current_script' => basename($_SERVER['PHP_SELF']), 'time' => time()];
$pdo->variable_save($name, $value);

// Get
$myvar = $pdo->variable_get($name, 'default_value_if_not_exists');
print_r($myvar);

// Delete var
$pdo->variable_delete($name);
```
This method can be use to store variables or configurations application (like `wp_options` for wordpress).


### Get PDO Var
You can get the PDO var to use with another lib _(like [fixtures](https://github.com/nelmio/alice) by examples)_.

```php
<?php
$pdo = $db->getPdoVar();

$request = new AnotherLib($pdo);
```




EasyCRUD
============================
The easyCRUD is a class which you can use to easily execute basic SQL operations like(insert, update, select, delete) on your database.
It uses the database class I've created to execute the SQL queries.

Actually it's just a little ORM class.

## How to use easyCRUD
### 1. First, create a new class. Then require the easyCRUD class.
### 2. Extend your class to the base class AbstractCrud and add the following fields to the class.
### Example class :
```php
<?php
use Jgauthi\Component\Database\AbstractCrud;

class YourClass Extends AbstractCrud
{
  # The table you want to perform the database actions on
  const TABLE = 'persons';

  # Primary Key of the table
  const PK = 'Id';
}
```

## EasyCRUD in action.
### Creating a new person
```php
<?php
use Jgauthi\Component\Database\Db;

// First we"ll have create the instance of the class
$db = new db($dbhost, $dbuser, $dbpass, $dbname, $dbport = 3306);
$person = new person($db);

// Create new person
$person->Firstname  = 'Kona';
$person->Age        = 20;
$person->Sex        = 'F';
$created            = $person->Create();

//  Or give the bindings to the constructor
$person  = new person($db, ['Firstname' => 'Kona', 'age' => '20', 'sex' => 'F']);
$created = $person->Create();

// SQL Equivalent: "INSERT INTO persons (Firstname,Age,Sex) VALUES ('Kona','20','F')"
```

### Deleting a person
```php
<?php
// Delete person
$person->Id  = 17;
$deleted     = $person->Delete();

// Shorthand method, give id as parameter
$deleted     = $person->Delete(17);

// SQL Equivalent: "DELETE FROM persons WHERE Id = 17 LIMIT 1"
```

### Saving person's data
```php
<?php
// Update personal data
$person->Firstname = 'John';
$person->Age  = 20;
$person->Sex  = 'F';
$person->Id   = 4;
// Returns affected rows
$saved = $person->save();

//  Or give the bindings to the constructor
$person = new person($db, ['Firstname' => 'John', 'age' => '20', 'sex' => 'F', 'Id' => '4']);
$saved = $person->save();

// SQL Equivalent: "UPDATE persons SET Firstname = 'John',Age = 20, Sex = 'F' WHERE Id= 4"
```

### Finding a person
```php
<?php
// Find person
$person->Id = 1;
$person->find();

echo $person->Firstname;
// Johny

// Shorthand method, give id as parameter
$person->find(1);

// SQL Equivalent: "SELECT * FROM persons WHERE Id = 1"
```
### Getting all the persons
```php
<?php
// Finding all person
// Arg1: Order (optional)
// Arg2: Display primary key in array keys result (optional, false by default)
$persons = $person->all(['lastname' => 'ASC', 'firstname' => 'ASC'], false);

// SQL Equivalent: "SELECT * FROM persons ORDER BY lastname ASC, firstname ASC"
```

### Check fields
```php
<?php
class Person Extends AbstractCrud
{
  const TABLE = 'persons';
  const PK = 'Id';

	public function __construct(Db &$db)
	{
		parent::__construct($db);

		$this->list_fields_table = [
			self::PK,
			'Firstname',
			'Age',
			'Sex',
		];

		$this->required_fields = [
		 	'Firstname',
		];
	}
}

// First we"ll have create the instance of the class
$db = new db($dbhost, $dbuser, $dbpass, $dbname);
$person = new person($db);

// Create new person
$person->Sex = 'F';

// Not working: field birthcity can't be use
$person->birthcity = 'Paris';
if($person->check_fields())
	$person->create();

// Not working: required fields not defined: Firstname
if($person->check_fields())
	$person->create();

// working
$person->Firstname = "John";
if($person->check_fields())
	$person->create();

// Check with primary key if person exist
$person->Id = 6;
if($person->exist())
	 echo 'The person with ID: "6" exist';
else echo 'The person with ID: "6" don\'t exist';
```

### Use table name / primary key outside the entity
```php
<?php
$result = $db->query('SELECT '. Person::PK .', Firstname, age, sex FROM '. Person::TABLE);
```

## Copyright and license
Code released under Beerware