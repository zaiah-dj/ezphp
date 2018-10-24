<?php

class Auth 
{
	public $user;

	public $authenticated;
	public $badParameter;

	private $role = array();

	public function loggedIn( ) 
	{
		//Check if session.logged_in is true
		return (array_key_exists( "logged_in", $_SESSION ) && $_SESSION[ "logged_in" ] == "true");
	}

	//Login user name
	public function login_user_name ( $username, $password ) 
	{
		//Handle hashing the password
		$passIt = "hello";
		print_r( $_SESSION );
		return ( $_SESSION[ "logged_in" ] = ( $password == $passIt ) ? true : false );
	}

	public function logout ()
	{
		//Set variables here to save time... I guess...
		$this->authenticated = false;

		//Destroy session
		session_destroy();

		//Get rid of whatever it is in the database too
	}
}




//An admin class for dealing with updates.
class Admin 
{
	public $nothing;

	private $disqus_id;

	private $oauth = array (
		"facebook" => "",
		"twitter" => "",
		"flickr" => ""
	);

	//Save some kind of content
	public function saveContent () {
		return 0;
	}

	//It's gonna be a battle to save parts, but I can probably get it together
}



//Start the session
session_start();


//Initialize an auth object
$authy = new Auth;


//If we're not logged in at all that's gonna change stuff
if ( $g->route == "logout" )
	$authy->logout();
else if ( $g->route == "login" )
{ 
	if ( $authy->loggedIn() )
		$authy->authenticated = true;
	else 
	{
		if ( array_key_exists( "pwd", $_POST ) && array_key_exists( "un", $_POST ) )
		{
			//Check if the user is part of a supported list
			if ( !$authy->login_user_name( $_POST["un"], $_POST["pwd"] ) )
			{
				//Then drop out
				$authy->badParameter  = false;
				$authy->authenticated = true;
			//$authy->username      = true;
			}
			else {
				$authy->badParameter  = true;
				$authy->authenticated = false;
			}
		}
		else {
			$authy->badParameter = false;
			$authy->authenticated = false;
			//$authy->username      = true;
		}
	}
}
else if ( $g->route == "admin" )
{
	if ( $authy->loggedIn() )
		$authy->authenticated = true;
	else 
	{
		$authy->badParameter = false;
		$authy->authenticated = false;
	}
}



/*...*/
$model = array (

	//Is someone logged in?
	"authenticated" => $authy->authenticated,

	//Who are you?
	"author" => "Antonio R. Collins II",

	//Let me see my photos and galleries
	//photo_Query => "select * from something"

	//Let me see my posts

	//Let me see my random ID's (disqus, site name )

	//Let me save content (incrementally if possible)

	//Let me save media

	//Let me edit comments?

	//Heading
	"heading"  => "Admin View",

	//Controls
	"controls" => array(
		//Let's break these up by class and id?	
		array(
			array( "key"     =>  "posts" ),
			array( "value"     =>  "/admin?action=posts" )
		),
		array(
			array( "key"     =>  "comments" ),
			array( "value"  =>  "/admin?action=comments" )
		),
		array(
			array( "key"     =>  "media" ),
			array( "value"     =>  "/admin?action=media" )
		)	
	),

	//...
	"fresh" => "black rob",

	"admin" => "bleu",

	"posts" => $g->dbfexec( "content_by_post" ),

	"list"  => array( 
		"choosy" => "pigeon"
	)
);


/*Mocks help initialize cheap shit really quickly*/
/*In this case, a Mock instance would specify an inmem db, and a couple of queries to start us off*/

?>
