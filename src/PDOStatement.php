<?php
/**
 * Database Access Layer.
 * @author Doug Wright
 */

namespace DVDoug\DB;

  /**
   * PDO-backed database statement.
   * @author Doug Wright
   */
  class PDOStatement extends \PDOStatement
  {
      /**
       * Used query string.
       */
      public function getQueryString()
      {
          return $this->queryString;
      }

      /**
       * Fetches the next row(s) from the result set as an associative array.
       * @param  bool       $aAllRows         true to fetch all remaining rows, false for next row only
       * @param  bool       $aGroupByFirstCol whether to group the results by the first column
       * @return array|bool
       */
      public function fetchAssoc($aAllRows = true, $aGroupByFirstCol = false)
      {
          if ($aAllRows) {
              if ($aGroupByFirstCol) {
                  return $this->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
              } else {
                  return $this->fetchAll(\PDO::FETCH_ASSOC);
              }
          } else {
              return $this->fetch(\PDO::FETCH_ASSOC);
          }
      }

      /**
       * Fetches the next row(s) from a 2-column result set as a key=>value pairs.
       * @param  bool       $aAllRows         true to fetch all remaining rows, false for next row only
       * @param  bool       $aGroupByFirstCol whether to group the results by the first column
       * @return array|bool
       */
      public function fetchKeyPair($aAllRows = true, $aGroupByFirstCol = false)
      {
          if ($aAllRows) {
              if ($aGroupByFirstCol) {
                  return $this->fetchAll(\PDO::FETCH_KEY_PAIR | \PDO::FETCH_GROUP);
              } else {
                  return $this->fetchAll(\PDO::FETCH_KEY_PAIR);
              }
          } else {
              return $this->fetch(\PDO::FETCH_KEY_PAIR);
          }
      }

      /**
       * Fetches the next row(s) from the result set as an object.
       * @param  string     $aClassName            object will be created as instance of this type
       * @param  array      $aConstructorArguments arguments to pass to constructor
       * @param  bool       $aRunConstructorFirst  whether the constructor should be run before/after the object's members are set
       * @param  bool       $aAllRows              true to fetch all remaining rows, false for next row only
       * @param  bool       $aGroupByFirstCol      whether to group the results by the first column
       * @return array|bool
       */
      public function fetchObj($aClassName = 'stdClass', $aConstructorArguments = [], $aRunConstructorFirst = true, $aAllRows = true, $aGroupByFirstCol = false)
      {
          if ($aRunConstructorFirst) {
              $this->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $aClassName, $aConstructorArguments);
          } else {
              $this->setFetchMode(\PDO::FETCH_CLASS, $aClassName, $aConstructorArguments);
          }

          if ($aAllRows) {
              if ($aGroupByFirstCol) {
                  return $this->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_GROUP);
              } else {
                  return $this->fetchAll(\PDO::FETCH_CLASS);
              }
          } else {
              return $this->fetch(\PDO::FETCH_CLASS);
          }
      }

      /**
       * Fetches the next row into the specified object.
       * @param  string     $aObject object to update
       * @return array|bool
       */
      public function fetchIntoObj($aObject)
      {
          $this->setFetchMode(\PDO::FETCH_INTO, $aObject);

          return $this->fetch(\PDO::FETCH_INTO);
      }

      /**
       * Binds a value to a variable.
       * @param  string $aParam    parameter name of the form :name
       * @param  mixed  $aVariable mixed The variable to bind to the parameter
       * @param  int    $aDataType data type for the parameter using the DatabaseInterface::PARAM_* constants
       * @return bool
       */
      public function bindParamToVariable($aParam, &$aVariable, $aDataType = DatabaseInterface::PARAM_IS_STR)
      {
          return $this->bindParam($aParam, $aVariable, $aDataType);
      }

      /**
       * Binds a column to a variable.
       * @param  string $aColumn   column name
       * @param  mixed  $aVariable mixed The variable to bind to the result
       * @param  int    $aDataType data type for the column using the DatabaseInterface::PARAM_* constants
       * @return bool
       */
      public function bindResultColumn($aColumn, &$aVariable, $aDataType = DatabaseInterface::PARAM_IS_STR)
      {
          return $this->bindColumn($aColumn, $aVariable, $aDataType);
      }

      /**
       * Binds a value to a parameter.
       * @param  string $aParam    parameter name of the form :name
       * @param  mixed  $aValue    mixed The value to bind to the parameter
       * @param  int    $aDataType data type for the parameter using the DatabaseInterface::PARAM_* constants
       * @return bool
       */
      public function bindParamToValue($aParam, $aValue, $aDataType = DatabaseInterface::PARAM_IS_STR)
      {
          return $this->bindValue($aParam, $aValue, $aDataType);
      }
  }
