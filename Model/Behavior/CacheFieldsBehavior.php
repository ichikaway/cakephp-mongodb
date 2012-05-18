<?php
/**
 * CacheFields behavior.
 *
 * Adds functionality specific to cache fields of relational models.
 * PHP version 5
 *
 * Copyright (c) 2012, Daniel Pakuschewski
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2010, Andy Dawson
 * @link          www.danielpk.com.br
 * @package       mongodb
 * @subpackage    mongodb.models.behaviors
 * @since         v 1.0 (21-May-2012)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * CacheFieldsBehavior class
 *
 * @uses          ModelBehavior
 * @package       mongodb
 * @subpackage    mongodb.models.behaviors
 */

class CacheFieldsBehavior extends ModelBehavior {
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
 * @param mixed $Model
 * @param array $config array()
 * @return void
 * @access public
 */
	public function setup(Model &$Model, $config = array()) {		
		
	}

	public function beforeSave(&$Model) {
		foreach ($Model->getAssociated('belongsTo') as $assoc) {
			$assoc = $Model->{$assoc};
			$fields = $Model->belongsTo[$assoc->alias]['cachedFields'];
			$fk = $Model->belongsTo[$assoc->alias]['foreignKey'];
			
			$data = $assoc->read(null, $Model->data[$Model->alias][$fk]);
			
			$newData = $this->extractData($data, $Model->belongsTo[$assoc->alias]);

			$Model->data[$Model->alias] = Set::merge($Model->data[$Model->alias], $newData);
			
		}
		return true;
	}

	public function afterSave(Model &$Model, $created) {
		if(!$created){
			foreach($Model->getAssociated('hasMany') as $assoc){
				$assoc = $Model->{$assoc};
				$newData = $this->extractData($Model->data, $assoc->belongsTo[$Model->alias]);
				$conditions[$Model->hasMany[$assoc->alias]['foreignKey']] = $Model->id;
				$assoc->updateAll($newData, $conditions);
			}	
		}
	}

	public function extractData($source, $extract) {
		// debug($extract);die;
		$data = array();
		foreach($extract['cachedFields'] as $f => $p){
			$data[$f] = Set::classicExtract($source, $p);
		}
		return $data;
	}
}