<?php

defined('SYSPATH') OR die('No direct script access.');

/**
 * Entity base class. All entities should extend this class.
 *
 * @package    Kohana
 * @category   ORM
 * @author     Dariusz Rorat
 */
abstract class Kohana_Entity
{

    const NOT_EXISTS_STATE = 00;
    const CREATE_STATE = 10;
    const READ_STATE = 20;
    const UPDATE_STATE = 30;
    const DELETE_STATE = 40;

    protected $_table_name;
    protected $_data = array();
    protected $_state = Entity::CREATE_STATE;

    /**
     * Create a new entity instance.
     *
     *     $entity = Entity::factory($name);
     *
     * @param   string  $name   entity name
     * @return  Entity
     */
    public static function factory($name)
    {
        // Add the entity prefix
        $class = 'Entity_' . $name;

        return new $class;
    }

    public function __construct()
    {
        $this->_state = array_key_exists('id', $this->_data) ? Entity::READ_STATE : Entity::CREATE_STATE;
    }

    public function get_table_name()
    {
        return $this->_table_name;
    }

    public function & __get($key)
    {
        if (array_key_exists($key, $this->_data))
        {
            return $this->_data[$key];
        } else
        {
            throw new Kohana_Exception('Entity variable is not set: :var', array(':var' => $key));
        }
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }

    public function __toString()
    {
        return get_class($this);
    }

    public function set($key, $value)
    {
        $this->_data[$key] = $value;
        $this->_state = array_key_exists('id', $this->_data) ? Entity::UPDATE_STATE : Entity::CREATE_STATE;
        return $this;
    }

    public function data($key = NULL)
    {
        if ($key !== NULL)
        {
            return array_key_exists($key, $this->_data) ? $this->_data[$key] : NULL;
        } else
        {
            return $this->_data;
        }
    }

    public function state($value = NULL)
    {
        if ($value === NULL)
        {
            return $this->_state;
        }

        $this->_state = $value;
        return $this;
    }

}
