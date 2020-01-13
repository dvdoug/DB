<?php

namespace DVDoug\DB\Test;

use DVDoug\DB\DatabaseInterface;
use DVDoug\DB\MySQLPDODatabase;

class MySQLPDO80Test extends MySQLPDOBaseTest
{
    /**
     * @var DatabaseInterface
     */
    public static $conn;

    protected function setUp()
    {
        if (PHP_MAJOR_VERSION < 7 || !extension_loaded('pdo_mysql')) { //PHP5 doesn't seem to like MySQL8
            $this->markTestSkipped();
        }

        // docker images can take a while to boot
        $triesLeft = 10;
        $success = false;
        do {
            try {
                static::$conn = new MySQLPDODatabase('127.0.0.1', 3380, 'test', 'testuser', 'testpass');
                $success = true;
            } catch (\Exception $e) {
                --$triesLeft;
                sleep(10);
            }
        } while (!$success && $triesLeft > 0);

        parent::setUp();
    }
}
