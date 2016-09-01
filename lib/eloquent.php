<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent extends Illuminate\Database\Eloquent\Model {

	// protected static $_instances = array();
	protected static $order = null;
	protected static $lang = null;
	protected static $rules = array();

	protected $validateVars = array(); // items to validate
	protected $messages;
	protected $messages_array = array();
	protected $validatedFields; // array ('comment');
	protected $errorFields; // array ('title');
	protected $allFields; // used for jquery each... array ('title' => false, 'comment' => true)

	protected $myWhereExists; // needed for findCreate()

	protected $dynamic_skip; // skip generation of dynamic_image array()
	protected $dynamic_override_model_name;
	protected $dynamic_override_dir_key;
	
	protected $skipValidation;

	// protected static $dispatcher;

	// protected static function boot()
	// {
	// 	parent::boot();
	// 	( ! static::$dispatcher) and static::$dispatcher = new Illuminate\Events\Dispatcher;
	// }

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

	/*
	 * Allows us, for example if we have `image` field, to get the array of dynamic photo sizes.
	 * ModulenameSettings should have suffix ('_thumb' and '_height') in order to appear here automatically like:
	 * thumb_width, thumb_height;
	 * avatar_width, avatar_height
	 * result will be
	   array(3) {
		   'http://example.com/photobanks/modules/317x317x85/uploads/default/katalogas/f90a0607f6c6660eaedd91314fc2609a.jpg',
		   'http://example.com/photobanks/modules/180x180x85/uploads/default/katalogas/f90a0607f6c6660eaedd91314fc2609a.jpg',
	   }
	*/
	public function getImageDynamicAttribute()
	{
		ci()->load->model('photobanks/photobankssetting_m');
		//$path = PhotobanksSetting_m::item('upload_path');

		$sizes = array();

		if ( ! $this->dynamic_skip and $this->image)
		{	
			$module_name = ($this->dynamic_override_model_name) ?: str_replace('_m', '', get_called_class());

			$model_name = ucfirst($module_name).'Setting_m';

			ci()->load->model(array(
				$module_name.'/'.strtolower($model_name),
			));
			
			$sizes_arr = explode('|', PhotobanksSetting_m::item('image_dynamic'));

			if ($this->dynamic_override_dir_key)
			{
				$upload_path = $model_name::item($this->dynamic_override_dir_key);
			}
			else
			{
				$upload_path = 'uploads/default/'.strtolower($module_name);
			}

			// $upload_path = ($this->dynamic_override_dir_key) ?:
			foreach ($sizes_arr as $size)
			{
				$sizes[] = base_url().'photobanks/modules/'.$size.'/'.$upload_path.'/'.$this->image;
			}
		}

		return $sizes;
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
		// Probably better to use DB instead of ELoquent anyway.
		// $order = (int) $order;

		// $obj = static::find($id);
		// if ($obj)
		// {
		// 	$obj->order = (int) $order;
		// 	return $obj->save();
		// }

		// return false;

		$obj = new static;
		$table = $obj->getTable();

		return Capsule::connection()->table($table)->where($obj->primaryKey, $id)->update(array('order' => $order));
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

		// we don't want to generate UID for translated model calls
		// auto generate UID if we have this field
		if (get_called_class() != 'EloquentTranslatedModel' and ( ! isset($attributes['uid']) or ! $attributes['uid']))
		{
			if (isset($fillable) and in_array('uid', $fillable))
			{
				// $attributes['uid'] = Uid::gen();
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
			elseif (ends_with($key, '_at'))
			{
				$timestamp = $attributes[$key];
				if (
					(int) $timestamp === $timestamp 
					and ($timestamp <= PHP_INT_MAX)
					and ($timestamp >= ~PHP_INT_MAX)
				)
				{
					// already a timestamp
					$attributes[$key] = $timestamp;
				}
				else
				{
					$format = ci()->settings->date_format;
					$validated = \DateTime::createFromFormat($format, $timestamp);

					if ($validated)
					{
						$attributes[$key] = $validated->getTimestamp();
					}
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
			elseif (ends_with($key, '_at'))
			{
				$timestamp = $attributes[$key];
				if (
					(int) $timestamp === $timestamp 
					and ($timestamp <= PHP_INT_MAX)
					and ($timestamp >= ~PHP_INT_MAX)
				)
				{
					// already a timestamp
					$attributes[$key] = $timestamp;
				}
				else
				{
					$format = ci()->settings->date_format;
					$validated = \DateTime::createFromFormat($format, $timestamp);

					if ($validated)
					{
						$attributes[$key] = $validated->getTimestamp();
					}
				}
			}
		}

		return parent::update($attributes);
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
				if (ends_with($key, '_at') and strpos($value, '-'))
				{
					switch ($key) {
						case 'expires_at':
							$this->validateVars[$key] = strtotime($attributes[$key].' 23:59:59');
							break;

						case 'starts_at':
							$this->validateVars[$key] = strtotime($attributes[$key].' 00:00:00');
							break;

						default:
							$this->validateVars[$key] = strtotime($attributes[$key].' 00:00:00');
							break;
					}
				}
				elseif (ends_with($key, '_at'))
				{
					$timestamp = $attributes[$key];
					if (
						(int) $timestamp === $timestamp 
						and ($timestamp <= PHP_INT_MAX)
						and ($timestamp >= ~PHP_INT_MAX)
					)
					{
						// already a timestamp
						$this->validateVars[$key] = $timestamp;
					}
					else
					{
						$format = ci()->settings->date_format;
						$validated = \DateTime::createFromFormat($format, $timestamp);
						
						if ($validated)
						{
							$this->validateVars[$key] = $validated->getTimestamp();
						}
					}
				}
				else
				{
					$this->validateVars[$key] = $attributes[$key];
				}
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
		ci()->form_validation->reset_validation();

		// if we are not passing and array, take existing object
		if ( ! $input and $this->id)
		{
			$input = $this->toArray();
		}

		ci()->form_validation->set_data($input);

		// set up the rules
		ci()->form_validation->set_rules($this->getRules());

		$return = false;
		// validate
		if (ci()->form_validation->run())
		{
			// reset the messages
			$this->messages = array();
			$this->errorFields = array();

			$return = true;
		}
		else
		{
			$this->messages = validation_errors();
			$this->messages_array = ci()->form_validation->error_array();
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

	public function getMessage($key)
	{
		return isset($this->messages_array[$key]) ? $this->messages_array[$key] : null;
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

	public function setSkipValidation($bool)
	{
		$this->skip_validation = (bool) $bool;

		return $this;
	}

	public function toArray()
	{
		$results = parent::toArray();

		// only fire, if the actual model has image property
		if (isset($this->attributes['image']) and class_exists('photobanks_m'))
		{
			$results['image_dynamic'] = $this->image_dynamic;
		}

		return $results;
	}

	public function save(array $options = array())
	{
		// no rules applied
		if ( ! isset(static::$rules) or count(static::$rules) === 0 or $this->skip_validation)
		{
			if (isset($this->attributes['skip_validation']))
			{
				unset($this->attributes['skip_validation']);
			}

			return parent::save($options);
		}
		// call validation
		if ($this->validate())
		{
			return parent::save($options);
		}

		return false;
	}

	public function getRules()
	{
		if ( ! isset(static::$rules) or count(static::$rules) === 0)
		{
			return false;
		}

		$parsed = array();
		foreach (static::$rules as $rule)
		{
			if (strpos($rule['label'], 'lang:myglobal:') === 0)
			{
				$key = str_replace('lang:myglobal:', '', $rule['label']);
				$rule['label'] = ci()->translate->{$key};
			}

			$parsed[] = $rule;
		}

		return $parsed;
	}

	public function hasErrors($field = null)
	{
		if (empty($this->errorFields))
		{
			return false;
		}

		if ( ! $field)
		{
			return count($this->errorFields) ? true : false;
		}
		
		return (array_search($field, $this->errorFields) !== false) ? true : false;
	}

}
