<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources;

use NoreSources\Container\Container;
use NoreSources\Type\IntegerRepresentation;
use NoreSources\Type\StringRepresentation;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

/**
 * Semantic version
 *
 * @property integer $major Major version number
 * @property integer $minor Minor version number
 * @property integer $patch Patch level number
 * @property string $prerelease Pre-release data
 * @property string $metadata Metadata
 *
 *
 * @see https://semver.org/
 */
class SemanticVersion implements StringRepresentation,
	IntegerRepresentation, ComparableInterface
{

	/**
	 * Major version number key
	 *
	 * @var string
	 */
	const MAJOR = 'major';

	/**
	 * Minor version number key
	 *
	 * @var string
	 */
	const MINOR = 'minor';

	/**
	 * Patch level number key
	 *
	 * @var string
	 */
	const PATCH = 'patch';

	/**
	 * Pre-release string key
	 *
	 * @var string
	 */
	const PRE_RELEASE = 'prerelease';

	/**
	 * Metadata key
	 *
	 * @var string
	 */
	const METADATA = 'metadata';

	/**
	 *
	 * @param array|string|integer $version
	 *        	Version number
	 * @param number $integerFormBase10Exponent
	 *        	Number of digits used to represent each part of the version number in a integer
	 *        	re.presentation
	 */
	public function __construct($version, $integerFormBase10Exponent = 2)
	{
		$this->set($version, $integerFormBase10Exponent);
	}

	/**
	 * Deep clone of pre-release and metadata internal object
	 */
	public function __clone()
	{
		$this->prerelease = clone $this->prerelease;
		$this->metadata = clone $this->metadata;
	}

	/**
	 *
	 * @param array|string|integer $version
	 *        	Version number
	 * @param number $integerFormBase10Exponent
	 *        	Number of digits used to represent each part of the version number in a integer
	 *        	.* representation.
	 */
	public function set($version, $integerFormBase10Exponent = 2)
	{
		$this->integerFormBase = \pow(10, $integerFormBase10Exponent);
		$this->major = 0;
		$this->minor = 0;
		$this->patch = 0;
		$this->prerelease = new SemanticPostfixedData('');
		$this->metadata = new SemanticPostfixedData('');

		if ($version instanceof SemanticVersion)
		{
			$this->major = $version->major;
			$this->minor = $version->minor;
			$this->patch = $version->patch;
			$this->prerelease = clone $version->prerelease;
			$this->metadata = clone $version->metadata;
		}
		elseif (is_int($version) ||
			($version instanceof IntegerRepresentation))
		{
			$version = TypeConversion::toInteger($version);
			$p = pow(10, $integerFormBase10Exponent);
			$this->patch = $version % $p;
			$this->minor = intval($version / $p) % $p;
			$this->major = intval($version / ($p * $p));
		}
		elseif (TypeDescription::hasStringRepresentation($version) &&
			preg_match(
				chr(1) . self::PATTERN . chr(1) . self::PATTERN_MODIFIERS,
				TypeConversion::toString($version), $matches))
		{
			$this->major = Container::keyValue($matches, 1, 0);
			$this->minor = Container::keyValue($matches, 3, 0);
			$this->patch = Container::keyValue($matches, 5, 0);
			$this->prerelease->set(Container::keyValue($matches, 6, ''));
			$this->metadata->set(Container::keyValue($matches, 8, ''));
		}
		elseif (Container::isArray($version))
		{
			$this->major = Container::keyValue($version, self::MAJOR, 0);
			$this->minor = Container::keyValue($version, self::MINOR, 0);
			$this->patch = Container::keyValue($version, self::PATCH, 0);
			$this->prerelease->set(
				Container::keyValue($version, self::PRE_RELEASE, ''));
			$this->metadata->set(
				Container::keyValue($version, self::METADATA, ''));
		}
		else
		{
			$s = '';
			if (TypeDescription::hasStringRepresentation($version))
				$s = TypeConversion::toString($version);
			else
				$s = var_export($version, true);
			throw new \InvalidArgumentException(
				TypeDescription::getName($version) . ' (' . $s .
				') has no valid conversion to ' . __CLASS__);
		}
	}

	public function setIntegerFormBase10Exponent(
		$integerFormBase10Exponent)
	{
		$this->integerFormBase = \pow(10, $integerFormBase10Exponent);
	}

	/**
	 * String representation of the semantic version
	 *
	 * @return string
	 */
	public function __toString()
	{
		$s = $this->major . '.' . $this->minor . '.' . $this->patch;
		$p = strval($this->prerelease);
		$m = strval($this->metadata);
		if (strlen($p))
			$s .= '-' . $p;
		if (strlen($m))
			$s .= '+' . $m;
		return $s;
	}

	/**
	 * Get a portion of the version string
	 *
	 * @param string|integer $from
	 *        	Start from the given version part.
	 * @param string|integer $to
	 *        	Ends with the given version part.
	 * @return string
	 */
	public function slice($from, $to)
	{
		$map = [
			self::MAJOR => 0,
			self::MINOR => 1,
			self::PATCH => 2,
			self::PRE_RELEASE => 3,
			self::METADATA => 4
		];

		if (\is_string($from))
			$from = Container::keyValue($map, $from, 0);
		if (\is_string($to))
			$to = Container::keyValue($map, $to, 4);

		$parts = [
			[
				$this->major
			],
			[
				$this->minor,
				'.'
			],
			[
				$this->patch,
				'.'
			],
			[
				\strval($this->prerelease),
				'-'
			],
			[
				$this->metadata,
				'+'
			]
		];

		$s = '';
		$j = $from;
		for ($i = $from; $i <= $to; $i++)
		{
			$part = TypeConversion::toString($parts[$i][0]);
			if (\strlen($part) == 0)
				continue;

			if ($j != $from)
				$s .= $parts[$i][1];
			$s .= $part;
			$j++;
		}

		return $s;
	}

	/**
	 * Integer representation of the version.
	 *
	 * Computed as (MAJOR * 10000) + (MINOR * 100) + PATCH
	 *
	 * @return integer
	 */
	public function getIntegerValue()
	{
		return ($this->patch + $this->minor * $this->integerFormBase +
			$this->major *
			($this->integerFormBase * $this->integerFormBase));
	}

	/**
	 * Get version number component
	 *
	 * @param string $member
	 *        	Version number component
	 * @throws \InvalidArgumentException
	 * @return number|string Version number component
	 */
	public function __get($member)
	{
		switch ($member)
		{
			case self::MAJOR:
				return $this->major;
			case self::METADATA:
				return strval($this->metadata);
			case self::MINOR:
				return $this->minor;
			case self::PATCH:
				return $this->patch;
			case self::PRE_RELEASE:
				return strval($this->prerelease);
		}

		throw new \InvalidArgumentException($member);
	}

	/**
	 * Set version number part value
	 *
	 * @param string $member
	 *        	Version number part.
	 * @param mixed $value
	 *        	Value
	 * @throws \InvalidArgumentException
	 */
	public function __set($member, $value)
	{
		switch ($member)
		{
			case self::MAJOR:
				if (is_int($value) && $value >= 0)
				{
					$this->major = $value;
				}
				else
					throw new \InvalidArgumentException($value);
			break;
			case self::METADATA:
				$this->metadata->set($value);
			break;
			case self::MINOR:
				if (is_int($value) && $value >= 0)
				{
					$this->minor = $value;
				}
				else
					throw new \InvalidArgumentException($value);
			break;
			case self::PATCH:
				if (is_int($value) && $value >= 0)
				{
					$this->patch = $value;
				}
				else
					throw new \InvalidArgumentException($value);
			break;
			case self::PRE_RELEASE:
				$this->prerelease->set($value);
			break;
		}

		throw new \InvalidArgumentException($member);
	}

	/**
	 * Compare the version number against another one.
	 *
	 * @param SemanticVersion|string|numbern $b
	 *        	Version nuber to compare.
	 * @return number <ul>
	 *         <li>&lt; 0 if $a have lower precedence than $b/li>
	 *         <li>0 if $a have the same precedence than $b/li>
	 *         <li>&gt;0 0 if $a have higher precedence than $b/li>
	 *         </ul>
	 */
	public function compare($b)
	{
		return self::compareVersions($this, $b);
	}

	/**
	 * Compare two versions
	 *
	 * @param mixed $a
	 * @param mixed $b
	 * @return number <ul>
	 *         <li>&lt; 0 if $a have lower precedence than $b/li>
	 *         <li>0 if $a have the same precedence than $b/li>
	 *         <li>&gt;0 0 if $a have higher precedence than $b/li>
	 *         </ul>
	 */
	public static function compareVersions($a, $b)
	{
		if (!($a instanceof SemanticVersion))
		{
			$a = new SemanticVersion($a);
		}

		if (!($b instanceof SemanticVersion))
		{
			$b = new SemanticVersion($b);
		}

		if ($a->major != $b->major)
		{
			return ($a->major < $b->major) ? -1 : 1;
		}
		if ($a->minor != $b->minor)
		{
			return ($a->minor < $b->minor) ? -1 : 1;
		}
		if ($a->patch != $b->patch)
		{
			return ($a->patch < $b->patch) ? -1 : 1;
		}

		return $a->prerelease->compare($b->prerelease);
	}

	/**
	 * Semanic version format pattern
	 *
	 * @var string
	 */
	const PATTERN = '^([0-9]|[1-9][0-9]*)(\.([0-9]|[1-9][0-9]*)(\.([0-9]|[1-9][0-9]*))?)?(?:-([0-9a-z-]+(?:\.[0-9a-z-]+)*))?(\+([0-9a-z-]+(?:\.[0-9a-z-]+)*))?$';

	const PATTERN_MODIFIERS = 'i';

	/**
	 *
	 * @var integer
	 */
	private $integerFormBase;

	/**
	 *
	 * @var number
	 */
	private $major;

	/**
	 *
	 * @var number
	 */
	private $minor;

	/**
	 *
	 * @var number
	 */
	private $patch;

	/**
	 *
	 * @var SemanticPostfixedData
	 */
	private $prerelease;

	/**
	 *
	 * @var SemanticPostfixedData
	 */
	private $metadata;
}

