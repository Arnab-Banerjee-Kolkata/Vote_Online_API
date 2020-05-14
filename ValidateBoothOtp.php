<?php

include 'Credentials.php';
include 'ShowPanelOptions.php';
include 'Protection.php';
include 'EncryptionKeys.php';


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}

$boothId=$conn->real_escape_string($_POST["boothId"]);
$otp1=$conn->real_escape_string($_POST["otp"]);
$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);
$type=$conn->real_escape_string($_POST["type"]);


$key_name="post_auth_key";


$response=array();
$response['validAuth']=false;
$response['validBooth']=false;
$response['validOtp']=false;


$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name = ?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);




if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

        
    $stmt=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=? AND status=1");
    $stmt->bind_param("s", $boothId);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count==1)
    {
        $count=-1;
        $stmt->close();
        $response['validBooth']=true;



        $stmt = $conn->prepare("SELECT otp FROM Booth WHERE booth_id=? AND status=1");
        $stmt->bind_param("s", $boothId);
        $stmt->execute();
        $stmt->bind_result($otp2);

        $otp1=encrypt($INTERNAL_AUTH_KEY, $otp1, $keySet[8]);

        if($stmt->fetch() && $otp1==$otp2)
        {                     
            $stmt->close();
            $response['validOtp']=true; 
            
            
            
            //SHOW APPROPRIATE VOTING PANEL
            $response['sub']=showPanelOptions($INTERNAL_AUTH_KEY, $conn, $boothId, $electionId, $type);

             
        }    
        else
        {
            $stmt->close();
        }                
        
        
        $times=mt_rand(1,12);
        while($times>0)
        {
            $otp=mt_rand(1000, mt_rand(1001,9999));
            $times--;
        }

        $otp=encrypt($INTERNAL_AUTH_KEY, $otp, $keySet[8]);

        $stmt2=$conn->prepare("UPDATE Booth SET otp=? WHERE booth_id=?");
        $stmt2->bind_param("ss", $otp, $boothId);
        $stmt2->execute();

        $stmt2->close();
    }
        
    
}
$conn->close();

echo json_encode($response);


?>
