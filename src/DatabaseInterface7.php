<?php

declare(strict_types=1);
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
    public const PARAM_IS_NULL = 0;

    /**
     * Param is int.
     */
    public const PARAM_IS_INT = 1;

    /**
     * Param is string.
     */
    public const PARAM_IS_STR = 2;

    /**
     * Param is blob.
     */
    public const PARAM_IS_BLOB = 3;

    /**
     * Param is boolean.
     */
    public const PARAM_IS_BOOL = 5;

    /**
     * Prepares a SQL statement for execution and returns a statement object.
     * @return StatementInterface
     */
    public function prepare(string $statement, array $driver_options = []);

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
     * @param string $aName name of the sequence object (if any) from which the ID should be returned
     */
    public function getLastInsertId($aName = null): string;

    /**
     * Escapes/quotes a parameter for use in a query.
     * @param  mixed  $aParam     the parameter to be quoted
     * @param  int    $aParamType data type hint for drivers
     * @return string a quoted string that is theoretically safe to pass into an SQL statement
     */
    public function escape($aParam, $aParamType = self::PARAM_IS_STR);

    /**
     * Adds appropriate quotes to an identifier so it can be safely used in an SQL statement.
     * @param mixed $aIdentifier the parameter to be quoted
     */
    public function quoteIdentifier($aIdentifier): string;

    /**
     * List of tables in a database.
     * @param string $aDatabase database/schema name
     */
    public function getTables($aDatabase = null): array;

    /**
     * List of columns (and types) in a table.
     * @param  string                $aDatabase database/schema name
     * @param  string                $aTable    table name
     * @return ColumnMetaInterface[]
     */
    public function getTableColumns($aDatabase, $aTable): array;

    /**
     * Primary key column(s).
     * @param string $aDatabase database/schema name
     * @param string $aTable    table name
     */
    public function getPrimaryKey($aDatabase, $aTable): array;

    /**
     * Non-PK indexes.
     * @param string $aDatabase database/schema name
     * @param string $aTable    table name
     */
    public function getIndexes($aDatabase, $aTable): array;

    /**
     * Get MySQL table definition.
     * @param string $aDatabase       database/schema name
     * @param string $aTable          table name
     * @param bool   $aSkipUnusedCols whether to skip unused columns
     */
    public function getMySQLTableDef($aDatabase, $aTable, $aSkipUnusedCols = true): string;

    /**
     * Get Oracle table definition.
     * @param string $aDatabase       database/schema name
     * @param string $aTable          table name
     * @param bool   $aSkipUnusedCols whether to skip unused columns
     */
    public function getOracleTableDef($aDatabase, $aTable, $aSkipUnusedCols = true): string;
}
