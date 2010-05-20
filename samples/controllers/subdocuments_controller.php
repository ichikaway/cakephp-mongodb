<?php
class SubdocumentsController extends AppController {

	var $name = 'Subdocuments';

	function index() {
		$params = array(
				'order' => array('_id' => -1),
				'limit' => 35,
				'page' => 1,
				);
		$results = $this->Subdocument->find('all', $params);
		$this->set(compact('results'));
	}



	function add() {
		if (!empty($this->data)) {
			$this->Subdocument->create();
			if ($this->Subdocument->save($this->data)) {
				$this->flash(__('Subdocument saved.', true), array('action'=>'index'));
			} else {
			}
		}
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->flash(__('Invalid Subdocument', true), array('action'=>'index'));
		}
		if (!empty($this->data)) {

			if ($this->Subdocument->save($this->data)) {
				$this->flash(__('The Subdocument has been saved.', true), array('action'=>'index'));
			} else {
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Subdocument->read(null,$id);
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->flash(__('Invalid Subdocument', true), array('action'=>'index'));
		}
		if ($this->Subdocument->delete($id)) {
			$this->flash(__('Subdocument deleted', true), array('action'=>'index'));
		} else {
			$this->flash(__('Subdocument deleted Fail', true), array('action'=>'index'));
		}
	}

}
?>
