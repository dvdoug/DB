DB
==

[![Build Status](https://github.com/dvdoug/DB/workflows/CI/badge.svg?branch=master)](https://github.com/dvdoug/DB/actions?query=workflow%3ACI+branch%3Amaster)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dvdoug/DB/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dvdoug/DB/?branch=master)
[![Download count](https://img.shields.io/packagist/dt/dvdoug/db.svg)](https://packagist.org/packages/dvdoug/db)
[![Current version](https://img.shields.io/packagist/v/dvdoug/db.svg)](https://packagist.org/packages/dvdoug/db)

PDO-like DB access, plus helper methods. PDO is a nice idea, but has too many driver bugs and inconsistencies
to be used as-is without a wrapper. Interfaces are designed with a PDO implementation in mind, but non-PDO
implementations are allowed for in cases where drivers don't exist or are simply too buggy.

Requirements
------------
* For schema conversions, minimum MySQL version is 5.6 

License
-------
DB is MIT-licensed. 
