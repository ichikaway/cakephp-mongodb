<?php

class MongodbSource extends DataSource{

	protected $_db = null;

    public function __construct($config = array()) {
        $defaults = array(
            'persistent' => true,
            'host'       => 'localhost',
            'database'   => '',
            'port'       => '27017',
        );
        parent::__construct(array_merge( $defaults, $config));

		return $this->connect();
	}

    public function connect() {
        $config = $this->config;
        $this->connected = false;
        $host = $config['host'] . ':' . $config['port'];
        $this->connection = new Mongo($host, true, $config['persistent']);
        if ($this->_db = $this->connection->selectDB($config['database'])) {
            $this->connected = true;
        }
        return $this->connected;
    }

	public function close(){
		return $this->disconnect();
	}	

    public function disconnect() {
        if ($this->connected) {
            $this->connected = !$this->connection->close();
            unset($this->_db);
            unset($this->connection);
            return !$this->connected;
        }
        return true;
    }

    public function listSource($data = null) {
        return $this->_db->listCollections();
    }

	
    public function read(&$model, $query = array()) {
		$query = $this->_setEmptyArrayIfEmpty($query);
		extract($query);

		if(!empty($order[0])){
			$order = array_shift($order);
		}

		$result = $this->_db->selectCollection($model->table)->find($conditions, $fields)
				  ->sort($order)->limit($limit)->skip( ($page - 1)  * $limit);

		if($model->findQueryType === 'count'){
			return array( array($model->name => array('count' =>  $result->count())) );
		}

		$results = null;
		while($result->hasNext()){
			$mongodata = $result->getNext();
			if(empty($mongodata['id'])){
				$mongodata['id'] = $mongodata['_id']->__toString();
			}
			$results[] = $mongodata;
		}
		return $results;

    }

	protected function _setEmptyArrayIfEmpty($data){
		if(is_array($data)){
			foreach($data as $key => $value){
				$data[$key] = empty($value) ? array() : $value;
			}
			return $data;
		}else{
			return empty($data) ? array() : $data ;
		}
	}


}

?>
