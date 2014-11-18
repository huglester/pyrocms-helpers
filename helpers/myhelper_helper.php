<?php

/**
 * Faster equivalent of call_user_func_array
 */
if ( ! function_exists('call_fuel_func_array'))
{
		function call_fuel_func_array($callback , array $args)
		{
				// deal with "class::method" syntax
				if (is_string($callback) and strpos($callback, '::') !== false)
				{
						$callback = explode('::', $callback);
				}

				// if an array is passed, extract the object and method to call
				if (is_array($callback) and isset($callback[1]) and is_object($callback[0]))
				{
						list($instance, $method) = $callback;

						// calling the method directly is faster then call_user_func_array() !
						switch (count($args))
						{
								case 0:
										return $instance->$method();

								case 1:
										return $instance->$method($args[0]);

								case 2:
										return $instance->$method($args[0], $args[1]);

								case 3:
										return $instance->$method($args[0], $args[1], $args[2]);

								case 4:
										return $instance->$method($args[0], $args[1], $args[2], $args[3]);
						}
				}

				elseif (is_array($callback) and isset($callback[1]) and is_string($callback[0]))
				{
						list($class, $method) = $callback;
						$class = '\\'.ltrim($class, '\\');

						// calling the method directly is faster then call_user_func_array() !
						switch (count($args))
						{
								case 0:
										return $class::$method();

								case 1:
										return $class::$method($args[0]);

								case 2:
										return $class::$method($args[0], $args[1]);

								case 3:
										return $class::$method($args[0], $args[1], $args[2]);

								case 4:
										return $class::$method($args[0], $args[1], $args[2], $args[3]);
						}
				}

				// if it's a string, it's a native function or a static method call
				elseif (is_string($callback) or $callback instanceOf \Closure)
				{
						is_string($callback) and $callback = ltrim($callback, '\\');

						// calling the method directly is faster then call_user_func_array() !
						switch (count($args))
						{
								case 0:
										return $callback();

								case 1:
										return $callback($args[0]);

								case 2:
										return $callback($args[0], $args[1]);

								case 3:
										return $callback($args[0], $args[1], $args[2]);

								case 4:
										return $callback($args[0], $args[1], $args[2], $args[3]);
						}
				}

				// fallback, handle the old way
				return call_user_func_array($callback, $args);
		}
}

if ( ! function_exists('result'))
{
		/**
		 * Checks if a return value is a Closure without params, and if
		 * so executes it before returning it.
		 *
		 * @param   mixed  $val
		 * @return  mixed  closure result
		 */
		function result($val)
		{
				if ($val instanceof Closure)
				{
						return $val();
				}

				return $val;
		}
}

if ( ! function_exists('cleanpath'))
{
		/**
		 * Cleans a file path so that it does not contain absolute file paths.
		 *
		 * @param   string  the filepath
		 * @return  string  the clean path
		 */
		function cleanpath($path)
		{
				static $search = array(APPSPATH, VENDORPATH, DOCROOT, '\\');
				static $replace = array('APPSPATH/', 'VENDORPATH/', 'DOCROOT/', DIRECTORY_SEPARATOR);
				return str_ireplace($search, $replace, $path);
		}
}

if ( ! function_exists('my_send_email') )
{
	// function my_send_email($data) {
	// 	// $CI =& get_instance();
	// 	ci()->load->library('tiny_swift');
	// 	return (ci()->tiny_swift->send($data)) ? true : false;
	// }

	function my_send_email($data) {
		$tinyswift = new TinySwift;
		return ($tinyswift->send($data)) ? true : false;
	}	
}


function month_array($lang = 'en')
{
	$array['en'] = array(
		1 => 'January',
		'February',
		'March',
		'April',
		'May',
		'June',
		'July',
		'August',
		'September',
		'October',
		'November',
		'December'
	);

	$array['lt'] = array(
		1 => 'Sausis',
		'Vasaris',
		'Kovas',
		'Balandis',
		'Gegužė',
		'Birželis',
		'Liepa',
		'Rugpjūtis',
		'Rugsėjis',
		'Spalis',
		'Lapkritis',
		'Gruodis'
	);

	return (isset($array[$lang])) ? $array[$lang] : $array['lt'];
}

