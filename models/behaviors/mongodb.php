<?php

/**
 * Mongo specific functionality such as upserting
 *
 * This behavior allows some of the awesome features mongoDB provides such as upserting.
 *
 * @author     Robert Ross (rross@sdreader.com)
 * @copyright  (c) 2011, The Daily Save LLC
 * @link       www.thedailysave.com
 * @package    mongodb
 * @subpackage mongodb.models.behaviors
 * @since 	   v1.0 April 16th, 2011
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class MongoBehavior extends ModelBehavior {
	/**
	 * Behavior name
	 *
	 * @var string
	 */
	public $name = 'Mongo';
	
	public function upsert(&$Model){
		$Model->upsert = true;
		
		return true;
	}
}