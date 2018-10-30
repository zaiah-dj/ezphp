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


//Can this just be a global function here, since everybody needs it
/* -------------------------------------------- *
 * dump ( routeName )
 * 
 * Dump values in a human readable fashion (in a 
 * web browser or JSON).
 * (This really beats print_r and var_dump).
 * -------------------------------------------- */
function ez_dump ( $var, $html = 0, $className = NULL ) 
{
	$tab = ( $html && gettype($html) == 'number' ) ? html_tdata() : NULL;
	//( $prefix ) ? printf( $prefix ) : 0;

	if ( gettype( $html ) == 'string' ) {
		printf( "%s:\n", $html );
	}

	//if ( !$this->config->debug )
	if ( 0 )
		return;
	else if ( !$html || gettype( $html ) == 'string' ) 
		print_r( $var );
	else {
		//if the type of var is something specific, I want to see formatted data
		//printf( $tab[ "start" ](), ( !$className ) ? "debug": $className ); 

		if ( ($_type = gettype($var)) != "array" && $type != "object" )
			printf( "\t" . $tab[ $_type ]() . "\n", $var );
		else {
			foreach ( $var as $kk => $vv ) {
				$tt = gettype( $vv );
				if ( $tt != "array" && $tt != "object" )
					printf( "\t<tr>\n\t\t<td>%s</td>\n\t\t<td>%s</td>\n\t</tr>\n", $kk,$vv );
				else {
					printf( "\t<tr>\n\t\t<td>%s</td>\n\t\t<td>", $kk );
					$this->dump( $vv );
					printf ( "</td>\n\t</tr>\n" );
				}
			}
		}
		//printf( $tab[ "end" ]() );
	} 
}

//Define a defualt name, this will change later...
$default_ns = "default";

//Do any requires here...
require ( "vendor/mustache.php" );

//Handle things that are really utility functions
class Util
{
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

	//HTML data
	private function html_tdata () { 
		return array( 
			"boolean" => function( ) { return "<tr>%s</tr>";  }
		 ,"integer" => function( ) { return "<tr>%d</tr>"; }
		 ,"float" => function ( ) { return "<tr>%f</tr>"; }
		 ,"string" => function( ) { return "<tr>%s</tr>"; }
		 ,"array" => function( ) { return "<tr>%s</tr>"; }
		 ,"object" => function( ) { return "<tr>%s</tr>"; }

			//Do I even use these?
		 ,"callable" => function() { return "<tr>%s</tr>"; }
		 ,"iterable" => function() { return "<tr>%s</tr>"; }
		 ,"mixed" => function() { return "<tr>%s</tr>"; }
		 ,"number" => function() { return "<tr>%s</tr>"; }

		 ,"end" => function( ) { return "</table>"; }
		 ,"start" => function( ) { return "\n<table class=\"%s\">\n"; }
		);
	}

}

