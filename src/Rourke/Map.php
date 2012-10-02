<?php

/*
 * This file is part of Rourke.
 *
 * (c) Jaap Verloop <j.verloop@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rourke;

/**
 * Builds and matches a collection of PCRE routes.
 *
 * @author Jaap Verloop <j.verloop@gmail.com>
 */
class Map
{
    /**
     * Version identification
     */
    const VERSION = '1.0.0-BETA1';

    /**
     * Segments of a route.
     * @var array
     */
    protected $path = array();

    /**
     * PCRE routes and corresponding handlers
     * @var array
     */
    protected $routes = array();

    /**
     * Appends one or more segments to the path.
     *
     * Multiple segments are separated by a forward slash.
     * Empty segments are skipped.
     *
     * Literal segment:
     *  - contains at least one character
     *
     *  Syntax: foobar
     *  for more details see Rourke\Map::appendLiteral()
     *
     * Dynamic segment:
     *  - enclosed in angle brackets.
     *  - requires a name.
     *  - optional regular expression starting with a colon.
     *
     *  Syntax: <foobar> | <foobar:pcre-regex>
     *  for more details see Rourke\Map::appendDynamic()
     *
     * @param string pattern
     * @return Map
     */
    public function append($pattern) {
        foreach (explode('/', $pattern) as $value) {
            if (strlen($value) === 0) {
                continue;
            }

            if (substr($value, 0, 1) === '<' && substr($value, -1) === '>') {
                $args = explode(':', substr($value, 1, -1), 2);
                $this->appendDynamic(array_shift($args), array_shift($args));
                continue;
            }

            $this->appendLiteral($value);
        }

        return $this;
    }

    /**
     * Appends a literal segment to the path.
     *
     * All characters allowed except a forward slash.
     *
     * @param string value
     * @throws \InvalidArgumentException
     * @return Map
     */
    public function appendLiteral($value)
    {
        if (strlen($value) == 0) {
            throw new \InvalidArgumentException('Value is empty');
        }

        if (strpos($value, '/') !== false) {
            throw new \InvalidArgumentException('Value contains a forward slash');
        }

        $this->path[] = preg_quote($value, '#');
        return $this;
    }

    /**
     * Appends a dynamic segment to the path.
     *
     * Converts into a named subpattern.
     * Therefor, PCRE rules apply to both name and regex.
     *
     * The regex can't contain a forward slash. If it's empty, the default regex
     * is used which matches everything until the next forward slash.
     *
     * @param string name
     * @param string regex
     * @throws \InvalidArgumentException
     * @return Map
     */
    public function appendDynamic($name, $regex = null)
    {
        if (strpos($regex, '/') !== false) {
            throw new \InvalidArgumentException('Regex contains a forward slash');
        }

        if (strlen($regex) == 0) {
            $regex = '[^/]+';
        }

        $this->path[] = sprintf('(?P<%s>%s)', $name, $regex);
        return $this;
    }

    /**
     * Matches a path against a regular expression.
     *
     * Returns an array containing a subset of the result.
     * Values without a name are removed from the array.
     *
     * If path does not match, a boolean false is returned.
     *
     * @param string $path
     * @param string $regex
     * @return mixed
     */
    public function match($path, $regex)
    {
        if (!preg_match($regex, $path, $params)) {
            return false;
        }

        foreach ($params as $key => $value) {
            if (is_int($key)) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * Binds a handler to a route.
     *
     * The route is a regular expression.
     * It's built with the available path segments.
     *
     * The handler is a callable which take a single array argument.
     *
     * For example, a handler looks like:
     * $handler = function(array $params) {
     *      return 'Hello, World!';
     * };
     *
     * @param mixed $handler
     * @throws InvalidArgumentException
     * @return Map
     */
    public function bind($handler)
    {
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException('Invalid callback');
        }

        $regex = sprintf('#^/%s$#', implode('/', $this->path));
        $this->routes[$regex] = $handler;
        return $this;
    }

    /**
     * Calls the handler of a matching route.
     *
     * Matches the path against all the routes.
     * The parameters, result of a match, are passed as a single argument
     * through the handler and returns the response.
     *
     * If path does not match, a boolean false is returned.
     *
     * @param string $path
     * @return mixed
     */
    public function route($path)
    {
        foreach ($this->routes as $regex => $handler) {
            if (($params = $this->match($path, $regex)) !== false) {
                return call_user_func($handler, $params);
            }
        }

        return false;
    }

    /**
     * Removes one or more elements from the path.
     *
     * @param int $number
     * @return Map
     */
    public function back($number = 1)
    {
        for ($i = 0; $i < $number; $i++) {
            array_pop($this->path);
        }

        return $this;
    }

    /**
     * Removes all elements from the path.
     *
     * @return Map
     */
    public function flush()
    {
        $this->path = array();
        return $this;
    }
}
