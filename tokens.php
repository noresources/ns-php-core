<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package Core
 */
namespace NoreSources;

const kTokenTypeUnknown = -1;
const kTokenTypeString = 0;
const kTokenTypeElement = 1;

/**
 * Remove line feeds in token content
 * @var integer
 */
const kTokenDumpSingleLine = 0x01;

/**
 * Always display whitespaces as a single space
 * @var integer
 */
const kTokenDumpCondensedWhitespaces = 0x02;

/**
 * Output all whitespaces as single space
 * @var integer
 */
const kTokenOutputCondensedWhitespaces = 0x02;

/**
 * Output code inside anonymous namespace if the code does not
 * reference any namespace
 * @var integer
 */
const kTokenOutputForceNamespace = 0x04;

/**
 * Do not output PHP open/close tags
 * @var integer
 */
const kTokenOutputIgnorePhpTags  = 0x08;

/**
 * Ignore all tokens which are not PHP code.
 * Implies @c kTokenOutputIgnorePhpTags
 * @var integer
 */
const kTokenOutputIgnoreInlineHTML = 0x18;

/**
 * @var integer
 */
const kTokenOutputIgnoreComments = 0x20;

/**
 * Move to the next token kind
 * @param array $tokens A token arary given by token_get_all()
 * @param int $tokenIndex Index of the current token
 * @param mixed $nextElementType Token to search
 *
 * @return The
 */
function token_move_next(&$tokens, &$tokenIndex, $nextElementType)
{
	$c = count($tokens);
	$tokenIndex++;
	while ($tokenIndex < $c)
	{
		$token = $tokens [$tokenIndex];
		if (\is_array($token) && \is_int($nextElementType) && ($token [0] == $nextElementType))
		{
			return $token;
		}
		elseif (is_string($token) && is_string($nextElementType) && ($token == $nextElementType))
		{
			return $token;
		}

		$tokenIndex++;
	}

	return null;
}

/**
 * Get token type
 * @param mixed $token a element of the token array given by token_get_all()
 * @return integer One of kTokenType* constants
 */
function token_type($token)
{
	if (is_array($token) && (count($token) == 3))
	{
		return kTokenTypeElement;
	}
	elseif (is_string($token))
	{
		return kTokenTypeString;
	}

	return kTokenTypeUnknown;
}

/**
 * Get the list of namespace declarations
 * @param array $tokens A token arary given by token_get_all()
 * @return multitype:
 */
function token_get_namespaces (&$tokens)
{
	$namespaces = array ();
	$visitor = token_get_visitor($tokens);

	// Search for namespaces
	$ns = null;
	while (($ns = $visitor->moveToToken(T_NAMESPACE)))
	{
		$search = $visitor->queryNextTokens(array (
				T_STRING,
				'{',
				';'
		), true);
		ksort($search);
		list ( $index, $entry ) = each($search);
		$token = $entry ['token'];
		$name = '';
		if ((token_type($token) == kTokenTypeElement) && ($token [0] == T_STRING))
		{
			$name = $token [1];
		}
			
		$item = array (
				'index' => $visitor->key(),
				'name' => $name
		);
			
		$namespaces [] = $item;
	}

	return $namespaces;
}

/**
 *
 * @param mixed $token An element of the token array given by token_get_all()
 */
function token_value($token)
{
	if (is_array($token))
	{
		return $token [1];
	}
	elseif (is_string($token))
	{
		return $token;
	}
	return null;
}

/**
 *
 * @param array $tokens A token array given by token_get_all()
 */
function token_get_visitor(&$tokens)
{
	return (new TokenVisitor($tokens));
}

