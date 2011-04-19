<?php
/**
 * GeoController class
 *
 * @uses          AppController
 * @package       mongodb
 * @subpackage    mongodb.samples.controllers
 */
class GeosController extends AppController {

	/**
	 * name property
	 *
	 * @var string 'Geo'
	 * @access public
	 */
	public $name = 'Geos';

	/**
	 * index method
	 *
	 * @return void
	 * @access public
	 */
	public function index($type = null, $lat = null, $long = null, $opt1 = null, $opt2 = null) {

		$params = array(
				'limit' => 35,
				'page' => 1,
				);

		if(!empty($type) && !empty($lat) && !empty($long)) {
			$lat = floatval($lat);
			$long = floatval($long);
			$opt1 = floatval($opt1);
			$opt2 = floatval($opt2);

			switch($type) {
				case('near'):
					if(!empty($opt1)){
						$cond = array('loc' => array('$near' => array($lat, $long), '$maxDistance' => $opt1));
					} else {
						$cond = array('loc' => array('$near' => array($lat, $long)));
					}
					break;
				case('box'):
					$lowerLeft = array($lat, $long);
					$upperRight = array($opt1, $opt2);
					$cond = array('loc' => array('$within' => array('$box' => array($lowerLeft, $upperRight))));
					break;
				case('circle'):
					$center = array($lat, $long);
					$radius = $opt1;
					$cond = array('loc' => array('$within' => array('$center' => array($center, $radius))));
					break;
			}
			$params['conditions'] = $cond;

		} else {
			$params['order'] = array('_id' => -1);
		}

		$results = $this->Geo->find('all', $params);
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

			$this->Geo->create();
			if ($this->Geo->save($this->data)) {
				$this->flash(__('Geo saved.', true), array('action' => 'index'));
			} else {
			}
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
			$this->flash(__('Invalid Geo', true), array('action' => 'index'));
		}
		if ($this->Geo->delete($id)) {
			$this->flash(__('Geo deleted', true), array('action' => 'index'));
		} else {
			$this->flash(__('Geo deleted Fail', true), array('action' => 'index'));
		}
	}
}
