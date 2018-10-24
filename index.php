<?php
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


//Do any requires here...
require ( "vendor/mustache.php" );

//Define a class for handling routes
class Backend
{
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
	private $dbo;

	//Run this when dong certain things...
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


	//Do anything database related with files
	public function dbexec ( $query ) 
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


	//Do anything database related with files
	public function dbfexec ( $file ) 
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


	//Check if route name exists, then check for model path, view path and special content types if any.
	public function check_route ( $routeName ) 
	{
		if ( !$routeName )
			return 0;

		return ( array_key_exists( $routeName, $this->routes) ); 
	}

	//Dump values in a human readable fashion (in a web browser or JSON) 
	public function dump ( $var, $className = NULL ) 
	{
		if ( !$this->config->debug )
			return;
		else {
			//if the type of var is something specific, I want to see formatted data
			$_type = gettype( $var );
			echo "type of var is: $_type";
			printf( "\n<table class=\"%s\">\n", ( !$className ) ? "debug" : $className ); 
			switch ( $_type ) {
				case "boolean":
					break;	
				case "integer":
					break;	
				case "float":
					break;	
				case "string":
					break;	
				case "array":
					foreach ( $var as $kk => $vv ) {
						if ( gettype( $vv ) != "array" )
							printf( "\t<tr>\n\t\t<td>%s</td>\n\t\t<td>%s</td>\n\t</tr>\n", $kk, $vv );
						else {
							printf( "\t<tr>\n\t\t<td>%s</td>\n\t\t<td>", $kk );
							$this->dump( $vv );
							printf ( "</td>\n\t</tr>\n" );
						}
					}
					break;	
				case "object":
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

	//Probably heavy on memory to run this everytime, so consider another way to
	//approach this
	function __construct()
	{
		//Load each json file and decode it, failing if errors are encountered.
		foreach ( $this->files as $cfg )
		{
			//Check if the file exists
			if ( !stat( $cfg ) )
			{
				printf("%s does not exist at our current directory.\n", $cfg) ;
				die();
			}

			//Then load it and put it whereever...
			$tmp = file_get_contents( $cfg );
			$tmp = utf8_encode( $tmp );
			$tmp = json_decode( $tmp );
			( $cfg == "config.json" ) ? $this->config = $tmp : $this->routeList = $tmp;
		}

		//TODO: zero-length model 
		//check that this->routelist (routes.json) loaded correctly,
		//for example, this should not be a zero-length array

		//Turn the routes into an array for quick lookups
		for ( $i = 0; $i < sizeof( $this->routeList ); ++$i )
		{
			//No route means fatal error
			/*if ( array_key_exists( route, $config[ $i ] ) ) {
				throw new Exception( "No route specified at line x...\n" );
			}*/

			//No model or view means hit default
			$this->routes[ $this->routeList[$i]->route ][ "model" ] = $this->routeList[ $i ]->model; 
			$this->routes[ $this->routeList[$i]->route ][ "view" ]  = $this->routeList[ $i ]->view; 
		
			//Check for content type changes
			$this->routes[ $this->routeList[ $i ]->route ][ "ctype" ] = null;
		}

		//Dump the classes to make sure that it looks good.
		//var_dump ( $this->routes );

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
$route = null;


//Serve your default route if there was no url or if the route is not supposed to be answered
//TODO: flexible default
if ( !$g->route ) {
	$g->dump("route does not exist");
	$g->model = "models/default.php";
	$g->view  = "views/default.mustache";
	$g->ctype = "text/html";
}
//If there was a URL, but we're not supposed to service it, serve a 404
else if ( !$g->check_route( $g->route ) ) {
	$g->dump("route exists, page not found");
	$g->model = "std/404.php";
	$g->view  = "std/404.mustache";
	$g->ctype = "text/html";
}
//Looks like a URL exists, and we're supposed to do some work.
else {
	//Answer to tests
	if ( $g->route == "test" ) {
		$g->dump("route 'test' requested!");
		//$g->dump($model);
		$g->model = "models/mock.php";
		$g->view  = "views/mock.mustache";
		$g->ctype = "text/html";
	}
	else {
		//TODO: catch bad evaluation
		$g->dump("other route requested.<br />");

		//TODO: function arr eval
		//Check for requested model, if it doesn't exist, default might be what you want
		$modelFile = ( array_key_exists ( "model", $g->routes [ $g->route ]) )
			? $g->routes [ $g->route ][ "model" ] : "default"; 

		//Same thing with views
		$viewFile = ( array_key_exists ( "view", $g->routes [ $g->route ] ) )
			? $g->routes [ $g->route ][ "view" ] : "default"; 
			
		//...and content-type	
		//$contentType = ...
		//TODO: alternate content type
		$g->ctype = "text/html";
		$g->model = "models/" . $modelFile . ".php"; 
		$g->view  = "views/" . $viewFile . ".mustache";

		if ( $g->config->debug ) 
		{
			echo "Serving model: ". $g->model . "<br />" ;
			echo "Serving view:  ". $g->view  . "<br />";
		}	
	}
}


//One final check, make sure files exist (if we get here, that's bad...)
//TODO: if $g->model is an array, obviously loop
if ( !stat( $g->model ) )
{
	//Model file doesn't exist.
	//if ( !stat( "std/404.php" ) )
	echo "<h2>500 Internal Server Error</h2>" . 
			 "<p>Model file for resource not found.</p>"
	;
	die();
}

//If there was a content type, make sure this exists too.
//TODO: if $g->view is an array, obviously loop
if ( !stat( $g->view ) )
{
	//View file doesn't exist
	echo "<h2>500 Internal Server Error</h2>" . 
			 "<p>View file for resource not found.</p>"
	;
	die();
}


//You made it to the end with no serious errors.
//TODO: loop again, convert to an array
include ( $g->model );
$g->dump($model);


//???
if ( $g->ctype == "text/html" )
	echo $m->render( file_get_contents( $g->view ), $model );
else {
	//force special content types...
	echo file_get_contents( $g->view );
}

if ( $g->config->debug ) {
	echo "<link rel=stylesheet href=\"std/debug.css\">";
	echo "<script src=\"std/debug.js\">";
}

?>
