<?php
class AllTest extends CakeTestSuite {
    public static function suite() {
        $suite = new CakeTestSuite('All tests');
        $dir = dirname(__FILE__);
        $suite->addTestDirectory($dir . DS . 'Behavior');
        $suite->addTestDirectory($dir . DS . 'Datasource');
        return $suite;
    }
}