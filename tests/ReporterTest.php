<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources\Test;

use NoreSources\Reporter;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class QuietLogger implements LoggerInterface
{
	use LoggerTrait;

	public function log($level, $message, array $context = array())
	{}
}

class EchoLogger implements LoggerInterface
{

	use LoggerTrait;

	public function log($level, $message, array $context = array())
	{
		echo ($level . ': ' . $message . PHP_EOL);
	}
}

final class ReporterTest extends \PHPUnit\Framework\TestCase
{

	final function testInstance()
	{
		$instance = Reporter::getInstance();
		$this->assertInstanceOf(Reporter::class, $instance, 'Singleton');
	}

	final function testMagic()
	{
		$instance = Reporter::getInstance();
		$instance->registerLogger('echo', new EchoLogger());
		$instance->registerLogger('echo2', new EchoLogger());

		ob_start();
		Reporter::warning('invoke warning statically');
		$actual = ob_get_contents();
		ob_end_clean();
		$expected = <<< EOS
warning: invoke warning statically
warning: invoke warning statically

EOS;
		$this->assertEquals($expected, $actual, 'Echo logger');
	}
}
