<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// how to replace this with more elegant solution?
require_once('connection.php');

class EloquentSettingModel extends Illuminate\Database\Eloquent\Model {

	public $timestamps = false;

	protected $guarded = array(
		'upload_path',
		'upload_path_flash',
		'thumb_upload_path',
		'company_upload_path',
	);

	public static function item($key = null, $default = null)
	{
		static $settings;

		if ( ! $settings)
		{
			$settings = static::first();

			if ( ! $settings)
			{
				throw new Exception("\$settings not found: ".get_class());
			}

			$settings = $settings->toArray();
		}

		if ((count(func_get_args()) === 0))
		{
			return $settings;
		}
		elseif (isset($settings[$key]))
		{
			return ($settings[$key]) ?: $default;
		}
		else
		{
			throw new Exception("Settings key not found!");
		}
	}
}
