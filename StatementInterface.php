<?php
/**
 * Database Access Layer
 * @package DB
 * @author Doug Wright
 */
  namespace DVDoug\DB;

  /**
   * Represents a prepared statement and, after the statement is executed, an associated result set
   * @author Doug Wright
   * @package DB
   */
  interface StatementInterface extends \Traversable {

    /**
     * Used query string
     */
    public function getQueryString();

    /**
     * Executes a prepared statement
     * @return bool.
     */
    public function execute();

    /**
     * Fetches the next row(s) from the result set as an associative array
     * @param bool $aAllRows true to fetch all remaining rows, false for next row only
     * @param bool $aGroupByFirstCol whether to group the results by the first column
     * @return array|false
     */
    public function fetchAssoc($aAllRows = true, $aGroupByFirstCol = false);

    /**
     * Fetches the next row(s) from a 2-column result set as a key=>value pairs
     * @param bool $aAllRows true to fetch all remaining rows, false for next row only
     * @param bool $aGroupByFirstCol whether to group the results by the first column
     * @return array|false
     */
    public function fetchKeyPair($aAllRows = true, $aGroupByFirstCol = false);

    /**
     * Fetches the next row(s) from the result set as an object
     * @param string $aClassName if specified, object will be created as instance of this type
     * @param array $aConstructorArguments arguments to pass to constructor
     * @param bool $aRunConstructorFirst whether the constructor should be run before/after the object's members are set
     * @param bool $aAllRows true to fetch all remaining rows, false for next row only
     * @param bool $aGroupByFirstCol whether to group the results by the first column
     * @return array|false
     */
    public function fetchObj($aClassName = NULL, $aConstructorArguments = array(), $aRunConstructorFirst = true, $aAllRows = true, $aGroupByFirstCol = false);

    /**
     * Fetches the next row into the specified object
     * @param string $aObject object to update
     * @return array|false
     */
    public function fetchIntoObj($aObject);

    /**
     * Binds a value to a variable
     * @param string $aParam parameter name of the form :name
     * @param mixed $aVariable mixed The variable to bind to the parameter
     * @param int $aDataType data type for the parameter using the DatabaseInterface::PARAM_* constants.
     * @return bool
     */
    public function bindParam($aParam, &$aVariable, $aDataType = NULL);

    /**
     * Binds a column to a variable
     * @param string $aColumn column name
     * @param mixed $aVariable mixed The variable to bind to the result
     * @param int $aDataType data type for the column using the DatabaseInterface::PARAM_* constants.
     * @return bool
     */
    public function bindColumn($aColumn, &$aVariable, $aDataType = NULL);

    /**
     * Binds a value to a parameter
     * @param string $aParam parameter name of the form :name
     * @param mixed $aValue mixed The value to bind to the parameter
     * @param int $aDataType data type for the parameter using the DatabaseInterface::PARAM_* constants.
     * @return bool
     */
    public function bindValue($aParam, $aValue, $aDataType = NULL);

    /**
     * Closes the cursor, enabling the statement to be executed again.
     * @return bool
     */
    public function closeCursor();

  }