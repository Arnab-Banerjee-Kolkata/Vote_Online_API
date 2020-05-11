<?php

include 'Credentials.php';
include 'Protection.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$adminId=$_POST["adminId"];

$key_name="post_auth_key";
checkServerIp($INTERNAL_AUTH_KEY);

$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['success']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt1=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
	$stmt1->bind_param("s",$adminId);
	$stmt1->execute();
	$stmt1->bind_result($count1);
	
	if($stmt1->fetch() && $count1==1)
	{
		$stmt1->close();
		$count1=-1;
		$response['validAdmin']=true;
		
		$stmt4=$conn->prepare("UPDATE Admin_Credentials SET status=0 WHERE id=?");
		$stmt4->bind_param("s",$adminId);
		$stmt4->execute();
		$stmt4->close();
		$response['success']=true;
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
