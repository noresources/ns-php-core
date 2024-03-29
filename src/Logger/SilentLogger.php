<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources\Logger;

use NoreSources\SingletonTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * A logger that does nothing
 */
class SilentLogger implements LoggerInterface
{
	use LoggerTrait;
	use SingletonTrait;

	public function __construct()
	{}

	/**
	 * Do nothing
	 *
	 * {@inheritdoc}
	 * @see \Psr\Log\LoggerInterface::log()
	 */
	public function log($level, $message, array $context = array())
	{}
}