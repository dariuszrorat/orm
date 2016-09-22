# Kohana ORM module

This module using Data Mapper pattern to work with CRUD operations.

## Examples

#### Example employee entity

application/classes/Entity/Employee.php

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Entity_Employee extends Entity
{
    protected $_table_name = 'employee';

    public function rules()
    {
        return array(
            'firstname' => array(
                array('not_empty'),
                array('max_length', array(':value', 32)),
            ),
            'lastname' => array(
                array('not_empty'),
                array('max_length', array(':value', 32)),
            ),
            'age' => array(
                array('not_empty'),
                array('digit'),
            ),
        );
    }

}
```

#### Create Employee

Basic:

```php
        $entity = Entity::factory('Employee');
        $entity->firstname = 'John';
        $entity->lastname = 'Doe';
        $entity->age = 40;

        try
        {
            $em = ORM_Entity_Manager::factory();
            $em->persist($entity);
            $em->flush();
        } catch (ORM_Entity_Validation_Exception $ex)
        {
            $errors = $ex->errors();
            // do something
        }

```

Alternate:

```php
        $entity = Entity::factory('Employee')
            ->set('firstname', 'John')
            ->set('lastname', 'Doe')
            ->set('age', 40);

        try
        {
            ORM_Entity_Manager::factory()
                ->persist($entity)
                ->flush();
        } catch (ORM_Entity_Validation_Exception $ex)
        {
            $errors = $ex->errors();
            // do something
        }
```

#### Read employees

Get all employees:

```php
        $results = ORM_Repository::factory('Employee')
            ->find_all();

        // use 1 hrs lifetime cache
        $results = ORM_Repository::factory('Employee')
            ->cached(3600)
            ->find_all();

```

Get filtered data:

```php
        $results = ORM_Repository::factory('Employee')
            ->where('age', '<', 30)
            ->find_all();

```

Simple get employee by id:

```php
        $id = 1;
        $result = ORM_Repository::factory('Employee', $id);
```

#### Update Employee

Update one record:

```php
        $entity = ORM_Repository::factory('Employee')
            ->where('id', '=', '1')
            ->find();

        $entity->price = 5000;

        ORM_Entity_Manager::factory()
            ->persist($entity)
            ->flush();

```

Update many records:

```php
        $entities = ORM_Repository::factory('Employee')
            ->find_all();

        foreach ($entities as $entity)
        {
            $entity->price = $entity->price + 100;
        }

        ORM_Entity_Manager::factory()
            ->persist($entities)
            ->flush();

```

#### Delete Employee

Delete one record:

```php
        $entity = ORM_Repository::factory('Employee')
            ->where('id', '=', 50)
            ->find();

        ORM_Entity_Manager::factory()
            ->remove($entity)
            ->flush();

```

Delete all records:

```php
        $entities = ORM_Repository::factory('Employee')
            ->find_all();

        foreach ($entities as $entity)
        {
            $entity->state(Entity::DELETED_STATE);
        }

        ORM_Entity_Manager::factory()
            ->persist($entities)
            ->flush();

```
