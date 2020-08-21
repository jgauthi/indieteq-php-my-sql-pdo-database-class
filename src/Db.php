<?php
/**
 *  DB - A simple database class (PHP 5.6 version)
 *
 * @author      Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
 * @contrib     jgauthi (https://github.com/jgauthi)
 * @git         https://github.com/jgauthi/indieteq-php-my-sql-pdo-database-class
 *
 * @version     1.3
 */

namespace Jgauthi\Component\Database;

use InvalidArgumentException;
use PDO;

class Db
{
    // @object, The PDO object
    private $pdo;

    // @object, PDO statement object
    private $sQuery;

    // @object, Object for logging exceptions
    private $debug = false;

    // @array, The parameters of the SQL query
    private $parameters;
    public $table = ['variable' => 'variable'];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * This method makes connection to the database
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $dbname
     * @param int $port 3306 value by default
     * @return self
     * @throws PDOException
     */
    static public function init($host, $user, $pass, $dbname, $port = 3306)
    {
        $pdo = new PDO("mysql:dbname={$dbname};host={$host};port={$port}", $user, $pass, [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8 COLLATE utf8_unicode_ci',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Log any exceptions on Fatal error
            // Disable emulation of prepared statements, use REAL prepared statements instead
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return new self($pdo);
    }

    /**
     * @param string $inifile Ini file, require values
        host=localhost
        user=root
        password=password
        dbname=database
        port=3306 (optional)

     * @return self
     * @throws PDOException
     */
    static public function initByIni($inifile)
    {
        if (!is_readable($inifile)) {
            throw new InvalidArgumentException("The ini file {$inifile} doesn't exists or not readable.");
        }

        $ini = parse_ini_file($inifile);
        $valuesNoExist = [];
        foreach (['host', 'user', 'password', 'dbname'] as $valueRequired) {
            if (!isset($ini[$valueRequired])) {
                $valuesNoExist[] = $valueRequired;
            }
        }

        if (!empty($valuesNoExist)) {
            throw new InvalidArgumentException(
                "Fields missing on ini file {$inifile}, please complete: ".
                implode(', ', $valuesNoExist)
            );
        }

        return self::init($ini['host'], $ini['user'], $ini['password'], $ini['dbname'], ((!empty($ini['port'])) ? $ini['port'] : 3306));
    }

    // You can use this little method if you want to close the PDO connection
    public function CloseConnection(): void
    {
        // Set the PDO object to null to close the connection
        // http://www.php.net/manual/en/pdo.connections.php
        $this->pdo = null;
    }

    /**
     * Every method which needs to execute a SQL query uses this method.
     *
     *	1. If not connected, connect to the database.
     *	2. Prepare Query.
     *	3. Parameterize Query.
     *	4. Execute Query.
     *	5. On exception : Write Exception into the log + SQL query.
     *	6. Reset the Parameters.
     *
     * @param string $query
     * @param array $parameters
     * @return bool
     */
    private function initQuery(string $query, ?array $parameters = []): bool
    {
        try {
            // Prepare query
            $this->sQuery = $this->pdo->prepare($query);

            // Add parameters to the parameter array
            $this->bindMore($parameters);

            // Bind parameters
            if (!empty($this->parameters)) {
                foreach ($this->parameters as $param => $value) {
                    if (is_int($value[1])) {
                        $type = PDO::PARAM_INT;
                    } elseif (is_bool($value[1])) {
                        $type = PDO::PARAM_BOOL;
                    } elseif (is_null($value[1])) {
                        $type = PDO::PARAM_NULL;
                    } else {
                        $type = PDO::PARAM_STR;

                        if ($value[1] instanceof \DateTimeInterface) {
                            $value[1] = $value[1]->format('Y-m-d H:i:s');
                        }
                    }
                    // Add type when binding the values to the column
                    $this->sQuery->bindValue($value[0], $value[1], $type);
                }
            }

            // Execute SQL
            $this->sQuery->execute();

        } catch (PDOException $e) {
            $msg = '[Mysql error] '.$e->getMessage();
            if ($this->debug) {
                $msg .= sprintf(', query: "%s"', $query);
            }

            trigger_error($msg);
            return false;

        } finally {
            // Reset the parameters
            $this->parameters = [];
        }

        return true;
    }

    /**
     * Return PDO var: to use with other library.
     * @return PDO
     */
    public function getPdoVar()
    {
        return $this->pdo;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug = true)
    {
        $this->debug = boolval($debug);
    }

    //-- Mysql Requests -------------------------------------------------------------------------------

    /**
     * Add the parameter to the parameter array
     *
     * @param string $para
     * @param string $value
     * @return self
     */
    public function bind($para, $value)
    {
        $this->parameters[sizeof($this->parameters)] = [':'.$para, $value];
        return $this;
    }

    /**
     * Add more parameters to the parameter array
     *
     * @param array $parray
     * @return self
     */
    public function bindMore($parray)
    {
        if (empty($this->parameters) && is_array($parray)) {
            $columns = array_keys($parray);
            foreach ($columns as $i => &$column) {
                $this->bind($column, $parray[$column]);
            }
        }

        return $this;
    }

    /**
     *  If the SQL query  contains a SELECT or SHOW statement it returns an array containing all of the result set row
     *	If the SQL statement is a DELETE, INSERT, or UPDATE statement it returns the number of affected rows.
     *
     * @param  string $query
     * @param  array  $params
     * @param  int    $fetchmode
     *
     * @return int|array|null
     */
    public function query($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
    {
        $query = trim(str_replace("\r", ' ', $query));

        if (!$this->initQuery($query, $params)) {
            return false;
        }

        $rawStatement = explode(' ', preg_replace("/\s+|\t+|\n+/", ' ', $query));

        // Which SQL statement is used
        $statement = strtolower($rawStatement[0]);

        if (in_array($statement, ['select', 'show'])) {
            return $this->sQuery->fetchAll($fetchmode);
        } elseif (in_array($statement, ['insert', 'replace', 'update', 'delete'])) {
            return $this->sQuery->rowCount();
        } else {
            return null;
        }
    }

    /**
     * Returns the last inserted id.
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Return nb rows from last query.
     *
     * @return int|null
     */
    public function numRows()
    {
        if (is_null($this->sQuery)) {
            return null;
        }

        $nb = $this->sQuery->rowCount();

        if (is_numeric($nb) && false !== $nb) {
            return $nb;
        }

        return null;
    }

    /**
     * Starts the transaction.
     *
     * @return boolean, true on success or false on failure
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Execute Transaction.
     *
     * @return boolean, true on success or false on failure
     */
    public function executeTransaction()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback of Transaction.
     *
     * @return boolean, true on success or false on failure
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Returns an array which represents a column from the result set.
     *
     * @param  string $query
     * @param  array  $params
     *
     * @return array
     */
    public function column($query, $params = null)
    {
        $this->initQuery($query, $params);
        $Columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);

        $column = null;

        foreach ($Columns as $cells) {
            $column[] = $cells[0];
        }

        return $column;
    }

    /**
     * Returns an array which represents a row from the result set.
     *
     * @param  string $query
     * @param  array  $params
     * @param  int    $fetchmode
     *
     * @return array
     */
    public function row($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
    {
        $this->initQuery($query, $params);
        $result = $this->sQuery->fetch($fetchmode);
        $this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
        return $result;
    }

    /**
     * Returns the value of one single field/column.
     *
     * @param  string $query
     * @param  array  $params
     *
     * @return string
     */
    public function single($query, $params = null)
    {
        $this->initQuery($query, $params);
        $result = $this->sQuery->fetchColumn();
        $this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
        return $result;
    }

    //-- Variable manager stored in base for custom project -------------------------------------------
    /*
        CREATE TABLE IF NOT EXISTS `variable` (
          `name` varchar(100) NOT NULL,
          `value` text,
          `serialize` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `dateUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Various variables and options for the application';

        ALTER TABLE `variable` ADD PRIMARY KEY (`name`);
    */
    /**
     * @param string $var_name
     * @param mixed|null $value_defaut
     * @return mixed|null
     */
    public function variable_get($var_name, $value_defaut = null)
    {
        $params = ['name' => $var_name];
        $result = $this->query("
            SELECT value, serialize
            FROM `{$this->table['variable']}`
            WHERE name = :name
            LIMIT 1
        ", $params);

        if (isset($result[0]['value'])) {
            return ($result[0]['serialize']) ? unserialize($result[0]['value']) : $result[0]['value'];
        } else {
            return $value_defaut;
        }
    }

    /**
     * @param string $var_name
     * @param mixed $value
     * @return array|int|null
     */
    public function variable_save($var_name, $value)
    {
        $params = [
            'name' => $var_name,
            'value' => $value,
            'serialize' => 0,
        ];

        if (is_array($value) || is_object($value)) {
            $params['serialize'] = 1;
            $params['value'] = serialize($value);
        }

        $sql = "REPLACE INTO `{$this->table['variable']}`
                         SET `name` = :name,
                             `value` = :value,
                             `serialize` = :serialize
        ";

        return $this->query($sql, $params);
    }

    /**
     * @param string $var_name
     * @return array|int|null
     */
    public function variable_delete($var_name)
    {
        return $this->query("DELETE FROM `{$this->table['variable']}` WHERE name = :name LIMIT 1", ['name' => $var_name]);
    }
}