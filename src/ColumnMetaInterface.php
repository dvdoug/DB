<?php
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
       * @return string
       */
      public function getName();

      /**
       * Get column type as used by originating database.
       * @return string
       */
      public function getOriginalType();

      /**
       * Get column type as suitable for MySQL.
       * @return string
       */
      public function getMySQLType();

      /**
       * Get column type as suitable for Oracle.
       * @return string
       */
      public function getOracleType();

      /**
       * Get length of column.
       * @return int
       */
      public function getLength();

      /**
       * Get column precision (number of digits).
       * @return int|null int for numeric columns, null for non-numeric
       */
      public function getPrecision();

      /**
       * Get column scale (number of digits after decimal place).
       * @return int|null int for numeric columns, null for non-numeric
       */
      public function getScale();

      /**
       * Get column name.
       * @return bool
       */
      public function isNullable();

      /**
       * Get column name.
       * @return string
       */
      public function getMaxValue();

      /**
       * Get column name.
       * @return string
       */
      public function getMinValue();

      /**
       * The number of distinct values in this column.
       * @return int
       */
      public function getDistinctValueCount();

      /**
       * Get MySQL column definition.
       */
      public function getMySQLColumnDef();

      /**
       * Get Oracle column definition.
       */
      public function getOracleColumnDef();
  }
