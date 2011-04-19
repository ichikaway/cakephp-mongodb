<?php

class Geo extends AppModel {
/*
	var $mongoSchema = array(
			'title' => array('type'=>'string'),
			'body'=>array('type'=>'string'),
			'loc'=>array(
				'lat' => array('type'=>'float'),
				'long' => array('type'=>'float'),
				),
			'created'=>array('type'=>'datetime'),
			'modified'=>array('type'=>'datetime'),
			);
*/

	function beforeSave() {
		if(!empty($this->data[$this->alias]['loc'])) {
			//convert location info from string to float 
			$this->data[$this->alias]['loc']['lat'] = floatval($this->data[$this->alias]['loc']['lat']);
			$this->data[$this->alias]['loc']['long'] = floatval($this->data[$this->alias]['loc']['long']);
		}
		return true;
	}


	function afterSave($created) {
		//create Gespatial Index
		$mongo = $this->getDataSource();
		$mongo->ensureIndex($this, array('loc' => "2d"));
		return true;
	}

}
