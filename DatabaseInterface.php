<?php
/**
 * Database Access Layer
 * @package DB
 * @author Doug Wright
 */
  namespace DVDoug\DB;

  /**
   * Represents a connection to a database server
   * @author Doug Wright
   * @package DB
   */
  interface DatabaseInterface {
    
    const PARAM_NULL = 0;
    const PARAM_INT = 1;
    const PARAM_STR = 2;
    const PARAM_LOB = 3;
    const PARAM_BOOL = 5;

    /**
     * Prepares a SQL statement for execution and returns a statement object
     * @param string $aSQL
     * @return StatementInterface
     */
    public function prepare($aSQL);

    /**
     * Initiates a transaction
     * @return bool
     */
    public function beginTransaction();

    /**
     * Commits a transaction
     * @return bool
     */
    public function commit();

    /**
     * Rolls back a transaction
     * @return bool
     */
    public function rollBack();

    /**
     * Checks if inside a transaction
     * @return bool
     */
    public function inTransaction();

    /**
     * Executes an SQL statement, returning the result set if any as a StatementInterface object 
     * @param string $aSQL the SQL statement to execute.
     * @return StatementInterface|true
     */
    public function query($aSQL);

    /**
     * Returns the ID of the last inserted row or sequence value
     * @param string $aName name of the sequence object (if any) from which the ID should be returned
     * @return string
     */
    public function getLastInsertId($aName = NULL);

    /**
     * Quotes a parameter for use in a query
     * @param mixed $aParam the parameter to be quoted.
     * @param $aParamaterType data type hint for drivers
     * @return string a quoted string that is theoretically safe to pass into an SQL statement
     */
    public function quote($aParam, $aParamType = DatabaseInterface::PARAM_STR, $aForLike = false);
    
    /**
     * Adds appropriate quotes to an identifier so it can be safely used in an SQL statement
     * @param mixed $aIdentifier the parameter to be quoted.
     * @return string
     */
    public function quoteIdentifier($aIdentifier);
   
  }