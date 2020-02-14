<?php

declare(strict_types=1);

namespace DVDoug\DB\Test;

use DVDoug\DB\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class MySQLPDOBaseTest extends TestCase
{
    /**
     * @var DatabaseInterface
     */
    public static $conn;

    protected function setUp(): void
    {
        $SQL = file_get_contents(__DIR__ . '/MySQL.sql');
        $statements = explode(";\n", $SQL);
        foreach ($statements as $statement) {
            static::$conn->exec($statement);
        }
    }

    public function testConnect(): void
    {
        $this->assertTrue(static::$conn instanceof DatabaseInterface);
    }

    /**
     * @depends testConnect
     */
    public function testQuoteIdentifier(): void
    {
        $this->assertEquals('`quoted`', static::$conn->quoteIdentifier('quoted'));
    }

    /**
     * @depends testConnect
     */
    public function testEscape(): void
    {
        $this->assertEquals("'quoted'", static::$conn->escape('quoted'));
        $this->assertEquals("'quoted'", static::$conn->escape('quoted', DatabaseInterface::PARAM_IS_STR));
        $this->assertEquals("'quo\'ted'", static::$conn->escape('quo\'ted'));
        $this->assertEquals("'quo\\\\ted'", static::$conn->escape('quo\\ted'));
        $this->assertEquals('12345', static::$conn->escape('12345', DatabaseInterface::PARAM_IS_INT));
        $this->assertEquals('0x' . bin2hex('escaped'), static::$conn->escape('escaped', DatabaseInterface::PARAM_IS_BLOB));
    }

    /**
     * @depends testConnect
     */
    public function testInvalidEscape(): void
    {
        $this->expectException(RuntimeException::class);
        static::$conn->escape('string', DatabaseInterface::PARAM_IS_INT);
    }

    /**
     * @depends testConnect
     */
    public function testGetTablesForOneDB(): void
    {
        $expected = ['test_integers', 'test_strings'];
        $actual = static::$conn->getTables('test');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @depends testConnect
     */
    public function testGetTablesForAllDBs(): void
    {
        $expected = ['test' => ['test_integers', 'test_strings']];

        $actual = static::$conn->getTables();
        unset($actual['information_schema']); //too variable
        unset($actual['performance_schema']); //too variable
        unset($actual['mysql']); //too variable
        unset($actual['sys']); //too variable
        unset($actual['travis']); //too variable

        $this->assertEquals($expected, $actual);
    }

    /**
     * @depends testConnect
     */
    public function testIntegerMySQLSchemaAllCols(): void
    {
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

        $actual = static::$conn->getMySQLTableDef('test', 'test_integers', false);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @depends testConnect
     */
    public function testIntegerOracleSchemaAllCols(): void
    {
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

        $actual = static::$conn->getOracleTableDef('test', 'test_integers', false);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @depends testConnect
     */
    public function testStringMySQLSchemaAllCols(): void
    {
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

        $actual = static::$conn->getMySQLTableDef('test', 'test_strings', false);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @depends testConnect
     */
    public function testStringOracleSchemaAllCols(): void
    {
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

        $actual = static::$conn->getOracleTableDef('test', 'test_strings', false);

        $this->assertEquals($expected, $actual);
    }
}
