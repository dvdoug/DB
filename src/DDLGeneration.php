<?php

declare(strict_types=1);
/**
 * Database Access Layer.
 * @author Doug Wright
 */

namespace DVDoug\DB;

/**
 * Metadata about a database column.
 * @author Doug Wright
 */
trait DDLGeneration
{
    /**
     * Get MySQL column definition.
     */
    public function getMySQLColumnDef()
    {
        $def = '`' . strtolower($this->getName()) . '` ';
        $MySQLType = $this->getMySQLType();
        if (strpos($MySQLType, 'UNSIGNED') !== false) {
            $unsigned = true;
            $MySQLType = substr($MySQLType, 0, -9);
        } else {
            $unsigned = false;
        }

        if (in_array($MySQLType, ['ENUM', 'SET'])) {
            $query = sprintf('SHOW COLUMNS FROM %s.%s LIKE %s',
                $this->connection->quoteIdentifier($this->database),
                $this->connection->quoteIdentifier($this->table),
                $this->connection->escape($this->name));

            $statement = $this->connection->query($query);
            $values = $statement->fetchAssoc(false)['Type'];
            $values = explode("','", substr($values, strpos($values, '(') + 2, -2));
            $values = array_intersect_key($values, array_unique(array_map('strtolower', $values)));
            asort($values);

            $def .= $MySQLType;
            $def .= '(' . implode(', ', array_map(function ($c) {return "'" . addslashes($c) . "'"; }, $values)) . ')';
        } elseif (in_array($MySQLType, ['CHAR', 'VARCHAR']) && $this->getLength() < 64 && $this->getDistinctValueCount() <= 16) {
            $query = sprintf('SELECT DISTINCT %s FROM %s.%s WHERE %s IS NOT NULL ORDER BY %s ASC',
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->database),
                $this->connection->quoteIdentifier($this->table),
                $this->connection->quoteIdentifier($this->name),
                $this->connection->quoteIdentifier($this->name));
            $values = [];
            foreach ($this->connection->query($query) as $value) {
                $values[] = trim($value[$this->name]);
            }
            $values = array_intersect_key($values, array_unique(array_map('strtolower', $values)));
            asort($values);

            if ($values) {
                $def .= 'ENUM';
                $def .= '(' . implode(', ', array_map(function ($c) {return "'" . addslashes($c) . "'"; }, $values)) . ')';
            } else {
                $def .= $MySQLType;
                if ($this->getLength() > 0) {
                    $def .= '(' . $this->getLength() . ')';
                }
            }
        } elseif (in_array($MySQLType, ['DATETIME', 'TIMESTAMP', 'TIME'])) {
            $def .= $MySQLType;
            $def .= '(' . (int) $this->getScale() . ')';
        } else {
            $def .= $MySQLType;

            if ($this->getScale() && !in_array($MySQLType, ['DATE'])) {
                $def .= '(' . $this->getPrecision() . ',' . $this->getScale() . ')';
            } elseif ($this->getPrecision() && !in_array($MySQLType, ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT'])) {
                $def .= '(' . $this->getPrecision() . ')';
            } elseif ($this->getLength() > 0 && !in_array($MySQLType, ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT'])) {
                $def .= '(' . $this->getLength() . ')';
            }

            if ($unsigned) {
                $def .= ' UNSIGNED';
            }
        }

        if ($this->isNullable()) {
            $def .= ' NULL';
        } else {
            $def .= ' NOT NULL';
        }

        return $def;
    }

    /**
     * Get Oracle column definition.
     */
    public function getOracleColumnDef()
    {
        $def = '`' . strtolower($this->getName()) . '` ';

        $def .= $this->getOracleType();

        if ($this->getScale()) {
            $def .= '(' . $this->getPrecision() . ',' . $this->getScale() . ')';
        } elseif ($this->getPrecision()) {
            $def .= '(' . $this->getPrecision() . ')';
        } elseif ($this->getLength()) {
            $def .= '(' . $this->getLength() . ')';
        }

        if ($this->isNullable()) {
            $def .= ' NULL';
        } else {
            $def .= ' NOT NULL';
        }

        return $def;
    }
}
