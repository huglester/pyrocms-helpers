<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// how to replace this with more elegant solution?
require_once('connection.php');

use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent extends Illuminate\Database\Eloquent\Model {

	// protected static $_instances = array();

	protected static $order = null;
	protected static $lang = null;
	protected static $image_fields = array();

	public function scopeActive($query)
	{
		return $query->where('is_active', '=', 1);
	}

	public function scopeOrder($query)
	{
		$instance = new static;
		$fillable = isset($instance->fillable) ? $instance->fillable : array();

		if (static::$order or in_array('order', $fillable))
		{
			return $query->orderBy('order');
		}

		return $query;
	}

	public function scopeLang($query)
	{
		if (static::$lang)
		{
			return $query->where('lang', CURRENT_LANGUAGE);
		}

		return $query;
	}

	// unix timestamp support
	public function getDates()
	{
		return array();
	}

	// unix timestamp support
	public function freshTimestamp()
	{	
		return time();
	}

	// For use with: User_m::update_order(1, $order);
	public static function update_order($id, $order)
	{
		// TODO change to DB ?
		$order = (int) $order;

		$obj = static::find($id);
		$obj->order = (int) $order;

		return $obj->save();
	}

	public static function exists($field, $value, $lang = null, $pk_id = 0)
	{
		// fallback to default language
		// we hide language selector if there are only 1 language in Translate settings
		($lang === null) and $lang = ci()->translate->def();

		$obj = new static;
		$table = $obj->getTable();
		$primary_key = $obj->primaryKey;

		try {
			$exists = Capsule::table($table)
				->select('*')
				->where($field, $value)
				->where('lang', strtolower($lang))
				->where($primary_key, '!=', (int) $pk_id) // edit support
				->first();
		} catch (\Exception $e) {
			// we support `lang` field here. in case table does not have `lang` field
			if (strpos($e->getMessage(), "Unknown column 'lang'") !== false)
			{
				$exists = Capsule::table($table)
					->select('*')
					->where($field, $value)
					->where($primary_key, '!=', (int) $pk_id) // edit support
					->first();
			}
			else
			{
				throw new \Exception($e->getMessage());
			}
		}

		return $exists;
	}

	public static function create(array $attributes)
	{
		$instance = new static;
		$fillable = isset($instance->fillable) ? $instance->fillable : array();

		// do we have enabled manual order?
		if (
			(static::$order and ! isset($attributes['order'])) or 
			(isset($fillable) and in_array('order', $fillable) and ! isset($attributes['order']))
			)
		{
			$order = $instance::max('order');
			$attributes['order'] = ++$order;
		}

		// fallback to default language
		if (
			(static::$lang and ! isset($attributes['lang'])) or 
			(isset($fillable) and in_array('lang', $fillable) and ! isset($attributes['lang']))
			)
		{
			$attributes['lang'] = ci()->translate->default_language();
		}

		foreach ($attributes as $key => $value)
		{
			// we have expires_at/starts_at field with value like: 2013-05-15 instead of UNIX timestamps
			// used in datepicker inputs
			if (ends_with($key, '_at') and strpos($value, '-'))
			{
				switch ($key) {
					case 'expires_at':
						$attributes[$key] = strtotime($value.' 23:59:59');
						break;
					
					case 'starts_at':
						$attributes[$key] = strtotime($value.' 00:00:00');
						break;
					
					default:
						$attributes[$key] = strtotime($value.' 00:00:00');
						break;
				}
			}
		}

		return parent::create($attributes);
	}

	/**
	 * Update the model in the database.
	 *
	 * @param  array  $attributes
	 * @return mixed
	 */
	public function update(array $attributes = array())
	{
		$instance = new static;
		$fillable = isset($instance->fillable) ? $instance->fillable : array();

		// fallback to default language
		if ((static::$lang or in_array('order', $fillable)) and ! isset($attributes['lang']) and ! $this->lang)
		{
			$attributes['lang'] = ci()->translate->default_language();
		}

		foreach ($attributes as $key => $value)
		{
			// we have expires_at/starts_at field with value like: 2013-05-15 instead of UNIX timestamps
			// used in datepicker inputs
			if (ends_with($key, '_at') and strpos($value, '-'))
			{
				switch ($key) {
					case 'expires_at':
						$attributes[$key] = strtotime($value.' 23:59:59');
						break;
					
					case 'starts_at':
						$attributes[$key] = strtotime($value.' 00:00:00');
						break;
					
					default:
						$attributes[$key] = strtotime($value.' 00:00:00');
						break;
				}
			}
		}
		
		return parent::update($attributes);
	}

	// auto fix, for: 'my intro' > '<p>my intro</p>'
	public function setIntroAttribute($value)
	{
		$tag = 'p';

		if ($value and strip_tags($value) and strpos($value, '<'.$tag) !== 0)
		{
			$value = '<'.$tag.'>'.$value.'</'.$tag.'>';
		}

		$this->attributes['intro'] = $value;
	}

	public function prep_value($value, $tag = 'p')
	{
		if ($value and strip_tags($value) and strpos($value, '<p') !== 0 and mb_strlen(strip_tags($value)) === mb_strlen($value))
		{
			$value = '<'.$tag.'>'.$value.'</'.$tag.'>';
		}
		
		return $value;
	}

}
