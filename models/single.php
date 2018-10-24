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
	"posts" => $g->dbfexec( "get_aggregated_content" ),
	"main" => $g->dbfexec( "get_title" ),

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