//Define a class for handling routes
//x TODO: Split out utility functions into a class called 'Util'
//TODO: Split out http related things into a class called 'HTTP'
//TODO: Split out db related things into a class called 'Db'
class Backend
{
	private $url       = null;
	private $routeList = null;
	public  $config;

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
	 * server_throw( status, message ) 
	 * 
	 * Throw new messages via a chosen source of
	 * transport
	 * -------------------------------------------- */
	public function server_throw ( $status, $msg, $obj=null ) {
		//clear everything that came before...
		$status_line = $this->HTTP_STATI[ $status ];

echo "<h2>youoyoyoyousadpoifudsaoifjdsa;kfjdsalkfjdsj</h2>";
echo "<p>
aasdfsadfdsafsa
dsafdsafdsaf
sadfdsafd
sadfdsaf
sadfdsaf
</p>";
		include "std/error.php";
		die();
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

	private function dump_backend() {
		$this->dump( $this->config );
		$this->dump( $this->routeList );
		$this->dump( $this->url );
		$this->dump( $this->urlpath );
	}

	//dump the thing
	public function dump ( $html = 0 ) {
		ez_dump( $this->config );
	}

	//Probably heavy on memory to run this everytime, so consider another way to
	//approach this
	function __construct( $file ) {
		//Load each needed configuration file and decode it, failing if errors are encountered.
		try {
			$this->config = yaml_parse_file( $file );	
		}
		catch (Exception $e) {
			$str = $e->getMessage();
			echo $str;	
			var_dump( $e );
			die();
		}


		//If the backend is sqlite, check that it exists
		if ( $this->config[ "dbbackend" ] == "sqlite3") {
			if ( !stat( $this->config[ "dbfile" ] ) ) {
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
			$this->pdo = new PDO( "sqlite:" . $this->config[ "dbfile" ] );
		}
		catch (Exception $e) {
			var_dump( $e );
			return;
		}
	}	
}

//a global error printer...
function return_err( $fn_name = "anon", $table, $code, $args = NULL ) {
	//print the error if requested
	printf( "%s: %s", $fn_name,  $table[ $code ] );
	return $code; 
}


//Initialize a route
class Route
{
	//list of static private errors
	const ERR_NONE = 0;
	const ERR_TOP_LEVEL_MATCH = 1;
	const ERR_INN_LEVEL_MATCH = 2;
	private $errors = array( 		
	  self::ERR_NONE            => "No errors."
	 ,self::ERR_TOP_LEVEL_MATCH => "top-level element didn't match type: %s"
	 ,self::ERR_INN_LEVEL_MATCH => "inner-level element at index %d didn't match type"	
	);

	//all properties used to handle things later
	private $ctype = "text/html";  //default to text/html
	private $models = array();
	private $views = array();
	private $parameters = array();
	private $methods = array();
	private $throw = array();
	private $pres = array();
	private $posts = array();
	
	//routing list from yaml should go here
	private $routes;
	
	//the route object that will be queried for things	
	private $route;

	//finally, save URL parsing and data too
	private $url;
	private $urlpath;

	//still need getters...
	public function getmodels () { return $this->models; }
	public function getviews () { return $this->views; }

	//An error reporting function
	private function err ( $code, $args = NULL ) {
		return return_err( "route: funct()", $this->errors, $code, $args ); 
	}

	//append a certain type to whatever element we're dealing with
	private function append_element( $val, & $dstarr, $types = ["array","object","string"]) {
		$fn = "append_element";
		$ft = NULL;
		$vt = strtolower( gettype( $val ) ); 
		//to make ! work, just add a 0 to the beginning of the $types array
		array_unshift( $types, 0 ); //array_unshift( $types, "" );

		//check the top-most element	
		if ( !array_search( $vt, $types, true ) ) {
			//printf( "%s: %s\n", $fn, "top-level element didn't match type" );
			return $this->err( self::ERR_TOP_LEVEL_MATCH );
		}

		//echo "\nval: "; var_dump( $vt ); var_dump( $val );

		//then check each element and add it
		if ( !array_search( $vt, [0,"array", "object"], true ) )
			$dstarr[] = $val;
		else {
			for ($i=0; $i<sizeof( $val ); $i++ ) {
				$ivt = gettype( $val[ $i ] );
				//var_dump( $ivt );var_dump( $val[ $i ] );
				if ( !array_search( $ivt, $types, true ) ) return $this->err( self::ERR_INN_LEVEL_MATCH );
				$dstarr[] = $val[ $i ];
			}
		}

		//return 0 for success here
		//echo "dstarr: ";var_dump( $dstarr );
		return 0;
	}

	//hmm... should this big mess go here?
	private function execs() {
		//TODO: Support plural nouns in YAML files (is this needed?)
		return array(
			"model" => function ( $v ) {
				return $this->append_element( $v, $this->models ); 
			}

		, "view" => function( $v ) {
				return $this->append_element( $v, $this->views );
			}

		, "content-type" => function( $v ) {
				//TODO: check against all content types and make sure this is on the list
				$this->ctype = $v;
				return 1;
			}

		, "parameter" => function( $v ) {
				return $this->append_element( $v, $this->parameters );
			}

		, "method" => function( $v ) {
				return $this->append_element( $v, $this->methods, [ "string" ] );
			}

		, "throw" => function( $v ) {
				return $this->append_element( $v, $this->throw, [ "function" ] );
			}

		, "pre" => function( $v ) {
				return $this->append_element( $v, $this->pres, [ "function", "string" ] );
			}

		, "post" => function( $v ) {
				return $this->append_element( $v, $this->posts, [ "function", "string" ] );
			}
		);
	}

	//dump
	public function dump() {
		ez_dump( $this->models, "models" );
		ez_dump( $this->views, "views" );
		ez_dump( $this->ctype, "content type" );
		ez_dump( $this->parameters, "parameters" );
		ez_dump( $this->methods, "methods" );
		ez_dump( $this->throw, "throw" );
		ez_dump( $this->pres, "pre" );
		ez_dump( $this->posts, "post" );
	}

	//the path to the file and any tests are tossed in here via $scope
	function __construct( $file, $scope = "_SERVER" ) {
		//parse the routing table
		try {
			$this->routes = yaml_parse_file( $file );	
		}
		catch (Exception $e) {
			$str = $e->getMessage();
			echo $str;	
			var_dump( $e );
			die();
		}
	
		//Finally, break up the URL for our super simple routing mechanism.
		//parse URL - only answer the first part
		if ( 0 ) {
			$this->url = parse_url( $_SERVER[ "REQUEST_URI" ] );
			$this->urlpath = isset( $this->url[ "path" ] ) ? explode( "/", $this->url["path"] ) : NULL;
		}
		else {
			$test_urls = array(
				//root
				 "http://ramarcollins.com"
				,"http://ramarcollins.com/dualmodel/"
				//single level terminating node
				,"http://ramarcollins.com/bacon"
				//single level node
				,"http://ramarcollins.com/api"
				//two level node with modifier
				,"http://ramarcollins.com/api/costs/12321321"
				//two level node with query string 
				,"http://ramarcollins.com/api/api?query=boom"
				//four level node with query string 
				,"http://ramarcollins.com/api/example2/example3/example4"
				//form submitted via GET
				,"http://ramarcollins.com/api/"
			);

			$this->url = parse_url( $test_urls[ 1 ] ); 
			$this->urlpath = isset( $this->url[ "path" ] ) ? explode( "/", $this->url["path"] ) : NULL;
		}

		//check if the routes keyword was used (this can make things clear)
		//$r = array_key_exists( "routes", $this->routes ) ? $this->routes["routes"]
		//: $this->routes;

		//move through all the URL path
		if ( $this->urlpath ) {
			//check the
			$_r = $this->routes[ "routes" ];
			$_x = $this->execs();

			//loop through the urlpath and find things
			for ( $i=1; $i < sizeof( $this->urlpath ); $i++ ) {
				$_s = $this->urlpath[ $i ];
				//stub exists
				if ( array_key_exists( $_s, $_r ) ) {
					//reset the routing object
					$_r = $_r[ $_s ];
					$any_set = [];

					//what was found? (verbose debugging only)
					//printf( "route stub '%s' found\n", $_s );

					//Loop through all the different keys and add some stuff
					foreach ( $_x as $xx => $yy ) {
						//check that the key exists, and if so, use the object to tell what
						//to do
						//printf( "\trunning closure for: '%-13s', ", $xx, gettype( $yy ) );
					 	$ns = ( array_key_exists( $xx, $_r ) ) ? $yy( $_r[ $xx ] ) : -1;
						//printf( "%2d - %s\n", $ns, ($ns == -1) ? "not present":"" );
								
						//handle errors
						if ( $ns == 0 )
							$any_set[] = $xx;
						else if ( $ns == -1 )
							continue;
						else if ( $ns ) {
							//TODO: Tell me exactly why some key failed
							printf( "route '%s' failed b/c of some reason...\n", $_s );
							return false;
						}	
					}

					//If any_set is blank, I didn't find any keys (callback or throw may
					//exist here)
					if ( !sizeof( $any_set ) ) {
						printf( "none found for any_set\n" );
						//If I'm at the end of my chain, this is most definitely an error,
						// ERR_NO_ROUTE_CONTENT
						//If I'm not at the end of my chain, I might be at a symbolic route
						// e.g. api/china and I'm currently evaluating 'api' 
					}
					else if ( sizeof( $any_set ) == 1 && array_search($any_set, "throw") ) {
						//If I just picked up an error, do something with that
					}
				}
			}
		}

		//Should I just array map on all of these here?
		$this->models = array_map( function ( $v ) { return "models/$v.php"; } , $this->models );
		$this->views = array_map( function ( $v ) { return "views/$v.mustache"; } , $this->views );
		//$this->dump();	
		//die();
	}	
}


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



//Initialize some objects
$m = new Mustache_Engine;
$g = new Backend( "config.yaml" ); //should take config.yaml as an argument
$r = new Route( "routes.yaml" ); //should take routes.yaml as an argument
$c = new Content; //not going to take anything as an argument so far
//$d = new DB; //should take db.yaml as an argument, if this is done
$route = null;


//you rollin'
//$g->dump( );$r->dump( );die();



//Check and make sure that model files all exist and work
foreach ( $r->getmodels() as $mfile ) {
	try {
		//What is this value
		$type = gettype( $mfile );
		
		//Execute what's asked
		if ( $type == 'function' ) { 
			$mfile( );
		}
		//Or check for file 
		else if ( $type == 'string' ) {
			//Make a full file name.
			( !stat( $mfile ) ) ? $g->server_throw( 500, "model file: $mfile doesn't exist." ) : 0;
			//why does this not work?
			include ( $mfile ); 
		}
		//If debug is on, I want to see all of this crap...
		//( 0 ) ? $g->dump($model) : 0;
	}
	catch ( Exception $e ) {
		$str = $e->getMessage();
		$strstr = "Couldn't handle all the awesomeness that is in $mfile: $str"; 
		$g->server_throw( 500, $strstr );
	}
}


echo "end prog.\n"; die();
//Check for the view file and load it (or something along those lines)
foreach ( $r->getviews() as $vfile ) {
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
