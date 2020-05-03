<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$adminId=$_POST["adminId"];
$adminOtp=$_POST["adminOtp"];

$key_name="post_auth_key";

$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validOtp']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt1=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=0");
	$stmt1->bind_param("s",$adminId);
	$stmt1->execute();
	$stmt1->bind_result($count1);
	
	if($stmt1->fetch() && $count1==1)
	{
		$stmt1->close();
		$count1=-1;
		$response['validAdmin']=true;
		
		$stmt2=$conn->prepare("SELECT OTP FROM Admin_Credentials WHERE id=? AND status=0");
		$stmt2->bind_param("s",$adminId);
		$stmt2->execute();
		$stmt2->bind_result($otp);
		
		if($stmt2->fetch() && $otp==$adminOtp)
		{
			$stmt2->close();
			$response['validOtp']=true;
			
			$stmt4=$conn->prepare("UPDATE Admin_Credentials SET status=1 WHERE id=?");
			$stmt4->bind_param("s",$adminId);
			$stmt4->execute();
			$stmt4->close();
		}
		
		else
		{
			$stmt2->close();
		}
		
		$times=mt_rand(1,12);
		while($times>0)
		{
			$otp1=mt_rand(1000, mt_rand(1001,9999));
			$times--;
		}
		
		$stmt3=$conn->prepare("UPDATE Admin_Credentials SET OTP=? WHERE id=?");
		$stmt3->bind_param("ds",$otp1,$adminId);
		$stmt3->execute();
		$stmt3->close();
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
