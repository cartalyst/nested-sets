<?php

/*
 * Part of the Nested Sets package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Cartalyst PSL License.
 *
 * This source file is subject to the Cartalyst PSL License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Nested Sets
 * @version    7.0.0
 * @author     Cartalyst LLC
 * @license    Cartalyst PSL
 * @copyright  (c) 2011-2020, Cartalyst LLC
 * @link       https://cartalyst.com
 */

namespace Cartalyst\NestedSets\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cartalyst\NestedSets\Presenter;
use Cartalyst\NestedSets\Nodes\NodeInterface;

/**
 * @todo, Finish implementation and tests.
 */
class PresenterTest extends TestCase
{
    /**
     * Close mockery.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        m::close();
    }

    public function testExtractingPresentableWithNormalAttribute()
    {
        $presenter = new Presenter();
        $node      = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface');
        $node->shouldReceive('getAttribute')->with($attribute = 'foo')->once()->andReturn('bar');
        $this->assertSame('bar', $presenter->extractPresentable($node, $attribute));
    }

    public function testExtractingPresentableWithClosure()
    {
        $presenter = new Presenter();
        $node      = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface');
        $attribute = function (NodeInterface $node) {
            return 'bar';
        };

        $this->assertSame('bar', $presenter->extractPresentable($node, $attribute));
    }

    public function testPresentingArrayAsArray()
    {
        $presenter = new Presenter();

        $array = [
            'foo',
            'bar',
            'baz' => [
                'bat',
                'qux',
            ],
        ];
        $this->assertSame($array, $presenter->presentArrayAsArray($array));
    }

    public function testPresentingArrayAsUl()
    {
        $presenter = new Presenter();

        $array = [
            'foo',
            'bar',
            'baz' => [
                'bat',
                'qux',
            ],
        ];
        $expected = '<ul><li>foo</li><li>bar</li><li>baz<ul><li>bat</li><li>qux</li></ul></li></ul>';
        $this->assertSame($expected, $presenter->presentArrayAsUl($array));
    }

    public function testPresentingArrayAsOl()
    {
        $presenter = new Presenter();

        $array = [
            'foo',
            'bar',
            'baz' => [
                'bat',
                'qux',
            ],
        ];
        $expected = '<ol><li>foo</li><li>bar</li><li>baz<ol><li>bat</li><li>qux</li></ol></li></ol>';
        $this->assertSame($expected, $presenter->presentArrayAsOl($array));
    }
}
