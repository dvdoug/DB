<?php

declare(strict_types=1);

if (\PHP_MAJOR_VERSION === 7) {
    require_once 'DatabaseInterface7.php';
    require_once 'PDODatabase7.php';
} else {
    require_once 'DatabaseInterface.php';
    require_once 'PDODatabase.php';
}
