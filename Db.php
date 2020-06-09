<?php
/**
 *  DB - A simple database class (PHP 7.4+ version)
 *
 * @author		Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
 * @contrib     jgauthi (https://github.com/jgauthi)
 * @git 		https://github.com/jgauthi/indieteq-php-my-sql-pdo-database-class
 *
 * @version     2.0
 */

namespace Jgauthi\Component\Database;

use PDO;
use PDOStatement;

class Db
{
    private ?PDO $pdo;
    private PDOStatement $sQuery;
    private array $settings;
    private bool $bConnected = false;
    private bool $debug = false;
    private array $parameters;
    public array $table = [];

    public function __construct(string $host, string $user, string $pass, string $dbname, int $port = 3306)
    {
        $this->settings = [
            'host' 		=> $host,
            'user' 		=> $user,
            'password' 	=> $pass,
            'dbname' 	=> $dbname,
            'port' 		=> $port,
        ];

        $this->Connect();
        $this->parameters = [];
        $this->table['variable'] = 'variable';
    }


	/**
	 *  This method makes connection to the database.
	 *
	 *	1. Reads the database settings from a ini file.
	 *	2. Puts  the ini content into the settings array.
	 *	3. Tries to connect to the database.
	 *	4. If connection failed, exception is displayed and a log file gets created.
	 */
    private function Connect(): bool
    {
        $dsn = "mysql:dbname={$this->settings['dbname']};host={$this->settings['host']};port={$this->settings['port']}";
        try {
            // Read settings from INI file, set UTF8
            $this->pdo = new PDO($dsn, $this->settings['user'], $this->settings['password'], [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ]);

            // We can now log any exceptions on Fatal error.
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Disable emulation of prepared statements, use REAL prepared statements instead.
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Connection succeeded, set the boolean to true.
            $this->bConnected = true;

        } catch (PDOException $e) {
            trigger_error("[Mysql error] {$e->getMessage()}");
        }

        return $this->bConnected;
    }

    /*
     *   You can use this little method if you want to close the PDO connection
     *
     */
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
	 */
    private function Init(string $query, ?array $parameters = []): bool
    {
        // Connect to database
        if (!$this->bConnected) {
            $this->Connect();
        }
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

            return !trigger_error($msg);

        } finally {
            // Reset the parameters
            $this->parameters = [];
        }

        return true;
    }

    /**
     * Return PDO var: to use with other library.
     */
    public function getPdoVar(): PDO
    {
        return $this->pdo;
    }

    public function setDebug(bool $debug = true): self
    {
        $this->debug = $debug;
        return $this;
    }

    //-- Mysql Requests -------------------------------------------------------------------------------

    /**
     * Add the parameter to the parameter array
     */
    public function bind(string $para, $value): self
    {
        $this->parameters[sizeof($this->parameters)] = [':'.$para, $value];
        return $this;
    }

    /**
     * Add more parameters to the parameter array
     */
    public function bindMore(?array $parray): self
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
     *  If the SQL query contains a SELECT or SHOW statement it returns an array containing all of the result set row
     *	If the SQL statement is a DELETE, INSERT, or UPDATE statement it returns the number of affected rows.
     *
     * @return int|array|null
     */
    public function query(string $query, ?array $params = null, int $fetchmode = PDO::FETCH_ASSOC)
    {
        $query = trim(str_replace("\r", ' ', $query));

        if (!$this->Init($query, $params)) {
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
     */
    public function lastInsertId(): int
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Return nb rows from last query.
     */
    public function numRows(): ?int
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
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Execute Transaction.
     */
    public function executeTransaction(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback of Transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Returns an array which represents a column from the result set.
     */
    public function column(string $query, ?array $params = null): array
    {
        $this->Init($query, $params);
        $Columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);

        $column = null;

        foreach ($Columns as $cells) {
            $column[] = $cells[0];
        }

        return $column;
    }

    /**
     * Returns an array which represents a row from the result set.
     */
    public function row(string $query, ?array $params = null, int $fetchmode = PDO::FETCH_ASSOC): array
    {
        $this->Init($query, $params);
        $result = $this->sQuery->fetch($fetchmode);
        $this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
        return $result;
    }

    /**
     * Returns the value of one single field/column.
     */
    public function single(string $query, ?array $params = null): string
    {
        $this->Init($query, $params);
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
    public function variable_get(string $var_name, $value_defaut = null)
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
    public function variable_save(string $var_name, $value)
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
    public function variable_delete(string $var_name)
    {
        return $this->query("DELETE FROM `{$this->table['variable']}` WHERE name = :name LIMIT 1", ['name' => $var_name]);
    }
}
