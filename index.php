<?php
/* -------------------------------------------- *
 * ezphp
 * -----
 *
 * A lightweight MVC framework for PHP.
 *
 * Author
 * ======
 * Antonio R. Collins II (rc@tubularmodular.com)
 *
 * Summary
 * ======= 
 * ....
 *
 * -------------------------------------------- */
//Define errors up here... 

//Command line flags can go here too
if ( $argv && sizeof( $argv ) > 1 ) 
{
	$opts    = getopt( "u:h" );
	$helpMsg = "HELP!" .
		"-u, --url       Test with a specific url";

	switch ( $opts )
	{
		case 'u':
			break;	
		case 'h':
			break;	
	}
	die();
}


//Define a defualt name, this will change later...
$default_ns = "default";

//Do any requires here...
require ( "vendor/mustache.php" );

//Define a class for configuration

//Define a class for content (models, views, and content-types)
class Content 
{
	//Content buffer
	public $content = null;

	//List of mimetypes
	private $mimetypes = array(

	);

	//...
	private $file_to_mime = array(

	);

	//
	private $buffer = "";

	public function set_rendering_engine ( $engine ) {
		//can only set your available ones 
	}

	public function set_content_type ( $ctype ) {
		//can only set content types for your supported mimes 
	}
	
	//
	public function render ( $file, $table ) {
		//mustache
		$m->render( file_get_contents( $file, $table ) );
	} 
} 

//Define a class for handling routes
class Backend
{
	public  $content   = null;
	private $url       = null;
	private $routeList = null;
	private $files     = array(
		/*Source files for configuration*/
		"config.json",
		"routes.json"
	);

	public $config;
	public $routes;
	public $route;

	//A "global" database handle
	public $dbo;

	//All the statuses... all of 'em
	private $HTTP_STATI = array(
		100 => "Continue",
		101 => "Switching Protocols", 
		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		204 => "No Content",
		206 => "Partial Content",
		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		307 => "Temporary Redirect",
		400 => "Bad Request",
		401 => "Unauthorized",	
		403 => "Forbidden",			
		404 => "Not Found",				
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Timeout",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request URI Too Long",
		415 => "Unsupported Media Type",
		416 => "Requested Range",
		417 => "Expectation Failed",
		418 => "I'm a teapot",
		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Timeout"
	);


	/* -------------------------------------------- *
   * convert_s2a ( $query ) 
	 * 
	 * Convert queries to associative arrays for
   * easy templating with something like Mustache
	 * -------------------------------------------- */
	private function convert_s2a ( $query ) 
	{
		if ( !$query ) {
			echo "Database error.";
			return NULL;
		}

		//Define
		$cc = $query->columnCount();
		$names = array();
		$results = array();
		$i=0;

		//Get all the column names
		foreach ( $query as $qv ) {
			foreach ( $qv as $key => $value ) {
				if ( gettype( $key ) == "string" ) {
					$names[ $i++ ] = $key;
				}
			}

			for ( $ii = 0; $ii < $cc; $ii++ )
				$results[ 0 ][ $names[ $ii ] ] = $qv[ $names[ $ii ] ];
			break;
		}

		//Then save each row
		foreach ( $query as $ind => $row ) 
		{
			$ix = $ind + 1;
			$results[ $ix ] = array();
			for ( $i = 0; $i < $cc; $i++ ){ 
				//print $row[ $names[ $i ] ] . "\t";
				$results[ $ix ][ $names[ $i ] ] = $row[ $names[ $i ] ];
			}
		}

		return $results;
	}


	/* -------------------------------------------- *
	 * server_throw( status, message ) 
	 * 
	 * Throw new messages via a chosen source of
	 * transport
	 * -------------------------------------------- */
	public function server_throw ( $status, $msg, $obj=null ) {
		//clear everything that came before...
		$status_line = $this->HTTP_STATI[ $status ];
		include "std/error.php";
		die();
	}


	/* -------------------------------------------- *
	 * dbexec( query, ba )
	 * 
	 * Run inline SQL using bound arguments. 
	 * -------------------------------------------- */
	public function dbexec ( $query, $bindArgs = NULL ) 
	{
		if ( $this->config->debug ) {
			echo "<li>" . $this->config->dbfile. "</li>";
			echo "<li>" . $query . "</li>";
		}

		try {
			$newQuery = $this->pdo->query( $query );
		}
		catch (Exception $e) {
			return NULL;
		}
		return $this->convert_s2a( $newQuery );
	}


	/* -------------------------------------------- *
	 * dbfexec( file )
	 * 
	 * Run SQL from files using bound arguments. 
	 * -------------------------------------------- */
	public function dbfexec ( $file, $bindArgs = NULL ) 
	{
		$filename = "sql/" . $file . ".sql";
		if ( !stat( $filename ) ) {
			printf( "Failed to open file: %s",  $filename );
			return NULL;
		}

		$queryText = file_get_contents( $filename );
		if ( $this->config->debug ) {
			echo "<li>" . $this->config->dbfile . "</li>";
			echo "<li>" . $queryText . "</li>";
		}

		$newQuery = $this->pdo->query( $queryText );
		return $this->convert_s2a( $newQuery );
	}


