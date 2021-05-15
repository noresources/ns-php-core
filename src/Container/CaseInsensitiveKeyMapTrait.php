<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package Http
 */
namespace NoreSources\Container;

/**
 * Case-insensitive key value map.
 *
 * Item access works case-insensitively but key case is preserved internally.
 *
 * Implements ArrayAccess, ContainerInterface, Countable, IteratorAggregator, ArrayRepresentation
 */
trait CaseInsensitiveKeyMapTrait
{

	/**
	 *
	 * @param array $array
	 */
	public function __construct($array = array())
	{
		$this->initializeCaseInsensitiveKeyMapTrait($array);
	}

	/**
	 *
	 * @return integer
	 */
	public function count()
	{
		return $this->map->count();
	}

	/**
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return $this->map->getIterator();
	}

	/**
	 *
	 * @return array
	 */
	public function getArrayCopy()
	{
		return $this->map->getArrayCopy();
	}

	/**
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function offsetExists($name)
	{
		return $this->caselessOffsetExists($name);
	}

	/**
	 *
	 * @param string $name
	 * @return mixed|NULL
	 */
	public function offsetGet($name)
	{
		return $this->caselessOffsetGet($name);
	}

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function offsetSet($name, $value)
	{
		return $this->caselessOffsetSet($name, $value);
	}

	/**
	 *
	 * @param string $name
	 */
	public function offsetUnset($name)
	{
		$this->caselessOffsetUnset($name);
	}

	public function exchangeArray($array)
	{
		$this->initializeCaseInsensitiveKeyMapTrait($array);
	}

	protected function initializeCaseInsensitiveKeyMapTrait(
		$array = array())
	{
		$this->map = new \ArrayObject($array);
		$this->keys = [];
		;
		foreach ($this->map as $key => $value)
			if (\is_string($key))
				$this->keys[\strtolower($key)] = $key;
	}

	protected function caselessOffsetExists($name)
	{
		if (\is_string($name))
			return Container::keyExists($this->keys, \strtolower($name));
		return $this->map->offsetExists($name);
	}

	protected function caselessOffsetGet($name)
	{
		if (\is_string($name))
			$name = Container::keyValue($this->keys, \strtolower($name),
				$name);
		return $this->map->offsetGet($name);
	}

	protected function caselessOffsetSet($name, $value)
	{
		if (\is_string($name))
		{
			$lower = \strtolower($name);
			if (($previous = Container::keyValue($this->keys, $lower)) &&
				($previous != $name))
				$this->map->offsetUnset($previous);

			$this->keys[$lower] = $name;
		}

		$this->map->offsetSet($name, $value);
	}

	protected function caselessOffsetUnset($name)
	{
		if (\is_string($name))
		{
			$lower = \strtolower($name);
			$name = Container::keyValue($this->keys, $lower, $name);
			Container::removeKey($this->keys, $lower);
		}

		$this->map->offsetUnset($name);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $map;

	/**
	 *
	 * @var array
	 */
	private $keys;
}