<?php
/**
 * Database Access Layer.
 * @author Doug Wright
 */

namespace DVDoug\DB;

/**
 * PDO-backed database connection (common parts).
 * @author Doug Wright
 */
abstract class PDODatabase extends \PDO implements DatabaseInterface
{
    /**
     * Character to use when quoting identifiers.
     */
    const IDENTIFIER_OPENQUOTE = '"';

    /**
     * Character to use when quoting identifiers.
     */
    const IDENTIFIER_CLOSEQUOTE = '"';

    /**
     * Constructor.
     * @param string $aDSN
     * @param string $aUsername
     * @param string $aPassword
     */
    public function __construct($aDSN, $aUsername, $aPassword, array $aDriverOptions = null)
    {
        parent::__construct($aDSN, $aUsername, $aPassword, $aDriverOptions);
        self::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['\DVDoug\DB\PDOStatement']);
    }

    /**
     * Executes an SQL statement, returning the result set if any as a StatementInterface object.
     * @param  string                  $aSQL the SQL statement to execute
     * @return StatementInterface|bool
     */
    public function query($aSQL)
    {
        return parent::query($aSQL);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     * @param  string $aName name of the sequence object (if any) from which the ID should be returned
     * @return string
     */
    public function getLastInsertId($aName = null)
    {
        return parent::lastInsertID($aName);
    }

    /**
     * Escapes/quotes a parameter for use in a query.
     *
     * @param mixed $aParam     the parameter to be quoted
     * @param int   $aParamType data type hint for drivers
     *
     * @throws \RuntimeException
     * @return string            a quoted string that is theoretically safe to pass into an SQL statement
     */
    public function escape($aParam, $aParamType = DatabaseInterface::PARAM_IS_STR)
    {
        switch ($aParamType) {
            case self::PARAM_IS_INT:
                if (is_int($aParam) || ctype_digit($aParam)) {
                    return (int) $aParam;
                } else {
                    throw new \RuntimeException("Parameter {$aParam} is not an integer");
                }
                break;

            default:
                return parent::quote($aParam, $aParamType);
        }
    }

    /**
     * Adds appropriate quotes to an identifier so it can be safely used in an SQL statement.
     * @param  mixed  $aIdentifier the parameter to be quoted
     * @return string
     */
    public function quoteIdentifier($aIdentifier)
    {
        return static::IDENTIFIER_OPENQUOTE . $aIdentifier . static::IDENTIFIER_CLOSEQUOTE;
    }

    /**
     * Get MySQL table definition.
     * @param  string $aDatabase       database/schema name
     * @param  string $aTable          table name
     * @param  bool   $aSkipUnusedCols whether to skip unused columns
     * @return string
     */
    public function getMySQLTableDef($aDatabase, $aTable, $aSkipUnusedCols = true)
    {
        $table = strtolower($aTable);

        $columns = $this->getTableColumns($aDatabase, $aTable);

        $colDefs = [];

        foreach ($columns as $columnName => $column) {
            if ($aSkipUnusedCols && $column->getDistinctValueCount() <= 1) {
                unset($columns[$columnName]);
                continue;
            }

            $colDefs[] = $column->getMySQLColumnDef();
        }

        $primaryKey = $this->getPrimaryKey($aDatabase, $aTable);
        $indexes = $this->getIndexes($aDatabase, $aTable);

        $tableDef = "CREATE TABLE `{$table}` (" . "\n";
        $tableDef .= implode(',' . "\n", $colDefs);

        if ($primaryKey) {
            $length = 0;
            foreach ($primaryKey as $primaryCol) {
                $length += $columns[$primaryCol]->getLength();
            }
            if ($length <= 191) { //skip index if too long for MySQL
                $tableDef .= ',' . "\n" . "\n";
                $tableDef .= 'PRIMARY KEY (';
                $tableDef .= implode(', ' . "\n", array_map(function ($c) {return '`' . strtolower($c) . '`'; }, $primaryKey));
                $tableDef .= ')';
            }
        }

        if ($indexes) {
            foreach ($indexes as $indexName => $indexColumns) {
                $length = 0;
                foreach ($indexColumns as &$col) {
                    if (!in_array($col, array_keys($columns))) { //skip index if it includes a skipped column
                        continue 2;
                    }

                    $length += $columns[$col]->getLength();
                    if ($length > 191) { //skip index if too long for MySQL
                        continue 2;
                    }

                    if (preg_match('/(BLOB|TEXT)$/', $columns[$col]->getMySQLType())) {
                        continue 2;
                    }
                }
                $tableDef .= ',' . "\n";
                $tableDef .= 'KEY `' . strtolower($indexName) . '` (';
                $tableDef .= implode(', ', array_map(function ($c) {return '`' . strtolower($c) . '`'; }, $indexColumns));
                $tableDef .= ')';
            }
        }
        $tableDef .= ') ENGINE=InnoDB ROW_FORMAT=COMPRESSED';

        return $tableDef;
    }

    /**
     * Get Oracle table definition.
     * @param  string $aDatabase       database/schema name
     * @param  string $aTable          table name
     * @param  bool   $aSkipUnusedCols whether to skip unused columns
     * @return string
     */
    public function getOracleTableDef($aDatabase, $aTable, $aSkipUnusedCols = true)
    {
        $table = strtolower($aTable);

        $columns = $this->getTableColumns($aDatabase, $aTable);

        $colDefs = [];

        foreach ($columns as $columnName => $column) {
            if ($aSkipUnusedCols && $column->getDistinctValueCount() <= 1) {
                unset($columns[$columnName]);
                continue;
            }

            $colDefs[] = $column->getOracleColumnDef();
        }

        $primaryKey = $this->getPrimaryKey($aDatabase, $aTable);
        $indexes = $this->getIndexes($aDatabase, $aTable);

        $tableDef = "CREATE TABLE `{$table}` (" . "\n";
        $tableDef .= implode(',' . "\n", $colDefs);

        if ($primaryKey) {
            $tableDef .= ',' . "\n" . "\n";
            $tableDef .= 'PRIMARY KEY (';
            $tableDef .= implode(', ' . "\n", array_map(function ($c) {return '"' . strtolower($c) . '"'; }, $primaryKey));
            $tableDef .= ')';
        }

        if ($indexes) {
            foreach ($indexes as $indexName => $indexColumns) {
                foreach ($indexColumns as &$col) {
                    if (!in_array($col, array_keys($columns))) { //skip index if it includes a skipped column
                        continue 2;
                    }
                }
                $tableDef .= ',' . "\n";
                $tableDef .= 'KEY `' . strtolower($indexName) . '` (';
                $tableDef .= implode(', ', array_map(function ($c) {return '"' . strtolower($c) . '"'; }, $indexColumns));
                $tableDef .= ')';
            }
        }
        $tableDef .= ')';

        return $tableDef;
    }
}
