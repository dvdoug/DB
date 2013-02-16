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
        $values = explode(',,', str_replace("','", "',,'", preg_replace('/^(enum|set)\((.*)\)$/', '$2', $statement->fetchAssoc(false)['Type'])));
      
        foreach ($values as &$value) {
          $value = str_replace("''", "'", trim($value, "'"));
        }
        asort($values);
      
        $def .= $MySQLType;
        $def .= '(' . join(', ', array_map(function($c) {return "'".addslashes($c)."'";}, $values)) . ')';
      }
      else if (in_array($MySQLType, array('CHAR', 'VARCHAR')) && $this->getDistinctValueCount() <= 16) {
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
          $def .= '(' . $this->getLength() . ')';
        }
      }
      else if (in_array($MySQLType, array('DATETIME', 'TIMESTAMP', 'TIME'))) {
        $def .= $MySQLType;
        $def .= '(' . (int)$this->getScale() . ')';
      }
      else {
        $def .= $MySQLType;

        if ($this->getScale()) {
          $def .= '(' . $this->getPrecision() . ',' . $this->getScale() . ')';
        }
        else if ($this->getPrecision() && strpos($MySQLType, 'INT') === false) {
          $def .= '(' . $this->getPrecision() . ')';
        }
        else if ($this->getLength() && strpos($MySQLType, 'INT') === false) {
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
