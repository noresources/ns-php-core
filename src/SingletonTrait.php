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

/**
 * Singleton pattern trait
 */
trait SingletonTrait
{

	/**
	 * Get the class singleton instance
	 *
	 * @return object Class singleton instance. If the singleton was not created yet,
	 *         the instance will be created by calling the class constructor with the arguments
	 *         given to the getInstance() method
	 */
	public static function getInstance()
	{
		if (\is_a(self::$singletonInstance, static::class))
			return self::$singletonInstance;

		$cls = new \ReflectionClass(static::class);
		self::$singletonInstance = $cls->newInstanceArgs(func_get_args());
		return self::$singletonInstance;
	}

	/**
	 * The class singleton instance
	 *
	 * @var object
	 */
	private static $singletonInstance;
}