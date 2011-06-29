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

/**
 * name property
 *
 * @var string 'Schemaless'
 * @access public
 */
	public $name = 'Schemaless';

/**
 * settings property
 *
 * @var array
 * @access public
 */
	public $settings = array();

/**
 * defaultSettings property
 *
 * @var array
 * @access protected
 */
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
		$Model->cacheSources = false;
		$Model->schema(true);
		return true;
	}
}