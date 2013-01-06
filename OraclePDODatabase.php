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
    
    public function __construct($aConnectionString, $aUsername, $aPassword) {
      parent::__construct("oci:dbname={$aConnectionString};charset=AL32UTF8", $aUsername, $aPassword);
    }
  }