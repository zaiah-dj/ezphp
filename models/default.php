<?php

$model = array( 
	"hello" => "Hello, World",

	/*Open graph stuff*/
	"og_url"        => $g->config->url,
	"og_type"       => "text/html",
	"og_title"      => $g->config->title,
	"og_description"=> $g->config->description,
	"og_image"      => $g->config->image,

	/*Queries*/
	"posts" => $g->dbfexec( "content_by_post" ),

	/*Links*/
	"atags" => array(
		array( 
			"href" => "juice",
			"alt" => "juice",
			"text" => "juice"
		)
	),

	/*Details*/
	"logo-text"     => $g->config->title
);

?>
