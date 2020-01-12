<?php

  namespace DVDoug\DB\Test;
  
  use DVDoug\DB\DatabaseInterface;
  use DVDoug\DB\MySQLPDODatabase;
  use PHPUnit\Framework\TestCase;

  class MySQLPDOTest extends TestCase {
    
    /**
     * @var DatabaseInterface 
     */
    public static $conn;
    
    public function testConnect() {
      
      if (!extension_loaded('pdo_mysql')) {
        $this->markTestSkipped();
      }
      
      static::$conn = new MySQLPDODatabase('localhost', 3306, 'test', 'travis', '');
      self::assertTrue(self::$conn instanceof DatabaseInterface);
    }
    
    /**
     * @depends testConnect
     */
    public function testQuoteIdentifier() {
      $this->setUpDB();
      
      self::assertEquals('`quoted`', self::$conn->quoteIdentifier('quoted'));
    }
    
    /**
     * @depends testConnect
     */
    public function testEscape() {
      $this->setUpDB();
    
      self::assertEquals("'quoted'", self::$conn->escape('quoted'));
      self::assertEquals("'quoted'", self::$conn->escape('quoted', DatabaseInterface::PARAM_IS_STR));
      self::assertEquals("'quo\'ted'", self::$conn->escape('quo\'ted'));
      self::assertEquals("'quo\\\\ted'", self::$conn->escape('quo\\ted'));
      self::assertEquals('12345', self::$conn->escape('12345', DatabaseInterface::PARAM_IS_INT));
      self::assertEquals('0x' . bin2hex('escaped'), self::$conn->escape('escaped', DatabaseInterface::PARAM_IS_BLOB));
    }

    /**
     * @depends testConnect
     * @expectedException \RuntimeException
     */
    public function testInvalidEscape() {
      $this->setUpDB();
      self::$conn->escape('string', DatabaseInterface::PARAM_IS_INT);
    }
    
    /**
     * @depends testConnect
     */
    public function testGetTablesForOneDB() {
      $this->setUpDB();
      
      $expected = array('test_integers', 'test_strings');
      $actual = self::$conn->getTables('test');

      self::assertEquals($expected, $actual);
    }
    
    /**
     * @depends testConnect
     */
    public function testGetTablesForAllDBs() {
      $this->setUpDB();
      
      $expected = array('test'=> array('test_integers', 'test_strings'));
      
      $actual = self::$conn->getTables();
      unset($actual['information_schema']); //too variable
      unset($actual['performance_schema']); //too variable
      unset($actual['mysql']); //too variable
      unset($actual['sys']); //too variable
      unset($actual['travis']); //too variable

      self::assertEquals($expected, $actual);
    }
    
    /**
     * @depends testConnect
     */
    public function testIntegerMySQLSchemaAllCols() {
      $this->setUpDB();
    
      $expected = <<<ENDSCHEMA
CREATE TABLE `test_integers` (
`tinyint` TINYINT NOT NULL,
`smallint` SMALLINT NOT NULL,
`mediumint` MEDIUMINT NOT NULL,
`int` INT NOT NULL,
`bigint` BIGINT NOT NULL,
`tinyint_unsigned` TINYINT UNSIGNED NOT NULL,
`smallint_unsigned` SMALLINT UNSIGNED NOT NULL,
`mediumint_unsigned` MEDIUMINT UNSIGNED NOT NULL,
`int_unsigned` INT UNSIGNED NOT NULL,
`bigint_unsigned` BIGINT UNSIGNED NOT NULL,
`int_null` INT NULL,
`int_unsigned_null` INT UNSIGNED NULL,
`int_default_null` INT NULL,
`int_default_12345` INT NOT NULL) ENGINE=InnoDB ROW_FORMAT=COMPRESSED
ENDSCHEMA;
     
      $actual = self::$conn->getMySQLTableDef('test', 'test_integers', false);
   
      self::assertEquals($expected, $actual);
    }
    
    /**
     * @depends testConnect
     */
    public function testIntegerOracleSchemaAllCols() {
      $this->setUpDB();
    
      $expected = <<<ENDSCHEMA
CREATE TABLE `test_integers` (
`tinyint` NUMBER(3) NOT NULL,
`smallint` NUMBER(5) NOT NULL,
`mediumint` NUMBER(7) NOT NULL,
`int` NUMBER(10) NOT NULL,
`bigint` NUMBER(19) NOT NULL,
`tinyint_unsigned` NUMBER(3) NOT NULL,
`smallint_unsigned` NUMBER(5) NOT NULL,
`mediumint_unsigned` NUMBER(7) NOT NULL,
`int_unsigned` NUMBER(10) NOT NULL,
`bigint_unsigned` NUMBER(20) NOT NULL,
`int_null` NUMBER(10) NULL,
`int_unsigned_null` NUMBER(10) NULL,
`int_default_null` NUMBER(10) NULL,
`int_default_12345` NUMBER(10) NOT NULL)
ENDSCHEMA;
       
      $actual = self::$conn->getOracleTableDef('test', 'test_integers', false);
  
      self::assertEquals($expected, $actual);
    }
    
    /**
     * @depends testConnect
     */
    public function testStringMySQLSchemaAllCols() {
      $this->setUpDB();
    
      $expected = <<<ENDSCHEMA
CREATE TABLE `test_strings` (
`varchar` VARCHAR(12) NOT NULL,
`char` ENUM('foo1', 'foo10', 'foo2', 'foo3', 'foo4', 'foo5', 'foo6', 'foo7', 'foo8', 'foo9') NOT NULL,
`varchar_null` VARCHAR(45) NULL,
`char_null` CHAR(67) NULL,
`enum` ENUM('abc', 'def') NOT NULL,
`enum_null` ENUM('hij', 'klm') NULL,
`set` SET('nop', 'qrs') NOT NULL,
`set_null` SET('tuv', 'wxyz') NULL) ENGINE=InnoDB ROW_FORMAT=COMPRESSED
ENDSCHEMA;
       
      $actual = self::$conn->getMySQLTableDef('test', 'test_strings', false);
  
      self::assertEquals($expected, $actual);
    }
    
    /**
     * @depends testConnect
     */
    public function testStringOracleSchemaAllCols() {
      $this->setUpDB();
    
      $expected = <<<ENDSCHEMA
CREATE TABLE `test_strings` (
`varchar` NVARCHAR(12) NOT NULL,
`char` CHAR(34) NOT NULL,
`varchar_null` NVARCHAR(45) NULL,
`char_null` CHAR(67) NULL,
`enum` NVARCHAR NOT NULL,
`enum_null` NVARCHAR NULL,
`set` NVARCHAR NOT NULL,
`set_null` NVARCHAR NULL)
ENDSCHEMA;
    
      $actual = self::$conn->getOracleTableDef('test', 'test_strings', false);
    
      self::assertEquals($expected, $actual);
    }
    
    
    private function setUpDB() {
      $SQL = file_get_contents(__DIR__.'/MySQL.sql');
      $statements = explode(";\r\n", $SQL);
      foreach ($statements as $statement) {
        static::$conn->exec($statement);
      }
    }
  
  }