/*
Array
(
	[2013] => 2013
	[2012] => 2012
	[2011] => 2011
	[2010] => 2010
	[2009] => 2009
	[2008] => 2008
	[2007] => 2007
	[2006] => 2006
	[2005] => 2005
	[2004] => 2004
	[2003] => 2003
)

*/
function years_array($end = null, $reverse = false)
{
	( ! $end) and $end = date('Y') - 20;

	$years = array_combine(range(date("Y"), $end), range(date("Y"), $end));

	return ($reverse) ? array_reverse($years) : $years;
}

function months_array()
{
	return array_combine(range(1, 12), range(1, 12));
}

function days_array()
{
	return array_combine(range(1, 31), range(1, 31));
}

function age($p_strDate)
{
	list($Y,$m,$d)    = explode("-",$p_strDate);
	$years            = date("Y") - $Y;

	if( date("md") < $m.$d ) { $years--; }
	return $years;
}

// this should be app specific probably :( will solve later
function _my_create_pagination($uri, $total_rows, $limit = NULL, $uri_segment = 4, $full_tag_wrap = TRUE, $prefix = NULL, $segment_count = 1)
{
	// Prefix current language
	//$uri = CURRENT_LANGUAGE.'/'.$uri;

	$limit or $limit = 3;

	$ci =& get_instance();
	$ci->load->library('pagi');

	if (strlen($ci->uri->segment(1)) === 2)
	{
		++$uri_segment;
		++$segment_count;
	}

	$current_page = $ci->uri->segment($uri_segment, 0);

	// Initialize pagination
	$config['suffix']				= $ci->config->item('url_suffix');
	$config['base_url']				= $config['suffix'] !== FALSE ? rtrim(site_url($uri), $config['suffix']) : site_url($uri);

	$segment_count_current          = 1;
	$config['first_url']			= site_url();
	while ($segment_count_current <= $segment_count)
	{
		$config['first_url']        .= $ci->uri->segment($segment_count_current).'/';
		++$segment_count_current;
	}
	$config['first_url'] = rtrim($config['first_url'], '/');

//	$config['first_url']			= site_url().$ci->uri->segment(1);

	$config['total_rows']			= $total_rows; // count all records

	if ($prefix)
	{
		$config['prefix']			= $prefix;
	}

	$config['per_page']				= $limit === NULL ? $ci->settings->records_per_page : $limit;
	$config['uri_segment']			= $uri_segment;
	$config['page_query_string']	= FALSE;

	$config['num_links'] = 4;
/*
	<div class="pagination">
<div class="prev disabled"><a href="#">Atgal</a></div>
<ul>
<li><a class="current" href="#">1</a></li>
<li><a href="#">2</a></li>
<li><a href="#">3</a></li>
<li><a href="#">4</a></li>
<li><a href="#">5</a></li>
</ul>
<div class="next"><a href="#">Toliau</a></div>
</div>
	*/
	$config['full_tag_open'] = '<div class="pagination">';
	$config['full_tag_close'] = '</div>';

	$config['first_link'] = '&lt;&lt;';
	$config['first_tag_open'] = '<li class="first">';
	$config['first_tag_close'] = '</li>';

	$config['prev_link'] = $ci->translate->paging_btn_prev;
	$config['prev_tag_open'] = '';
	$config['prev_tag_close'] = '';

	$config['cur_tag_open'] = '<li class="current"><a>';
	$config['cur_tag_close'] = '</a></li>';

	$config['num_tag_open'] = '<li>';
	$config['num_tag_close'] = '</li>';

	$config['next_link'] = $ci->translate->paging_btn_next;
	$config['next_tag_open'] = '';
	$config['next_tag_close'] = '';

	$config['last_link'] = '&gt;&gt;';
	$config['last_tag_open'] = '<li class="last">';
	$config['last_tag_close'] = '</li>';

	$ci->pagi->first_link = FALSE;
	$ci->pagi->next_link = FALSE;
	$ci->pagi->initialize($config); // initialize pagination

	$offset = ($current_page < 2) ? 0 : ($current_page-1) * $config['per_page'];

	return array(
		'current_page' 	=> $current_page,
		'per_page' 		=> $config['per_page'],
		'limit'			=> array($config['per_page'], $offset),
		'links' 		=> $ci->pagi->create_links($full_tag_wrap)
	);
}





