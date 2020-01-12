<?php
/**
 * Database Access Layer.
 * @author Doug Wright
 */

namespace DVDoug\DB;

/**
 * Represents a connection to a database server.
 * @author Doug Wright
 */
interface DatabaseInterface
{
    /**
     * Param is null.
     */
    const PARAM_IS_NULL = 0;

    /**
     * Param is int.
     */
    const PARAM_IS_INT = 1;

    /**
     * Param is string.
     */
    const PARAM_IS_STR = 2;

    /**
     * Param is blob.
     */
    const PARAM_IS_BLOB = 3;

    /**
     * Param is boolean.
     */
    const PARAM_IS_BOOL = 5;

    /**
     * Prepares a SQL statement for execution and returns a statement object.
     * @param  string             $aSQL
     * @return StatementInterface
     */
    public function prepare($aSQL);

    /**
     * Initiates a transaction.
     * @return bool
     */
    public function beginTransaction();

    /**
     * Commits a transaction.
     * @return bool
     */
    public function commit();

    /**
     * Rolls back a transaction.
     * @return bool
     */
    public function rollBack();

    /**
     * Checks if inside a transaction.
     * @return bool
     */
    public function inTransaction();

    /**
     * Executes an SQL statement, returning the result set if any as a StatementInterface object.
     * @param  string                  $aSQL the SQL statement to execute
     * @return StatementInterface|bool
     */
    public function query($aSQL);

    /**
     * Returns the ID of the last inserted row or sequence value.
     * @param  string $aName name of the sequence object (if any) from which the ID should be returned
     * @return string
     */
    public function getLastInsertId($aName = null);

    /**
     * Escapes/quotes a parameter for use in a query.
     * @param  mixed  $aParam     the parameter to be quoted
     * @param  int    $aParamType data type hint for drivers
     * @return string a quoted string that is theoretically safe to pass into an SQL statement
     */
    public function escape($aParam, $aParamType = self::PARAM_IS_STR);

    /**
     * Adds appropriate quotes to an identifier so it can be safely used in an SQL statement.
     * @param  mixed  $aIdentifier the parameter to be quoted
     * @return string
     */
    public function quoteIdentifier($aIdentifier);

    /**
     * List of tables in a database.
     * @param  string $aDatabase database/schema name
     * @return array
     */
    public function getTables($aDatabase = null);

    /**
     * List of columns (and types) in a table.
     * @param  string                $aDatabase database/schema name
     * @param  string                $aTable    table name
     * @return ColumnMetaInterface[]
     */
    public function getTableColumns($aDatabase, $aTable);

    /**
     * Primary key column(s).
     * @param  string $aDatabase database/schema name
     * @param  string $aTable    table name
     * @return array
     */
    public function getPrimaryKey($aDatabase, $aTable);

    /**
     * Non-PK indexes.
     * @param  string $aDatabase database/schema name
     * @param  string $aTable    table name
     * @return array
     */
    public function getIndexes($aDatabase, $aTable);

    /**
     * Get MySQL table definition.
     * @param  string $aDatabase       database/schema name
     * @param  string $aTable          table name
     * @param  bool   $aSkipUnusedCols whether to skip unused columns
     * @return string
     */
    public function getMySQLTableDef($aDatabase, $aTable, $aSkipUnusedCols = true);

    /**
     * Get Oracle table definition.
     * @param  string $aDatabase       database/schema name
     * @param  string $aTable          table name
     * @param  bool   $aSkipUnusedCols whether to skip unused columns
     * @return string
     */
    public function getOracleTableDef($aDatabase, $aTable, $aSkipUnusedCols = true);
}
