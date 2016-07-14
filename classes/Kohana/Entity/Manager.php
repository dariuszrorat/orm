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
        $object->state(Entity::DELETE_STATE);
        $this->_persisters[] = $object;
        
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
        foreach ($this->_persisters as $object)
        {
            $this->_unit_of_work($object);
        }
        return TRUE;
    }

    protected function _unit_of_work($object)
    {
        $table = $object->get_table_name();
        $state = $object->state();
        $result = FALSE;

        switch ($state)
        {
            case Entity::CREATE_STATE:
                {
                    $vars = $object->data();
                    $result = DB::insert($table, array_keys($vars))
                            ->values($vars)
                            ->execute();
                    $object->id = $result[0];
                    $object->state(Entity::READ_STATE);
                }
                break;
            case Entity::UPDATE_STATE:
                {
                    $id = $object->id;
                    $vars = $object->data();
                    $result = DB::update($table)
                            ->set($vars)
                            ->where('id', '=', $id)
                            ->execute();
                    $object->state(Entity::READ_STATE);
                }
                break;
            case Entity::DELETE_STATE:
                {
                    $id = $object->id;
                    $result = DB::delete($table)
                            ->where('id', '=', $id)
                            ->execute();
                    unset($object);
                }
                break;
            default: break;
        }
        return $result;
    }

}