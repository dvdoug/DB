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
  class MySQLColumnMeta implements ColumnMetaInterface
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
          $statement = $this->connection->prepare('SELECT TABLE_SCHEMA,
                                                      TABLE_NAME,
                                                      COLUMN_NAME, 
                                                      DATA_TYPE,
                                                      CHARACTER_MAXIMUM_LENGTH,
                                                      NUMERIC_PRECISION,
                                                      COALESCE(/*!56000 DATETIME_PRECISION, */NUMERIC_SCALE) AS SCALE,
                                                      IS_NULLABLE,
                                                      COLUMN_TYPE
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

          if (strpos($meta['COLUMN_TYPE'], 'unsigned') !== false) {
              $this->type .= ' UNSIGNED';
          }

          /*
           * Metadata from the data stored
           */
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
      }

      /**
       * Get column name.
       */
      public function getName(): string
      {
          return $this->name;
      }

      /**
       * Get column type as suitable for MySQL.
       */
      public function getMySQLType(): string
      {
          return $this->type;
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
        case 'TINYINT UNSIGNED':
        case 'SMALLINT':
        case 'SMALLINT UNSIGNED':
        case 'MEDIUMINT':
        case 'MEDIUMINT UNSIGNED':
        case 'INT':
        case 'INT UNSIGNED':
        case 'BIGINT':
        case 'BIGINT UNSIGNED':
        case 'DECIMAL':
        case 'DECIMAL UNSIGNED':
          return 'NUMBER';

        case 'FLOAT':
        case 'FLOAT UNSIGNED':
          return 'BINARY_FLOAT';

        case 'DOUBLE':
        case 'DOUBLE UNSIGNED':
          return 'BINARY_DOUBLE';

        case 'DATE':
        case 'DATETIME':
          if ($this->precision) {
              return 'TIMESTAMP';
          } else {
              return 'DATE';
          }

          // no break
        case 'TIMESTAMP':
          return 'TIMESTAMP';

        case 'CHAR':
        case 'TIME':
        case 'YEAR':
          return 'CHAR';

        case 'ENUM':
        case 'SET':
        case 'VARCHAR':
          return 'NVARCHAR';

        case 'TINYBLOB':
        case 'SMALLBLOB':
        case 'BLOB':
        case 'MEDIUMBLOB':
        case 'LONGBLOB':
        case 'BINARY':
        case 'VARBINARY':
          return 'BLOB';

        case 'TINYTEXT':
        case 'SMALLTEXT':
        case 'TEXT':
        case 'MEDIUMTEXT':
        case 'LONGTEXT':
          return 'NCLOB';

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
        case 'BIT':
        case 'TINYINT':
        case 'TINYINT UNSIGNED':
        case 'SMALLINT':
        case 'SMALLINT UNSIGNED':
        case 'MEDIUMINT':
        case 'MEDIUMINT UNSIGNED':
        case 'INT':
        case 'INT UNSIGNED':
        case 'BIGINT':
        case 'BIGINT UNSIGNED':
        case 'DECIMAL':
        case 'DECIMAL UNSIGNED':
        case 'FLOAT':
        case 'FLOAT UNSIGNED':
        case 'DOUBLE':
        case 'DOUBLE UNSIGNED':
        case 'CHAR':
        case 'TIME':
        case 'YEAR':
        case 'VARCHAR':
          return $this->length;
        default:
          return 0;
      }
      }

      /**
       * Get column type as used by originating database.
       */
      public function getOriginalType(): string
      {
          return $this->type;
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
