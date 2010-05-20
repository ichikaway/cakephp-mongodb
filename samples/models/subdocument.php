<?php

class Subdocument extends AppModel {
	var $mongoSchema = array(
			'title' => array('type'=>'string'),
			'body'=>array('type'=>'string'),
			'subdoc'=>array(
				'name' => array('type'=>'string'),
				'age' => array('type'=>'integer')
			),
			'created'=>array('type'=>'date'),
			'modified'=>array('type'=>'date'),
			);

}

?>
