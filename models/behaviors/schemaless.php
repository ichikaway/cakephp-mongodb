<?php
/**
 * Schemaless behavior.
 *
 * Adds functionality specific to MongoDB/schemaless dbs
 * Allow /not/ specifying the model's schema, and derive it (for cake-compatibility) from the data
 * being saved. Note that used carelessly this is a pretty dangerous thing to allow - means a user
 * can modify input forms adding whatever fields they like (unless you'er using the security
 * component) and fill your db with their junk.
 *
 * PHP version 5
 *
 * Copyright (c) 2010, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2010, Andy Dawson
 * @link          www.ad7six.com
 * @package       mongodb
 * @subpackage    mongodb.models.behaviors
 * @since         v 1.0 (24-May-2010)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * SchemalessBehavior class
 *
 * @uses          ModelBehavior
 * @package       mongodb
 * @subpackage    mongodb.models.behaviors
 */
class SchemalessBehavior extends ModelBehavior {

	public $name = 'Schemaless';

	public $settings = array();

	protected $_defaultSettings = array(
	);

/**
 * setup method
 *
 * Don't currently have any settings at all - disabled
 *
 * @param mixed $Model
 * @param array $config array()
 * @return void
 * @access public
 */
	public function setup(&$Model, $config = array()) {
		//$this->settings[$Model->alias] = array_merge($this->_defaultSettings, $config);
	}

/**
 * beforeSave method
 *
 * Set the schema to allow saving whatever has been passed
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	public function beforeSave(&$Model) {
		$this->setSchema($Model);
		return true;
	}

/**
 * setSchema method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	public function setSchema(&$Model) {
		$schema = $this->_deriveSchema($Model->data[$Model->alias]);
		$Model->_schema = $schema;
	}

/**
 * deriveSchema method
 *
 * Returns a pseudo-schema based on the data passed.
 *
 * @param mixed $data
 * @return void
 * @access protected
 */
	protected function _deriveSchema($data) {
		$fields = array_keys($data);
		$return = array(
			'_id' => array('type'=>'string', 'length' => 26, 'PRIMARY' => true),
		);
		foreach($fields as $field) {
			if (in_array($field, array('created', 'modified', 'updated'))) {
				$return[$field] = array('type' => 'datetime');
			} else {
				$return[$field] = array('type' => 'string', 'length' => 2000);
			}
		}
		return $return;
	}
}