<?php

class Post extends AppModel {

	var $useTable = false;

	var $_schema = array(
			'title' => array('type'=>'string'),
			'body'=>array('type'=>'string'),
			'hoge'=>array('type'=>'string')
			);	

}

?>
