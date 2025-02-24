<?php include("../../../config.inc.php");

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$errorstring = '';

// accept a string called qs. I guess a get
// but it could be long so no a post.
// return adjusted dimension




$qs = '';
if(isset($_POST['qs'])){ 
    $qs = trim($_POST['qs']); 
}


if($qs ==''){
    $errorstring .="no query string";

}

if($errorstring==''){
$field_to_use = 'country';
if(isset($_POST['variant'])){
    if($_POST['variant']=='latlng'){
        $field_to_use = 'latlng';
    }
}

$allowed_domains = ["http://dpnew.localhost","https://sparkly.dnapainter.com","https://dnapainter.com", "https://shiny.dnapainter.com"];

if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_domains)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}


    // qs is a stringified JSON array.
    // so 
    $locs = json_decode($qs);

    foreach($locs as $location){
        //print_r($location);
        $location->value = cleanLocation($location->value);

        // check in local table geocache for trimmed copy of this in the field query_string
        $existing_qs_country = locExists($location->value, $conn);      
        // if it exists, then update the value
        if($existing_qs_country){
            if($field_to_use == 'latlng'){
                // use a different function to get the latlng
                $location->value = locExists($location->value, $conn, 'latlng');
            }
            else {
                $location->value = $existing_qs_country;
            }
        }
        else {
        // otherwise, query geonames  
        //echo $location->value;
        //echo '<br>';
            if(!isset($location->cb)){
                $location->cb = '';
            }
            $retrieved_geoname = getGeonameValues($location->value, $location->cb, $gnserver, $gnusername);
            //echo  $location->value ;
            //echo '<br/><br>';

            //print_r($retrieved_geoname);            
            //echo '<br/><br>';
            //echo $retrieved_geoname->totalResultsCount;
            if($retrieved_geoname =='' || $retrieved_geoname->totalResultsCount==0){
//                $location->value = $location->value; // Could make this 'Unspecified'
                $location->value = 'Undetermined';
                $errorstring .="Could not guess country from entered string";
            }
            else {
               // echo '2 got here'.'<br />';
                updateGeocache($location->value, $retrieved_geoname->geonames, $conn, $location->cb);
                if($field_to_use == 'latlng'){
                    $location->value = getLatLngfromGeoname($retrieved_geoname->geonames);
                }
                else {
                    $location->value = getCountryNamefromGeoname($retrieved_geoname->geonames);
                }
            }
        }
    }


    echo json_encode($locs);
    die;

  
    // if I find it, then return the country for that field.    
  
}

if($errorstring !=''){
    $mydata = array("error" => $errorstring);
    echo json_encode($mydata);
    die;
}




function cleanLocation($loc){
    // Geonames doesn't like 'Co.' - so adjust it
    // is this going to clobber Colorado? I don't know!
    // possibly also replace '/'the second thing in strings. I don't know. FFS!
    $loc = str_replace('/', ', ', $loc);
    $loc = str_replace('?', '', $loc);
    $loc = str_replace(', Co.', ', County ', $loc);
    $loc = str_replace(', Co ', ', County', $loc); // this is because 'Down' returns Jamaica while Belfast, Co. Antrim returns nothing!
    $loc = preg_replace("/\([^)]+\)/","",$loc); // remove anything in parentheses
    $loc = rtrim($loc, '.');
    // can add any other adjustments here
    return trim($loc);
}

function locExists($qs, $conn, $field_to_use = 'country'){
    $sql = "SELECT lat, lng, sub_country, sub_country_code, country, country_code from geocache WHERE query_string = '".mysqli_real_escape_string($conn,$qs)."';";
   $result = $conn->query($sql);
    if ($result->num_rows == 0) { return false; }
    while($row = $result->fetch_assoc()) {
        if($field_to_use =='latlng'){
            return $row['lat'].','.$row['lng'];
        }
        $sub_country = $row['sub_country'];
        if($sub_country !='' && $row['country_code']=='GB'){
            return $sub_country;
        }        
        if(trim($row['country'])!=''){
            return $row['country'];
        }
    }
    return false;
}

function getCountryNamefromGeoname($geoname){
   // print_r($geoname);
    if($geoname[0]->countryName !='United Kingdom'){
        return $geoname[0]->countryName;
    }
    return $geoname[0]->adminName1;
}

function getLatLngfromGeoname($geoname){
   // print_r($geoname);
    return $geoname[0]->latitude.','.$geoname[0]->longitude;

}

function getGeonameValues($qs, $cb, $gnserver, $gnusername){
    if($cb){
        $cb = '&countryBias='.$cb;
    }
    else {
        $cb = '';
    }

    $requeststring = $gnserver.'searchJSON?q='.rawurlencode($qs).$cb.'&maxRows=1&orderby=population&style=full&username='.$gnusername;
    //return ''; 
                //echo $requeststring;

    $json_data = json_decode(file_get_contents($requeststring));
    if($json_data){
        if($json_data->totalResultsCount==0){
            // if no results for 'AND', try 'OR', but remove the order by population in this case otherwise likely ust to get big places
            // who knows if this calibration will work but worth a try...
            $requeststring = str_replace('&orderby=population','', $requeststring);
            $requeststring .= '&operator=OR';
            //echo $requeststring;
            $json_data = json_decode(file_get_contents($requeststring));
        }
    }
    
    // TODO - could do 2 queries - second with OR operator.  look at results to see.
    return $json_data;
    // do a curl I guess. 
}

function updateGeocache($qs, $geoname, $conn, $cb){
    //echo $qs;
    //print_r($geoname[0]);
    //print_r($geoname[0]);
//$location->value, $retrieved_geoname->geonames, $conn
   $conn->set_charset("utf8"); //utf8mb4
   $sql = "INSERT INTO `geocache` (`id`, `geoname_id`, `query_string`, `country_bias`, `town`, `sub_country`, `sub_country_code`, `country`, `country_code`, `lat`, `lng`)
            VALUES
            (NULL, 
            '".mysqli_real_escape_string($conn,$geoname[0]->geonameId)."',
            '".mysqli_real_escape_string($conn,$qs)."',
            '".mysqli_real_escape_string($conn,$cb)."',
            '".mysqli_real_escape_string($conn,$geoname[0]->name)."',
            '".mysqli_real_escape_string($conn,$geoname[0]->adminName1)."',
            '".mysqli_real_escape_string($conn,$geoname[0]->adminCode1)."',
            '".mysqli_real_escape_string($conn,$geoname[0]->countryName)."',
            '".mysqli_real_escape_string($conn,$geoname[0]->countryCode)."',
            '".mysqli_real_escape_string($conn,$geoname[0]->lat)."',
            '".mysqli_real_escape_string($conn,$geoname[0]->lng)."');";
    //echo $sql;
  $conn->query($sql);

}

$conn->close();
?>