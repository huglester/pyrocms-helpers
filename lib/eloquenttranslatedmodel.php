<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// how to replace this with more elegant solution?
require_once('connection.php');

/*
	CREATE TABLE `default_translated_t` (
		`uid` INT( 11 ) NOT NULL DEFAULT '0',
		`module` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
		`parent_id` INT( 11 ) UNSIGNED NOT NULL ,
		`key` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
		`val` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
		`lang` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
		INDEX (`uid`, `module` , `parent_id` , `key` , `lang` )
	) ENGINE = InnoDB;
*/

class EloquentTranslatedModel extends Eloquent {

	protected $table = "translated_t";
	public    $timestamps = false;

	public $fillable = array(
		'module',
		'parent_id',
		'key',
		'val',
		'lang',
	);
	
	public static function create(array $attributes)
	{
		if ($uid = array_get($attributes, 'uid'))
		{

		}
		else
		{
			if ( ! array_get($attributes, 'module'))
			{
				throw new Exception("Module name should be passed!");
			}
			
			if ( ! array_get($attributes, 'parent_id'))
			{
				throw new Exception("parent_id should be passed!");
			}
		}

		$langs = ci()->translate->languages_admin();
		$count = 0;
		$count_success = 0;

		foreach ($langs as $lang_slug => $lang)
		{
			$keys = filter_by_key_suffix($attributes, '_'.$lang_slug, true);

			foreach ($keys as $key => $val)
			{
				++$count;

				$input = array(
					'key' => $key,
					'val' => $val,
					'lang' => $lang_slug,
				);

				if ($uid)
				{
					$input['uid'] = $uid;
				}
				else
				{
					$input['module'] = $attributes['module'];
					$input['parent_id'] = $attributes['parent_id'];
				}

				if ($obj = parent::create($input))
				{
					++$count_success;
				}
			}
		}

		return ($count === $count_success);
	}

	public static function items_delete($module, $parent_id, $uid = 0)
	{
		if ($uid)
		{
			return static::where('uid', $uid)->delete();
		}
		else
		{
			return static::where('module', $module)->where('parent_id', $parent_id)->delete();
		}
	}

	public static function items($module, $parent_id, $uid = 0)
	{
		if ($uid)
		{
			$items = static::where('uid', $uid)->get()->toArray();
		}
		else
		{
			$items = static::where('module', $module)->where('parent_id', $parent_id)->get()->toArray();
		}

		$unique_keys = array();

		foreach ($items as $i)
		{
			if ( ! in_array($i['key'], $unique_keys))
			{
				$unique_keys[] = $i['key'];
			}
		}

		$array = array();
		// we foreach on lang, in case new language was added, and we don't want undefined notices...
		$langs = ci()->translate->languages_admin();

		foreach ($langs as $lang_slug => $lang)
		{
			$array[$lang_slug] = array();

			foreach ($unique_keys as $k)
			{
				$array[$lang_slug][$k] = '';

				// $array[$lang_slug][$k] = array_get($items)
				foreach ($items as $i)
				{
					if ($i['key'] == $k and $i['lang'] == $lang_slug)
					{
						$array[$lang_slug][$k] = $i['val'];
					}
				}
			}
		}

		return $array;
	}

}
