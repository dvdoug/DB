<?php
/**
 * Database Access Layer
 * @package DB
 * @author Doug Wright
 */
  namespace DVDoug\DB;

  /**
   * MySQL database connection (PDO)
   * @author Doug Wright
   * @package DB
   */
  class MySQLPDODatabase extends PDODatabase {

    /**
     * Character to use when quoting identifiers
     */
    const IDENTIFIER_OPENQUOTE = '`';

    /**
     * Character to use when quoting identifiers
     */
    const IDENTIFIER_CLOSEQUOTE = '`';

    /**
     * Constructor
     * @param string $aHost hostname to connect to 
     * @param int $aPort port number to connect to
     * @param string $aDefaultDatabase name of default database to use
     * @param string $aUsername connection username
     * @param string $aPassword connection password
     * @param string $aCharset connection character set
     */
    public function __construct($aHost, $aPort, $aDefaultDatabase, $aUsername, $aPassword, $aCharset = 'utf8mb4') {
      parent::__construct("mysql:host={$aHost};port={$aPort};dbname={$aDefaultDatabase};charset={$aCharset}", $aUsername, $aPassword);
    }

    /**
     * List of tables in a database
     * @param string $aDatabase database/schema name
     * @return array
     */
    public function getTables($aDatabase = NULL) {
      if ($aDatabase) {
        $statement = $this->prepare("SELECT TABLE_SCHEMA, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :database ORDER BY TABLE_NAME ASC");
        $statement->bindParamToValue(':database', $aDatabase);
        $statement->execute();
      }
      else {
        $statement = $this->query("SELECT TABLE_SCHEMA, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES");
      }

      $result = $statement->fetchAssoc(true, true);

      $tables = array();
      foreach ($result as $database => $dbtables) {
        $tables[$database] = array();
        foreach ($dbtables as $table) {
          $tables[$database][] = $table['TABLE_NAME'];
        }
      }
      return $aDatabase ? $tables[$aDatabase] : $tables;
    }

    /**
     * List of columns (and types) in a table
     * @param string $aDatabase database/schema name
     * @param string $aTable table name
     * @return ColumnMetaInterface[]
     */
    public function getTableColumns($aDatabase, $aTable) {
      $statement = $this->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :table_name ORDER BY ORDINAL_POSITION ASC");
      $statement->bindParamToValue(':database', $aDatabase);
      $statement->bindParamToValue(':table_name', $aTable);
      $statement->execute();

      $result = $statement->fetchAssoc();
      $columns = array();
      foreach ($result as $row) {
        $columns[$row['COLUMN_NAME']] = new MySQLColumnMeta($this, $aDatabase, $aTable, $row['COLUMN_NAME']);
      }
      return $columns;
    }

      /**
     * Primary key column(s)
     * @param string $aDatabase database/schema name
     * @param string $aTable table name
     * @return array
     */
    public function getPrimaryKey($aDatabase, $aTable) {
      $columns = array();
      $SQL = "SELECT ORDINAL_POSITION, COLUMN_NAME
                     FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                     WHERE KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA = :database
                           AND KEY_COLUMN_USAGE.TABLE_NAME = :table_name
                           AND KEY_COLUMN_USAGE.CONSTRAINT_NAME = 'PRIMARY'
                     ORDER BY ORDINAL_POSITION";
      $statement = $this->prepare($SQL);
      $statement->bindParamToValue(':database', $aDatabase);
      $statement->bindParamToValue(':table_name', $aTable);
      $statement->execute();

      $result = $statement->fetchAssoc();
      foreach ($result as $column) {
        $columns[] = $column['COLUMN_NAME'];
      }
      return $columns;
    }

    /**
     * Non-PK indexes
     * @param string $aDatabase database/schema name
     * @param string $aTable table name
     * @return array
    */
    public function getIndexes($aDatabase, $aTable) {

      $indexes = array();
      $SQL = "SELECT INDEX_NAME, COLUMN_NAME
                     FROM INFORMATION_SCHEMA.STATISTICS
                     WHERE TABLE_SCHEMA = :database
                           AND TABLE_NAME = :table_name
                           AND INDEX_NAME != 'PRIMARY'
                     ORDER BY INDEX_NAME ASC, SEQ_IN_INDEX ASC";
      $statement = $this->prepare($SQL);
      $statement->bindParamToValue(':database', $aDatabase);
      $statement->bindParamToValue(':table_name', $aTable);
      $statement->execute();

      $result = $statement->fetchAssoc(true, true);

      foreach ($result as $index => $columnList) {
        $indexes[$index] = array();
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
