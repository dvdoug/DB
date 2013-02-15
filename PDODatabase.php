<?php
/**
 * Database Access Layer
 * @package DB
 * @author Doug Wright
 */
  namespace DVDoug\DB;

  /**
   * PDO-backed database connection (common parts)
   * @author Doug Wright
   * @package DB
   */
  abstract class PDODatabase extends \PDO implements DatabaseInterface {

    /**
     * Character to use when quoting identifiers
     */
    const IDENTIFIER_OPENQUOTE = '"';

    /**
     * Character to use when quoting identifiers
     */
    const IDENTIFIER_CLOSEQUOTE = '"';

    /**
     * Constructor
     * @param string $aDSN
     * @param string $aUsername
     * @param string $aPassword
     * @param array $aDriverOptions
     */
    public function __construct($aDSN, $aUsername, $aPassword, array $aDriverOptions = NULL) {
      parent::__construct($aDSN, $aUsername, $aPassword, $aDriverOptions);
      self::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      self::setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\DVDoug\DB\PDOStatement'));
      self::setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Executes an SQL statement, returning the result set if any as a StatementInterface object
     * @param string $aSQL the SQL statement to execute.
     * @return StatementInterface|true
     */
    public function query($aSQL) {
      return parent::query($aSQL);
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     * @param string $aName name of the sequence object (if any) from which the ID should be returned
     * @return string
     */
    public function getLastInsertId($aName = NULL) {
      return parent::lastInsertID($aName);
    }

    /**
     * Escapes/quotes a parameter for use in a query
     * @param mixed $aParam the parameter to be quoted.
     * @param $aParamaterType data type hint for drivers
     * @return string a quoted string that is theoretically safe to pass into an SQL statement
     */
    public function escape($aParam, $aParamType = DatabaseInterface::PARAM_IS_STR) {
      switch ($aParamType) {
      
        case self::PARAM_IS_INT:
          if (is_int($aParam) || ctype_digit($aParam)) {
            return (int)$aParam;
          }
          else {
            throw new \RuntimeException("Parameter {$aParam} is not an integer");
          }
          break;
          
        default:
          return parent::quote($aParam, $aParamType);
      }
    }

    /**
     * Adds appropriate quotes to an identifier so it can be safely used in an SQL statement
     * @param mixed $aIdentifier the parameter to be quoted.
     * @return string
     */
    public function quoteIdentifier($aIdentifier) {
      return static::IDENTIFIER_OPENQUOTE . $aIdentifier . static::IDENTIFIER_CLOSEQUOTE;
    }

    /**
     * Get MySQL table definition
     * @param string $aDatabase database/schema name
     * @param string $aTable table name
     * @param bool $aSkipUnusedCols whether to skip unused columns
     * @return string
     */
    public function getMySQLTableDef($aDatabase, $aTable, $aSkipUnusedCols = true) {

      $table = strtolower($aTable);

      $columns = $this->getTableColumns($aDatabase, $aTable);

      $colDefs = array();

      foreach ($columns as $columnName => $column) {
        $column instanceof ColumnMetaInterface;

        if ($aSkipUnusedCols && $column->getDistinctValueCount() <= 1) {
          unset($columns[$columnName]);
          continue;
        }

        $colDefs[] = $column->getMySQLColumnDef();
      }

      $primaryKey = $this->getPrimaryKey($aDatabase, $aTable);
      $indexes = $this->getIndexes($aDatabase, $aTable);

      $tableDef = "CREATE TABLE `{$table}` (\r\n";
      $tableDef .= join(", \r\n", $colDefs);

      if ($primaryKey) {
        $tableDef .= ",\r\n\r\n";
        $tableDef .= 'PRIMARY KEY (';
        $tableDef .= join(", \r\n", array_map(function($c) {return '`'.strtolower($c).'`';}, $primaryKey));
        $tableDef .= ')';
      }

      if ($indexes) {
        foreach ($indexes as $indexName => $indexColumns) {
          foreach ($indexColumns as &$col) {
            if (!in_array($col, array_keys($columns))) { //skip index if it includes a skipped column
              continue 2;
            }
          }
          $tableDef .= ",\r\n";
          $tableDef .= 'KEY `' . strtolower($indexName) . '` (';
          $tableDef .= join(", ", array_map(function($c) {return '`'.strtolower($c).'`';}, $indexColumns));
          $tableDef .= ')';
        }
      }
      $tableDef .= ") ENGINE=InnoDB ROW_FORMAT=COMPRESSED";

      return $tableDef;
    }

    /**
     * Get Oracle table definition
     * @param string $aDatabase database/schema name
     * @param string $aTable table name
     * @param bool $aSkipUnusedCols whether to skip unused columns
     * @return string
     */
    public function getOracleTableDef($aDatabase, $aTable, $aSkipUnusedCols = true) {

      $table = strtolower($aTable);

      $columns = $this->getTableColumns($aDatabase, $aTable);

      $colDefs = array();

      foreach ($columns as $columnName => $column) {
        $column instanceof ColumnMetaInterface;

        if ($aSkipUnusedCols && $column->getDistinctValueCount() <= 1) {
          unset($columns[$columnName]);
          continue;
        }

        $colDefs[] = $column->getOracleColumnDef();
      }

      $primaryKey = $this->getPrimaryKey($aDatabase, $aTable);
      $indexes = $this->getIndexes($aDatabase, $aTable);

      $tableDef = "CREATE TABLE `{$table}` (\r\n";
      $tableDef .= join(", \r\n", $colDefs);

      if ($primaryKey) {
        $tableDef .= ",\r\n\r\n";
        $tableDef .= 'PRIMARY KEY (';
        $tableDef .= join(", \r\n", array_map(function($c) {return '"'.strtolower($c).'"';}, $primaryKey));
        $tableDef .= ')';
      }

      if ($indexes) {
        foreach ($indexes as $indexName => $indexColumns) {
          foreach ($indexColumns as &$col) {
            if (!in_array($col, array_keys($columns))) { //skip index if it includes a skipped column
              continue 2;
            }
          }
          $tableDef .= ",\r\n";
          $tableDef .= 'KEY `' . strtolower($indexName) . '` (';
          $tableDef .= join(", ", array_map(function($c) {return '"'.strtolower($c).'"';}, $indexColumns));
          $tableDef .= ')';
        }
      }
      $tableDef .= ")";

      return $tableDef;
    }
  }
