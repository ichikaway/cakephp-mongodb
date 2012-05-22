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
 * 
 * @todo 
 * 		- Dry Code
 * 		- Add options fields on hasMany cachedField to set which fields should be cached.
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

	public function beforeSave(&$Model) { //belongsTo
		foreach ($Model->getAssociated('belongsTo') as $assoc) {
			$assoc = $Model->{$assoc};
			$fk = $Model->belongsTo[$assoc->alias]['foreignKey'];
			if(
				isset($Model->data[$Model->alias][$fk]) &&
				isset($Model->belongsTo[$assoc->alias]['cachedFields'])
			){				
				$data = $assoc->read(null, $Model->data[$Model->alias][$fk]);
				$newData = $this->extractData($data, $Model->belongsTo[$assoc->alias]);
				$Model->data[$Model->alias] = Set::merge($Model->data[$Model->alias], $newData);
			}
		}
		return true;
	}

	public function afterSave(Model &$Model, $created) {
		if(!$created){
			foreach($Model->getAssociated('hasMany') as $assoc){
				$assoc = $Model->{$assoc};
				if(isset($assoc->belongsTo[$Model->alias]['cachedFields'])){
					$newData = $this->extractData($Model->data, $assoc->belongsTo[$Model->alias]);
					$conditions[$Model->hasMany[$assoc->alias]['foreignKey']] = $Model->id;
					$assoc->updateAll($newData, $conditions);	
				}
			}	
		}

		foreach ($Model->getAssociated('belongsTo') as $assoc) {
			$assoc = $Model->{$assoc};
			if(isset($assoc->hasMany[$Model->alias]['cachedFields'])){
				$fk = $Model->belongsTo[$assoc->alias]['foreignKey'];
				$cacheData = $Model->find('all', array(
					'conditions' => array(
						$fk => $Model->data[$Model->alias][$fk]
					)
				));
				$data = $this->extractData($cacheData, $assoc->hasMany[$Model->alias]);
				$data = array_merge(array($assoc->primaryKey => $Model->data[$Model->alias][$fk]), $data);
				$assoc->save($data, false);
			}
		}
	}

	public function beforeDelete(Model &$Model) {
		$this->settings[$Model->alias]['delete'] = $Model->read(null, $Model->id);
		return true;
	}

	public function afterDelete(Model &$Model) {
		foreach ($Model->getAssociated('belongsTo') as $assoc) {
			$assoc = $Model->{$assoc};
			if(isset($assoc->hasMany[$Model->alias]['cachedFields'])){
				$fk = $Model->belongsTo[$assoc->alias]['foreignKey'];
				$Model->data = $this->settings[$Model->alias]['delete'];
				$cacheData = $Model->find('all', array(
					'conditions' => array(
						$fk => $Model->data[$Model->alias][$fk]
					)
				));
				$data = $this->extractData($cacheData, $assoc->hasMany[$Model->alias]);
				$data = array_merge(array($assoc->primaryKey => $Model->data[$Model->alias][$fk]), $data);
				$assoc->save($data, false);
			}
		}
	}

	public function extractData($source, $extract) {
		$data = array();
		foreach($extract['cachedFields'] as $f => $p){
			$data[$f] = Set::classicExtract($source, $p);
		}
		return $data;
	}
}