### What Are Nested Sets?

Taken directly from [Wikipedia](http://en.wikipedia.org/wiki/Nested_set_model):

> The nested set model is a particular technique for representing [nested sets](http://en.wikipedia.org/wiki/Nested_set) (also known as [trees](http://en.wikipedia.org/wiki/Tree_(data_structure)) or [hierarchies](http://en.wikipedia.org/wiki/Hierarchy)) in [relational databases](http://en.wikipedia.org/wiki/Relational_database).

Thank you Wikipedia. What does all this mean? It means you can transform a set of rows in your database into hierarchical data; parent-child relationships.

### But Nested Sets Have Been Done Before?

Yes, that's correct, it has. Chances are though, you've been using the [Adjacency List Model](http://en.wikipedia.org/wiki/Adjacency_list). If you have, you've probably got a database table that looks a bit like:

	+----+----------------------+-----------+
	| id | name                 | parent_id |
	+----+----------------------+-----------+
	|  1 | Electronics          |      NULL |
	|  2 | Televisions          |         1 |
	|  3 | Tube                 |         2 |
	|  4 | LCD                  |         2 |
	|  5 | Plasma               |         2 |
	|  6 | Computers            |         1 |
	|  7 | Mac                  |         6 |
	|  8 | iMac                 |         7 |
	|  9 | MacBook Pro          |         7 |
	| 10 | PC                   |         6 |
	+----+----------------------+-----------+

*Note, you'll probably have a `sort` column as well in your table.*

This is alright, it's easy to gain an understanding of what's happening. However, the biggest problem with this pattern is when reading data a number of queries are required. This is bad because it increases the load on your SQL server and therefore bottlenecks your application (especially on large data-sets)

There are two common ways to approach querying data from an Adjanceny List Model setup:

#### Method 1 (Bad):

	SELECT    t1.name AS lev1, t2.name as lev2, t3.name as lev3, t4.name as lev4
	FROM      categories AS t1
	LEFT JOIN categories AS t2 ON t2.parent_id = t1.id
	LEFT JOIN categories AS t3 ON t3.parent_id = t2.id
	LEFT JOIN categories AS t4 ON t4.parent_id = t3.id
	WHERE t1.name = 'Electronics';

This is a pain because you need to know how many levels of nesting are required. Sure, you could play it safe and do (for example) 900 joins, but this is going to be mega-slow.

The data returned looks like:

	+-------------+-------------+--------------+-------------+
	| lev1        | lev2        | lev3         | lev4        |
	+-------------+-------------+--------------+-------------+
	| Electronics | Televisions | Tube         | NULL        |
	| Electronics | Televisions | LCD          | NULL        |
	| Electronics | Televisions | Plasma       | NULL        |
	| Electronics | Computers   | Mac          | iMac        |
	| Electronics | Computers   | Mac          | MacBook Pro |
	| Electronics | Computers   | PC           | NULL        |
	+-------------+-------------+--------------+-------------+

You could then loop through each record, consolidate similar records and build a hierarchical data set.

So, aside from the multiple joins, unkown levels of nesting, large data set returned and the work required to transform it into hierarchical data, this method could suffice.

#### Method 2 (Woeful):

The second method is an even simpler approach but requires a new SQL query for each node! This is ludicrous but it's been done:

	// Function to recurse results and create a tree
	function recurse_results($results, &$tree)
	{
		foreach ($results as $result)
		{
			$childResults = /* Query database for all nodes where the parent ID is this result and get array of results */;

			if ($childResults)
			{
				// Recursive
				recurse_results($childResults, $tree);
			}
		}
	}

	// Fetch the top level node
	$results = mysql_query("
		SELECT *
		FROM   categories
		WHERE  parent_id = NULL
	");

	// Prepare results
	// ...

	$tree = array();
	recurse_results($results, $tree);

This is quite frankly, disgusting, inefficient code. What would happen if you wanted to retrieve all the nodes who didn't have a parent, who didn't have children, the path of an node (all the parent's of an node up to the root node).

### Enter the "Modified Preorder Tree Traversal" (MPTT) Algorithm

Despite it's horrible name, it's actually a beautiful algorithm and is also known as the **Nested Sets Model**. In the Nested Set Model, we can look at our hierarchy in a new way, not as nodes and lines, but as nested containers. Try picturing our electronics categories this way:

![Nested Categories - Bubble](https://raw.github.com/cartalyst/nested-sets/master/resources/nested-sets-chart-bubble.png?login=bencorlett&token=693c41f1f8e03be38e9e4527cd53d6b5)

We can see from the above diagram how a parent category contains it's children, and they contain their children and so on. YOu may also notice the pattern with the numbering for the `lft` and `rgt` limits, where each child's `lft` limiit is one greater than it's parent. The same category structure can be represented as a tree:

![Nested Categories - Tree](https://raw.github.com/cartalyst/nested-sets/master/resources/nested-sets-chart-tree.png?login=bencorlett&token=09fe2749898d036012e79a6eacdbf28c)

You can read up on the Nested Sets Model over at [this blog post by Mike Hillyer](http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/). It's a great read.

Advantages:

1. It's fast. Blazingly fast. You can retrieve lots of information with just one query.

Disadvantages:

2. The database schema is a little confusing at first. Rather than having a simple `parent_id` and `sort`, we have `lft` and `rgt` limits. Unless you understand MPTT, this makes virtually zero sense.
3. Creating and updating can take multiple queries. *A way to overcome potential issues is to run the necessary queries inside a database transaction.*

We represent the same data as in the Adjacency List Model using the Nested Sets Model like so:

	+-------------+----------------------+-----+-----+
	| category_id | name                 | lft | rgt |
	+-------------+----------------------+-----+-----+
	|           1 | Electronics          |   1 |  20 |
	|           2 | Televisions          |   2 |   9 |
	|           3 | Tube                 |   3 |   4 |
	|           4 | LCD                  |   5 |   6 |
	|           5 | Plasma               |   7 |   8 |
	|           6 | Computers            |  10 |  19 |
	|           7 | Mac                  |  11 |  16 |
	|           8 | MacBook Pro          |  12 |  13 |
	|           9 | iMac                 |  14 |  15 |
	|          10 | PC                   |  17 |  18 |
	+-------------+----------------------+-----+-----+

We use the `lft` and `rgt` limits to represent the position and nesting of our nodes. Rather than going into too much detail, go and [read Mike's blog post](http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/) on the Nested Sets Model.

We have created an API for the most common operations using the MPTT Algorithm, that is compatible with [Laravel's](http://www.laravel.com) brilliant [Eloquent](http://four.laravel.com/docs/eloquent) model. However, you do not have to use this if you don't want, you can make your own implementations which use our API.