function pages_html($data = array(), $allow_null = false)
{
	$page = ci()->db->select('id')->from('pages')->where('uri', strtolower($data['lang']))->get()->row();




	//$data['form_slug'] = 'test';
	$data['value'] = '';
	//$data['current_parent'] = 0;

	$html = '<select name="'.$data['form_slug'].'" id="'.$data['form_slug'].'">';

	if ( ! $page)
	{
		return $html.'</select>';
	}

	if ($allow_null)
	{
		$html .= '<option value="0">--None--</option>';
	}
	else
	{
		//$tree = array();
	}

	$html .= pages_build_tree_select(array('current_parent' => $data['current_parent'], 'parent_id' => $page->id)).'</select>';

	return $html;
}

/**
 * Tree select function
 *
 * Creates a tree to form select.
 *
 * This originally appears in the PyroCMS navigation
 * admin controller, but we need it here so here it is.
 *
 * @param	array
 * @return	array
 */
function pages_build_tree_select($params)
{
	$params = array_merge(array(
		'tree'			=> array(),
		'parent_id'		=> 0,
		'current_parent'=> 0,
		'current_id'	=> 0,
		'level'			=> 0
	), $params);

	extract($params);

	if ( ! $tree)
	{
		if ($pages = ci()->db->select('id, parent_id, title')->order_by('`order`')->get('pages')->result())
		{
			foreach($pages as $page)
			{
				$tree[$page->parent_id][] = $page;
			}
		}
	}

	if ( ! isset($tree[$parent_id]))
	{
		return;
	}

	$html = '';

	foreach ($tree[$parent_id] as $item)
	{
		if ($current_id == $item->id)
		{
			continue;
		}

		$html .= '<option value="' . $item->id . '"';
		$html .= $current_parent == $item->id ? ' selected="selected">': '>';

		if ($level > 0)
		{
			for ($i = 0; $i < ($level*2); $i++)
			{
				$html .= '&nbsp;';
			}

			$html .= '-&nbsp;';
		}

		$html .= $item->title . '</option>';

		$html .= pages_build_tree_select(array(
			'tree'			=> $tree,
			'parent_id'		=> (int) $item->id,
			'current_parent'=> $current_parent,
			'current_id'	=> $current_id,
			'level'			=> $level + 1
		));
	}

	return $html;
}

function array_paginate($items, $size = 2, $preserve_keys = true)
{
	$total = count($items);

	$new_array = array();
	
	$page_nr = 1;
	$count = 0;
	foreach ($items as $key => $item)
	{
		++$count;

		( ! isset($new_array[$page_nr])) and $new_array[$page_nr] = array();

		if ($preserve_keys)
		{
			$new_array[$page_nr][$key] = $item;
		}
		else
		{
			$new_array[$page_nr][] = $item;
		}

		// reset
		if ($count === $size)
		{
			$count = 0;
			++$page_nr;
		}
	}

	return $new_array;
}

function array_paginate_col($array = array(), $per_column = 5, $preserve_keys = true)
{
	$total = count($array);

	$limit = (int) ceil(bcdiv($total, $per_column, 2));
	$page_nr = 1;

	$new_array = array();
	$array_key = 0;

	$count = 0;
	foreach ($array as $key => $value)
	{
		++$count;

		( ! isset($new_array[$page_nr])) and $new_array[$page_nr] = array();

		if ($preserve_keys)
		{
			$new_array[$page_nr][$key] = $value;
		}
		else
		{
			$new_array[$page_nr][] = $value;
		}

		if ($count === $limit)
		{
			$count = 0;
			++$page_nr; // move to the next column
		}
	}

	return $new_array;
}


