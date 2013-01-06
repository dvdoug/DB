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
    
    public function __construct($aHost, $aPort, $aDefaultDatabase, $aUsername, $aPassword) {
      parent::__construct("mysql:host={$aHost};port={$aPort};dbname={$aDefaultDatabase};charset=utf8", $aUsername, $aPassword);
    }
  }