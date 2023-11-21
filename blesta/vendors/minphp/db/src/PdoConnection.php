<?php
namespace Minphp\Db;

use PDO;
use PDOStatement;
use RuntimeException;
use InvalidArgumentException;

/**
 * Establishes and maintains a connection to one or more PDO resources.
 */
class PdoConnection
{
    /**
     * @var array Default PDO attribute settings
     */
    protected $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_STRINGIFY_FETCHES => false
    );

    /**
     * @var PDO PDO connection
     */
    private $connection;

    /**
     * @var array An array of all database connections established
     */
    private static $connections = array();

    /**
     * @var array An array of all database connection info (used to find a matching connection)
     */
    private static $dbInfos = array();

    /**
     * @var array An array of database info for this instance
     */
    private $dbInfo;

    /**
     * @var PDOStatement PDO Statement
     */
    private $statement;

    /**
     * @var mixed Fetch Mode the PDO:FETCH_* constant (int)
     */
    private $fetchMode = PDO::FETCH_OBJ;

    /**
     * @var boolean Reuse existing connection if available
     */
    private $reuseConnection = true;

    /**
     * Creates a new Model object that establishes a new PDO connection using
     * the given database info, or the default configured info set in the database
     * config file if no info is given
     *
     * @param array $dbInfo Database information for this connection:
     *  - driver DB Driver
     *  - host DB Host
     *  - database DB name
     *  - user DB User
     *  - pass DB Password
     *  - port DB Port
     *  - options Array of PDO connection options
     */
    public function __construct(array $dbInfo)
    {
        $this->dbInfo = $dbInfo;
    }

    /**
     * Attemps to initialize a connection to the database if one does not already exist
     *
     * @return PDO The PDO connection
     */
    public function connect()
    {
        $connection = $this->getConnection();
        if ($connection instanceof PDO) {
            return $connection;
        }

        // Attempt to reuse an existing connection if one exists that matches this connection
        if ($this->reuseConnection
            && ($key = array_search($this->dbInfo, self::$dbInfos)) !== false
        ) {
            $connection = self::$connections[$key];
            $this->setConnection($connection);
            return $connection;
        }

        $dsn = $this->makeDsn($this->dbInfo);
        $username = isset($this->dbInfo['user'])
            ? $this->dbInfo['user']
            : null;
        $password = isset($this->dbInfo['pass'])
            ? $this->dbInfo['pass']
            : null;
        $options = (array) (
                isset($this->dbInfo['options'])
                ? $this->dbInfo['options']
                : null
            )
            + $this->options;

        $connection = $this->makeConnection($dsn, $username, $password, $options);

        self::$connections[] = $connection;
        self::$dbInfos[] = $this->dbInfo;
        $this->setConnection($connection);

        return $connection;
    }

    /**
     * Set whether or not to reuse an existing connection
     *
     * @param boolean $enable True to reuse an existing matching connection if available
     * @return PdoConnection
     */
    public function reuseConnection($enable)
    {
        $this->reuseConnection = $enable;
        return $this;
    }

    /**
     * Sets the fetch mode to the given value, returning the old value
     *
     * @param int $fetchMode The PDO:FETCH_* constant (int) to fetch records
     */
    public function setFetchMode($fetchMode)
    {
        $cur = $this->fetchMode;
        $this->fetchMode = $fetchMode;
        return $cur;
    }

    /**
     * Get the last inserted ID
     *
     * @param string $name The name of the sequence object from which the ID should be returned
     * @return string The last ID inserted, if available
     */
    public function lastInsertId($name = null)
    {
        return $this->connect()->lastInsertId($name);
    }

    /**
     * Sets the given value to the given attribute for this connection
     *
     * @param long $attribute The attribute to set
     * @param int $value The value to assign to the attribute
     * @return PdoConnection
     */
    public function setAttribute($attribute, $value)
    {
        $this->connect()->setAttribute($attribute, $value);
        return $this;
    }

    /**
     * Query the Database using the given prepared statement and argument list
     *
     * @param string $sql The SQL to execute
     * @param string $... Bound parameters [$param1, $param2, ..., $paramN]
     * @return PDOStatement The resulting PDOStatement from the execution of this query
     */
    public function query($sql)
    {
        $params = func_get_args();
        // Shift the SQL parameter off of the list
        array_shift($params);

        // If 2nd param is an array, use it as the series of params, rather than
        // the rest of the param list
        if (isset($params[0]) && is_array($params[0])) {
            $params = $params[0];
        }

        $this->connect();

        // Store this statement in our PDO object for easy use later
        $this->statement = $this->prepare($sql, $this->fetchMode);

        // Execute the query
        $this->statement->execute($params);

        // Return the statement
        return $this->statement;
    }

    /**
     * Prepares an SQL statement to be executed by the PDOStatement::execute() method.
     * Useful when executing the same query with different bound parameters.
     *
     * @param string $sql The SQL statement to prepare
     * @param int $fetchMode The PDO::FETCH_* constant
     * @return PDOStatement The resulting PDOStatement from the preparation of this query
     * @see PDOStatement::execute()
     */
    public function prepare($sql, $fetchMode = null)
    {
        if ($fetchMode === null) {
            $fetchMode = $this->fetchMode;
        }

        $this->statement = $this->connect()->prepare($sql);
        // Set the default fetch mode for this query
        $this->statement->setFetchMode($fetchMode);

        return $this->statement;
    }

    /**
     * Begin a transaction
     *
     * @return boolean True if the transaction was successfully opened, false otherwise
     */
    public function begin()
    {
        return $this->connect()->beginTransaction();
    }

    /**
     * Rolls back and closes the transaction
     *
     * @return boolean True if the transaction was successfully rolled back and closed, false otherwise
     */
    public function rollBack()
    {
        return $this->connect()->rollBack();
    }

    /**
     * Commits a transaction
     *
     * @return boolean True if the transaction was successfully commited and closed, false otherwise
     */
    public function commit()
    {
        return $this->connect()->commit();
    }

    /**
     * Returns the connection's PDO object if a connection has been established, null otherwise.
     *
     * @return PDO The PDO connection object, null if no connection exists
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the PDO connection to use
     *
     * @param PDO $connection
     * @return PdoConnection
     */
    public function setConnection(PDO $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get the number of rows affected by the last query
     *
     * @param PDOStatement $statement The statement to count affected rows on,
     * if null the last query() statement will be used.
     * @return int The number of rows affected by the previous query
     * @throws RuntimeException Thrown when not PDOStatement available
     */
    public function affectedRows(PDOStatement $statement = null)
    {
        if ($statement === null) {
            $statement = $this->statement;
        }

        if (!($statement instanceof PDOStatement)) {
            throw new RuntimeException('Can not get affectedRows without a PDOStatement.');
        }

        return $statement->rowCount();
    }

    /**
     * Build a DSN string using the given array of parameters
     *
     * @param array $db An array of parameters
     * @return string The DSN string
     * @throws InvalidArgumentException Thrown when $db contains invalid parameters
     */
    public function makeDsn(array $db)
    {
        if (!isset($db['driver']) || !isset($db['database']) || !isset($db['host'])) {
            throw new InvalidArgumentException(
                sprintf('Required %s', "array('driver'=>,'database'=>,'host'=>)")
            );
        }

        return isset($db['port'])
            ? $db['driver'] . ':host=' . $db['host'] . ';dbname=' . $db['database'] . ';port=' . $db['port']
            : $db['driver'] . ':host=' . $db['host'] . ';dbname=' . $db['database'];
    }

    /**
     * Establish a new PDO connection using the given array of information. If
     * a connection already exists, no new connection will be created.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return \PDO The connection
     */
    private function makeConnection($dsn, $username, $password, $options)
    {
        return new PDO($dsn, $username, $password, $options);
    }
}