function token_output (&$tokens, $flags = 0, $namespaces = null)
{
	$output =  '';
	$condensedWhitespace = '';
	$echoTag = false;
	
	if (!is_array ($namespaces))
	{
		$namespaces = token_get_namespaces($tokens);
	}

	$visitor = token_get_visitor($tokens);
	if ($flags & kTokenOutputIgnorePhpTags)
	{
		$openTag  = $visitor->moveToToken(T_OPEN_TAG);
	}

	while ($visitor->valid())
	{
		$token = $visitor->current();
		$type = token_type ($token);
		$value = token_value ($token);

		if ($type == kTokenTypeString)
		{
			$output .= $value;
		}
		elseif ($type == kTokenTypeElement)
		{
			switch ($token[0])
			{
				case T_OPEN_TAG_WITH_ECHO:
				{
					if ($flags & kTokenOutputIgnorePhpTags)
					{
						$output .= 'echo (';
						$echoTag = true;
					}
					else 
					{
						$output .= $value;
					}
				} break;
				case T_OPEN_TAG:
				{
					if (!($flags & kTokenOutputIgnorePhpTags))
					{
						$output .= $value;
					}
					
					if (($flags & kTokenOutputForceNamespace) && (count($namespaces) == 0))
					{
						$output .= 'namespace';
						$s = ($flags & kTokenOutputCondensedWhitespaces) ? $condensedWhitespace : PHP_EOL;
						$output .= $s . '{' . $s;
					}
					
				} break;
				case T_CLOSE_TAG:
				{
					if ($echoTag)
					{
						echo ');';
					}
					else 
					{
						if (($flags & kTokenOutputForceNamespace) && (count($namespaces) == 0))
						{
							$s = ($flags & kTokenOutputCondensedWhitespaces) ? $condensedWhitespace : PHP_EOL;
							$output .= $s . '}';
						}
						
						if (!($flags & kTokenOutputIgnorePhpTags))
						{
							$output .= $value;
						}
					}
					$echoTag = false;
				}
				case T_INLINE_HTML:
				{
						if (!($flags & kTokenOutputIgnoreInlineHTML))
						{
							$output .= $value;
						}
				} break;
				case T_WHITESPACE:
				{
					$output .= (($flags & kTokenOutputCondensedWhitespaces) ? $condensedWhitespace : $value);
				}
				break;
				case T_COMMENT:
				case T_DOC_COMMENT:
				{
					if (!($flags & kTokenOutputIgnoreComments))
					{
						$output .= $value;
					}
				} break;
				default:
					$output .= $value;
			}
		}

		$visitor->next();
		if (strlen ($output))
		{
			$condensedWhitespace = ' ';
		}
	}
	
	return $output;
}

/**
 * Dump token table to a condensed format
 * @param unknown $tokens Token array given by token_get_all ()
 * @param string $eol A string to add after each entry output
 * @param number $flags Display options
 */
function token_dump($tokens, $eol = PHP_EOL, $flags = 0)
{
	$i = 0;
	$result = '';
	foreach ($tokens as $t)
	{
		$type = token_type($t);
		$name = ($type == kTokenTypeElement) ? token_name($t [0]) : 'string';
		$value = token_value($t);

		if (($flags & kTokenDumpCondensedWhitespaces) && ($type == kTokenTypeElement) && ($t [1] == T_WHITESPACE))
		{
			$value = '';
		}

		if ($flags & kTokenDumpSingleLine)
		{
			$value = str_replace("\r", '<CR>', str_replace("\n", '<LF>', $value));
		}

		if ($i > 0)
		{
			$result .= $eol;
		}

		$result .= '[' . $i . ', ' . $name . '] <' . $value . '>';

		$i++;
	}

	return $result;
}

/**
 * Iterate a token array
 */
class TokenVisitor implements \iterator, \ArrayAccess, \Countable
{

	/**
	 *
	 * @param array $tokens A token array given by token_get_all()
	 */
	public function __construct(&$tokens)
	{
		$this->tokenArray = $tokens;
		$this->tokenCount = count($this->tokenArray);
		$this->tokenIndex = -1;
		$this->state = array ();
	}

	/**
	 * Move token index to a given value
	 * @param integer $index
	 * @return integer
	 */
	public function setTokenIndex($index)
	{
		if ($index < 0)
		{
			$this->tokenIndex = -1;
		}
		elseif ($index >= $this->tokenCount)
		{
			$this->tokenIndex = $this->tokenCount;
		}

		$this->tokenIndex = $index;
		return $this->tokenIndex;
	}

