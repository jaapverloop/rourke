<?php

/*
 * This file is part of Rourke.
 *
 * (c) Jaap Verloop <j.verloop@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rourke\Test;

use Rourke\Map;

class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Rourke\Map::append
     * @covers Rourke\Map::appendLiteral
     */
    public function testAppendPatternContainingLiteralSegment()
    {
        $map = new Map();
        $map->append('foobar');
        $this->assertAttributeSame(array('foobar'), 'path', $map);
    }

    /**
     * @covers Rourke\Map::append
     * @covers Rourke\Map::appendDynamic
     */
    public function testAppendPatternContainingDynamicSegment()
    {
        $map = new Map();
        $map->append('<foobar>');
        $this->assertAttributeSame(array('(?P<foobar>[^/]+)'), 'path', $map);
    }

    /**
     * @covers Rourke\Map::append
     * @covers Rourke\Map::appendDynamic
     */
    public function testAppendPatternContainingDynamicSegmentWithRegex()
    {
        $map = new Map();
        $map->append('<foo:(?:bar|baz)>');
        $this->assertAttributeSame(array('(?P<foo>(?:bar|baz))'), 'path', $map);
    }

    /**
     * @covers Rourke\Map::append
     */
    public function testAppendPatternContainingMultipleSegments()
    {
        $map = new Map();
        $map->append('foobar/foobaz');
        $this->assertAttributeSame(array('foobar', 'foobaz'), 'path', $map);
    }

    /**
     * @covers Rourke\Map::append
     */
    public function testAppendPatternContainingEmptySegments()
    {
        $map = new Map();
        $map->append('/');
        $this->assertAttributeSame(array(), 'path', $map);
    }

    /**
     * @covers Rourke\Map::appendLiteral
     */
    public function testAppendLiteralSegmentWithPcreChars()
    {
        $map = new Map();
        $map->appendLiteral('foo.bar');
        $this->assertAttributeSame(array('foo\.bar'), 'path', $map);
    }

    /**
     * @covers Rourke\Map::appendLiteral
     * @expectedException InvalidArgumentException
     */
    public function testAppendLiteralSegmentWithoutValue()
    {
        $map = new Map();
        $map->appendLiteral('');
    }

    /**
     * @covers Rourke\Map::appendLiteral
     * @expectedException InvalidArgumentException
     */
    public function testAppendLiteralSegmentContainingForwardSlash()
    {
        $map = new Map();
        $map->appendLiteral('foo/bar');
    }

    /**
     * @covers Rourke\Map::appendDynamic
     * @expectedException InvalidArgumentException
     */
    public function testAppendDynamicSegmentContainingForwardSlash()
    {
        $map = new Map();
        $map->appendDynamic('foobar', 'foo/bar');
    }

    /**
     * @covers Rourke\Map::match
     */
    public function testMatchPath()
    {
        $map = new Map();
        $this->assertFalse($map->match('/foo/bar', '#^/foo/baz$#'));
        $this->assertSame(array('foo' => 'bar'), $map->match('/foo/bar', '#^/foo/(?P<foo>[^/]+)$#'));
    }

    /**
     * @covers Rourke\Map::bind
     */
    public function testBindToPathWithoutSegments()
    {
        $map = new Map();
        $map->bind('var_dump');
        $this->assertAttributeSame(array('#^/$#' => 'var_dump'), 'routes', $map);
    }

    /**
     * @covers Rourke\Map::bind
     */
    public function testBindToPathWithSingleSegment()
    {
        $map = new Map();
        $map->append('foo')->bind('var_dump');
        $this->assertAttributeSame(array('#^/foo$#' => 'var_dump'), 'routes', $map);
    }

    /**
     * @covers Rourke\Map::bind
     */
    public function testBindToPathWithMultipleSegments()
    {
        $map = new Map();
        $map->append('foo/bar')->bind('var_dump');
        $this->assertAttributeSame(array('#^/foo/bar$#' => 'var_dump'), 'routes', $map);
    }

    /**
     * @covers Rourke\Map::bind
     * @expectedException InvalidArgumentException
     */
    public function testBindInvalidCallable()
    {
        $map = new Map();
        $map->bind('foobar');
    }

    /**
     * @covers Rourke\Map::route
     */
    public function testRouteWithMatch()
    {
        $test = $this;
        $handler = function($params) use($test) {
            $test->assertCount(1, func_get_args());
            $test->assertSame(array('param' => 'foobar'), $params);
        };

        $map = new Map();
        $map->append('<param:foobar>')->bind($handler)->route('/foobar');
    }

    /**
     * @covers Rourke\Map::route
     */
    public function testRouteWithoutMatch()
    {
        $map = new Map();
        $this->assertFalse($map->route('/foobar'));
    }

    /**
     * @covers Rourke\Map::back
     */
    public function testBackSingleSegment()
    {
        $map = new Map();
        $map->append('foo/bar')->back();
        $this->assertAttributeSame(array('foo'), 'path', $map);
    }

    /**
     * @covers Rourke\Map::back
     */
    public function testBackMultipleSegments()
    {
        $map = new Map();
        $map->append('foo/bar/foo/baz')->back(2);
        $this->assertAttributeSame(array('foo', 'bar'), 'path', $map);
    }

    /**
     * @covers Rourke\Map::flush
     */
    public function testFlush()
    {
        $map = new Map();
        $map->append('foo/bar')->flush();
        $this->assertAttributeSame(array(), 'path', $map);
    }
}
