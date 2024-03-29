<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources\Test;

use NoreSources\Container\Stack;

final class StackTest extends \PHPUnit\Framework\TestCase
{

	public function testPushPop()
	{
		$stack = new Stack();

		$stack->push(2);
		$stack->push(new \DateTime('now'));

		$this->assertCount(2, $stack);

		$a = $stack->pop();
		$this->assertCount(1, $stack);
		$this->assertInstanceOf(\DateTime::class, $a);

		$c = $stack->top();
		$this->assertEquals(2, $c);
	}

	public function testGet()
	{
		$stack = new Stack();
		$stack->push(new \DateInterval('P2000Y3M1D'));
		$this->assertEquals(2000, $stack->y);
	}

	public function testSet()
	{
		$stack = new Stack();
		$stack->push(new \DateInterval('P2000Y3M1D'));
		$stack->y = 2019;
		$this->assertEquals(2019, $stack->y);
	}

	public function testTopCall()
	{
		$stack = new Stack();
		$stack->push(new \DateTime('now'));
		$stack->setTimestamp(0);

		$this->assertEquals('1970-01-01T00:00:00+0000',
			$stack->format(\DateTIme::ISO8601));
	}

	public function testTopInvoke()
	{
		$stack = new Stack();
		$f = function ($value) {
			return $value;
		};

		$stack->push($f);
		$stack->push(42);

		$exception = null;
		$value = null;
		try
		{
			$value = $stack(42);
		}
		catch (\Exception $e)
		{
			$exception = $e;
		}

		$this->assertInstanceOf(\Exception::class, $exception);

		$stack->pop();

		$exception = null;
		$value = null;
		try
		{
			$value = $stack(42);
		}
		catch (\Exception $e)
		{
			$exception = $e;
		}

		$this->assertEquals(null, $exception);
		$this->assertEquals(42, $value,
			'Result invoked top element of stack');
	}

	public function testIterator()
	{
		$stack = new Stack();
		$stack->push(1);
		$stack->push(2);
		$stack->push(3);

		$this->assertEquals(3, $stack->top(), 'Top');
		$expected = [
			3,
			2,
			1
		];
		$actual = [];
		foreach ($stack as $value)
			$actual[] = $value;

		$this->assertEquals($expected, $actual,
			'Stack iterator direction');
	}
}
