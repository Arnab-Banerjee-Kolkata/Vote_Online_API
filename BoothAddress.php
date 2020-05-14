<?php

include 'Credentials.php';
include 'Protection.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$place=$conn->real_escape_string($_POST["place"]);

checkForbiddenPhrase($INTERNAL_AUTH_KEY, $postAuthKey1);
checkForbiddenPhrase($INTERNAL_AUTH_KEY, $place);

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
	
	$stmt2=$conn->prepare("SELECT locality,address,land_mark FROM Booth WHERE place=? AND (status=0 OR status=1) ORDER BY locality");
	$stmt2->bind_param("s",$place);
	$stmt2->execute();
	$stmt2->bind_result($area,$address,$landmark);
	
	$allBoothsInPlace=array();
	
	while($stmt2->fetch())
	{
		$boothAddress=array();
		$boothAddress['area']=$area;
		$boothAddress['address']=$address;
		$boothAddress['landmark']=$landmark;
		
		array_push($allBoothsInPlace,$boothAddress);
	}
    
	$stmt2->close();
	
	$response['allBoothsInPlace']=$allBoothsInPlace;
	$response['success']=true;

}

else
	$stmt->close();

$conn->close();
echo json_encode($response);

?>