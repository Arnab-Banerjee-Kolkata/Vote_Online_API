<?php

include 'Credentials.php';
include 'Protection.php';

$conn=new mysqli($servername,$username,$password,$dbname);

if($conn->connect_error){
	die("Connection failed ".$conn->connect_error);
}

$postAuthKey=$_POST["postAuthKey"];
$booth_id=$_POST["boothId"];
$otp=$_POST["otp"];

$key_name="post_auth_key";
$webIp=getServerIp($INTERNAL_AUTH_KEY);


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;
$response['validLogin']=false;


$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey==$postAuthKey2 && $webIp==$WEB_IP)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(booth_id), network_address FROM Booth WHERE booth_id=?");
	$stmt2->bind_param("s",$booth_id);
	$stmt2->execute();
	$stmt2->bind_result($count, $network);
    $stmt2->fetch();
    $stmt2->close();
    

	
	
	if($count==1)
	{
		$response['validBooth']=true;
        $count=-1;

        $stmt=$conn->prepare("SELECT status FROM Booth WHERE booth_id=?");
        $stmt->bind_param("s", $booth_id);
        $stmt->execute();
        $stmt->bind_result($loginState);

        if($stmt->fetch() && $loginState==0)
        {
            $stmt->close();
            $response['validLogin']=true;

            $stmt3=$conn->prepare("SELECT otp FROM Booth WHERE booth_id=?");
            $stmt3->bind_param("s",$booth_id);
            $stmt3->execute();
            $stmt3->bind_result($otpsent);
            
            if($stmt3->fetch() && $otp==$otpsent)
            {
                $response['success']=true;
                $stmt3->close();

                $stmt3=$conn->prepare("UPDATE Booth SET status=1 WHERE booth_id=?");
                $stmt3->bind_param("s", $booth_id);
                $stmt3->execute();
                $stmt3->close();
            }
            else
            {
                $stmt3->close();
            }

            $otp=rand(1000, 9999);
            $stmt2=$conn->prepare("UPDATE Booth SET otp=? WHERE booth_id=?");
            $stmt2->bind_param("ss", $otp, $booth_id);
            $stmt2->execute();
            $stmt2->close();
        }
		
	}
}
$conn->close();

echo json_encode($response);

?>
			
	
	
