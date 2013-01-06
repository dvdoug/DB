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
    
    public function __construct() {
      self::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      self::setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\DVDoug\DB\PDOStatement', array()));
      self::setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
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
     * Quotes a parameter for use in a query
     * @param mixed $aParam the parameter to be quoted.
     * @param $aParamaterType data type hint for drivers
     * @return string a quoted string that is theoretically safe to pass into an SQL statement
     */
    public function quote($aParam, $aParamType = DatabaseInterface::PARAM_STR) {
      return parent::quote($aParam, $aParamType);
    }
    
    /**
     * Adds appropriate quotes to an identifier so it can be safely used in an SQL statement
     * @param mixed $aIdentifier the parameter to be quoted.
     * @return string
     */
    public function quoteIdentifier($aIdentifier) {
      return static::IDENTIFIER_OPENQUOTE . $aIdentifier . static::IDENTIFIER_CLOSEQUOTE;
    }
   
  }