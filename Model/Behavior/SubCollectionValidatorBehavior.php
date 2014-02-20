<?php
/**
 * SubCollectionValidatorBehavior.
 *
 * Enalbe validation of subsets, used in some NoSQL
 * Databases like MongoDB.
 *
 * PHP versions 5
 *
 * Copyright 2012, Radig Soluções em TI (http://radig.com.br)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2012, Radig Soluções em TI. (http://radig.com.br)
 * @link          http://github.com/radig/
 * @package       Mongodb.Model.Behavior
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeValidationSet', 'Model/Validator');
class SubCollectionValidatorBehavior extends ModelBehavior {

    private $_methods = array();

    private $_Model = null;

    public function setup(Model $model, $config = array()) {
        if(!isset($this->_methods[$model->name])) {
            $this->_methods[$model->name] = $model->validator()->getMethods();
        }
    }

    public function beforeValidate(Model $model, $config = array()) {
        $this->_Model = $model;

        if(is_a($model->getDataSource(), 'Mongodbsource') && isset($model->collectionValidate)) {
            foreach($model->data[$model->alias] as $fieldName => $value) {
                if(!isset($model->collectionValidate[$fieldName])) {
                    continue;
                }

                $this->_validateCollection($model->data[$model->alias][$fieldName], $model->collectionValidate[$fieldName]);
            }
        }

        return true;
    }

    public function beforeSave(Model $model, $config = array()) {
        return empty($model->validationErrors);
    }

    protected function _validateCollection($data, $ruleset) {
        foreach($data as $field => $value) {
            if(is_array($value) && isset($ruleset["_{$field}"])) {
                $status &= $this->_validateCollection($data[$field], $ruleset["_{$field}"]);
                continue;
            }

            if(!isset($ruleset[$field])) {
                continue;
            }

            $obj = new CakeValidationSet($field, $ruleset[$field]);
            $obj->setMethods($this->_methods[$this->_Model->name]);
            $errors = $obj->validate($data);

            foreach($errors as $error) {
                $this->_Model->invalidate($field, $error);
            }
        }
    }
}