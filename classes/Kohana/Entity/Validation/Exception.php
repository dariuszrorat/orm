<?php

defined('SYSPATH') OR die('No direct script access.');

/**
 * Entity Validation exceptions.
 *
 * @package    Kohana/ORM
 * @author     Dariusz Rorat
 */
class Kohana_Entity_Validation_Exception extends Kohana_Exception
{

    protected $_alias;
    protected $_object;

    /**
     * Constructs a new exception for the specified entity
     *
     * @param  string     $alias       The alias to use when looking for error messages
     * @param  Validation $object      The Validation object of the model
     * @param  string     $message     The error message
     * @param  array      $values      The array of values for the error message
     * @param  integer    $code        The error code for the exception
     * @return void
     */
    public function __construct($alias, Validation $object, $message = 'Failed to validate array', array $values = NULL, $code = 0, Exception $previous = NULL)
    {
        $this->_alias = $alias;        
        $this->_object = $object;

        parent::__construct($message, $values, $code, $previous);
    }

    /**
     * Returns a merged array of the errors from all the Validation objects in this exception
     *
     *     // Will load Entity_User errors from messages/entity-validation/user.php
     *     $e->errors('orm-validation');
     *
     * @param   string  $directory Directory to load error messages from
     * @param   mixed   $translate Translate the message
     * @return  array
     * @see generate_errors()
     */
    public function errors($directory = NULL, $translate = TRUE)
    {
        return $this->generate_errors($this->_alias, $this->_object, $directory, $translate);
    }

    /**
     * Recursive method to fetch all the errors in this exception
     *
     * @param  string $alias     Alias to use for messages file
     * @param  array  $array     Array of Validation objects to get errors from
     * @param  string $directory Directory to load error messages from
     * @param  mixed  $translate Translate the message
     * @return array
     */
    protected function generate_errors($alias, $object, $directory, $translate)
    {
        $errors = array();
        
        if (is_array($object))
        {
            $errors[$key] = ($key === '_external')
                    // Search for errors in $alias/_external.php
                    ? $this->generate_errors($alias . '/' . $key, $object, $directory, $translate)
                    // Regular models get their own file not nested within $alias
                    : $this->generate_errors($key, $object, $directory, $translate);
        } elseif ($object instanceof Validation)
        {
            if ($directory === NULL)
            {
                // Return the raw errors
                $file = NULL;
            } else
            {
                $file = trim($directory . '/' . $alias , '/');
                echo Debug::vars($file);
            }

            // Merge in this array of errors
            $errors += $object->errors($file, $translate);
        }
        return $errors;
    }

}

// End Kohana_Entity_Validation_Exception
