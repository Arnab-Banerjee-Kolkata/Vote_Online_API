<?php

include 'Credentials.php';
include 'Protection.php';

$conn=new mysqli($servername,$username,$password,$dbname);

if($conn->connect_error){
	die("Connection failed ".$conn->connect_error);
}

$postAuthKey=$_POST["postAuthKey"];
$booth_id=$_POST["booth_id"];


$key_name="post_auth_key";
$webIp=getServerIp($INTERNAL_AUTH_KEY);

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey==$postAuthKey2 && $webIp==$WEB_IP)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT booth_id FROM Booth WHERE booth_id=?");
	$stmt2->bind_param("s",$booth_id);
	$stmt2->execute();
	$stmt2->bind_result($boothid);
	
	
	if($stmt2->fetch() && $booth_id==$boothid)
	{
		$stmt2->close();
		$response['validBooth']=true;

        $stmt=$conn->prepare("UPDATE Booth SET status=0 WHERE booth_id=?");
        $stmt->bind_param("s", $booth_id);
        $stmt->execute();
        
        $stmt->close();
        $response['success']=true;		
	}
}
$conn->close();

echo json_encode($response);

?>
			
	
	