/**
 * Pre-release and build metadata parts
 */
final class SemanticPostfixedData extends \ArrayObject implements
	StringRepresentation, ComparableInterface
{

	/**
	 *
	 * @param mixed $data
	 */
	public function __construct($data)
	{
		parent::__construct();
		$this->set($data);
	}

	/**
	 * Dot separated part string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return \implode('.', $this->getArrayCopy());
	}

	/**
	 *
	 * @param integer $offset
	 *        	Part index
	 * @param string $value
	 *        	Part value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		if (!(\is_numeric($offset) || \is_null($offset)))
		{
			throw new \InvalidArgumentException(
				'Non-numeric key "' . strval($offset) . '"');
		}

		self::validate($value);
		parent::offsetSet($offset, $value);
	}

	/**
	 * Set version part content
	 *
	 * @param array|string $data
	 *        	Dot-separated string representation or array of parts
	 * @throws \InvalidArgumentException
	 */
	public function set($data)
	{
		$this->exchangeArray([]);

		if (\is_string($data))
		{
			if (strlen($data) == 0)
				return;
			$data = explode('.', $data);
		}

		if (Container::isArray($data))
		{
			foreach ($data as $value)
			{
				$this->append($value);
			}
		}
		elseif (\is_numeric($data))
			$this->append($data);
		else
			throw new \InvalidArgumentException(
				'Invalid value type ' . TypeDescription::getName($data));
	}

	/**
	 * Append part
	 */
	#[\ReturnTypeWillChange]
	public function append($value)
	{
		self::validate($value);
		return parent::append($value);
	}

	/**
	 *
	 * @param SemanticPostfixedData $data
	 * @throws NotComparableException::
	 * @return number An integer value
	 *         <ul>
	 *         <li>&lt; 0 if Object have lower precedence than $data</li>
	 *         <li>0 if Object have the same precedence than $data</li>
	 *         <li>&gt;0 0 if Object have higher precedence than $data</li>
	 *         </ul>
	 */
	public function compare($data)
	{
		if (!($data instanceof SemanticPostfixedData))
			throw new NotComparableException($this, $data);
		$ia = $this->getIterator();
		$ib = $data->getIterator();
		$ca = $this->count();
		$cb = $data->count();

		if ($ca == 0)
			return ($cb == 0) ? 1 : -1;
		elseif ($cb == 0)
			return -1;

		$numericRegex = chr(1) . '^[0-9]+$' . chr(1);

		while ($ia->valid() && $ib->valid())
		{
			$va = $ia->current();
			$vb = $ib->current();

			if (preg_match($numericRegex, $va))
			{
				if (preg_match($numericRegex, $vb))
				{
					// identifiers consisting of only digits are compared numerically
					$va = intval($va);
					$vb = intval($vb);
					if ($va < $vb)
						return -1;
					elseif ($va > $vb)
						return 1;
				}

				// Numeric identifiers always have lower precedence than non-numeric identifiers
				return -1;
			}
			elseif (preg_match($numericRegex, $vb))
			{
				// Numeric identifiers always have lower precedence than non-numeric identifiers
				return 1;
			}
			else
			{
				// identifiers with letters or hyphens are compared lexically in ASCII sort order.
				$v = self::compareString($va, $vb);
				if ($v != 0)
					return $v;
			}

			$ia->next();
			$ib->next();
		}

		if ($ia->valid())
		{
			return 1;
		}

		if ($ib->valid())
		{
			return -1;
		}

		return 0;
	}

	/**
	 *
	 * @param mixed $value
	 * @throws SemanticVersionRuleException
	 */
	#[\ReturnTypeWillChange]
	private static function validate($value)
	{
		if (preg_match(chr(1) . '^0+[0-9]*$' . chr(1), $value))
			throw new SemanticVersionRuleException(9,
				'Numeric identifiers MUST NOT include leading zeroes',
				$value);

		if (!preg_match(chr(1) . '^[A-Za-z0-9-]+$' . chr(1), $value))
			throw new SemanticVersionRuleException(9,
				'Invalid pre-release/build metadata format', $value);
	}

	private static function compareString($a, $b)
	{
		$sa = strlen($a);
		$sb = strlen($b);
		$m = min($sa, $sb);
		for ($i = 0; $i < $m; $i++)
		{
			$va = ord(substr($a, $i));
			$vb = ord(substr($b, $i));

			if ($va != $vb)
			{
				return ($va < $vb) ? -1 : 1;
			}
		}

		return (($sa < $sb) ? -1 : (($sa > $sb) ? 1 : 0));
	}
}
