<?php
/**
 * SubdocumentsController class
 *
 * @uses          AppController
 * @package       mongodb
 * @subpackage    mongodb.samples.controllers
 */
class SubdocumentsController extends AppController {

/**
 * name property
 *
 * @var string 'Subdocuments'
 * @access public
 */
	public $name = 'Subdocuments';

/**
 * index method
 *
 * @return void
 * @access public
 */
	public function index() {
		$params = array(
			'order' => array('_id' => -1),
			'limit' => 35,
			'page' => 1,
		);
		$results = $this->Subdocument->find('all', $params);
		$this->set(compact('results'));
	}

/**
 * add method
 *
 * @return void
 * @access public
 */
	public function add() {
		if (!empty($this->data)) {
			$this->Subdocument->create();
			if ($this->Subdocument->save($this->data)) {
				$this->flash(__('Subdocument saved.', true), array('action' => 'index'));
			} else {
			}
		}
	}

/**
 * edit method
 *
 * @param mixed $id null
 * @return void
 * @access public
 */
	public function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->flash(__('Invalid Subdocument', true), array('action' => 'index'));
		}
		if (!empty($this->data)) {

			if ($this->Subdocument->save($this->data)) {
				$this->flash(__('The Subdocument has been saved.', true), array('action' => 'index'));
			} else {
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Subdocument->read(null, $id);
		}
	}

/**
 * delete method
 *
 * @param mixed $id null
 * @return void
 * @access public
 */
	public function delete($id = null) {
		if (!$id) {
			$this->flash(__('Invalid Subdocument', true), array('action' => 'index'));
		}
		if ($this->Subdocument->delete($id)) {
			$this->flash(__('Subdocument deleted', true), array('action' => 'index'));
		} else {
			$this->flash(__('Subdocument deleted Fail', true), array('action' => 'index'));
		}
	}
}