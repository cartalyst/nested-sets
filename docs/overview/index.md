### An Overview of Nested sets

Nested sets is a bundle for Laravel 3 that enhances the
[Cartalyst Crud Model](https://github.com/cartalyst/crud) to use a
[Nested Sets model](http://en.wikipedia.org/wiki/Nested_set_model). This model is
unbelievably fast when retrieving a hierarchical tree of data. It is so much faster
than the [Adjancy List model](http://en.wikipedia.org/wiki/Adjacency_list)
(what you're probably used to seeing) because it uses **just one** database call
to retrieve an entire hierarchical tree of data.

This will keep your application fast and you users happy.

----------

### Features

Nested sets has made the following features available to any object that inherits
it:

- Multiple root objects
- Creating an unlimited tree of objects
- Assign first / last child of a parent object
- Assign previous / next sibling of any object
- Recursively fetch the children of an object, including limiting the depth that
objects are recursively fetched.
- Deleting objects with the choice of orphaning children or removing them.
- Outputting hierarchy in several formats, including:
	- Unordered lists `ul`
	- Ordered lists `ol`
	- XML
	- JSON
