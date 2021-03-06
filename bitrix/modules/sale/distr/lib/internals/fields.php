<?php
namespace Bitrix\Sale\Internals;

class Fields
	implements \ArrayAccess, \Iterator, \Countable
{
	/** @var  array */
	protected $values = array();

	/** @var array */
	protected $changedValues = array();

	public function __construct(array $values = null)
	{
		if ($values !== null)
			$this->values = $values;
	}

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	public function get($name)
	{
		// this condition a bit faster
		// it is possible to omit array_key_exists here, but for uniformity...
		if (isset($this->values[$name]) || array_key_exists($name, $this->values))
			return $this->values[$name];

		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public function set($name, $value)
	{
		if ($this->markChanged($name, $value))
		{
			$this->values[$name] = $value;
			return true;
		}

		return false;
	}

	/**
	 * @internal
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public function init($name, $value)
	{
		$this->values[$name] = $value;
		return true;
	}



	public function clear()
	{
		$this->values = array();
		$this->changedValues = array();
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @param array $values
	 */
	public function setValues(array $values)
	{
		foreach ($values as $name => $value)
			$this->set($name, $value);
	}

	/**
	 * @param array $values
	 */
	public function resetValues(array $values)
	{
		$this->values = array();
		if ($values !== null)
			$this->values = $values;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return bool
	 */
	protected function markChanged($name, $value)
	{
		if ($this->get($name) != $value)
		{
			$this->changedValues[$name] = true;
			return true;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getChangedKeys()
	{
		return array_keys($this->changedValues);
	}

	/**
	 * @return array
	 */
	public function getChangedValues()
	{
		$r = array();
		foreach ($this->values as $k => $v)
		{
			if (isset($this->changedValues[$k]))
				$r[$k] = $v;
		}
		return $r;
	}

	/**
	 * Return the current element
	 */
	public function current()
	{
		return current($this->values);
	}

	/**
	 * Move forward to next element
	 */
	public function next()
	{
		return next($this->values);
	}

	/**
	 * Return the key of the current element
	 */
	public function key()
	{
		return key($this->values);
	}

	/**
	 * Checks if current position is valid
	 */
	public function valid()
	{
		$key = $this->key();
		return ($key != null);
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	public function rewind()
	{
		return reset($this->values);
	}

	/**
	 * Whether a offset exists
	 */
	public function offsetExists($offset)
	{
		return isset($this->values[$offset]) || array_key_exists($offset, $this->values);
	}

	/**
	 * Offset to retrieve
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * Offset to set
	 */
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
		if (isset($this->changedValues[$offset]))
			unset($this->changedValues[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	public function count()
	{
		return count($this->values);
	}
}