<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// how to replace this with more elegant solution?
require_once('connection.php');

use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent extends Illuminate\Database\Eloquent\Model {

	// protected static $_instances = array();
	protected static $order = null;
	protected static $lang = null;

	protected $validateVars = array(); // items to validate
	protected $messages;
	protected $validatedFields; // array ('comment');
	protected $errorFields; // array ('title');
	protected $allFields; // used for jquery each... array ('title' => false, 'comment' => true)

	protected $myWhereExists; // needed for findCreate()

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
		$instance = new static;
		$fillable = isset($instance->fillable) ? $instance->fillable : array();

		if (static::$lang or in_array('lang', $fillable))
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

	// For use with: User_m::update_order_simple(1, $order); and not touch the timestamps
	public static function update_order_simple($id, $order)
	{
		$obj = new static;
		$table = $obj->getTable();

		return Capsule::connection()->table($table)->where($obj->primaryKey, $id)->update(array('order' => $order));
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
		if ( ! isset($attributes['lang']) or ! $attributes['lang'])
		{
			if
			(
				((isset(static::$lang) and static::$lang)) or 
				(isset($fillable) and in_array('lang', $fillable))
			)
			{
				$attributes['lang'] = ci()->translate->default_language();
			}
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
		if ( ! isset($attributes['lang']) or ! $attributes['lang'])
		{
			if
			(
				((isset(static::$lang) and static::$lang)) or 
				(isset($fillable) and in_array('lang', $fillable))
			)
			{
				$attributes['lang'] = ci()->translate->default_language();
			}
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

	public function fill(array $attributes)
	{
		// if ((empty($fillable) or count($fillable) === 0) and (empty(static::$rules) or count(static::$rules) === 0))
		if ( ! isset(static::$rules))
		{
			return parent::fill($attributes);
		}

		// we have rules, proceed
		$this->validateVars = array();

		foreach ($attributes as $key => $value)
		{
			// $key = $this->removeTableFromKey($key);

			if ($this->isFillable($key))
			{
				$this->validateVars[$key] = $attributes[$key];
			}
		}

		return parent::fill($this->getValidateVars());
	}

	public function validate($input = null)
	{
		// no rules applied
		if ( ! isset(static::$rules) or count(static::$rules) === 0)
		{
			return true;
		}

		// $input = array_merge($this->attributes, $this->getValidateVars());
		$input = $this->getValidateVars();

		// handle both $_POST and custom arrays
		ci()->form_validation->set_data($input);

		// set up the rules
		ci()->form_validation->set_rules(static::$rules);

		$return = false;
		// validate
		if (ci()->form_validation->run())
		{
			$return = true;
		}
		else
		{
			$this->messages = validation_errors();
		}

		foreach (static::$rules as $rule)
		{
			$field = $rule['field'];

			if ((bool) form_error($field))
			{
				$this->errorFields[] = $field;
			}
			else
			{
				$this->validatedFields[] = $field;
			}

			$this->allFields[$field] = ! (bool) form_error($field);
		}

		return $return;
	}

	public static function findCreate(array $attributes)
	{
		if ( ! $obj = static::myWhereExists($attributes))
		{
			return parent::create($attributes);
		}

		return $obj;
	}

	public static function createUpdate(array $attributes)
	{
		if ($obj = static::myWhereExists($attributes))
		{
			$obj->fill($attributes);
			$obj->save();
		}
		else
		{
			return parent::create($attributes);
		}
		
		return $obj;
	}

	public static function myWhereExists(array $attributes, $myWhereExists = array())
	{
		$query = new static();

		if (
				( ! isset($query->myWhereExists) or count($query->myWhereExists) === 0) 
			and count($myWhereExists) === 0)
		{
			throw new Exception("$myWhereExists should be and array of at least one key in order to use myWhereExists() method.");
		}

		$myWhereExists = (count($myWhereExists) > 0) ? $myWhereExists : $query->myWhereExists;

		foreach ($myWhereExists as $key)
		{
			$query = $query->where($key, array_get($attributes, $key));
		}

		return $query->first();
	}

	public function getMessages()
	{
		return $this->messages;
	}

	public function getValidateVars()
	{
		return $this->validateVars;
	}

	public function getErrorFields()
	{
		return $this->errorFields;
	}

	public function getValidtedFields()
	{
		return $this->validatedFields;
	}

	public function getAllFields()
	{
		return $this->allFields;
	}

}
