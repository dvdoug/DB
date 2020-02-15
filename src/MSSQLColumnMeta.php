<?php

declare(strict_types=1);
/**
 * Database Access Layer.
 * @author Doug Wright
 */

namespace DVDoug\DB;

/**
 * Metadata about a database column.
 * @author Doug Wright
 */
class MSSQLColumnMeta implements ColumnMetaInterface
{
    use DDLGeneration;

    /**
     * Database connection.
     * @var DatabaseInterface
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
        $statement = $this->connection->prepare('SELECT TABLE_SCHEMA,
                                                      TABLE_NAME,
                                                      COLUMN_NAME,
                                                      DATA_TYPE,
                                                      CHARACTER_MAXIMUM_LENGTH,
                                                      NUMERIC_PRECISION,
                                                      COALESCE(DATETIME_PRECISION, NUMERIC_SCALE) AS SCALE,
                                                      IS_NULLABLE
                                               FROM INFORMATION_SCHEMA.COLUMNS
                                               WHERE TABLE_SCHEMA = :database
                                                     AND TABLE_NAME = :table_name
                                                     AND COLUMN_NAME = :column_name');
        $statement->bindParamToValue(':database', $this->database);
        $statement->bindParamToValue(':table_name', $this->table);
        $statement->bindParamToValue(':column_name', $this->name);
        $statement->execute();

        $meta = $statement->fetchAssoc(false);

        $this->type = strtoupper($meta['DATA_TYPE']);
        $this->length = $meta['CHARACTER_MAXIMUM_LENGTH'] ?: $meta['NUMERIC_PRECISION'];
        $this->precision = $meta['NUMERIC_PRECISION'];
        $this->scale = $meta['SCALE'];
        $this->isNullable = ($meta['IS_NULLABLE'] == 'YES');

        /*
         * Metadata from the data stored
         */
        if (!in_array($this->type, ['TEXT', 'NTEXT', 'IMAGE'])) {
            $query = sprintf('SELECT COUNT(*) AS COUNT FROM (SELECT %s FROM %s.%s GROUP BY %s) distinctvalues',
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->database),
                $this->connection->quoteIdentifier($this->table),
                $this->connection->quoteIdentifier($this->name));
            $this->distinctValues = $this->connection->query($query)->fetchAssoc(false)['COUNT'];
        }

        if (!in_array($this->type, ['BIT', 'TEXT', 'NTEXT', 'IMAGE', 'UNIQUEIDENTIFIER'])) {
            $query = sprintf('SELECT MIN(%s) AS ROWMIN, MAX(%s) AS ROWMAX FROM %s.%s WHERE %s IS NOT NULL',
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->database),
                $this->connection->quoteIdentifier($this->table),
                $this->connection->quoteIdentifier($this->name));
            $data = $this->connection->query($query)->fetchAssoc(false);
            $this->maxValue = $data['ROWMAX'];
            $this->minValue = $data['ROWMIN'];
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
     * @throws \Exception
     */
    public function getMySQLType(): string
    {
        switch ($this->type) {
            case 'BIT':
            case 'TINYINT':
                return 'TINYINT UNSIGNED';
                break;

            case 'SMALLINT':
                return 'SMALLINT';
                break;

            case 'INT':
                return 'INT';
                break;

            case 'BIGINT':
                return 'BIGINT';
                break;

            case 'DECIMAL':
            case 'NUMERIC':
            case 'MONEY':
            case 'SMALLMONEY':
                return 'DECIMAL';
                break;

            case 'FLOAT':
                return 'FLOAT';
                break;

            case 'REAL':
                return 'DOUBLE';
                break;

            case 'DATE':
                return 'DATE';
                break;

            case 'DATETIME':
            case 'DATETIME2':
            case 'SMALLDATETIME':
                /*
                 * Work out whether date or datetime
                */
                $query = sprintf("SELECT COUNT(*) AS COUNT FROM %s.%s WHERE %s IS NOT NULL AND CONVERT(VARCHAR(8), %s, 108) != '00:00:00'",
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

            case 'DATETIMEOFFSET':
                if ($this->minValue >= '1970-01-01 00:00:01' && $this->maxValue <= '2038-01-19 03:14:07') {
                    return 'TIMESTAMP';
                } else {
                    return 'DATETIME';
                }
                break;

            case 'TIME':
                return 'TIME';
                break;

            case 'CHAR':
            case 'NCHAR':
                return 'CHAR';
                break;

            case 'VARCHAR':
            case 'NVARCHAR':
                if ($this->getLength() == -1) {
                    return 'LONGTEXT';
                } else {
                    return 'VARCHAR';
                }
                break;

            case 'TEXT':
            case 'NTEXT':
                return 'LONGTEXT';
                break;

            case 'BINARY':
                return 'BINARY';
                break;

            case 'VARBINARY':
                if ($this->getLength() == -1) {
                    return 'LONGBLOB';
                } else {
                    return 'VARBINARY';
                }
                break;

            case 'IMAGE':
                return 'LONGBLOB';
                break;

            case 'ROWVERSION':
            case 'TIMESTAMP': //XXX rowversion, not a time
            case 'HIERARCHYID':
            case 'XML':
                return 'VARCHAR';
                break;

            case 'UNIQUEIDENTIFIER':
                return 'CHAR';
                break;

            default:
                throw new \Exception("Unknown conversion for column type {$this->type}");
        }
    }

    /**
     * Get column type as suitable for Oracle.
     *
     * @throws \Exception
     */
    public function getOracleType(): string
    {
        switch ($this->type) {
            case 'BIT':
            case 'TINYINT':
            case 'SMALLINT':
            case 'INT':
            case 'BIGINT':
            case 'DECIMAL':
            case 'NUMERIC':
            case 'MONEY':
            case 'SMALLMONEY':
                return 'NUMBER';
                break;

            case 'FLOAT':
                return 'BINARY_FLOAT';
                break;

            case 'REAL':
                return 'BINARY_DOUBLE';
                break;

            case 'DATE':
                return 'DATE';
                break;

            case 'DATETIME':
            case 'DATETIME2':
            case 'SMALLDATETIME':
            case 'DATETIMEOFFSET':
                if ($this->precision) {
                    return 'TIMESTAMP';
                } else {
                    return 'DATE';
                }
                break;

            case 'TIME':
                return 'TIME';
                break;

            case 'CHAR':
            case 'NCHAR':
                return 'NCHAR';
                break;

            case 'VARCHAR':
            case 'NVARCHAR':
            case 'TEXT':
            case 'NTEXT':
                return 'NVARCHAR';
                break;

            case 'BINARY':
            case 'VARBINARY':
            case 'IMAGE':
                return 'BLOB';
                break;

            case 'ROWVERSION':
            case 'TIMESTAMP': //XXX rowversion, not a time
            case 'HIERARCHYID':
            case 'XML':
                return 'NVARCHAR';
                break;

            case 'UNIQUEIDENTIFIER':
                return 'CHAR';
                break;

            default:
                throw new \Exception("Unknown conversion for column type {$this->type}");
        }
    }

    /**
     * Get length of column.
     */
    public function getLength(): int
    {
        switch ($this->getOriginalType()) {
            case 'TINYINT':
            case 'SMALLINT':
            case 'INT':
            case 'BIGINT':
            case 'DECIMAL':
            case 'NUMERIC':
            case 'MONEY':
            case 'SMALLMONEY':
            case 'BIT':
            case 'FLOAT':
            case 'REAL':
            case 'CHAR':
            case 'NCHAR':
            case 'VARCHAR':
            case 'NVARCHAR':
            case 'ROWVERSION':
            case 'TIMESTAMP':
            case 'HIERARCHYID':
            case 'XML':
                return $this->length;
                break;

            case 'UNIQUEIDENTIFIER':
                return 36;
                break;

            default:
                return 0;
        }
    }

    /**
     * Get column precision (number of digits).
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Get column scale (number of digits after decimal place).
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
