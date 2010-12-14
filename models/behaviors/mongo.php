<?php
class MongoBehavior extends ModelBehavior {

	function isUnique($fields, $or = true) {
		if (!is_array($fields)) {
			$fields = func_get_args();
			if (is_bool($fields[count($fields) - 1])) {
				$or = $fields[count($fields) - 1];
				unset($fields[count($fields) - 1]);
			}
		}

		foreach ($fields as $field => $value) {
			if (is_numeric($field)) {
				unset($fields[$field]);

				$field = $value;
				if (isset($this->data[$this->alias][$field])) {
					$value = $this->data[$this->alias][$field];
				} else {
					$value = null;
				}
			}

			if (strpos($field, '.') === false) {
				unset($fields[$field]);
				$fields[$this->alias . '.' . $field] = $value;
			}
		}
		if ($or) {
			$fields = array('$or' => $fields);
		}
		if (!empty($this->id)) {
			$fields[$this->alias . '.' . $this->primaryKey . ' !='] =  $this->id;
		}
		debug($fields);
		exit();
		return ($this->find('count', array('conditions' => $fields, 'recursive' => -1)) == 0);
	}

	function getDb(&$Model){
		$ds = $Model->getDataSource();
		if(!$ds->isConnected()) $ds->connect();
		return $ds->getDb();
	}
}?>