/*function starts_with($haystack, $needle)
{
	return strpos($haystack, $needle) === 0;
}

function ends_with($haystack, $needle)
{
	return $needle === substr($haystack, strlen($haystack) - strlen($needle));
}*/


function array_search_recursive($needle,$haystack, $strict=false, $path=array())
{
 foreach ($haystack as $i => $x) {
	if (is_array($x)) {
	  $b = find($needle, $x);
	  if ($b) return count($haystack) > 1 ? array($i, $x) : $b;
	}
	else if ($x == $needle) {
	  return array($i, $x);
	}
  }

  return false;
}

/*

It gives print_r() the task of checking for infinite recursion (which it does well) and uses the indentation in the output to find the depth of the array.
 */
function array_depth($array)
{
	$max_indentation = 1;

	$array_str = print_r($array, true);
	$lines = explode("\n", $array_str);

	foreach ($lines as $line)
	{
		$indentation = (strlen($line) - strlen(ltrim($line))) / 4;

		if ($indentation > $max_indentation)
		{
			$max_indentation = $indentation;
		}
	}

	return ceil(($max_indentation - 1) / 2) + 1;
}


function filter_by_key_prefix($arr, $prefix, $drop_prefix = false)
{
	$params = array();
	foreach ($arr as $k => $v)
	{
		if (strpos($k, $prefix) === 0)
		{
			if ($drop_prefix)
			{
				$k = substr($k, strlen($prefix));
			}
			$params[$k] = $v;
		}
	}
 
	return $params;
}
 
function filter_by_key_suffix($arr, $prefix, $drop_prefix = false)
{
	$params = array();
	foreach ($arr as $k => $v)
	{
		if (strpos($k, $prefix) === strlen($k) - strlen($prefix))
		{
			if ($drop_prefix)
			{
				$k = substr($k, 0, strlen($k) - strlen($prefix));
			}
 
			$params[ $k ] = $v;
		}
	}
 
	return $params;
}
 
function strpos_array(array $haystack, $needle = '', $case_sensitive = false)
{
	$func = ($case_sensitive) ? 'strpos' : 'stripos';
 
	foreach ($haystack as $r)
	{
		if ($func($needle, $r) !== false)
		{
			return true;
		}
	}
 
	return false;
}
 

if ( ! function_exists('hug_array_insert') )
{
	/*
		Insert given array in specific position
	*/
	function hug_array_insert(array &$original, $value, $pos)
	{
		if (count($original) < abs($pos))
		{
			throw new \Exception('Position larger than number of elements in array in which to insert.');
		}

		array_splice($original, $pos, 0, $value);

		return $original;
	}
}

function days_old_diff($startTimestamp, $endTimestamp = null, $mode = null)
{
	static $t;
	// 09:38
	$time = date('H:i', $startTimestamp);

	($endTimestamp === null) and $endTimestamp = time();
	//gmdate(format)

	$startTimestamp = strtotime(date('Y-m-d 00:00:00', $startTimestamp));
	$endTimestamp = strtotime(date('Y-m-d 00:00:00', $endTimestamp));
	// date('Y-m-d 00:00:00', $endTimestamp);
	// echo date('Y-m-d 00:00:00', $startTimestamp);
	// echo date('Y-m-d 00:00:00', $endTimestamp);
	// return ;
	$timeDiff = abs($endTimestamp - $startTimestamp);

	// echo $timeDiff;exit;

	$numberDays = $timeDiff/86400;  // 86400 seconds in one day

	// and you might want to convert to integer
	$numberDays = intval($numberDays);

	$prefix = '';

	switch ($numberDays) {
		case 0:
			break;
		case 1:
			$prefix = ci()->translate->time_yesterday.' ';
			break;
		
		case 2:
			$prefix = ci()->translate->time_two_days_ago.' ';
			break;
		
		default:
			// Friday, March 29 2013 01:29:57
			($t === null) and $t = new Huglester\Translated\Date;

			($mode) and $t->setMode($mode);

			$date = date('F d, ', $startTimestamp);
			$prefix = $t->tr($date, 'lt');

			break;
	}

	return $prefix.$time;
}

