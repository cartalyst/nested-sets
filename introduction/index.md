## Introduction

A modern and framework agnostic nested sets package utilizing the Modified Preorder Tree Traversal algorithm.

The package follows the FIG standard PSR-4 to ensure a high level of interoperability between shared PHP code and is fully unit-tested.

The package requires PHP 7.2.5+ and comes bundled with a Laravel 7 Service Provider to simplify the optional framework integration.

Have a [read through the Installation Guide](#installation) and on how to [Integrate it with Laravel 7](#laravel-7).

### Quick Example

#### Making a Root Node

```php
$countries = new Menu(['name' => 'Countries']);

$countries->makeRoot();
```

#### Make a Node a Child of Another Node

```php
$australia = new Menu(['name' => 'Australia']);

$australia->makeLastChildOf($countries);
```

#### Make a Node a Sibling of Another Node

```php
$england = new Menu(['name' => 'England']);

$england->makePreviousSiblingOf($newZealand);
```
