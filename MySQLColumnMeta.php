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
  class MySQLColumnMeta implements ColumnMetaInterface {
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
                                                      CHARACTER_MAXIMUM_LENGTH,
                                                      NUMERIC_PRECISION,
                                                      NUMERIC_SCALE,
                                                      IS_NULLABLE
                                               FROM INFORMATION_SCHEMA.COLUMNS
                                               WHERE TABLE_SCHEMA = :database
                                                     AND TABLE_NAME = :table_name
                                                     AND COLUMN_NAME = :column_name");
      $statement->bindParamToValue(':database', $this->database);
      $statement->bindParamToValue(':table_name', $this->table);
      $statement->bindParamToValue(':column_name', $this->name);
      $statement->execute();
      
      $meta = $statement->fetchAssoc(false);
      
      $this->type = $meta['DATA_TYPE'];
      $this->length = $meta['CHARACTER_MAXIMUM_LENGTH'];
      $this->precision = $meta['NUMERIC_PRECISION'];
      $this->scale = $meta['NUMERIC_SCALE'];
      $this->isNullable = ($meta['NULLABLE'] == 'YES');
      
      /*
       * Metadata from the data stored
       */
      $query = sprintf("SELECT COUNT(DISTINCT %s) AS COUNT FROM %s.%s",
                       $this->connection->quoteIdentifier($this->name),
                       $this->connection->quoteIdentifier($this->database),
                       $this->connection->quoteIdentifier($this->table));
      $this->distinctValues = $this->connection->query($query)->fetchAssoc(false)['COUNT'];
      
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
      return $this->type;
    }
    
    /**
     * Get column type as suitable for Oracle
     * @return string
     */
    public function getOracleType() {
      switch ($this->type) {
        case 'BIT':
        case 'TINYINT':
        case 'SMALLINT':
        case 'MEDIUMINT':
        case 'INT':
        case 'BIGINT':
        case 'DECIMAL':
          return 'NUMBER';
          
        case 'FLOAT':
          return 'BINARY_FLOAT';
          
        case 'DOUBLE':
          return 'BINARY_DOUBLE';
          
        case 'DATE':
        case 'DATETIME':
          return 'DATE';
          
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
     * Get length of column
     * @return int
     */
    public function getLength() {
      switch($this->getOriginalType()) {
        case 'BIT':
        case 'TINYINT':
        case 'SMALLINT':
        case 'MEDIUMINT':
        case 'INT':
        case 'BIGINT':
        case 'DECIMAL':
        case 'FLOAT':
        case 'DOUBLE':
        case 'CHAR':
        case 'TIME':
        case 'YEAR':
        case 'VARCHAR':
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