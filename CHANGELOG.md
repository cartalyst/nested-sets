# Changelog

### v8.0.0 - 2022-04-06

- Updated for Laravel 9.

### v7.0.0 - 2020-12-23

- BC Break: PHP 7.4 is the minimum required PHP version
- Fixed issue with presenter not being properly set on PHP 7.4
- Add PHP 8 support

### v6.0.0 - 2020-09-12

- Updated for Laravel 8.

### v5.0.0 - 2020-03-03

- Updated for Laravel 7.

### v4.0.0 - 2019-09-11

- BC Break: PHP 7.2 is the minimum required PHP version
- BC Break: Laravel 6.0 is the minimum supported Laravel version

### v3.1.3 - 2017-11-10

`FIXED`

- Added missing json presenter method.

### v3.1.2 - 2017-11-08

`FIXED`

- A bug affecting the `getPath` method.

### v3.1.1 - 2017-01-30

- Updated service provider for laravel 5.4 support.

### v3.1.0 - 2016-06-28

- Implemented `moveNodeAsRoot()`.
- Changed slideNodeInTree to check consistency of tree attribute
- Added `nextSibling()` and `prevSibling()` to worker.
- Added `getNextSibling()` and `getPrevSibling()` to trait.

### v3.0.0 - 2015-03-02

- Refactored to use a trait instead of a base node class.

### v2.0.3 - 2015-02-25

`FIXED`

- An issue when used with PostgreSQL.

`REVISED`

- Switched to PSR-2, PSR-4.

### v2.0.2 - 2014-01-07

`ADDED`

- `getDepth` method.

`FIXED`

- A bug when using other than mysql database drivers.

### v2.0.1 - 2013-11-27

`ADDED`

- `allFlat` method. Returns a collection containing a flat array of all nodes.

`FIXED`

- Fixed a bug introduced by an Eloquent change. [#52]

### v2.0.0 - 2013-08-22

- Initial release.
