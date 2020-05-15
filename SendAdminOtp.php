<?php

require('textlocal.class.php');
require('Credentials.php');
include 'Credentials.php';
include 'Protection.php';

function sendOTP($conn, $internalAuthKey, $countryCode, $regMobNo, $adminOTP, $API_KEY)
{
    include 'Credentials.php';    
    include 'EncryptionKeys.php';     
    if($internalAuthKey==$INTERNAL_AUTH_KEY)    
    {
        $adminOTP=decrypt($internalAuthKey, $adminOTP, $keySet[38]);        
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
    die("Connection failed: " . $conn->connect_error);
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
	
	$stmt2=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=0");
	$stmt2->bind_param("s",$adminId);
	$stmt2->execute();
	$stmt2->bind_result($count);
	
	if($stmt2->fetch() && $count==1)
	{
		$stmt2->close();
		$count=-1;
		
		$response['validAdmin']=true;
		
		$stmt3=$conn->prepare("SELECT phone_number,OTP FROM Admin_Credentials WHERE id=?");
		$stmt3->bind_param("s",$adminId);
		$stmt3->execute();
		$stmt3->bind_result($regMobNo,$adminOTP);
		$stmt3->fetch();
		$stmt3->close();
		
		$response['success']=sendOTP($conn,$INTERNAL_AUTH_KEY,91,$regMobNo,$adminOTP,$API_KEY);
	}
	else
		$stmt2->close();
}
else
	$stmt->close();

$conn->close();
echo json_encode($response);

?>
