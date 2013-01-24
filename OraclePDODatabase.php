<?php
/**
 * Database Access Layer
 * @package DB
 * @author Doug Wright
 */
  namespace DVDoug\DB;

  /**
   * Oracle database connection (PDO)
   * @author Doug Wright
   * @package DB
   */
  class OraclePDODatabase extends PDODatabase {

    public function __construct($aConnectionString, $aUsername, $aPassword, $aCharset = 'AL32UTF8') {
      parent::__construct("oci:dbname={$aConnectionString};charset={$aCharset}", $aUsername, $aPassword);
      $this->exec("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");
    }

    /**
     * List of tables in a database
     * @param string $aDatabase database/schema name
     * @return array
     */
    public function getTables($aDatabase = NULL) {
      if ($aDatabase) {
        $statement = $this->prepare("SELECT OWNER, TABLE_NAME FROM ALL_TABLES WHERE OWNER = :owner ORDER BY TABLE_NAME ASC");
        $statement->bindParamToValue(':owner', $aDatabase);
        $statement->execute();
      }
      else {
        $statement = $this->query("SELECT OWNER, TABLE_NAME FROM ALL_TABLES");
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
      $statement = $this->prepare("SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS WHERE OWNER = :owner AND TABLE_NAME = :table_name ORDER BY COLUMN_ID ASC");
      $statement->bindParamToValue(':owner', $aDatabase);
      $statement->bindParamToValue(':table_name', $aTable);
      $statement->execute();

      $result = $statement->fetchAssoc();

      $columns = array();
      foreach ($result as $row) {
        $columns[$row['COLUMN_NAME']] = new OracleColumnMeta($this, $aDatabase, $aTable, $row['COLUMN_NAME']);
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
      $SQL = "SELECT COLS.POSITION, COLS.COLUMN_NAME
                     FROM ALL_CONSTRAINTS cons
                          JOIN ALL_CONS_COLUMNS cols
                            ON cons.CONSTRAINT_NAME = cols.CONSTRAINT_NAME
                               AND cons.OWNER = cols.OWNER
                     WHERE cols.TABLE_NAME = :table_name
                           AND cons.CONSTRAINT_TYPE = 'P'
                           AND cols.OWNER = :database_name
                     ORDER BY cols.POSITION";
      $statement = $this->prepare($SQL);
      $statement->bindParamToValue(':table_name', $aTable);
      $statement->bindParamToValue(':database_name', $aDatabase);
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
                   FROM ALL_IND_COLUMNS
                   WHERE TABLE_NAME = :table_name
                         AND TABLE_OWNER = :database_name
                   ORDER BY INDEX_NAME ASC, COLUMN_POSITION ASC";
      $statement = $this->prepare($SQL);
      $statement->bindParamToValue(':table_name', $aTable);
      $statement->bindParamToValue(':database_name', $aDatabase);
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
