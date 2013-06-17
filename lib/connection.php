<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as Capsule;

$CI = & get_instance();
$config = $CI->db; // Get the DB object

$capsule = new Capsule;

// somehow hostname returns a hostname with a port (localhost:3306), while PDO does not accept it
if (strpos($config->hostname, ':'))
{
	list($hostname, $port) = explode(':', $config->hostname);
}
else
{
	$hostname = $config->hostname;
	$port = $config->port;
}

$capsule->addConnection(array(
	'driver' => $config->dbdriver,
	'host' => $hostname,
	'port' => $port,
	'database' => $config->database,
	'username' => $config->username,
	'prefix' => $config->dbprefix,
	'password' => $config->password,
	'charset' => $config->char_set,
	'collation' => $config->dbcollat,
));

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

$conn = $capsule->connection();

$conn->setFetchMode(PDO::FETCH_OBJ);

/*// If you want to use the Eloquent ORM...
$capsule->setupEloquent();

// Making A Query Builder Call...
$capsule->connection()->table('users')->where('id', 1)->first();

// Making A Schema Builder Call...
$capsule->connection()->schema()->create('users', function($t)
{
	$t->increments('id');
	$t->string('email');
	$t->timestamps();
});*/

// Usage example:

/*
use Illuminate\Database\Capsule\Manager as Capsule;

#1 
Capsule::schema()->create('naujienos', function($table)
{
	$table->increments('id');
	
	$table->string('title', 255)->default();
	$table->string('slug', 255)->default();
});

#2
$results = Capsule::table('news')->orderBy('created_at', 'desc')->get();

*/
