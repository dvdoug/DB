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
  trait DDLGeneration {

    /**
     * Get MySQL column definition
     */
    public function getMySQLColumnDef() {
      $def = '`' . strtolower($this->getName()) . '` ';
      $MySQLType = $this->getMySQLType();
      if (strpos($MySQLType, 'UNSIGNED') !== false) {
        $unsigned = true;
        $MySQLType = substr($MySQLType, 0, -9);
      }
      else {
        $unsigned = false;
      }

      if (in_array($MySQLType, array('ENUM', 'SET'))) {
        $query = sprintf("SHOW COLUMNS FROM %s.%s LIKE %s",
            $this->connection->quoteIdentifier($this->database),
            $this->connection->quoteIdentifier($this->table),
            $this->connection->escape($this->name));

        $statement = $this->connection->query($query);
        $values = $statement->fetchAssoc(false)['Type'];
        $values = explode("','", substr($values, strpos($values, '(') + 2, -2));
        asort($values);

        $def .= $MySQLType;
        $def .= '(' . join(', ', array_map(function($c) {return "'".addslashes($c)."'";}, $values)) . ')';
      }
      else if (in_array($MySQLType, array('CHAR', 'VARCHAR')) && $this->getLength() < 64 && $this->getDistinctValueCount() <= 16) {
        $query = sprintf("SELECT DISTINCT %s FROM %s.%s WHERE %s IS NOT NULL ORDER BY %s ASC",
                         $this->connection->quoteIdentifier($this->name),
                         $this->connection->quoteIdentifier($this->database),
                         $this->connection->quoteIdentifier($this->table),
                         $this->connection->quoteIdentifier($this->name),
                         $this->connection->quoteIdentifier($this->name));
        $values = array();
        foreach ($this->connection->query($query) as $value) {
          $values[] = trim($value[$this->name]);
        }

        if ($values) {
          $def .= 'ENUM';
          $def .= '(' . join(', ', array_map(function($c) {return "'".addslashes($c)."'";}, $values)) . ')';
        }
        else {
          $def .= $MySQLType;
          if ($this->getLength() > 0) {
            $def .= '(' . $this->getLength() . ')';
          }
        }
      }
      else if (in_array($MySQLType, array('DATETIME', 'TIMESTAMP', 'TIME'))) {
        $def .= $MySQLType;
        $def .= '(' . (int)$this->getScale() . ')';
      }
      else {
        $def .= $MySQLType;

        if ($this->getScale() && !in_array($MySQLType, array('DATE'))) {
          $def .= '(' . $this->getPrecision() . ',' . $this->getScale() . ')';
        }
        else if ($this->getPrecision() && !in_array($MySQLType, array('TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT'))) {
          $def .= '(' . $this->getPrecision() . ')';
        }
        else if ($this->getLength() > 0 && !in_array($MySQLType, array('TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT'))) {
          $def .= '(' . $this->getLength() . ')';
        }

        if ($unsigned) {
          $def .= ' UNSIGNED';
        }
      }

      if ($this->isNullable()) {
        $def .= ' NULL';
      }
      else {
        $def .= ' NOT NULL';
      }

      return $def;
    }

    /**
     * Get Oracle column definition
     */
    public function getOracleColumnDef() {
      $def = '`' . strtolower($this->getName()) . '` ';

      $def .= $this->getOracleType();

      if ($this->getScale()) {
        $def .= '(' . $this->getPrecision() . ',' . $this->getScale() . ')';
      }
      else if ($this->getPrecision()) {
        $def .= '(' . $this->getPrecision() . ')';
      }
      else if ($this->getLength()) {
        $def .= '(' . $this->getLength() . ')';
      }

      if ($this->isNullable()) {
        $def .= ' NULL';
      }
      else {
        $def .= ' NOT NULL';
      }

      return $def;
    }
  }