	/* -------------------------------------------- *
	 * check_route ( routeName )
	 * 
	 * Check if route name exists, then check for model 
   * path, view path and special content types if any.
	 * -------------------------------------------- */
	public function check_route ( $routeName ) 
	{
		if ( !$routeName )
			return 0;

		return ( array_key_exists( $routeName, $this->routes) ); 
	}


	/* -------------------------------------------- *
	 * dump ( routeName )
	 * 
	 * Dump values in a human readable fashion (in a 
   * web browser or JSON).
   * (This really beats print_r and var_dump).
	 * -------------------------------------------- */
	public function dump ( $var, $className = NULL ) 
	{
		//if ( !$this->config->debug )
		if ( 0 )
			return;
		else {
			//if the type of var is something specific, I want to see formatted data
			$_type = gettype( $var );
			//echo "type of var is: $_type";
			printf( "\n<table class=\"%s\">\n", ( !$className ) ? "debug" : $className ); 
			switch ( $_type ) {
				case "boolean":
				case "integer":
				case "float":
				case "string":
					printf( "\t<tr>%s</tr>\n", $var );
					break;	
				case "array":
				case "object":
					foreach ( $var as $kk => $vv ) {
						$tt = gettype( $vv );
						if ( $tt != "array" && $tt != "object" )
							printf( "\t<tr>\n\t\t<td>%s</td>\n\t\t<td>%s</td>\n\t</tr>\n", $kk, $vv );
						else {
							printf( "\t<tr>\n\t\t<td>%s</td>\n\t\t<td>", $kk );
							$this->dump( $vv );
							printf ( "</td>\n\t</tr>\n" );
						}
					}
					break;	
				case "callable":
					break;	
				case "iterable":
					break;	
				case "resource":
					break;	
				case "null":
					break;	
				/*
				case mixed:
					break;	
				case number:
					break;	
				case callback:
					break;	
				case array|object:
					break;	
				case void:
					break;	
				*/
				default:
					echo "$_type";
			}
			printf( "\n</table>\n" );
		}
	}

	//prepare routes from the list

	//dump routes
	public function dump_all_routes ( ) 
	{
		$this->dump( $this->routes );
	}

	//Probably heavy on memory to run this everytime, so consider another way to
	//approach this
	function __construct() {
		//Load each json file and decode it, failing if errors are encountered.
		foreach ( $this->files as $cfg ) {
			//Check if the file exists
			if ( !stat( $cfg ) ) {
				printf("%s does not exist at our current directory.\n", $cfg) ;
				die();
			}

			//Then load it and put it whereever...
			$tmp = file_get_contents( $cfg );
			$tmp = utf8_encode( $tmp );
			$tmp = json_decode( $tmp );
			( $cfg == "config.json" ) ? $this->config = $tmp : $this->routeList = $tmp;
		}

		/*try yaml*/
		try {
			$this->config = yaml_parse_file( "config.yaml" );	
			$this->routeList = yaml_parse_file( "routes.yaml" );	
			//print_r( $this->config );
			$this->dump( $this->config );
			$this->dump( $this->routeList );
			foreach ( $this->config as $kk => $vv ) {
				echo $kk;
				echo $vv;
			}
			die();
		}
		catch (Exception $e) {
			$str = $e->getMessage();
			echo $str;	
			var_dump( $e );
			die();
		}
		/*stop*/
		

		$this->dump( $this->config );
		$this->dump( $this->routeList );
		$this->dump( gettype( $this->routeList ) );
		die();

		//TODO: check for zero-length route list?
		for ( $i = 0; $i < sizeof( $this->routeList ); ++$i ) {
			//TODO: Handle other non-existent routes and "details"
			//No route means fatal error
			/*if ( array_key_exists( route, $config[ $i ] ) ) {
				throw new Exception( "No route specified at line x...\n" );
			}*/

			//strings, functions and arrays ought to be allowed
			$mtype = gettype( $this->routeList[ $i ]->model );
			$vtype = gettype( $this->routeList[ $i ]->view );

			//Convert models to an array
//$this->dump( $this->routeList[ $i ]->model );die();

			$this->routes[ $this->routeList[$i]->route ][ "model" ] = ( $mtype != 'array' ) ?
				[ $this->routeList[ $i ]->model ] : $this->routeList[ $i ]->model; 
		
			//Convert views to an array	
			$this->routes[ $this->routeList[$i]->route ][ "view" ] = ( $vtype != 'array' ) ?
				[ $this->routeList[ $i ]->view ] : $this->routeList[ $i ]->view; 

			//Check for content type changes
			$this->routes[ $this->routeList[ $i ]->route ][ "ctype" ] = null;
		}

		//Dump the classes to make sure that it looks good.
		//$this->dump ( $this->routes );

		//Finally, break up the URL for our super simple routing mechanism.
		//parse URL - only answer the first part
		$this->url = parse_url( $_SERVER[ "REQUEST_URI" ] );
		$this->url = explode( "/", $this->url["path"] );
		$this->route = (sizeof($this->url) > 1) ? $this->url[1] : null; 

		//If the backend is sqlite, check that it exists
		if ( $this->config->backend == sqlite3) {
			if ( !stat( $this->config->dbfile ) )
			{
				$this->dump( "DATABASE FILE NOT FOUND: " . $this->config->dbfile);  
				$this->pdo = null;
				return;	
			}
		}
		else {
			0;
		}

		//Open the database here too	
		try {
			//$newQuery = $this->pdo->query( $query );
			$this->pdo = new PDO( "sqlite:" . $this->config->dbfile );
		}
		catch (Exception $e) {
			var_dump( $e );
			return;
		}
		//$this->pdo = new PDO( "sqlite:" . $this->config->dbfile );
	}	
}


