<?php

class Post extends AppModel {

	var $useTable = false;

	var $_schema = array('title' => array('type'=>'text'),'body'=>array('type'=>'text'),'hoge'=>array('type'=>'text'));	

}

?>
