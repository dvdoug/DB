DB
==

PDO-like DB access, plus helper methods. PDO is a nice idea, but has too many driver bugs and inconsistencies
to be used as-is without a wrapper. Interfaces are designed with a PDO implementation in mind, but non-PDO
implementations are allowed for in case where drivers don't exist or are simply too buggy.

Requirements
------------

* PHP 5.3 or higher

License
-------
DB is MIT-licensed. 