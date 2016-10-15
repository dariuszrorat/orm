<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Default auth permission
 *
 * @package    Kohana/Auth
 * @author     Dariusz Rorat
 * @copyright  (c) 2016 Dariusz Rorat
 * @license    BSD
 */
class Model_Auth_Permission extends ORM {

	// Relationships
	protected $_has_many = array(
		'users' => array('model' => 'User','through' => 'permissions_users'),
                'roles' => array('model' => 'Role','through' => 'permissions_roles'),
	);

	public function rules()
	{
		return array(
			'name' => array(
				array('not_empty'),
				array('min_length', array(':value', 4)),
				array('max_length', array(':value', 32)),
			),
			'description' => array(
				array('max_length', array(':value', 255)),
			)
		);
	}

} // End Auth Permission Model
