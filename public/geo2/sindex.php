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

echo rawurlencode('Erryroe, Monaghan, Ireland');
exit;


$qs = '';
if(isset($_POST['qs'])){ 
    $qs = trim($_POST['qs']); 
}


if($qs ==''){
    $errorstring .="no query string";

}



/*$qs = '[{"ahn":[1],"value":"Hackney, London"},{"ahn":[2,10,21],"value":"Belfast, Co. Antrim"},{"ahn":[4],"value":"Kensington, London"},{"ahn":[8,17],"value":"Breslau"},{"ahn":[16,32,70],"value":"Kieferstaedtel"},{"ahn":[64],"value":"Loslau"},{"ahn":[129],"value":"Loslau ?"},{"ahn":[33],"value":"Zabrze"},{"ahn":[35],"value":"Kieferstädtel, Upper Silesia"},{"ahn":[9,37,74,148,19,39],"value":"Havant, Hampshire"},{"ahn":[36],"value":"Ludlow, Shropshire"},{"ahn":[73],"value":"Southwark"},{"ahn":[147,589],"value":"Wellington, Shropshire"},{"ahn":[1176],"value":"Ludson, Shropshire"},{"ahn":[75,150],"value":"Southwark, London"},{"ahn":[76,306,1227,307,77,154,155],"value":"Brading, IOW"},{"ahn":[613],"value":"Shalfleet, IOW"},{"ahn":[2452],"value":"Carisbrook, IOW"},{"ahn":[4904],"value":"Calbourne, IOW"},{"ahn":[622],"value":"Newchurch, IOW"},{"ahn":[1244,1245,623],"value":"Isle of Wight"},{"ahn":[78],"value":"Bishops Waltham/Wickham, Hampshire"},{"ahn":[159],"value":"Hayling Island, Hampshire"},{"ahn":[5,11],"value":"Ballymena, Co. Antrim"},{"ahn":[20],"value":"Castleblayney, Co. Monaghan"},{"ahn":[41],"value":"Co. Monaghan"},{"ahn":[43],"value":"Newton Crommelin, Co. Antrim"},{"ahn":[44],"value":"Co. Down"},{"ahn":[23],"value":"Caledon, Co. Tyrone"},{"ahn":[6,26,104],"value":"Harrow, London"},{"ahn":[12],"value":"Willesden"},{"ahn":[24,48],"value":"Hailey Nr Witney"},{"ahn":[96,193],"value":"Hailey Nr Witney, Oxfordshire"},{"ahn":[192,780],"value":"Witney, Oxfordshire"},{"ahn":[195],"value":"Oxfordshire"},{"ahn":[49],"value":"Ramsden, Oxfordshire"},{"ahn":[25,50,100],"value":"Billesdon, Leicestershire"},{"ahn":[101],"value":"Thrussington, Leicestershire"},{"ahn":[51],"value":"Bagworth, Leicestershire"},{"ahn":[13],"value":"St Austell"},{"ahn":[105],"value":"Ruislip, Middlesex"},{"ahn":[27],"value":"St Austell, Cornwall"},{"ahn":[108],"value":"Lewannick"},{"ahn":[432,218],"value":"Cornwall"},{"ahn":[866,867],"value":"Lewannick, Cornwall"},{"ahn":[217],"value":"North Hill, Cornwall"},{"ahn":[55,111],"value":"Exeter, Devon"},{"ahn":[110,220,440,880,3520],"value":"Christow, Devon"},{"ahn":[446,447],"value":"Devon"},{"ahn":[30],"value":"Blackwood, Monmouthshire"},{"ahn":[60],"value":"Eglwysilan / Caerphilly, Glamorgan"},{"ahn":[120,121],"value":"Lantwit-fardre, Glamorgan"},{"ahn":[61],"value":"Bedwelty district, Monmouth"},{"ahn":[123],"value":"Merthyr Tydfil"},{"ahn":[247],"value":"Llanfabon, Glamorgan"},{"ahn":[62],"value":"Abercanaid, Glamorgan"},{"ahn":[125],"value":"Ebbw Vale, Monmouthshire"},{"ahn":[63],"value":"Craig-Y-Pandy, Tregarth, Caernarvonshire"}]';*/

/*$qs = '[{"ahn":[1],"value":"Botten Västgården, Långå, Hede (Z)"},{"ahn":[2,10,21],"value":"Belfast, Co. Antrim"},{"ahn":[4],"value":"Kensington, London"}]';*/

//    $locs = json_decode($qs);
//    print_r($locs);
//    exit;

echo rawurlencode('Nr. Whithorn, Wigtownshire, Scotland');
exit;


if($errorstring==''){


$allowed_domains = ["dnapainter.com", "shiny.dnapainter.com"];

if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_domains)) {
 //   header('Access-Control-Allow-Origin: https://' . $_SERVER['HTTP_ORIGIN']);
}
 header('Access-Control-Allow-Origin: https://shiny.dnapainter.com');

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
            $location->value = $existing_qs_country;
        }
        else {
        // otherwise, query geonames  
        //echo $location->value;
        //echo '<br>'; 
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
                updateGeocache($location->value, $retrieved_geoname->geonames, $conn);
                $location->value = getCountryNamefromGeoname($retrieved_geoname->geonames);
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





// http://api.geonames.org/searchJSON?q=Dollis%20Hill&maxRows=10&style=full&username=delicado
// insert the values into the db
// return the country

function cleanLocation($loc){
    // Geonames doesn't like 'Co.' - so adjust it
    // is this going to clobber Colorado? I don't know!
    // possibly also replace '/'the second thing in strings. I don't know. FFS!
    $loc = str_replace('/', ', ', $loc);
    $loc = str_replace('?', '', $loc);
    $loc = str_replace(', Co.', ', County ', $loc);
    $loc = str_replace(', Co ', ', County', $loc); // this is because 'Down' returns Jamaica while Belfast, Co. Antrim returns nothing!
    $loc = preg_replace("/\([^)]+\)/","",$loc); // remove anything in parentheses
    // can add any other adjustments here
    return trim($loc);
}

function locExists($qs, $conn){
    $sql = "SELECT sub_country, sub_country_code, country, country_code from geocache WHERE query_string = '".mysqli_real_escape_string($conn,$qs)."';";
   $result = $conn->query($sql);
    if ($result->num_rows == 0) { return false; }
    while($row = $result->fetch_assoc()) {
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

function updateGeocache($qs, $geoname, $conn){
    //echo $qs;
    //print_r($geoname[0]);
    //print_r($geoname[0]);
//$location->value, $retrieved_geoname->geonames, $conn
   $sql = "INSERT INTO `geocache` (`id`, `geoname_id`, `query_string`, `town`, `sub_country`, `sub_country_code`, `country`, `country_code`, `lat`, `lng`)
            VALUES
            (NULL, 
            '".mysqli_real_escape_string($conn,$geoname[0]->geonameId)."',
            '".mysqli_real_escape_string($conn,$qs)."',
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