//Initialize some objects
$m = new Mustache_Engine;
$g = new Backend;
$c = new Content;
$route = null;


//TODO: flexible default
//Serve your default route if there was no url or if the route is not supposed to be answered
if ( !$g->route )
	{ $g->model = [ "default" ]; $g->view = [ "default" ]; $g->ctype = "text/html"; }
//If there was a URL, but we're not supposed to service it, serve a 404
else if ( !$g->check_route( $g->route ) ) {
	$g->server_throw( 404, "route exists, page not found..." );
}
//Looks like a URL exists, and we're supposed to do some work.
else {
	//Answer to tests
	if ( $g->route == "test" ) 
		{ $g->model = ["mock"]; $g->view = ["mock"]; $g->ctype = "text/html"; }
	else {
		//TODO: catch bad evaluation
		//$g->dump("other route requested.<br />");

		//TODO: function arr eval
		//Check for requested model, if it doesn't exist, default might be what you want
		//$modelFile = ( array_key_exists ( "model", $g->routes [ $g->route ]) )
		$g->model = ( array_key_exists ( "model", $g->routes [ $g->route ]) )
			? $g->routes [ $g->route ][ "model" ] : [ $default_ns ] ; 

		//Same thing with views
		//$viewFile = ( array_key_exists ( "view", $g->routes [ $g->route ] ) )
		$g->view = ( array_key_exists ( "view", $g->routes [ $g->route ] ) )
			? $g->routes [ $g->route ][ "view" ] : [ $default_ns ] ;

		//...and content-type	
		//TODO: alternate content type
		$g->ctype = "text/html";
	}
}

if ( 1 ) {
	//This is an example of what I'd like to see in a debug window
	$vv = array( 
		model => $g->model
	 ,view  => $g->view
	 ,route => $g->route
	);

	$g->dump( $vv );
	$g->dump_all_routes();	
	die();
}


//Check and make sure that model files all exist and work
foreach ( $g->model as $mfile ) {
	try {
		//What is this value
		$type = gettype( $mfile );
		
		//Execute what's asked
		if ( $type == 'function' ) 
			$mfile( );

		//Or check for file 
		else if ( $type == 'string' ) {
			//Make a full file name.
			$fname = "models/{$mfile}.php";
			( !stat( $fname ) ) ? $g->server_throw( 500, "model file: $fname doesn't exist." ) : 0;
			( 1 ) ? include ( $fname ) : 0; 
		}
	
		//If debug is on, I want to see all of this crap...
		//( 0 ) ? $g->dump($model) : 0;
	}
	catch ( Exception $e ) {
		$str = $e->getMessage();
		$strstr = "Couldn't handle all the awesomeness that is in $mfile: $str"; 
		server_throw( 500, $strstr );
	}
}


//Check for the view file and load it (or something along those lines)
foreach ( $g->view as $vfile ) {
	//For right now (as of the next 2 hours), views are just files in mustache...
	try {
		if ( $g->ctype == "text/html" ) {
			$fname = "views/{$vfile}.mustache";
			( !stat( $fname ) ) ? server_throw( 500, "view file: $fname doesn't exist." ) : 0;
			echo $m->render( file_get_contents( $fname ), $model );
		}
		/*
		else if ( ... ) {
			//xml, json, and whatnot ought to be able to kind of short-circuit in some
			//cases... how do I get regular files?
		}
		*/
		else {
			//mmm, this seems wrong...
			echo file_get_contents( $vfile );
		}
	}
	catch ( Exception $ev ) {
		$str = $e->getMessage();
		$strstr = "Couldn't accurately represent the awesomeness that is in $vfile: $str"; 
		server_throw( 500, $strstr );
	}
}


//Add a stub for simple debugging.
if ( $g->config->debug ) {
	echo "<link rel=stylesheet href=\"std/debug.css\">";
	echo "<script src=\"std/debug.js\">";
}

?>
