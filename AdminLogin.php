<?php

include 'Credentials.php';
include 'Protection.php';
include 'EncryptionKeys.php';

checkServerIp($INTERNAL_AUTH_KEY);
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
$adminId=$conn->real_escape_string($_POST["adminId"]);
$adminOtp=$conn->real_escape_string($_POST["adminOtp"]);

$key_name="post_auth_key";

$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validOtp']=false;
$response['success']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt1=$conn->prepare("SELECT COUNT(id),OTP,sms_count,otpCount FROM Admin_Credentials WHERE id=? AND status=0");
	$stmt1->bind_param("s",$adminId);
	$stmt1->execute();
	$stmt1->bind_result($count1,$otp,$smsCount,$otpCount);
	
	if($stmt1->fetch() && $count1==1)
	{
		$stmt1->close();
		$count1=-1;
		$response['validAdmin']=true;

        $adminOtp=encrypt($INTERNAL_AUTH_KEY, $adminOtp, $keySet[38]);
		$otpCount++;

		if($otp==$adminOtp && $smsCount<=4 && $otpCount<=4)
		{
			$response['validOtp']=true;
			$smsCount=0;
			$otpCount=0;
			
			$stmt4=$conn->prepare("UPDATE Admin_Credentials SET status=1,sms_count=? WHERE id=?");
			$stmt4->bind_param("ds",$smsCount,$adminId);
			$stmt4->execute();
			$stmt4->close();
			
            $response['success']=true;
		}
		
		elseif($otp!=$adminOtp && ($smsCount>=4 || $otpCount>=4)) //Suspend
		{
			$stmt5=$conn->prepare("UPDATE Admin_Credentials SET status=2 WHERE id=?");
			$stmt5->bind_param("s",$adminId);
			$stmt5->execute();
			$stmt5->close();
			
			$response['adminSuspended']=true;
		}
		
		$otp1=generateOtp($INTERNAL_AUTH_KEY);
        $otp1=encrypt($INTERNAL_AUTH_KEY, $otp1, $keySet[38]);
		
		$stmt3=$conn->prepare("UPDATE Admin_Credentials SET OTP=?,otpCount=? WHERE id=?");
		$stmt3->bind_param("sds",$otp1,$otpCount,$adminId);
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
