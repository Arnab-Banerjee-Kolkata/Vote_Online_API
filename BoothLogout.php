<?php

include 'Credentials.php';
include 'Protection.php';
include 'EncryptionKeys.php';

checkServerIp($INTERNAL_AUTH_KEY);
foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}

$conn=new mysqli($servername,$username,$password,$dbname);

if($conn->connect_error){
	die("Connection failed ");
}


$postAuthKey=$conn->real_escape_string($_POST["postAuthKey"]);
$booth_id=$conn->real_escape_string($_POST["booth_id"]);


$key_name="post_auth_key";

$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;

    boothAutoLogout($INTERNAL_AUTH_KEY, $conn);
	
	$stmt2=$conn->prepare("SELECT booth_id FROM Booth WHERE booth_id=?");
	$stmt2->bind_param("s",$booth_id);
	$stmt2->execute();
	$stmt2->bind_result($boothid);
	
	
	if($stmt2->fetch() && $booth_id==$boothid)
	{
		$stmt2->close();
		$response['validBooth']=true;

        $stmt=$conn->prepare("UPDATE Booth SET status=0 WHERE booth_id=? AND (status=1 OR status=0)");
        $stmt->bind_param("s", $booth_id);
        $stmt->execute();
        
        $stmt->close();

        $otp=generateOtp($INTERNAL_AUTH_KEY);
        $otp=encrypt($INTERNAL_AUTH_KEY, $otp, $keySet[$BOOTH_KEY]);

        $stmt2=$conn->prepare("UPDATE Booth SET otp=? WHERE booth_id=?");
        $stmt2->bind_param("ss", $otp, $boothId);
        $stmt2->execute();

        $stmt2->close();

        $response['success']=true;		
	}
}
$conn->close();

echo json_encode($response);

?>
			
	
	
