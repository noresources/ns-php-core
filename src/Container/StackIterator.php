<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources\Container;

/**
 * Stack element iterator
 */
class StackIterator implements \Iterator
{

	/**
	 *
	 * @param array $stackElements
	 *        	Reference to Stack elements array
	 */
	public function __construct(&$stackElements)
	{
		$this->stackElements = $stackElements;
		$this->index = \count($this->stackElements) - 1;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::next()
	 */
	#[\ReturnTypeWillChange]
	public function next()
	{
		return $this->index--;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::valid()
	 */
	#[\ReturnTypeWillChange]
	public function valid()
	{
		return ($this->index >= 0);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::current()
	 */
	#[\ReturnTypeWillChange]
	public function current()
	{
		return $this->stackElements[$this->index];
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::rewind()
	 */
	#[\ReturnTypeWillChange]
	public function rewind()
	{
		$this->index = \count($this->stackElements) - 1;
	}

	#[\ReturnTypeWillChange]
	public function key()
	{
		return $this->index;
	}

	/**
	 *
	 * @var array
	 */
	private $stackElements;

	/**
	 *
	 * @var integer
	 */
	private $index;
}