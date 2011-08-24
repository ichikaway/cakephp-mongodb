<?php
/**
 * PostsController class
 *
 * @uses          AppController
 * @package       mongodb
 * @subpackage    mongodb.samples.controllers
 */
class PostsController extends AppController {


/**
 * name property
 *
 * @var string 'Posts'
 * @access public
 */
	public $name = 'Posts';

/**
 * index method
 *
 * @return void
 * @access public
 */
	public function index() {
		$params = array(
			'fields' => array('title', 'body', 'hoge'),
			//'fields' => array('Post.title', ),
			//'conditions' => array('title' => 'hehe'),
			//'conditions' => array('hoge' => array('$gt' => '10', '$lt' => '34')),
			//'order' => array('title' => 1, 'body' => 1),
			'order' => array('_id' => -1),
			'limit' => 35,
			'page' => 1,
		);
		$results = $this->Post->find('all', $params);
		//$result = $this->Post->find('count', $params);
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

			$this->Post->create();
			if ($this->Post->save($this->data)) {
				$this->flash(__('Post saved.', true), array('action' => 'index'));
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
			$this->flash(__('Invalid Post', true), array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Post->save($this->data)) {
				$this->flash(__('The Post has been saved.', true), array('action' => 'index'));
			} else {
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Post->read(null, $id);
			//$this->data = $this->Post->find('first', array('conditions' => array('_id' => $id)));
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
			$this->flash(__('Invalid Post', true), array('action' => 'index'));
		}
		if ($this->Post->delete($id)) {
			$this->flash(__('Post deleted', true), array('action' => 'index'));
		} else {
			$this->flash(__('Post deleted Fail', true), array('action' => 'index'));
		}
	}

/**
 * deleteall method
 *
 * @return void
 * @access public
 */
	public function deleteall() {
		$conditions = array('title' => 'aa');
		if ($this->Post->deleteAll($conditions)) {
			$this->flash(__('Post deleteAll success', true), array('action' => 'index'));

		} else {
			$this->flash(__('Post deleteAll Fail', true), array('action' => 'index'));
		}
	}

/**
 * updateall method
 *
 * @return void
 * @access public
 */
	public function updateall() {
		$conditions = array('title' => 'ichi2' );

		$field = array('title' => 'ichi' );

		if ($this->Post->updateAll($field, $conditions)) {
			$this->flash(__('Post updateAll success', true), array('action' => 'index'));

		} else {
			$this->flash(__('Post updateAll Fail', true), array('action' => 'index'));
		}
	}

	public function createindex() {
		$mongo = ConnectionManager::getDataSource($this->Post->useDbConfig);
		$mongo->ensureIndex($this->Post, array('title' => 1));

	}
}