<?php
//Convert a query to a multi level array 
function convert_to_d2array ( $query ) 
{
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
		break;
	}

	/*for ( $i = 0; $i < $cc; ++$i )
		echo $names[ $i ];*/

	//Then save each row
	foreach ( $query as $ind => $row ) 
	{
		$results[ $ind ] = array();
		for ( $i = 0; $i < $cc; ++$i ){ 
			//print $row[ $names[ $i ] ] . "\t";
			$results[ $ind ][ $names[ $i ] ] = $row[ $names[ $i ] ];
		}
	}

	return $results;
}



class Mock
{
	/*PUBLIC PROPERTIES*/
	/*A text*/
	public $dtext  = "Some text";

	/*A number*/
	public $dint   = 1023219;

	/*A float */
	public $dfloat = 328293.99;

	/*A bool */
	public $dbool  = true;

	/*An indexed array */
	public $diarray= array("Volvo", "BMW", "Toyota");

	/*An indexed array */
	public $daarray= array(
		"swedish"  => "Volvo", 
		"japanese" => "Toyoto", 
		"german"   => "BMW"
	);


	public $d2array= array(
		array( 
			"anime"  => "Ghost In the Shell", 
			"author" => "Masamune Shirow", 
		),
		array( 
			"anime"  => "Yu yu Hakusho", 
			"author" => "Yoshihiro Togashi", 
		),
		array( 
			"anime"  => "Dragonball Z", 
			"author" => "Akira Toriyama", 
		),
		array( 
			"anime"  => "Gundam Wing", 
			"author" => "Katsuyuki Sumizawa", 
		),
		array( 
			"anime"  => "1999", 
			"author" => "X", 
		),
	);

	/*An object*/
	public $dobject= null;

	/*A null*/
	public $dnull  = null;

	/*A resource */
	public $drsrc  = null;



	/*PRIVATE QUERIES*/
	/*Our test query will live here.*/
	private $qCreate = "CREATE TABLE space ( 
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		bar varchar(100),
		name varchar(100)
	)";

	private $qInsert = "" .
		"INSERT INTO space VALUES ( NULL, 'Jolly men walk about the bank.', 'Antonio Collins' );" .	
		"INSERT INTO space VALUES ( NULL, 'Jolly men walk about the bank.', 'Antonio Collins' );" .	
		"INSERT INTO space VALUES ( NULL, 'Jolly men walk about the bank.', 'Antonio Collins' );" .	
		"INSERT INTO space VALUES ( NULL, 'Jolly men walk about the bank.', 'Antonio Collins' );" .	
		"INSERT INTO space VALUES ( NULL, 'Jolly men walk about the bank.', 'Antonio Collins' );"
	;

	public $qSelect = "SELECT * FROM space";

	public $db      = null;
	
	/*PUBLIC METHODS*/



	/*Initialize other items*/
	function __construct()
	{
		try {
			$this->db = new PDO( "sqlite::memory:" );
			$this->db->exec( $this->qCreate );	
			$this->db->exec( $this->qInsert );	
		}
		catch (PDOException $e)
		{
			print "Error in db: " . $e->getMessage() . "<br />";
			die();
		}

		//For a resource (or query) test, use an in-memory database
		//var_dump( $dobject );
		/*
		*/
	}

	/*Looks like you need a function to initialize all of this*/
}



$model = new Mock;
$trapper = $model->db->query( $model->qSelect ); 
$model->drsrc = convert_to_d2array ( $trapper );

//Not sure how to get lambdas working yet, so
//I'll just convert queries to arrays of arrays
?>
