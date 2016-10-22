<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Default auth group
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2016 Dariusz Rorat
 * @license    BSD
 */
class Model_Auth_Group extends ORM {

	// Relationships
	protected $_has_many = array(
		'users' => array('model' => 'User','through' => 'groups_users'),
		'permissions' => array('model' => 'Permission','through' => 'permissions_groups'),
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

} // End Auth Group Model
