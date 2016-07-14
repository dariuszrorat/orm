<?php

defined('SYSPATH') OR die('No direct script access.');

class Kohana_Entity_Repository
{

    protected $_name;
    protected $_table_name;
    protected $_db_pending = array();

    public function __construct($name)
    {
        $this->_name = $name;
        $entity = Entity::factory($this->_name);
        $this->_table_name = $entity->get_table_name();
    }

    public function find()
    {
        return $this->_load_result(FALSE);
    }

    public function find_all()
    {
        return $this->_load_result(TRUE);
    }

    /**
     * Alias of and_where()
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function where($column, $op, $value)
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'where',
            'args' => array($column, $op, $value),
        );

        return $this;
    }

    /**
     * Creates a new "AND WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function and_where($column, $op, $value)
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'and_where',
            'args' => array($column, $op, $value),
        );

        return $this;
    }

    /**
     * Creates a new "OR WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function or_where($column, $op, $value)
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'or_where',
            'args' => array($column, $op, $value),
        );

        return $this;
    }

    /**
     * Alias of and_where_open()
     *
     * @return  $this
     */
    public function where_open()
    {
        return $this->and_where_open();
    }

    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function and_where_open()
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'and_where_open',
            'args' => array(),
        );

        return $this;
    }

    /**
     * Opens a new "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function or_where_open()
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'or_where_open',
            'args' => array(),
        );

        return $this;
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function where_close()
    {
        return $this->and_where_close();
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function and_where_close()
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'and_where_close',
            'args' => array(),
        );

        return $this;
    }

    /**
     * Closes an open "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function or_where_close()
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'or_where_close',
            'args' => array(),
        );

        return $this;
    }

    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param   mixed   $column     column name or array($column, $alias) or object
     * @param   string  $direction  direction of sorting
     * @return  $this
     */
    public function order_by($column, $direction = NULL)
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'order_by',
            'args' => array($column, $direction),
        );

        return $this;
    }

    /**
     * Return up to "LIMIT ..." results
     *
     * @param   integer  $number  maximum results to return
     * @return  $this
     */
    public function limit($number)
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'limit',
            'args' => array($number),
        );

        return $this;
    }

    /**
     * Enables or disables selecting only unique columns using "SELECT DISTINCT"
     *
     * @param   boolean  $value  enable or disable distinct columns
     * @return  $this
     */
    public function distinct($value)
    {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending[] = array(
            'name' => 'distinct',
            'args' => array($value),
        );

        return $this;
    }

    protected function _load_result($multiple = FALSE)
    {
        if ($multiple === TRUE)
        {
            $builder = DB::select('*')
                    ->from($this->_table_name);
            $this->_compile_where($builder);
            $results = $builder
                    ->as_object('Entity_' . $this->_name)
                    ->execute()
                    ->as_array();

            return $results;
        } else
        {
            $builder = DB::select('*')
                    ->from($this->_table_name);
            $this->_compile_where($builder);
            $result = $builder            
                    ->as_object('Entity_' . $this->_name)
                    ->execute()
                    ->current();

            return $result;
        }
    }

    protected function _compile_where($builder)
    {
        // Process pending database method calls
        foreach ($this->_db_pending as $method)
        {
            $name = $method['name'];
            $args = $method['args'];

            //$this->_db_applied[$name] = $name;

            call_user_func_array(array($builder, $name), $args);
        }

        return;
    }

}
