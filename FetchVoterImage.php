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
$boothId=$conn->real_escape_string($_POST["boothId"]);
$aadhaarNo=$conn->real_escape_string($_POST["aadhaarNo"]);

$key_name="post_auth_key";
checkServerIp($INTERNAL_AUTH_KEY);

$response=array();
$response['validAuth']=false;
$response['validBooth']=false;
$response['validAadhaar']=false;
$response['imagePath']="";
$response['success']=false;


$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);

$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt1=$conn->prepare("SELECT status FROM Booth WHERE booth_id=?");
	$stmt1->bind_param("s",$boothId);
	$stmt1->execute();
	$stmt1->bind_result($status0);
	
	if($stmt1->fetch() && $status0==1)
	{
		$stmt1->close();
		$response['validBooth']=true;
		
		$stmt2=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_DB WHERE aadhaar_no=?");
		$stmt2->bind_param("s",$aadhaarNo);
		$stmt2->execute();
		$stmt2->bind_result($count1);
		
		if($stmt2->fetch() && $count1==1)
		{
			$stmt2->close();
			$count1=-1;
			$response['validAadhaar']=true;
			
			$stmt3=$conn->prepare("SELECT image_path FROM Govt_DB WHERE aadhaar_no=?");
			$stmt3->bind_param("s",$aadhaarNo);
			$stmt3->execute();
			$stmt3->bind_result($imagePath);
            $stmt3->fetch();
			$stmt3->close();
			
			$response["imagePath"]=$imagePath;
			$response["success"]=true;
		}
		else
		{
			$stmt2->close();
		}
	}
	else
	{
		$stmt1->close();
	}
}
else
{
	$stmt->close();
}
$conn->close();
echo json_encode($response);

?>

	
	
	
	
	
	