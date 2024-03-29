<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources\Type;

use NoreSources\DateTime;
use NoreSources\Container\Container;
use NoreSources\Container\InvalidContainerException;

/**
 * Type conversion utility class
 */
class TypeConversion
{

	/**
	 *
	 * @param mixed $value
	 *        	Value to convert
	 * @param string $type
	 *        	Target type.
	 * @param callable $fallback
	 *        	A cacallback to invoke if the method is nuable to convert the value e
	 * @throws \BadMethodCallException
	 */
	public static function to($value, $type, $fallback)
	{
		$methodName = 'to' . $type;
		if (\method_exists(self::class, $methodName))
			return call_user_func([
				self::class,
				$methodName
			], $value, $fallback);

		throw new \BadMethodCallException(
			'Mo method to convert to ' . $type . ' (' . $methodName .
			' not found)');
	}

	/**
	 * Convert value to POD array
	 *
	 * @param mixed $value
	 *        	Value to convert
	 * @param callable $fallback
	 *        	A cacallback to invoke if the method is nuable to convert the value e
	 * @throws TypeConversionException
	 * @return array
	 */
	public static function toArray($value, $fallback = null)
	{
		try
		{
			$v = Container::createArray($value, null);
		}
		catch (InvalidContainerException $e)
		{
			$v = null;
		}

		if (\is_array($v))
			return $v;

		if (\is_callable($fallback))
			return call_user_func($fallback, $value);

		throw new TypeConversionException($value, __METHOD__);
	}

	/**
	 * Convert value to DateTime
	 *
	 * @param mixed $value
	 *        	Value to convert
	 * @param \DateTimeZone $timezone
	 *        	Time zone of constructed DateTime.
	 * @param callable $fallback
	 *        	A cacallback to invoke if the method is nuable to convert the value e
	 * @throws TypeConversionException
	 * @return \DateTimeInterface A DateTime with the given time zone.
	 */
	public static function toDateTime($value, $timezone = null,
		$fallback = null)
	{
		// Backward compatibility
		if (\is_callable($timezone) && !\is_callable($fallback))
		{
			$fallback = $timezone;
			$timezone = null;
		}

		if ($timezone && !($timezone instanceof \DateTimeZone))
			$timezone = new \DateTimeZone($timezone);

		$message = null;
		$d = null;
		if ($value instanceof \DateTimeInterface)
		{
			$d = $value;
			if ($timezone && ($timezone !== $value->getTimezone()))
				$d = clone $value;
		}
		elseif (\is_float($value) || \is_integer($value))
			$d = new DateTime($value, $timezone);
		elseif (\is_string($value))
		{
			try
			{
				$d = new DateTime($value, $timezone);
			}
			catch (\Exception $e)
			{
				$message = $e->getMessage();
			}
		}
		elseif (DateTime::isDateTimeStateArray($value))
			$d = DateTime::createFromArray($value);
		elseif (Container::isArray($value))
		{
			try
			{
				$d = DateTime::createFromArray($value);
			}
			catch (\Exception $e)
			{
				$message = 'Unsupported array content. ' .
					$e->getMessage();
			}
		}

		if ($d instanceof \DateTimeInterface)
		{
			if ($timezone)
				$d->setTimezone($timezone);
			return $d;
		}

		if (\is_callable($fallback))
			return \call_user_func($fallback, $value, $timezone);

		throw new TypeConversionException($value, __METHOD__, $message);
	}

	/**
	 * Convert value to integer
	 *
	 * @param mixed $value
	 *        	Value to convert
	 * @param callable $fallback
	 *        	A cacallback to invoke if the method is nuable to convert the value e
	 * @throws TypeConversionException
	 * @return integer
	 */
	public static function toInteger($value, $fallback = null)
	{
		if ($value instanceof IntegerRepresentation)
			return $value->getIntegerValue();

		if ($value instanceof \DateTimeInterface)
			return $value->getTimestamp();
		elseif ($value instanceof \DateTimeZone)
			return $value->getOffset(
				new DateTime('now', DateTime::getUTCTimezone()));
		elseif (\is_bool($value))
			return ($value ? 1 : 0);
		elseif (\is_null($value))
			return 0;

		if (\is_numeric($value))
		{
			$i = @intval($value);
			if (\is_integer($i))
				return $i;
		}

		if (\is_callable($fallback))
			return call_user_func($fallback, $value);

		throw new TypeConversionException($value, __METHOD__);
	}

	/**
	 * Convert value to float
	 *
	 * @param mixed $value
	 *        	Value to convert
	 * @param callable $fallback
	 *        	A cacallback to invoke if the method is nuable to convert the value e
	 * @throws TypeConversionException
	 * @return float
	 */
	public static function toFloat($value, $fallback = null)
	{
		if ($value instanceof FloatRepresentation)
			return $value->getFloatValue();

		if ($value instanceof \DateTimeInterface)
			return DateTime::toJulianDay($value);
		elseif ($value instanceof \DateTimeZone)
			return \floatval(
				$value->getOffset(
					new DateTime('now', DateTime::getUTCTimezone())));
		elseif (\is_bool($value))
			return ($value ? 1. : 0.);
		elseif (\is_null($value))
			return 0.;

		if (\is_numeric($value))
		{
			$f = @floatval($value);
			if (\is_float($f))
				return $f;
		}

		if (\is_callable($fallback))
			return call_user_func($fallback, $value);

		throw new TypeConversionException($value, __METHOD__);
	}

	/**
	 * Convert value to string
	 *
	 * @param mixed $value
	 *        	Value to convert to string
	 * @param callable $fallback
	 *        	This callable will be invoked if there is no straigntforward conversion.
	 *        	The callable must accept one argument (the value) and return a string or
	 *        	FALSE if it is unable to convert the value to string.
	 * @throws TypeConversionException
	 * @return string
	 */
	public static function toString($value, $fallback = null)
	{
		if (\is_string($value))
			return $value;
		elseif (\is_numeric($value))
			return \strval($value);
		elseif (\is_object($value))
		{
			if (\method_exists($value, '__toString'))
				return \call_user_func([
					$value,
					'__toString'
				]);
			elseif ($value instanceof \DateTimeInterface)
				return $value->format(\DateTIme::ISO8601);
			elseif ($value instanceof \DateTimeZone)
				return $value->getName();
			elseif ($value instanceof \Serializable)
				return $value->serialize();
			elseif ($value instanceof \JsonSerializable &&
				\function_exists('\json_encode'))
				return \json_encode($value->jsonSerialize());

			if (\is_callable($fallback))
				return \call_user_func($fallback, $value);
			throw new TypeConversionException($value, __METHOD__);
		}
		elseif (\is_array($value))
		{
			if (\is_callable($fallback))
				return \call_user_func($fallback, $value);
			throw new TypeConversionException($value, __METHOD__);
		}

		$s = @\strval($value);
		if ($s !== false)
			return $s;

		if (\is_callable($fallback))
			$s = \call_user_func($fallback, $value);

		if ($s !== false)
			return $s;

		throw new TypeConversionException($value, __METHOD__);
	}

	/**
	 * Convert value to boolean
	 *
	 * @param mixed $value
	 *        	Value to convert
	 * @return boolean
	 */
	public static function toBoolean($value)
	{
		if ($value instanceof BooleanRepresentation)
			return $value->getBooleanValue();
		return @boolval($value);
	}

	/**
	 * Convert any value to NULL
	 *
	 * @param mixed $value
	 *        	Value to convert
	 * @return NULL, obviously...
	 */
	private static function toNull($value)
	{
		return null;
	}
}