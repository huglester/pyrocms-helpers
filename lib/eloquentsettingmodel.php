<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class EloquentSettingModel extends Illuminate\Database\Eloquent\Model {

	public $timestamps = false;

	protected $guarded = array(
		'upload_path',
		'upload_path_flash',
		'thumb_upload_path',
		'company_upload_path',
	);

	protected static $_instances = array();

	public static function item($key = null, $default = null, $force_reget = false)
	{
		$class = strtolower(get_called_class());

		if ( ! array_key_exists($class, static::$_instances) or $force_reget)
		{
			static::$_instances[$class] = static::first();

			if ( ! static::$_instances[$class])
			{
				throw new Exception("\$settings not found: ".get_class());
			}

			static::$_instances[$class] = static::$_instances[$class]->toArray();
		}

		if ((count(func_get_args()) === 0))
		{
			return static::$_instances[$class];
		}
		elseif (isset(static::$_instances[$class][$key]))
		{
			return (static::$_instances[$class][$key]) ?: $default;
		}
		else
		{
			if ($default)
			{
				return $default;
			}

			throw new Exception("Settings key not found!");
		}
	}
}
