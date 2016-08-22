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
            $em = Entity_Manager::factory();
            $em->persist($entity);
            $em->flush();
        } catch (Entity_Validation_Exception $ex)
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
            Entity_Manager::factory()
                ->persist($entity)
                ->flush();
        } catch (Entity_Validation_Exception $ex)
        {
            $errors = $ex->errors();
            // do something
        }
```

#### Read employees

Get all employees:

```php
        $results = Entity_Manager::factory()
            ->get_repository('Employee')
            ->find_all();

        // or
        $results = Entity_Manager::factory('Employee')
            ->find_all();

        // use 1 hrs lifetime cache
        $results = Entity_Manager::factory('Employee')
            ->cached(3600)
            ->find_all();

```

Get filtered data:

```php
        $results = Entity_Manager::factory()
            ->get_repository('Employee')
            ->where('age', '<', 30)
            ->find_all();

```

#### Update Employee

Update one record:

```php
        $entity = Entity_Manager::factory('Employee')
            ->where('id', '=', '1')
            ->find();

        $entity->price = 5000;

        Entity_Manager::factory()
            ->persist($entity)
            ->flush();

```

Update many records:

```php
        $entities = Entity_Manager::factory('Employee')
            ->find_all();

        foreach ($entities as $entity)
        {
            $entity->price = $entity->price + 100;
        }

        Entity_Manager::factory()
            ->persist($entities)
            ->flush();

```

#### Delete Employee

Delete one record:

```php
        $entity = Entity_Manager::factory('Employee')
            ->where('id', '=', 50)
            ->find();

        Entity_Manager::factory()
            ->remove($entity)
            ->flush();

```

Delete all records:

```php
        $entities = Entity_Manager::factory('Employee')
            ->find_all();

        foreach ($entities as $entity)
        {
            $entity->state(Entity::DELETED_STATE);
        }

        Entity_Manager::factory()
            ->persist($entities)
            ->flush();

```
