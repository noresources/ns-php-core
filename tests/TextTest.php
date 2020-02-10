<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package Core
 */
namespace NoreSources;

final class TextTest extends \PHPUnit\Framework\TestCase
{

	final function testToHex()
	{
		$this->assertEquals('01', Text::toHexadecimalString(true), 'true');
		$this->assertEquals('00', Text::toHexadecimalString(false), 'false');
		$this->assertEquals('00', Text::toHexadecimalString(null), 'null');

		$tests = [
			1 => '01',
			1585608654548 => '01712da3fed4',
			'hello world' => '68656c6c6f20776f726c64',
			'I çay ¥€$ !' => '4920c3a7617920c2a5e282ac242021'
		];

		foreach ($tests as $input => $expected)
		{
			$this->assertEquals($expected, Text::toHexadecimalString($input),
				$input . ' (lowercase)');

			$this->assertEquals(\strtoupper($expected), Text::toHexadecimalString($input, true),
				$input . ' (uppercase)');
		}
	}
}