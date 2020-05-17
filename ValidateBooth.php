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
	die("Connection failed ".$conn->connect_error);
}


$postAuthKey=$conn->real_escape_string($_POST["postAuthKey"]);
$booth_id=$conn->real_escape_string($_POST["boothId"]);
$otp=$conn->real_escape_string($_POST["otp"]);

$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;
$response['validLogin']=false;



$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(booth_id),status,otp,sms_count FROM Booth WHERE booth_id=?");
	$stmt2->bind_param("s",$booth_id);
	$stmt2->execute();
	$stmt2->bind_result($count,$loginState,$otpsent,$smsCount);
	$stmt2->fetch();
	$stmt2->close();
	
	if($count==1)
	{
		$response['validBooth']=true;
		$count=-1;

        if($loginState==0)
        {
            $response['validLogin']=true;

            $otp=encrypt($INTERNAL_AUTH_KEY, $otp, $keySet[8]);
            
            if($otp==$otpsent && $smsCount<=4)
            {
                $response['success']=true;
		$smsCount=0;

                $stmt3=$conn->prepare("UPDATE Booth SET status=1,sms_count=? WHERE booth_id=?");
                $stmt3->bind_param("ds",$smsCount,$booth_id);
                $stmt3->execute();
                $stmt3->close();
            }
            elseif($otp!=$otpsent && $smsCount>=4)
            {
                $stmt4=$conn->prepare("UPDATE Booth SET status=2 WHERE booth_id=?");
		$stmt4->bind_param("s",$booth_id);
		$stmt4->execute();
		$stmt4->close();
				
		$response['boothSuspended']=true;
            }

            $times=mt_rand(1,12);
            while($times>0)
            {
                $otp=mt_rand(1000, mt_rand(1001,9999));
                $times--;
            }

            $otp=encrypt($INTERNAL_AUTH_KEY, $otp, $keySet[8]);

            $stmt2=$conn->prepare("UPDATE Booth SET otp=? WHERE booth_id=?");
            $stmt2->bind_param("ss", $otp, $booth_id);
            $stmt2->execute();
            $stmt2->close();
        }
		
	}
}
else
$stmt->close();
$conn->close();

echo json_encode($response);

?>
