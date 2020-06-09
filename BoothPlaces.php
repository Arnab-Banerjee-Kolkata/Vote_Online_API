<?php

include 'Credentials.php';
include 'Protection.php';

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ");
}

$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);


$key_name="post_auth_key";

$response=array();

$response['success']=false;
$response['validAuth']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;

	$stmt2=$conn->prepare("SELECT DISTINCT Booth.place, State.name FROM Booth, State WHERE Booth.state_code=State.code ORDER BY place");
	$stmt2->execute();
	$stmt2->bind_result($place, $stateName);
	
	$allPlaces=array();
	
	while($stmt2->fetch())
	{
        $boothPlace=array();
        $boothPlace['place']=$place;;
        $boothPlace['state']=$stateName;

		array_push($allPlaces,$boothPlace);
	}

	$stmt2->close();
    $response['listOfPlaces']=$allPlaces;
    $response['success']=true;
    
}
else
	$stmt->close();

$conn->close();
echo json_encode($response);
?>
