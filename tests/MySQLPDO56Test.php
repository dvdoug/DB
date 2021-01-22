<?php

declare(strict_types=1);

namespace DVDoug\DB\Test;

use DVDoug\DB\DatabaseInterface;
use DVDoug\DB\MySQLPDODatabase;
use Exception;
use function extension_loaded;
use function sleep;

class MySQLPDO56Test extends MySQLPDOBaseTest
{
    /**
     * @var DatabaseInterface
     */
    public static $conn;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_mysql')) {
            $this->markTestSkipped();
        }

        // docker images can take a while to boot
        $triesLeft = 10;
        $success = false;
        do {
            try {
                static::$conn = new MySQLPDODatabase('127.0.0.1', 3356, 'test', 'testuser', 'testpass');
                $success = true;
            } catch (Exception $e) {
                --$triesLeft;
                sleep(10);
            }
        } while (!$success && $triesLeft > 0);

        parent::setUp();
    }
}
