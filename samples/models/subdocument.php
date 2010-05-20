<?php

class Subdocument extends AppModel {
	var $mongoSchema = array(
			'title' => array('type'=>'string'),
			'body'=>array('type'=>'string'),
			'subdoc'=>array('type'=>'string'),
			'created'=>array('type'=>'date'),
			'modified'=>array('type'=>'date'),
			);	

}

?>
