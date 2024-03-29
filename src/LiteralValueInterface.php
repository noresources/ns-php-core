<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package Core
 */
namespace NoreSources;

/**
 * Represents an object holding a literal value
 */
interface LiteralValueInterface
{

	/**
	 *
	 * @return null|boolean|integer|float|string
	 */
	function getLiteralValue();
}
