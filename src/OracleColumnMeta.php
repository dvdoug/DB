<?php

declare(strict_types=1);
/**
 * Database Access Layer.
 * @author Doug Wright
 */

namespace DVDoug\DB;

use function abs;
use function bccomp;
use Exception;
use function max;
use function sprintf;
use function strpos;

/**
 * Metadata about a database column.
 * @author Doug Wright
 */
class OracleColumnMeta implements ColumnMetaInterface
{
    use DDLGeneration;

    /**
     * Database connection.
     */
    protected DatabaseInterface $connection;

    /**
     * Database name.
     */
    protected string $database;

    /**
     * Table name.
     */
    protected string $table;

    /**
     * Column name.
     */
    protected string $name;

    /**
     * Column type.
     */
    protected string $type;

    /**
     * Column length.
     */
    protected int $length;

    /**
     * Column precision.
     */
    protected ?int $precision;

    /**
     * Column scale.
     */
    protected ?int $scale;

    /**
     * Column nullable?
     */
    protected bool $isNullable;

    /**
     * Column max value.
     */
    protected ?string $maxValue;

    /**
     * Column min value.
     */
    protected ?string $minValue;

    /**
     * Number of distinct values.
     */
    protected int $distinctValues;

    /**
     * Constructor.
     * @param DatabaseInterface $aConnection connection to database
     * @param string            $aDatabase   database/schema name
     * @param string            $aTable      table name
     * @param string            $aColumnName column name
     */
    public function __construct(DatabaseInterface $aConnection, $aDatabase, $aTable, $aColumnName)
    {
        $this->connection = $aConnection;
        $this->database = $aDatabase;
        $this->table = $aTable;
        $this->name = $aColumnName;

        /*
         * Basic metadata from the schema
         */
        $statement = $this->connection->prepare('SELECT OWNER AS TABLE_SCHEMA,
                                                      TABLE_NAME,
                                                      COLUMN_NAME,
                                                      DATA_TYPE,
                                                      DATA_LENGTH,
                                                      DATA_PRECISION,
                                                      DATA_SCALE,
                                                      NULLABLE,
                                                      CHAR_LENGTH
                                               FROM ALL_TAB_COLUMNS
                                               WHERE OWNER = :owner
                                                     AND TABLE_NAME = :table_name
                                                     AND COLUMN_NAME = :column_name');
        $statement->bindParamToValue(':owner', $this->database);
        $statement->bindParamToValue(':table_name', $this->table);
        $statement->bindParamToValue(':column_name', $this->name);
        $statement->execute();

        $meta = $statement->fetchAssoc(false);

        $this->type = $meta['DATA_TYPE'];
        $this->length = $meta['CHAR_LENGTH'] ?: $meta['DATA_LENGTH'];
        $this->precision = $meta['DATA_PRECISION'];
        $this->scale = $meta['DATA_SCALE'];
        $this->isNullable = ($meta['NULLABLE'] == 'Y');

        /*
         * Metadata from the data stored
         */
        try {
            $query = sprintf('SELECT COUNT(*) AS COUNT FROM (SELECT %s FROM %s.%s GROUP BY %s) distinctvalues',
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->database),
                $this->connection->quoteIdentifier($this->table),
                $this->connection->quoteIdentifier($this->name));
            $this->distinctValues = $this->connection->query($query)->fetchAssoc(false)['COUNT'];

            $query = sprintf('SELECT MIN(%s) AS ROWMIN, MAX(%s) AS ROWMAX FROM %s.%s WHERE %s IS NOT NULL',
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->database),
                $this->connection->quoteIdentifier($this->table),
                $this->connection->quoteIdentifier($this->name));
            $data = $this->connection->query($query)->fetchAssoc(false);
            $this->maxValue = $data['ROWMAX'];
            $this->minValue = $data['ROWMIN'];
        } catch (Exception $e) { //LONG column has restrictions on querying, so just get total value count
            if (strpos($e->getMessage(), 'ORA-00997: illegal use of LONG datatype') !== false) {
                $query = sprintf('SELECT COUNT(*) AS COUNT FROM %s.%s WHERE %s IS NOT NULL',
                    $this->connection->quoteIdentifier($this->database),
                    $this->connection->quoteIdentifier($this->table),
                    $this->connection->quoteIdentifier($this->name));
                $this->distinctValues = $this->connection->query($query)->fetchAssoc(false)['COUNT'] ?: 1;
            }
        }
    }

    /**
     * Get column name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get column type as used by originating database.
     */
    public function getOriginalType(): string
    {
        return $this->type;
    }

    /**
     * Get column type as suitable for MySQL.
     *
     * @throws Exception
     */
    public function getMySQLType(): string
    {
        switch ($this->type) {
            case 'NUMBER':
                if ($this->scale == 0) {
                    if ($this->minValue >= 0) { //unsigned
                        if (bccomp($this->maxValue, '256') === -1) {
                            return 'TINYINT UNSIGNED';
                        } elseif (bccomp($this->maxValue, '65536') === -1) {
                            return 'SMALLINT UNSIGNED';
                        } elseif (bccomp($this->maxValue, '16777216') === -1) {
                            return 'MEDIUMINT UNSIGNED';
                        } elseif (bccomp($this->maxValue, '4294967296') === -1) {
                            return 'INT UNSIGNED';
                        } elseif (bccomp($this->maxValue, '18446744073709551616') === -1) {
                            return 'BIGINT UNSIGNED';
                        } else {
                            return 'NUMERIC';
                        }
                    } else { //signed
                        if (bccomp(max(abs($this->minValue), $this->maxValue), '128') === -1) {
                            return 'TINYINT';
                        } elseif (bccomp(max(abs($this->minValue), $this->maxValue), '32768') === -1) {
                            return 'SMALLINT';
                        } elseif (bccomp(max(abs($this->minValue), $this->maxValue), '8388608') === -1) {
                            return 'MEDIUMINT';
                        } elseif (bccomp(max(abs($this->minValue), $this->maxValue), '2147483648') === -1) {
                            return 'INT';
                        } elseif (bccomp(max(abs($this->minValue), $this->maxValue), '9223372036854775808') === -1) {
                            return 'BIGINT';
                        } else {
                            return 'DECIMAL';
                        }
                    }
                } else {
                    return 'DECIMAL';
                }
                break;

            case 'CHAR':
            case 'NCHAR':
                return 'CHAR';
                break;

            case 'VARCHAR':
            case 'VARCHAR2':
            case 'NVARCHAR':
            case 'NVARCHAR2':
                return 'VARCHAR';
                break;

            case 'TIMESTAMP':
            case 'TIMESTAMP WITH TIME ZONE':
            case 'TIMESTAMP WITH LOCAL TIME ZONE':
                if ($this->minValue >= '1970-01-01 00:00:01' && $this->maxValue <= '2038-01-19 03:14:07') {
                    return 'TIMESTAMP';
                } else {
                    return 'DATETIME';
                }

            // no break
            case 'DATE':
                /*
                 * Work out whether date or datetime
                 */
                $query = sprintf("SELECT COUNT(*) AS COUNT FROM %s.%s WHERE %s IS NOT NULL AND TO_CHAR(%s, 'SSSSS') > 0",
                    $this->connection->quoteIdentifier($this->database),
                    $this->connection->quoteIdentifier($this->table),
                    $this->connection->quoteIdentifier($this->name),
                    $this->connection->quoteIdentifier($this->name));
                $rows = $this->connection->query($query)->fetchAssoc(false);

                if ($rows['COUNT'] > 0) {
                    return 'DATETIME';
                } else {
                    return 'DATE';
                }
                break;

            case 'BINARY_FLOAT':
                return 'FLOAT';

            case 'BINARY_DOUBLE':
                return 'DOUBLE';

            case 'BLOB':
            case 'BFILE':
            case 'LONG RAW':
            case 'RAW':
                return 'LONGBLOB';
                break;

            case 'LONG':
            case 'CLOB':
            case 'NCLOB':
                return 'LONGTEXT';

            case 'ROWID':
                return 'LONGTEXT';

            default:
                throw new Exception("Unknown conversion for column type {$this->type}");
        }
    }

    /**
     * Get column type as suitable for Oracle.
     */
    public function getOracleType(): string
    {
        return $this->type;
    }

    /**
     * Get length of column.
     */
    public function getLength(): int
    {
        switch ($this->getOriginalType()) {
            case 'NUMBER':
            case 'CHAR':
            case 'NCHAR':
            case 'VARCHAR':
            case 'VARCHAR2':
            case 'NVARCHAR':
            case 'NVARCHAR2':
            case 'BINARY_FLOAT':
            case 'BINARY_DOUBLE':
                return $this->length;
                break;
            default:
                return 0;
        }
    }

    /**
     * Get column precision (number of digits).
     * @return int|null int for numeric columns, null for non-numeric
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Get column scale (number of digits after decimal place).
     * @return int|null int for numeric columns, null for non-numeric
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * Get whether column is nullable.
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * Get max value.
     */
    public function getMaxValue(): ?string
    {
        return $this->maxValue;
    }

    /**
     * Get min value.
     */
    public function getMinValue(): ?string
    {
        return $this->minValue;
    }

    /**
     * The number of distinct values in this column.
     */
    public function getDistinctValueCount(): int
    {
        return $this->distinctValues;
    }
}
