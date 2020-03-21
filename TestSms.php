<?php

	require('textlocal.class.php');
        require('Credentials.php');

    include 'Credentials.php';

function sendOTP($countryCode, $regMobNo, $voterOTP, $API_KEY)
{
    //$API_KEY='Fc4V2GCvV40-FI9ZBaCcRjDtTYFVsYcA7YV0YfDv1p';
 
	$Textlocal = new Textlocal(false, false, $API_KEY);
 
	$numbers = array($countryCode.$regMobNo);
	$sender = 'RMVOTE';
	$message = 'This is your VOTER OTP: '.$voterOTP;
        
        try{
 
	$result =$Textlocal->sendSms($numbers, $message, $sender);
	//print_r($result);
        }catch(Exception $e)
        {
                die('Error: '.$e->getMessage());
        }

    if($result->status=="success")
		return true;
	else
		return false;
}




// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$aadhaarNo=$_POST["aadhaarNo"];
$smsAuthKey1=$_POST["smsAuthKey"];

// prepare and bind
$stmt = $conn->prepare("SELECT country_code, reg_mob_no from Govt_DB where aadhaar_no=?");
$stmt->bind_param("s", $aadhaarNo);

$response=array();
$response['success']=false;

$stmt->execute();

$stmt->bind_result($countryCode, $regMobNo);

if($stmt->fetch())
{      
        $stmt->close();

        $stmt2=$conn->prepare("SELECT voter_otp from Credentials where aadhaar_no=?");
        $stmt2->bind_param("s", $aadhaarNo);
        
        $stmt2->execute();
        $stmt2->bind_result($voterOTP);
        
        if($stmt2->fetch())
        {
                $stmt2->close();

                $key_name="sms_auth_key";

                $stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
                $stmt3->bind_param("s", $key_name);

                $stmt3->execute();
                $stmt3->bind_result($smsAuthKey2);


                if($stmt3->fetch() && $smsAuthKey1==$smsAuthKey2)
                {
                    $stmt3->close();
                
                    if(sendOTP($countryCode, $regMobNo, $voterOTP, $API_KEY))
                        $response['success']=true;
                }
                
        }
}
$conn->close();

echo json_encode($response);


?>