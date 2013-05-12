### Nested Sets

Our Nested Sets package is a PHP 5.3+, framework agnostic package for managing hierarchical data. It can be used for any website in need of:

* Categories
* Navigation
* Users
* Tags

And so much more. There are two main parts to Nested Sets:

* **Workers** - workers are the classes which do the queries on the database which respect the [Nested Sets Model](/nestedsets2/introduction/about). We provide a default worker which utilizes the [illuminate/database](https://packagist.org/packages/illuminate/database) package (which ships as part of Laravel 4).
* **Nodes** - nodes are object representations of the data in a database. We ship Nested Sets with an Eloquent Model implementation, making it super easy to get started with very little effort.


-----------

### Features

* Standard CRUD features for nested set nodes.
* Multiple tree support in one database table (e.g. could be used for multi-store websites).
* Retrieving tree (hierachical structure) data for nodes.
* Retrieving flat data for nodes.
* Retrieve all nodes without children.
* Retrieve the path of a node in the tree structure.
* Retrieve the depth of a node in a tree.
* Retrieving the depth of a node relative to any other node in it's path.
* Map a tree of nodes (or arrays) into the database.
