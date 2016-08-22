<?php

defined('SYSPATH') OR die('No direct script access.');

/**
 * Kohana Entity Manager
 *
 * @package    Kohana/ORM
 * @author     Dariusz Rorat
 */
class Kohana_Entity_Manager
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
     * Creates and returns a new entity manager or repository. 
     * @param   string  $repository  Repository name
     * @return  Entity_Repository or Entity_Manager
     */
    public static function factory($repository = NULL)
    {
        if ($repository !== NULL)
        {
            return new Entity_Repository($repository);
        }
        return new Entity_Manager();
    }

    public function __construct()
    {
        
    }

    /**
     * Get the repository.
     *
     * @param   string  $name   repository name
     * @return  Entity_Repository
     */
    public function get_repository($name)
    {
        $repository = new Entity_Repository($name);
        return $repository;
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
            throw new Entity_Exception('Entity not exists: :var', array(':var' => $object));
        }
        $table = $object->table_name();
        $state = $object->state();
        $result = FALSE;

        switch ($state)
        {
            case Entity::CREATED_STATE:
                {
                    $this->check($object);
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
        if (empty($data))
        {
            return $this;
        }
        $array = $this->_validation($object);

        if (($valid = $array->check()) === FALSE)
        {
            $object_name = strtolower(substr(get_class($object), 7));
            $exception = new Entity_Validation_Exception($object_name, $array);
            throw $exception;
        }

        return $this;
    }

}
