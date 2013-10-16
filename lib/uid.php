<?php defined('BASEPATH') or exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as Capsule;

/*
	CREATE TABLE IF NOT EXISTS `default_uid` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
*/

class Uid {

	public static function gen()
	{
		$id = Capsule::table('uid')->insertGetId(array());
		
		if ( ! $id)
		{
			throw new Exception("Could not generate UID!");
		}

		return $id;
	}

}
