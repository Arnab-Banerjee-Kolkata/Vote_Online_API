<?php

require('textlocal.class.php');
require('Credentials.php');
include 'Credentials.php';
include 'Protection.php';
include 'EncryptionKeys.php';

function sendOTP($conn, $internalAuthKey, $countryCode, $regMobNo, $adminOTP, $API_KEY, $key)
{
    include 'Credentials.php';        
    if($internalAuthKey==$INTERNAL_AUTH_KEY)    
    {
        $adminOTP=decrypt($internalAuthKey, $adminOTP, $key);        
        $Textlocal = new Textlocal(false, false, $API_KEY);
        $numbers = array($countryCode.$regMobNo);        
        $sender = 'RMVOTE';        
        if($adminOTP=="-1")            
        $adminOTP="Corrupt OTP. Contact office";        
        $message = 'This is your ADMIN OTP: '.$adminOTP;
        try{
        $result =$Textlocal->sendSms($numbers, $message, $sender);            
        }catch(Exception $e)            
        {                
            $conn->close();                
            die('Error: '.$e->getMessage());            
        }
        if($result->status=="success")            
            return true;        
        else            
            return false;    
    }    
    return false;
}

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}

checkServerIp($INTERNAL_AUTH_KEY);

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

$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
    
    adminAutoLogout($INTERNAL_AUTH_KEY, $conn);

    $otp1=generateOtp($INTERNAL_AUTH_KEY);
    $otp1=encrypt($INTERNAL_AUTH_KEY, $otp1, $keySet[38]);
    
    $stmt3=$conn->prepare("UPDATE Admin_Credentials SET OTP=?,otpCount=0 WHERE id=?");
    $stmt3->bind_param("ss",$otp1,$adminId);
    $stmt3->execute();
    $stmt3->close();

	
	$stmt2=$conn->prepare("SELECT COUNT(id),phone_number,OTP,sms_count FROM Admin_Credentials WHERE id=? AND status=0 AND sms_count<4");
	$stmt2->bind_param("s",$adminId);
	$stmt2->execute();
	$stmt2->bind_result($count,$regMobNo,$adminOTP,$smsCount);
	
	if($stmt2->fetch() && $count==1)
	{
		$stmt2->close();
		$count=-1;
		
		$response['validAdmin']=true;
		
		$response['success']=sendOTP($conn,$INTERNAL_AUTH_KEY,91,$regMobNo,$adminOTP,$API_KEY, $keySet[38]);
		
		$stmt4=$conn->prepare("UPDATE Admin_Credentials SET sms_count=? WHERE id=?");
		$stmt4->bind_param("ds",++$smsCount,$adminId);
		$stmt4->execute();
		$stmt4->fetch();
		$stmt4->close();
	}
	else
		$stmt2->close();
}
else
	$stmt->close();

$conn->close();
echo json_encode($response);

?>
