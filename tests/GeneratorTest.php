<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources\Test;

use NoreSources\DateTime;

final class GeneratorTest extends \PHPUnit\Framework\TestCase
{

	public function testDateTimeGenerator()
	{
		for ($i = 0; $i < 10; $i++)
		{
			$v = Generator::randomDateTime(
				[
					'fromType' => rand(Generator::TYPE_STRING,
						Generator::TYPE_FLOAT)
				]);
			$this->assertInstanceOf(DateTIme::class, $v);
		}
	}
}
