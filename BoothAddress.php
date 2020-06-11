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
$place=$conn->real_escape_string($_POST["place"]);


$key_name="post_auth_key";

$response=array();

$response['success']=false;
$response['validAuth']=false;
$response['validPlace']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(place) FROM Booth WHERE place=?  AND (status=0 OR status=1)");
	$stmt2->bind_param("s",$place);
	$stmt2->execute();
	$stmt2->bind_result($count);
	
	if($stmt2->fetch() && $count>=1)
	{
		$stmt2->close();
		$response['validPlace']=true;

		$stmt3=$conn->prepare("SELECT locality,address,land_mark,map_link,coordinates FROM Booth WHERE place=? AND (status=0 OR status=1) ORDER BY locality");
		$stmt3->bind_param("s",$place);
		$stmt3->execute();
		$stmt3->bind_result($area,$address,$landmark,$mapLink,$coordinates);
	
		$allBoothsInPlace=array();
	
		while($stmt3->fetch())
		{
			$boothAddress=array();
			$boothAddress['area']=$area;
			$boothAddress['address']=$address;
			$boothAddress['landmark']=$landmark;
            $boothAddress['mapLink']=$mapLink;
            $boothAddress['coordinates']=$coordinates;
		
			array_push($allBoothsInPlace,$boothAddress);
		}
    
		$stmt3->close();
	
		$response['allBoothsInPlace']=$allBoothsInPlace;
		$response['success']=true;
	}
	else
		$stmt2->close();
	
}

else
	$stmt->close();

$conn->close();
echo json_encode($response);

?>