	/**
	 * Move iterator the the next token of the given type
	 * @param mixed $nextElementType One of the php parser token type or a string
	 * @return mixed An element of the token array or @null if the given token type was not found
	 */
	public function moveToToken($nextElementType)
	{
		return (token_move_next($this->tokenArray, $this->tokenIndex, $nextElementType));
	}

	/**
	 * Search position of a set of tokens types after the current token
	 * @param array $nextElementTypes Array of token types
	 * @param boolean $tokenIndexAsResultKey If @true,
	 *        the result array keys are the search result token indexes.
	 * @return array of search result
	 *         A search result is an associative array with the following keys
	 *         * index: Token index in token array
	 *         * token: Token information
	 */
	public function queryNextTokens($nextElementTypes, $tokenIndexAsResultKey = false)
	{
		if (!is_array($nextElementTypes))
		{
			$nextElementTypes = array (
					$nextElementTypes
			);
		}

		$s = $this->key();
		$result = array ();
		foreach ($nextElementTypes as $e)
		{
			$t = $this->moveToToken($e);
			$r = array (
					'index' => $this->key(),
					'token' => $t
			);
			if ($tokenIndexAsResultKey)
			{
				$result [$this->key()] = $r;
			}
			else
			{
				$result [] = $r;
			}
			$this->setTokenIndex($s);
		}

		return $result;
	}

	/**
	 * Store current token index
	 * @return integer Current token index
	 */
	public function pushState()
	{
		$this->state = array_push($this->tokenIndex);
		return $this->tokenIndex;
	}

	/**
	 * Pop the last token index state stored and move the visitor token index to this value
	 * @return integer new token index
	 */
	public function popState()
	{
		if (count($this->state))
		{
			$this->tokenIndex = array_pop();
		}

		return $this->tokenIndex;
	}

	/**
	 * Get the type of the current token
	 * @return integer One of the kTokenType* constnats
	 */
	public function currentType()
	{
		return token_type($this->current());
	}

	// Iterator
	public function current()
	{
		if ($this->tokenIndex < 0)
		{
			if ($this->tokenCount)
			{
				$this->next();
			}
			else
			{
				return null;
			}
		}
		return $this->tokenArray [$this->tokenIndex];
	}

	public function next()
	{
		$this->tokenIndex++;
	}

	public function key()
	{
		return $this->tokenIndex;
	}

	public function valid()
	{
		return (($this->tokenIndex < $this->tokenCount) && ($this->tokenCount > 0));
	}

	public function rewind()
	{
		$this->tokenIndex = -1;
	}

	// ArrayAccess
	public function offsetExists($offset)
	{
		return (($offset >= 0) && ($offset < $this->tokenCount));
	}

	public function offsetGet($offset)
	{
		return $this->tokenArray [$offset];
	}

	public function offsetSet($offset, $value)
	{
		throw new \Exception('offsetSet is not allowed');
	}

	public function offsetUnset($offset)
	{
		throw new \Exception('offsetUnset is not allowed');
	}
	
	// Countable
	public function count()
	{
		return $this->tokenCount;
	}

	/**
	 *
	 * @var array
	 */
	private $tokens;

	/**
	 *
	 * @var integer
	 */
	private $tokenIndex;

	/**
	 * Number of elements of $tokens
	 * @var integer
	 */
	private $tokenCount;

	/**
	 *
	 * @var array $state
	 */
	private $state;
}

/**
 * Tokenized version of a PHP source code file
 */
class SourceFile
{

	public function __construct($fileName)
	{
		$this->tokens = token_get_all(file_get_contents($fileName));
		$this->namespaces = array ();
		$this->parse();
	}
	
	public function __toString()
	{
		return $this->asString(0);
	}

	public function getTokenVisitor()
	{
		return token_get_visitor($this->tokens);
	}

	public function asString ($flags = 0)
	{
		return token_output ($this->tokens, $flags, $this->namespaces);
	}
	
	public function dumpTokens($eol = PHP_EOL, $flags = 0)
	{
		return token_dump($this->tokens, $eol, $flags);
	}

	public function getNamespaces()
	{
		return $this->namespaces;
	}

	private function parse()
	{
		$this->namespaces = token_get_namespaces($this->tokens);
	}

	private $tokens;

	private $namespaces;
}