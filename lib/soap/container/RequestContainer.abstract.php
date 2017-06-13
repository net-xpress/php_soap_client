<?php

abstract class RequestContainer
{
	/**
	 * @var array
	 */
	private $items;

	/**
	 * @var int
	 */
	private $capacity;

	/**
	 * creates a new RequestContainer with a specified capacity
	 *
	 * @param int $capacity
	 */
	public function __construct($capacity)
	{
		if( !$capacity > 0 )
		{
			throw new RuntimeException( "RequestContainer initialized without capacity" );
		}
		$this->items    = array();
		$this->capacity = $capacity;
	}

	/**
	 * @return array of items
	 */
	protected function getItems()
	{
		return $this->items;
	}

	/**
	 * returns the assembled request
	 *
	 * @return mixed
	 */
	public abstract function getRequest();

	/**
	 * if container isn't full an item is added at it's end
	 *
	 * @param mixed $item
	 * @param null|int $index
	 */
	public function add($item, $index = null)
	{
		if( count( $this->items ) < $this->capacity )
		{
			if( is_null( $index ) )
			{
				$this->items[] = $item;
			}
			else
			{
				$this->items[$index] = $item;
			}
		}
	}

	/**
	 * check if container reached it's capacity
	 *
	 * @return bool
	 */
	public function isFull()
	{
		return count( $this->items ) === $this->capacity;
	}
}
