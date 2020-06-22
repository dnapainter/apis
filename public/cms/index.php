<?php include("../../../config.inc.php");

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$errorstring = '';
// get params: chr, start, end, build (can update later so can take an array of items)
$chr = false;
$start = false;
$end = false;

if(isset($_GET['chr'])){ $chr = stripString($_GET['chr']); }
if(isset($_GET['start'])){ $start = stripString($_GET['start']);  }
if(isset($_GET['end'])){ $end = stripString($_GET['end']);  }


if($chr=='x' || $chr=='X'){
    $chr = 23;
}
// error checking - for chromosome
if(!is_numeric($chr) || $chr < 1 || $chr > 23){
	$errorstring .= 'Chromosome is not valid\n';
}
if(!is_numeric($start)){
    $errorstring .= 'Start position is not valid\n';
}
if(!is_numeric($end)){
    $errorstring .= 'End position is not valid\n';
}


// TODO error checking - physical position
// might want to put this in the db


$startcM = getMapPos($chr, $start, $conn);
$endcM = getMapPos($chr, $end, $conn);

if($startcM !==false && $endcM !==false && ($endcM > $startcM)){ // false needed as could be zero


    if($chr==23){
        $chr = 'X';
    }
  $mydata = array("chr" => $chr, "start"=> $start, "end"=> $end, "cm" => number_format(($endcM - $startcM),1));
  header('Content-type:application/json;charset=utf-8');
  echo json_encode($mydata);
  die;
}
else {
    $errorstring .="problem";
}

if($errorstring !=''){
    $mydata = array("error" => $errorstring);
    echo json_encode($mydata);
    die;
}

/*
need to cover instances where
- start is below the first value
- bullseye situation where they are the same
- all done, but at the moment you could put 99 billion as the upper bound and it wouldn't mind.
- not sure if this is a problem or not
*/

function stripString($str){
    $str = str_replace(",","", $str);
    return trim($str);
}

function getMapPos($chr, $physPos, $conn){
	$sql= "(SELECT * FROM genetmap
			WHERE chr = ".mysqli_real_escape_string($conn, $chr)."
			AND pos >= ".mysqli_real_escape_string($conn,$physPos)."
			ORDER BY pos asc
			LIMIT 1
		)
		UNION
        (
        SELECT * FROM genetmap
            WHERE chr = ".mysqli_real_escape_string($conn,$chr)."
            AND pos < ".mysqli_real_escape_string($conn,$physPos)."
            ORDER BY pos desc
            LIMIT 1
        )
		ORDER BY pos;";
    $result = $conn->query($sql);
	if ($result->num_rows == 0) { return false; }
	$count = 0;


  	while($row = $result->fetch_assoc()) {

        if($count == 0){
    		$lowerpos = $row["pos"];
            $lowercm = $row["cm"];
            $higherpos = $row["pos"];
            $highercm = $row["cm"];            
    	}
    	else {
    	    $higherpos = $row["pos"];
            $highercm = $row["cm"];            
        }
        $count++;
    }
   /*
    echo  '<br />count after loop: ';
    echo $count;
    echo '<br />lowerpos:'. $lowerpos;
    echo '<br />higherpos:'. $higherpos;
    echo '<br />lowercm:'. $lowercm;
    echo '<br />highercm:'. $highercm;
    echo '<br />physPos:'. $physPos;
    */
    $ratio = ($physPos - $lowerpos)/($higherpos-$lowerpos);
    if($higherpos==$lowerpos){
        $ratio = 1;
    }
    //    echo '<br />ratio:'. $ratio;

    $geneticPos = $lowercm + ($ratio * ($highercm - $lowercm));
  
    return $geneticPos;
}

// interpolate between that and the actual value passed

$conn->close();
?>
