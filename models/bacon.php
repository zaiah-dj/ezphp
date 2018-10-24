<?php


$model = array( 
	"posts" => $g->dbexec( "select * from content where id in ( 73, 74 )" ) 
	//"rent" => $g->dbexec( "select * from content where id = 73" ) 
);


?>
