<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// how to replace this with more elegant solution?
require_once('connection.php');

class EloquentTranslated extends Eloquent {

	public static function create(array $attributes)
	{
		$success = false;

		if ($results = parent::create($attributes))
		{
			$parent_id = $results->getAttribute('id');

			$translated = filter_by_key_prefix($attributes, 'translated_', true);
			$translated['module'] = strtolower(get_called_class());
			$translated['parent_id'] = $parent_id;

			// we create child translated
			$success = EloquentTranslatedModel::create($translated);
		}

		return ($success) ? $results : false;
	}

	public function update(array $attributes = array())
	{
		$success = false;
		$parent_id = $this->getAttribute('id');

		// delete old items
		EloquentTranslatedModel::items_delete(strtolower(get_called_class()), $parent_id);


		$translated = filter_by_key_prefix($attributes, 'translated_', true);
		$translated['module'] = strtolower(get_called_class());
		$translated['parent_id'] = $parent_id;

		// we create child translated
		$success = EloquentTranslatedModel::create($translated);

		return ($success) ? parent::update($attributes) : false;
	}

	public function delete()
	{
		// delete old items
		EloquentTranslatedModel::items_delete(strtolower(get_called_class()), $this->id);

		return parent::delete();
	}

	public static function destroy($ids)
	{
		throw new \Exception("Please call delete() on translated models");
	}

	public function getTranslatedAttribute()
	{
		return $this->translated(); // without this, child elements were not working
	}

	public function translated()
	{
		return EloquentTranslatedModel::items(strtolower(get_called_class()), $this->id);
	}

	/**
	 * Get an array with the values of a given column.
	 *
	 * @param  string  $column
	 * @param  string  $key
	 * @return array
	 */
	// public function dropdown($column, $key = null)
	// {
	// 	$columns = $this->getListSelect($column, $key);

	// 	// First we will just get all of the column values for the record result set
	// 	// then we can associate those values with the column if it was specified
	// 	// otherwise we can just give these values back without a specific key.
	// 	$results = new Collection($this->get($columns));

	// 	$values = $results->fetch($columns[0])->all();

	// 	// If a key was specified and we have results, we will go ahead and combine
	// 	// the values with the keys of all of the records so that the values can
	// 	// be accessed by the key of the rows instead of simply being numeric.
	// 	if ( ! is_null($key) and count($results) > 0)
	// 	{
	// 		$keys = $results->fetch($key)->all();

	// 		return array_combine($keys, $values);
	// 	}

	// 	return $values;
	// }

	public function toArray()
	{
		$results = parent::toArray();
		
		$results['translated'] = $this->translated();
		$results['image_dynamic'] = $this->image_dynamic;

		return $results;
	}

}
