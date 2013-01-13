<?php
/**
 * Database Access Layer
 * @package DB
 * @author Doug Wright
 */
  namespace DVDoug\DB;

  /**
   * Metadata about a database column
   * @author Doug Wright
   * @package DB
   */
  class OracleColumnMeta implements ColumnMetaInterface {
    use DDLGeneration;
    
    /**
     * Database connection
     * @var DatabaseInterface
     */
    protected $connection;
    
    /**
     * Database name
     * @var string
     */
    protected $database;

    /**
     * Table name
     * @var string
     */
    protected $table;
    
    /**
     * Column name
     * @var string
     */
    protected $name;
    
    /**
     * Column type
     * @var string
     */
    protected $type;
    
    /**
     * Column length
     * @var int
     */
    protected $length;
    
    /**
     * Column precision
     * @var int|null
     */
    protected $precision;
    
    /**
     * Column scale
     * @var int|null
     */
    protected $scale;
    
    /**
     * Column nullable?
     * @var boolean
     */
    protected $isNullable;
    
    /**
     * Column max value
     * @var string
     */
    protected $maxValue;
    
    /**
     * Column min value
     * @var string
     */
    protected $minValue;
    
    /**
     * Number of distinct values
     * @var int
     */
    protected $distinctValues;
    
    /**
     * Constructor
     * @param DatabaseInterface $this->connection connection to database
     * @param string $aDatabase database/schema name
     * @param string $aTable table name
     * @param string $aColumn column name
     */
    public function __construct(DatabaseInterface $aConnection, $aDatabase, $aTable, $aColumnName) {
      
      $this->connection = $aConnection;
      $this->database = $aDatabase;
      $this->table = $aTable;
      $this->name = $aColumnName;
      
      /*
       * Basic metadata from the schema
       */
      $statement = $this->connection->prepare("SELECT DATA_TYPE,
                                                      DATA_LENGTH,
                                                      DATA_PRECISION,
                                                      DATA_SCALE,
                                                      NULLABLE,
                                                      CHAR_LENGTH
                                               FROM ALL_TAB_COLUMNS
                                               WHERE OWNER = :owner
                                                     AND TABLE_NAME = :table_name
                                                     AND COLUMN_NAME = :column_name");
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
        $query = sprintf("SELECT COUNT(DISTINCT %s) AS COUNT FROM %s.%s",
                         $this->connection->quoteIdentifier($this->name),
                         $this->connection->quoteIdentifier($this->database),
                         $this->connection->quoteIdentifier($this->table));
        $this->distinctValues = $this->connection->query($query)->fetchAssoc(false)['COUNT'];
        
        if ($this->isNullable) { //COUNT DISTINCT ignores NULL, so see if we have any
          $query = sprintf("SELECT COUNT(*) AS COUNT FROM %s.%s WHERE %s IS NULL",
                           $this->connection->quoteIdentifier($this->database),
                           $this->connection->quoteIdentifier($this->table),
                           $this->connection->quoteIdentifier($this->name));
          if ($this->connection->query($query)->fetchAssoc(false)['COUNT']) {
            $this->distinctValues++;
          }
        }
        
        $query = sprintf("SELECT MIN(%s) AS ROWMIN, MAX(%s) AS ROWMAX FROM %s.%s WHERE %s IS NOT NULL",
                         $this->connection->quoteIdentifier($this->name),
                         $this->connection->quoteIdentifier($this->name),
                         $this->connection->quoteIdentifier($this->database),
                         $this->connection->quoteIdentifier($this->table),
                         $this->connection->quoteIdentifier($this->name));
        $data = $this->connection->query($query)->fetchAssoc(false);
        $this->maxValue = $data['ROWMAX'];
        $this->minValue = $data['ROWMIN'];
        
      }
      catch (\Exception $e) { //LONG column has restrictions on querying, so just get total value count 
        if (strpos($e->getMessage(), 'ORA-00997: illegal use of LONG datatype') !== false) {
          $query = sprintf("SELECT COUNT(*) AS COUNT FROM %s.%s WHERE %s IS NOT NULL",
                           $this->connection->quoteIdentifier($this->database),
                           $this->connection->quoteIdentifier($this->table),
                           $this->connection->quoteIdentifier($this->name));
          $this->distinctValues = $this->connection->query($query)->fetchAssoc(false)['COUNT'] ?: 1; 
        }
      }
    }
    
    /**
     * Get column name
     * @return string
     */
    public function getName() {
      return $this->name;
    }

    /**
     * Get column type as used by originating database 
     * @return string
     */
    public function getOriginalType() {
      return $this->type;
    }
    
    /**
     * Get column type as suitable for MySQL
     * @return string
     */
    public function getMySQLType() {
      switch ($this->type) {
      
        case 'NUMBER':
          if ($this->scale == 0) {
            if ($this->minValue >= 0) { //unsigned
              if (bccomp($this->maxValue, '256') === -1) {
                return 'TINYINT UNSIGNED';
              }
              else if (bccomp($this->maxValue, '65536') === -1) {
                return 'SMALLINT UNSIGNED';
              }
              else if (bccomp($this->maxValue, '16777216') === -1) {
                return 'MEDIUMINT UNSIGNED';
              }
              else if (bccomp($this->maxValue, '4294967296') === -1) {
                return 'INT UNSIGNED';
              }
              else if (bccomp($this->maxValue, '18446744073709551616') === -1) {
                return 'BIGINT UNSIGNED';
              }
              else {
                return 'NUMERIC';
              }
            }
            else { //signed
              if (bccomp(max(abs($this->minValue), $this->maxValue), '128') === -1) {
                return 'TINYINT';
              }
              else if (bccomp(max(abs($this->minValue), $this->maxValue), '32768') === -1) {
                return 'SMALLINT';
              }
              else if (bccomp(max(abs($this->minValue), $this->maxValue), '8388608') === -1) {
                return 'MEDIUMINT';
              }
              else if (bccomp(max(abs($this->minValue), $this->maxValue), '2147483648') === -1) {
                return 'INT';
              }
              else if (bccomp(max(abs($this->minValue), $this->maxValue), '9223372036854775808') === -1) {
                return 'BIGINT';
              }
              else {
                return 'NUMERIC';
              }
            }
          }
          else {
            return 'NUMERIC';
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
          return 'TIMESTAMP';
      
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
          }
          else {
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
          throw new \Exception("Unknown conversion for column type {$this->type}");
      
      }
    }
    
    /**
     * Get column type as suitable for Oracle
     * @return string
     */
    public function getOracleType() {
      return $this->type;
    }
    
    /**
     * Get length of column
     * @return int
     */
    public function getLength() {
      switch($this->getOriginalType()) {
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
        default:
          return;
      }
    }
    
    /**
     * Get column precision (number of digits)
     * @return int|null int for numeric columns, null for non-numeric
     */
    public function getPrecision() {
      return $this->precision;
    }
    
    /**
     * Get column scale (number of digits after decimal place)
     * @return int|null int for numeric columns, null for non-numeric
     */
    public function getScale() {
      return $this->scale;
    }
    
    /**
     * Get column name
     * @return string
     */
    public function isNullable() {
      return $this->isNullable;
    }
    
    /**
     * Get column name
     * @return string
     */
    public function getMaxValue() {
      return $this->maxValue;
    }
    
    /**
     * Get column name
     * @return string
     */
    public function getMinValue() {
      return $this->minValue;
    }
    
    /**
     * The number of distinct values in this column
     * @return int
     */
    public function getDistinctValueCount() {
      return $this->distinctValues;
    }    
  }