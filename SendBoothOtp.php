<?php

require('textlocal.class.php');
require('Credentials.php');
include 'Credentials.php';
include 'Protection.php';

function sendOTP($conn, $internalAuthKey, $countryCode, $regMobNo, $boothOTP, $API_KEY)
{
    include 'Credentials.php';    
    include 'EncryptionKeys.php';     
    if($internalAuthKey==$INTERNAL_AUTH_KEY)    
    {
        $boothOTP=decrypt($internalAuthKey, $boothOTP, $keySet[8]);        
        $Textlocal = new Textlocal(false, false, $API_KEY);
        $numbers = array($countryCode.$regMobNo);        
        $sender = 'RMVOTE';        
        if($boothOTP=="-1")            
        $boothOTP="Corrupt OTP. Contact office";        
        $message = 'This is your BOOTH OTP: '.$boothOTP;
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
$boothId=$conn->real_escape_string($_POST["boothId"]);

$key_name="post_auth_key";

$response=array();

$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(booth_id),phone_number,otp,sms_count FROM Booth WHERE booth_id=? AND status=0 AND sms_count<4");
	$stmt2->bind_param("s",$boothId);
	$stmt2->execute();
	$stmt2->bind_result($count,$regMobNo,$boothOTP,$smsCount);
	
	if($stmt2->fetch() && $count==1)
	{
		$stmt2->close();
		$count=-1;
		
		$response['validBooth']=true;
		
		$response['success']=sendOTP($conn,$INTERNAL_AUTH_KEY,91,$regMobNo,$boothOTP,$API_KEY);
		
		$stmt4=$conn->prepare("UPDATE Booth SET sms_count=? WHERE booth_id=?");
		$stmt4->bind_param("ds",++$smsCount,$boothId);
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
