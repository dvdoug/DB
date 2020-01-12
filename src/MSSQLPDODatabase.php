<?php
/**
 * Database Access Layer.
 * @author Doug Wright
 */

namespace DVDoug\DB;

  /**
   * MSSQL database connection (PDO).
   * @author Doug Wright
   */
  class MSSQLPDODatabase extends PDODatabase
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
       * @param string $aHost            hostname to connect to
       * @param int    $aPort            port number to connect to
       * @param string $aDefaultDatabase name of default database to use
       * @param string $aUsername        connection username
       * @param string $aPassword        connection password
       */
      public function __construct($aHost, $aPort, $aDefaultDatabase, $aUsername, $aPassword)
      {
          parent::__construct("sqlsrv:Server={$aHost},{$aPort};Database={$aDefaultDatabase}", $aUsername, $aPassword);
      }

      /**
       * Escapes/quotes a parameter for use in a query.
       * @param  mixed  $aParam     the parameter to be quoted
       * @param  int    $aParamType data type hint for drivers
       * @return string a quoted string that is theoretically safe to pass into an SQL statement
       */
      public function escape($aParam, $aParamType = DatabaseInterface::PARAM_IS_STR)
      {
          switch ($aParamType) {
        default:
          return parent::escape($aParam, $aParamType);
      }
      }

      /**
       * List of tables in a database.
       * @param  string $aDatabase database/schema name
       * @return array
       */
      public function getTables($aDatabase = null)
      {
          if ($aDatabase) {
              $statement = $this->prepare('SELECT TABLE_SCHEMA, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :database ORDER BY TABLE_NAME ASC');
              $statement->bindParamToValue(':database', $aDatabase);
              $statement->execute();
          } else {
              $statement = $this->query('SELECT TABLE_SCHEMA, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES');
          }

          $result = $statement->fetchAssoc(true, true);

          $tables = [];
          foreach ($result as $database => $dbtables) {
              $tables[$database] = [];
              foreach ($dbtables as $table) {
                  $tables[$database][] = $table['TABLE_NAME'];
              }
          }

          return $aDatabase ? $tables[$aDatabase] : $tables;
      }

      /**
       * List of columns (and types) in a table.
       * @param  string                $aDatabase database/schema name
       * @param  string                $aTable    table name
       * @return ColumnMetaInterface[]
       */
      public function getTableColumns($aDatabase, $aTable)
      {
          $statement = $this->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :table_name ORDER BY ORDINAL_POSITION ASC');
          $statement->bindParamToValue(':database', $aDatabase);
          $statement->bindParamToValue(':table_name', $aTable);
          $statement->execute();

          $result = $statement->fetchAssoc();
          $columns = [];
          foreach ($result as $row) {
              $columns[$row['COLUMN_NAME']] = new MSSQLColumnMeta($this, $aDatabase, $aTable, $row['COLUMN_NAME']);
          }

          return $columns;
      }

      /**
       * Primary key column(s).
       * @param  string $aDatabase database/schema name
       * @param  string $aTable    table name
       * @return array
       */
      public function getPrimaryKey($aDatabase, $aTable)
      {
          $columns = [];
          $SQL = 'SELECT ind.name AS INDEX_NAME,
                     col.name AS COLUMN_NAME
              FROM sys.indexes ind
                   JOIN sys.index_columns ic
                     ON ind.object_id = ic.object_id
                        AND ind.index_id = ic.index_id
                   JOIN sys.columns col
                     ON ic.object_id = col.object_id
                        AND ic.column_id = col.column_id
                   JOIN sys.tables t
                     ON ind.object_id = t.object_id
                   JOIN sys.schemas s
                     ON t.schema_id = s.schema_id
             WHERE ind.is_primary_key = 1
                   AND col.is_nullable = 0
                   AND s.name = :database
                   AND t.name = :table_name
             ORDER BY ind.name, ic.index_column_id';
          $statement = $this->prepare($SQL);
          $statement->bindParamToValue(':database', $aDatabase);
          $statement->bindParamToValue(':table_name', $aTable);
          $statement->execute();

          $result = $statement->fetchAssoc();
          foreach ($result as $column) {
              $columns[] = $column['COLUMN_NAME'];
          }

          if (!$columns) { //Try uniqueidentifier
              $statement = $this->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE DATA_TYPE = 'uniqueidentifier' AND TABLE_SCHEMA = :database AND TABLE_NAME = :table_name");
              $statement->bindParamToValue(':database', $aDatabase);
              $statement->bindParamToValue(':table_name', $aTable);
              $statement->execute();
              $result = $statement->fetchAssoc(false);
              if ($result) {
                  $columns[] = $result['COLUMN_NAME'];
              }
          }

          return $columns;
      }

      /**
       * Non-PK indexes.
       * @param  string $aDatabase database/schema name
       * @param  string $aTable    table name
       * @return array
       */
      public function getIndexes($aDatabase, $aTable)
      {
          $indexes = [];
          $SQL = 'SELECT ind.name AS INDEX_NAME,
                     col.name AS COLUMN_NAME
              FROM sys.indexes ind
                   JOIN sys.index_columns ic
                     ON ind.object_id = ic.object_id
                        AND ind.index_id = ic.index_id
                   JOIN sys.columns col
                     ON ic.object_id = col.object_id
                        AND ic.column_id = col.column_id
                   JOIN sys.tables t
                     ON ind.object_id = t.object_id
                   JOIN sys.schemas s
                     ON t.schema_id = s.schema_id
             WHERE ind.is_primary_key = 0
                   AND s.name = :database
                   AND t.name = :table_name
             ORDER BY ind.name ASC, ic.index_column_id ASC';
          $statement = $this->prepare($SQL);
          $statement->bindParamToValue(':database', $aDatabase);
          $statement->bindParamToValue(':table_name', $aTable);
          $statement->execute();

          $result = $statement->fetchAssoc(true, true);

          foreach ($result as $index => $columnList) {
              $index = substr($index, 0, 64);
              $indexes[$index] = [];
              foreach ($columnList as $col) {
                  $indexes[$index][] = $col['COLUMN_NAME'];
              }
          }

          /*
           * Subtract PK if any
           */
          $PK = $this->getPrimaryKey($aDatabase, $aTable);
          foreach ($indexes as $name => $columns) {
              if ($PK === $columns) {
                  unset($indexes[$name]);
                  break;
              }
          }

          return $indexes;
      }
  }
