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
interface ColumnMetaInterface
{
    /**
     * Constructor.
     * @param DatabaseInterface $aConnection connection to database
     * @param string            $aDatabase   database/schema name
     * @param string            $aTable      table name
     * @param string            $aColumnName column name
     */
    public function __construct(DatabaseInterface $aConnection, $aDatabase, $aTable, $aColumnName);

    /**
     * Get column name.
     */
    public function getName(): string;

    /**
     * Get column type as used by originating database.
     */
    public function getOriginalType(): string;

    /**
     * Get column type as suitable for MySQL.
     */
    public function getMySQLType(): string;

    /**
     * Get column type as suitable for Oracle.
     */
    public function getOracleType(): string;

    /**
     * Get length of column.
     */
    public function getLength(): int;

    /**
     * Get column precision (number of digits).
     * @return int|null int for numeric columns, null for non-numeric
     */
    public function getPrecision(): ?int;

    /**
     * Get column scale (number of digits after decimal place).
     * @return int|null int for numeric columns, null for non-numeric
     */
    public function getScale(): ?int;

    /**
     * Get whether column is nullable.
     */
    public function isNullable(): bool;

    /**
     * Get max value.
     */
    public function getMaxValue(): ?string;

    /**
     * Get min value.
     */
    public function getMinValue(): ?string;

    /**
     * The number of distinct values in this column.
     */
    public function getDistinctValueCount(): int;

    /**
     * Get MySQL column definition.
     */
    public function getMySQLColumnDef(): string;

    /**
     * Get Oracle column definition.
     */
    public function getOracleColumnDef(): string;
}
