<?php

defined('SYSPATH') OR die('No direct script access.');

/**
 * Kohana ORM Entity Manager
 *
 * @package    Kohana/ORM
 * @author     Dariusz Rorat
 */
class Kohana_ORM_Entity_Manager
{

    /**
     * Stores transaction mode information for ORM models
     * @var bool
     */
    protected $_transactional = FALSE;

    /**
     * Stores persisters information for ORM models
     * @var array
     */
    protected $_persisters = array();

    /**
     * Creates and returns a new entity manager.
     * @return Entity_Manager
     */
    public static function factory()
    {
        return new ORM_Entity_Manager();
    }

    public function __construct()
    {
        
    }

    /**
     * Set managed object(s)
     *
     * @param   entity object or array of objects
     * @return  this
     */
    public function persist($objects = NULL)
    {
        $objects = func_get_args();
        foreach ($objects as $object)
        {
            if (is_array($object))
            {
                $this->_persisters = array_merge($this->_persisters, $object);
            } else
            {
                $this->_persisters[] = $object;
            }
        }
        return $this;
    }

    /**
     * Set object state to remove
     *
     * @param   entity object(s)
     * @return  this
     */
    public function remove($objects = NULL)
    {
        $objects = func_get_args();
        foreach ($objects as $object)
        {
            $object->state(Entity::DELETED_STATE);
            $this->_persisters[] = $object;
        }

        return $this;
    }

    /**
     * Set transaction mode
     * 
     * @return this
     */
    public function transactional()
    {
        $this->_transactional = TRUE;
        return $this;
    }

    /**
     * Commit changes
     *
     * @return bool
     */
    public function flush()
    {
        return $this->_apply_changes();
    }

    /**
     * Get current persisters
     * 
     * @return array
     */
    public function persisters()
    {
        return $this->_persisters;
    }

    /**
     * Apply all changes
     * 
     * @return bool
     */
    protected function _apply_changes()
    {
        try
        {
            if ($this->_transactional)
            {
                Database::instance()->begin();
            }
            foreach ($this->_persisters as $object)
            {
                $this->_unit_of_work($object);
            }
            if ($this->_transactional)
            {
                Database::instance()->commit();
            }
            return TRUE;
        } catch (Exception $ex)
        {
            if ($this->_transactional)
            {
                Database::instance()->rollback();
            }
            throw $ex;
        }
    }

    /**
     * Unit of work method. Works on one persister.
     * 
     * @return mixed
     */
    protected function _unit_of_work($object)
    {
        if ($object->state() === Entity::NOT_EXISTS_STATE)
        {
            throw new ORM_Entity_Exception('Entity not exists: :var', array(':var' => $object));
        }
        $table = $object->table_name();
        $state = $object->state();
        $result = FALSE;

        switch ($state)
        {
            case Entity::CREATED_STATE:
                {
                    $this->check($object);
                    $this->filter($object);
                    $vars = $object->data();
                    $result = DB::insert($table, array_keys($vars))
                            ->values($vars)
                            ->execute();
                    $object->id = $result[0];
                    $object->state(Entity::LOADED_STATE);
                }
                break;
            case Entity::UPDATED_STATE:
                {
                    $this->check($object);
                    $this->filter($object);
                    $id = $object->id;
                    $vars = $object->data();
                    $result = DB::update($table)
                            ->set($vars)
                            ->where('id', '=', $id)
                            ->execute();
                    $object->state(Entity::LOADED_STATE);
                }
                break;
            case Entity::DELETED_STATE:
                {
                    $id = $object->id;
                    $result = DB::delete($table)
                            ->where('id', '=', $id)
                            ->execute();
                    $object->state(Entity::NOT_EXISTS_STATE);
                }
                break;
            default: break;
        }
        return $result;
    }

    protected function _validation($object)
    {
        // Build the validation object with its rules
        $validation = Validation::factory($object->data());

        foreach ($object->rules() as $field => $rules)
        {
            $validation->rules($field, $rules);
        }
        return $validation;
    }

    public function check($object)
    {
        $data = $object->data();
        $rules = $object->rules();
        if (empty($data) || empty($rules))
        {
            return $this;
        }
        $array = $this->_validation($object);

        if (($valid = $array->check()) === FALSE)
        {
            $object_name = strtolower(substr(get_class($object), 7));
            $exception = new ORM_Entity_Validation_Exception($object_name, $array);
            throw $exception;
        }

        return $this;
    }

    /**
     * Filters the entity
     *
     * @param  object
     * @return this
     */

    public function filter($object)
    {
        $filters = $object->filters();
        if (empty($filters))
        {
            return;
        }
        $data = $object->data();
        $changed = $object->changed();
        foreach ($data as $key => $value)
        {
            if ($changed[$key] === TRUE)
            {
                $object->set($key, $this->_run_filter($key, $value, $filters));
            }
        }
        return $this;
    }

    /**
     * Filters a value for a specific column
     *
     * @param  string $field  The column name
     * @param  string $value  The value to filter
     * @return string
     */
    protected function _run_filter($field, $value, $filters)
    {

	$wildcards = empty($filters[TRUE]) ? array() : $filters[TRUE];
	$filters = empty($filters[$field]) ? $wildcards : array_merge($wildcards, $filters[$field]);

	$_bound = array
	(
	    ':field' => $field,
	);

	foreach ($filters as $array)
	{
		$_bound[':value'] = $value;

		$filter = $array[0];
		$params = Arr::get($array, 1, array(':value'));

		foreach ($params as $key => $param)
		{
			if (is_string($param) AND array_key_exists($param, $_bound))
			{
				$params[$key] = $_bound[$param];
			}
		}

		if (is_array($filter) OR ! is_string($filter))
		{
			$value = call_user_func_array($filter, $params);
		}
		elseif (strpos($filter, '::') === FALSE)
		{
			$function = new ReflectionFunction($filter);
			$value = $function->invokeArgs($params);
		}
		else
		{
			list($class, $method) = explode('::', $filter, 2);
			$method = new ReflectionMethod($class, $method);
			$value = $method->invokeArgs(NULL, $params);
		}
	}

	return $value;
    }

}
