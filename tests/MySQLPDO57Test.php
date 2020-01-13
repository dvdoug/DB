<?php

namespace DVDoug\DB\Test;

use DVDoug\DB\DatabaseInterface;
use DVDoug\DB\MySQLPDODatabase;

class MySQLPDO57Test extends MySQLPDOBaseTest
{
    /**
     * @var DatabaseInterface
     */
    public static $conn;

    protected function setUp()
    {
        if (!extension_loaded('pdo_mysql')) {
            $this->markTestSkipped();
        }

        // docker images can take a while to boot
        $triesLeft = 10;
        $success = false;
        do {
            try {
                static::$conn = new MySQLPDODatabase('127.0.0.1', 3357, 'test', 'testuser', 'testpass');
                $success = true;
            } catch (\Exception $e) {
                --$triesLeft;
                sleep(10);
            }
        } while (!$success && $triesLeft > 0);

        parent::setUp();
    }
}
