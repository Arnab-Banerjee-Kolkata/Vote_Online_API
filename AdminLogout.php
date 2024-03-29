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

$key_name="post_auth_key";

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

    adminAutoLogout($INTERNAL_AUTH_KEY, $conn);
	
	$stmt1=$conn->prepare("SELECT COUNT(id),status FROM Admin_Credentials WHERE id=? AND (status=1 OR status=0)");
	$stmt1->bind_param("s",$adminId);
	$stmt1->execute();
	$stmt1->bind_result($count1,$oldStatus);
	
	if($stmt1->fetch() && $count1==1)
	{
		$stmt1->close();
		$count1=-1;
		$response['validAdmin']=true;
		
		$stmt4=$conn->prepare("UPDATE Admin_Credentials SET status=0 WHERE id=?");
		$stmt4->bind_param("s",$adminId);
		$stmt4->execute();
		$stmt4->close();

        if($oldStatus==1)
        {
            $otp1=generateOtp($INTERNAL_AUTH_KEY);
            $otp1=encrypt($INTERNAL_AUTH_KEY, $otp1, $keySet[$ADMIN_KEY]);
            
            $stmt3=$conn->prepare("UPDATE Admin_Credentials SET OTP=?,otpCount=0 WHERE id=?");
            $stmt3->bind_param("ss",$otp1,$adminId);
            $stmt3->execute();
            $stmt3->close();
        }

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