function obj_get($obj, $key, $default = null)
{
	if (is_object($obj) and isset($obj->$key))
	{
		return $obj->$key;
	}

	return $default;
}

function is_valid_email($email)
{
	//return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);

	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function recursive_copy($src, $dst)
{ 
	$dir = opendir($src);
	
	if ( ! is_dir($dst))
	{
		mkdir($dst);
		chmod($dst, 0777);
	}

	while (false !== ( $file = readdir($dir)))
	{
		if (( $file != '.' ) && ( $file != '..' ))
		{
			if ( is_dir($src . '/' . $file) )
			{
				recurse_copy($src . '/' . $file,$dst . '/' . $file);
			}
			else
			{
				copy($src . '/' . $file,$dst . '/' . $file); 
			}
		}
	}

	closedir($dir);
}

function hex2rgb($hex)
{
	$hex = str_replace("#", "", $hex);

	if (strlen($hex) == 3)
	{
		$r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
		$g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
		$b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
	}
	else
	{
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
	}

	$rgb = array($r, $g, $b);

	return $rgb;
}

function array_reverse_recursive($array, $preserve_keys = false)
{
	foreach ($array as $key => $val)
	{
		if (is_array($val))
		{
			$array[$key] = array_reverse_recursive($val);
		}
	}

	return array_reverse($array, $preserve_keys);
}

/*
	Translated helpers
*/
function tdropdown($array, $field = 'title', $lang = null)
{
	($lang === null) and $lang = ci()->translate->def();

	$new_array = array();

	foreach ($array as $v)
	{
		$new_array[$v['id']] = translated($v['translated'], $field, $lang);
	}

	return $new_array;
}

/*
	Checks if selected pivot model has the key
	useful with using checkboxes for multi-selects
*/
function selected_pivot($input, $value)
{
	return pivot_exists($inputm, $value);
}

/*
Example:
form_checkbox('lessons[]', $key, pivot_exists($item->drivings, $key), 'id="drivings_selected_'.$key.'"');
*/
function pivot_exists($collection, $key)
{
	if ($collection instanceOf Illuminate\Database\Eloquent\Collection)
	{
		$collection = $collection->toArray();
	}

	if ( ! empty($collection))
	{
		foreach ($collection as $value)
		{
			if ($value['id'] == $key)
			{
				return true;
			}
		}
	}

	return false;
}

/*
	Example:
	pivot_selected($item->languages->toArray(), 3, 'speak_id')
*/
function pivot_selected($collection, $key, $field)
{
	if ($collection instanceOf Illuminate\Database\Eloquent\Collection)
	{
		$collection = $collection->toArray();
	}

	if ( ! empty($collection))
	{
		foreach ($collection as $item)
		{
			if ($item['id'] == $key)
			{
				return array_get($item['pivot'], $field, false);
			}
		}
	}

	return false;
}


function dq()
{
	$CI =& get_instance();
	
	
		$style = 'width:96%; margin:1em; overflow:auto;text-align:left; font-family:Courier; font-size:0.86em; background:#efe none; color:#000; text-align:left; border:solid 1px;padding:0.42em';
	echo "<fieldset style='$style'>";
			echo    '<legend>last SQL query dumper:</legend>';        
			echo    "<pre style='width:58.88%; margin:-1.2em 0 1em 9.0em;overflow:auto'>";
				echo $CI->db->last_query();
			echo '</pre>';
	echo '</fieldset>';
}

function edq()
{
	$log = Illuminate\Database\Capsule\Manager::connection()->getQueryLog();
	d($log);
}

function invoice_pad($input, $pad_length = 4, $pad_string = "0")
{
	return str_pad($input, $pad_length, $pad_string, STR_PAD_LEFT);
}

function default_value($value = null, $default = null)
{
	return ($value) ?: $default;
}

/*
 * Transforms string:
 * 1=Business|2=Individual
 *
 * to:
 *
 * array (
 *  1 => 'Business',
 *  2 => 'Individual',
 * );
 *
 * for use with form_dropdown();
 * */
function string_to_dropdown($string)
{
	$array = explode('|', $string);
	$new_array = array();
	foreach ($array as $item)
	{
		$item = explode('=', $item);

		if (count($item) != 2)
		{
			throw new Exception('Syntax should be 1=Business|2=Individual');
		}

		// If they didn't specify a key=>value (example: name=Your Name) then we'll use the value for the key also
		$new_array[$item[0]] = $item[1];
	}

	return $new_array;
}

// returns something like
// parent_id=3&action=delete
function uri_get_params()
{
	$get = ci()->input->get();

	if ($get and is_array($get))
	{
		return http_build_query($get);
	}

	return null;
}

/*
	taken from: http://stackoverflow.com/questions/6284553/using-an-array-as-needles-in-strpos
	if strpos in array

	Example usage:
	$string = 'Whis string contains word "cheese" and "tea".';
	$array  = array('burger', 'melon', 'cheese', 'milk');

	if (strposa($string, $array, 1))
	{
		echo 'true';
	}
	else
	{
		echo 'false';
	}
*/
function strposa($haystack, $needle, $offset=0)
{
	if ( ! is_array($needle)) $needle = array($needle);

	foreach($needle as $k => $query)
	{
		if (strpos($haystack, $query, $offset) !== false) return $k; // stop on first true result
	}

	return false;
}

function is_weekend($date)
{
	return (date('N', $date) >= 6);
}

if ( ! function_exists('recursive_rmdir'))
{
	function recursive_rmdir($dir)
	{
		if (is_dir($dir))
		{
			$objects = scandir($dir);

			foreach ($objects as $object)
			{
				if ($object != "." && $object != "..")
				{
					if (filetype($dir."/".$object) == "dir")
					{
						recursive_rmdir($dir."/".$object);
					}
					else
					{
						@unlink($dir."/".$object);
					}
				}
			}

			reset($objects);
			@rmdir($dir);
		}
	}
}

/*
	Example usage: 
	$filtered = arr_filter_keys($items, array(
		'image',
		'translated',
		'uri',
		'image_dynamic',
		'photobank.image',
		'photobank.photos',
		'photobank.thumb_url',
		'photobank.gallery_url',
	));
*/
if ( ! function_exists('arr_filter_keys'))
{
	function arr_filter_keys(array $data, array $keys, $is_multi = true)
	{
		$return = array();

		if ( ! $is_multi)
		{
			$data = array($data);
		}

		foreach ($data as $input)
		{
			$output = array();
			foreach ($keys as $key)
			{
				if (strpos($key, '.') !== false)
				{
					$parts = explode('.', $key);
					$count = count($parts);

					if ($count >= 1)
					{
						if ( ! isset($output[$parts[0]]))
						{
							$output[$parts[0]] = array();
						}
					}

					if ($count >= 2)
					{
						$value = ($count === 2) ? Huglester\Common\Arr::get($input, $key) : array();
						$output[$parts[0]][$parts[1]] = $value;
					}

					if ($count >= 3)
					{
						$value = ($count === 3) ? Huglester\Common\Arr::get($input, $key) : array();
						$output[$parts[0]][$parts[1]][$parts[2]] = $value;
					}

					if ($count >= 4)
					{
						throw new Exception("Too deep nesting.", 1);
					}
				}
				else
				{
					$output[$key] = Huglester\Common\Arr::get($input, $key);
				}
			}

			$return[] = $output;
		}

		if ( ! $is_multi)
		{
			return reset($return);
		}

		return $return;
	}
}

if ( ! function_exists('field_visible'))
{
	function field_visible($field, $settings)
	{
		$hidden = explode(' ', $settings['hidden']);
		$translated_hidden = explode(' ', $settings['translated_hidden']);

		if (strpos($field, 'translated_') === 0)
		{
			$parts = explode('_', $field);

			if ( ! empty($translated_hidden) and in_array($parts[1], $translated_hidden))
			{
				return false;
			}
		}
		else
		{
			if ( ! empty($hidden) and in_array($field, $hidden))
			{
				return false;
			}
		}

		return true;
	}

}
