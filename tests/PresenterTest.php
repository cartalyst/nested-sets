<?php namespace Cartalyst\NestedSets\Tests;
/**
 * Part of the Nested Sets package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Nested Sets
 * @version    2.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Mockery as m;
use Cartalyst\NestedSets\Nodes\NodeInterface;
use Cartalyst\NestedSets\Presenter;
use PHPUnit_Framework_TestCase;

/**
 * @todo, Finish implementation and tests.
 */
class PresenterTest extends PHPUnit_Framework_TestCase {

	/**
	 * Close mockery.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		m::close();
	}

	public function testExtractingPresentableWithNormalAttribute()
	{
		$presenter = new Presenter;
		$node = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface');
		$node->shouldReceive('getAttribute')->with($attribute = 'foo')->once()->andReturn('bar');
		$this->assertEquals('bar', $presenter->extractPresentable($node, $attribute));
	}

	public function testExtractingPresentableWithClosure()
	{
		$presenter = new Presenter;
		$node = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface');
		$attribute = function(NodeInterface $node) { return 'bar'; };

		$this->assertEquals('bar', $presenter->extractPresentable($node, $attribute));
	}

	public function testPresentingArrayAsArray()
	{
		$presenter = new Presenter;

		$array = array(
			'foo',
			'bar',
			'baz' => array(
				'bat',
				'qux',
			),
		);
		$this->assertEquals($array, $presenter->presentArrayAsArray($array));
	}

	public function testPresentingArrayAsUl()
	{
		$presenter = new Presenter;

		$array = array(
			'foo',
			'bar',
			'baz' => array(
				'bat',
				'qux',
			),
		);
		$expected = '<ul><li>foo</li><li>bar</li><li>baz<ul><li>bat</li><li>qux</li></ul></li></ul>';
		$this->assertEquals($expected, $presenter->presentArrayAsUl($array));
	}

	public function testPresentingArrayAsOl()
	{
		$presenter = new Presenter;

		$array = array(
			'foo',
			'bar',
			'baz' => array(
				'bat',
				'qux',
			),
		);
		$expected = '<ol><li>foo</li><li>bar</li><li>baz<ol><li>bat</li><li>qux</li></ol></li></ol>';
		$this->assertEquals($expected, $presenter->presentArrayAsOl($array));
	}

}
