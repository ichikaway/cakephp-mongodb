<?php
/**
 * Sql Compatible.
 *
 * Attach this behavior to be able to query mongo DBs without using mongo specific syntax.
 * If you don't need this behavior don't attach it and save a few cycles
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
 * SqlCompatibleBehavior class
 *
 * @uses          ModelBehavior
 * @package       mongodb
 * @subpackage    mongodb.models.behaviors
 */
class SqlCompatibleBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'SqlCompatible'
 * @access public
 */
	public $name = 'SqlCompatible';

/**
 * Runtime settings
 *
 * Keyed on model alias
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
		'convertDates' => true,
		'operators' => array(
			'!=' => '$ne',
			'>' => '$gt',
			'>=' => '$gte',
			'<' => '$lt',
			'<=' => '$lte',
			'IN' => '$in',
			'NOT' => '$not',
			'NOT IN' => '$nin'
		)
	);

/**
 * setup method
 *
 * Allow overriding the operator map
 *
 * @param mixed $Model
 * @param array $config array()
 * @return void
 * @access public
 */
	public function setup(&$Model, $config = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaultSettings, $config);
	}

/**
 * If requested, convert dates from MongoDate objects to standard date strings
 *
 * @param mixed $Model
 * @param mixed $results
 * @param mixed $primary
 * @return void
 * @access public
 */
	public function afterFind(&$Model, $results, $primary) {
		if ($this->settings[$Model->alias]['convertDates']) {
			$this->convertDates($results);
		}
		return $results;
	}

/**
 * beforeFind method
 *
 * If conditions are an array ensure they are mongified
 *
 * @param mixed $Model
 * @param mixed $query
 * @return void
 * @access public
 */
	public function beforeFind(&$Model, $query) {
		if (is_array($query['conditions']) && $this->_translateConditions($Model, $query['conditions'])) {
			return $query;
		}
		return true;
	}

/**
 * Convert MongoDate objects to strings for the purpose of view simplicity
 *
 * @param mixed $results
 * @return void
 * @access public
 */
	public function convertDates(&$results) {
		if (is_array($results)) {
			foreach($results as &$row) {
				$this->convertDates($row);
			}
		} elseif (is_a($results, 'MongoDate')) {
			$results = date('Y-M-d h:i:s', $results->sec);
		}
	}

/**
 * translateConditions method
 *
 * Loop on conditions and desqlify them
 *
 * @param mixed $Model
 * @param mixed $conditions
 * @return void
 * @access protected
 */
	protected function _translateConditions(&$Model, &$conditions) {
		$return = false;
		foreach($conditions as $key => &$value) {
			$uKey = strtoupper($key);
			if (substr($uKey, -5) === 'NOT IN') {
				// 'Special' case because it has a space in it, and it's the whole key
				$conditions[substr($key, 0, -5)]['$nin'] = $value;
				unset($conditions[$key]);
				$return = true;
				continue;
			}
			if ($uKey === 'OR') {
				unset($conditions[$key]);
				foreach($value as $key => $part) {
					$part = array($key => $part);
					$this->_translateConditions($Model, $part);
					$conditions['$or'][] = $part;
				}
				$return = true;
				continue;
			}
			if (substr($uKey, -3) === 'NOT') {
				// 'Special' case because it's awkward
				$childKey = key($value);
				$childValue = current($value);

				if (in_array(substr($childKey, -1), array('>', '<', '='))) {
					$parts = explode(' ', $childKey);
					$operator = array_pop($parts);
					if ($operator = $this->_translateOperator($Model, $operator)) {
						$childKey = implode(' ', $parts);
					}
				} else {
					$conditions[$childKey]['$nin'] = (array)$childValue;
					unset($conditions['NOT']);
					$return = true;
					continue;
				}

				$conditions[$childKey]['$not'][$operator] = $childValue;
				unset($conditions['NOT']);
				$return = true;
				continue;
			}
			if (substr($uKey, -5) === ' LIKE') {
				// 'Special' case because it's awkward
				if ($value[0] === '%') {
					$value = substr($value, 1);
				} else {
					$value = '^' . $value;
				}
				if (substr($value, -1) === '%') {
					$value = substr($value, 0, -1);
				} else {
					$value .= '$';
				}
				$value = str_replace('%', '.*', $value);

				$conditions[substr($key, 0, -5)] = new MongoRegex("/$value/i");
				unset($conditions[$key]);
				$return = true;
				continue;
			}

			if (!in_array(substr($key, -1), array('>', '<', '='))) {
				$return = true;
				continue;
			}
			if (is_numeric($key && is_array($value))) {
				if ($this->_translateConditions($Model, $value)) {
					$return = true;
					continue;
				}
			}
			$parts = explode(' ', $key);
			$operator = array_pop($parts);
			if ($operator = $this->_translateOperator($Model, $operator)) {
				$newKey = implode(' ', $parts);
				$conditions[$newKey][$operator] = $value;
				unset($conditions[$key]);
				$return = true;
			}
			if (is_array($value)) {
				if ($this->_translateConditions($Model, $value)) {
					$return = true;
					continue;
				}
			}
		}
		return $return;
	}

/**
 * translateOperator method
 *
 * Use the operator map for the model and return what the db really wants to hear
 *
 * @param mixed $Model
 * @param mixed $operator
 * @return string
 * @access protected
 */
	protected function _translateOperator($Model, $operator) {
		if (!empty($this->settings[$Model->alias]['operators'][$operator])) {
			return $this->settings[$Model->alias]['operators'][$operator];
		}
		return '';
	}
}