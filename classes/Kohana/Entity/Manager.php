<?php

defined('SYSPATH') OR die('No direct script access.');

class Kohana_Entity_Manager
{

    protected $_repository;
    protected $_transactional = FALSE;
    protected $_persisters = array();

    public static function factory($name = NULL)
    {
        if ($name !== NULL)
        {
            return new Entity_Repository($name);
        }
        return new Entity_Manager();
    }

    public function __construct()
    {
        //none
    }

    /**
     * Get the repository.
     *
     * @param   string  $name   repository name
     * @return  Entity_Repository
     */

    public function get_repository($name)
    {
        $this->_repository = new Entity_Repository($name);
        return $this->_repository;
    }

    /**
     * Set managed object(s)
     *
     * @param   entity object or array of objects
     * @return  this
     */

    public function persist($object)
    {
        if (is_array($object))
        {
            $this->_persisters = array_merge($this->_persisters, $object);
        } else
        {
            $this->_persisters[] = $object;
        }
        return $this;
    }

    /**
     * Set object state to remove
     *
     * @param   entity object
     * @return  this
     */

    public function remove($object)
    {
        $object->state(Entity::DELETED_STATE);
        $this->_persisters[] = $object;
        
        return $this;
    }

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
    
    public function persisters()
    {
        return $this->_persisters;
    }

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
        }
        catch (Exception $ex)
        {
            if ($this->_transactional)
            {
                Database::instance()->rollback();
            }
            throw $ex;
        }
    }

    protected function _unit_of_work($object)
    {
        if ($object->state() === Entity::NOT_EXISTS_STATE)
        {
            throw new Entity_Exception('Entity not exists: :var', array(':var' => $object));
        }
        $table = $object->get_table_name();
        $state = $object->state();
        $result = FALSE;

        switch ($state)
        {
            case Entity::CREATED_STATE:
                {
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

}
