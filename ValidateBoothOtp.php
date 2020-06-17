<?php

include 'Credentials.php';
include 'ShowPanelOptions.php';
include 'Protection.php';
include 'EncryptionKeys.php';

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

date_default_timezone_set("Asia/Kolkata");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ");
}


$boothId=$conn->real_escape_string($_POST["boothId"]);
$otp1=$conn->real_escape_string($_POST["otp"]);
$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);


$key_name="post_auth_key";


$response=array();
$response['validAuth']=false;
$response['validBooth']=false;
$response['validApproval']=false;
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


        //REMOVES EXPIRED APPROVAL
        $stmt5=$conn->prepare("SELECT COUNT(booth_id), approved_at FROM Govt_Approval WHERE booth_id=?");
        $stmt5->bind_param("s",$boothId);
        $stmt5->execute();
        $stmt5->bind_result($count4, $approvedAt);
        $stmt5->fetch();
        $stmt5->close();

        if($count4==1)
        {
            $approvedAt=new DateTime($approvedAt);
            $currentTime=new DateTime(date("Y-m-d H:i:s"));
            $minsPassed=$approvedAt->diff($currentTime);

            $minutes = $minsPassed->days * 24 * 60;
            $minutes += $minsPassed->h * 60;
            $minutes += $minsPassed->i;

            //echo $minutes."   ".$APPROVAL_MINUTES."<br>";
            if($minutes>=$APPROVAL_MINUTES)
            {
                $stmt=$conn->prepare("DELETE FROM Govt_Approval WHERE booth_id=?");
                $stmt->bind_param("s", $boothId);
                $stmt->execute();
                $stmt->fetch();
                $stmt->close();

                $count4=0;
            }
        }

        $stmt5=$conn->prepare("SELECT COUNT(booth_id) FROM Govt_Approval WHERE booth_id=? AND panel_count=0");
        $stmt5->bind_param("s",$boothId);
        $stmt5->execute();
        $stmt5->bind_result($count);
        $stmt5->fetch();
        $stmt5->close();

        if($count==1)
        {
            $response['validApproval']=true;
            $count=-1;
        
            $stmt = $conn->prepare("SELECT vote_code FROM Booth WHERE booth_id=?");
            $stmt->bind_param("s", $boothId);
            $stmt->execute();
            $stmt->bind_result($otp2);

            $otp1=encrypt($INTERNAL_AUTH_KEY, $otp1, $keySet[$BOOTH_KEY]);

            if($stmt->fetch() && $otp1==$otp2)
            {                     
                $stmt->close();
                $response['validOtp']=true; 


                $voteCode=generateOtp($INTERNAL_AUTH_KEY);
                $voteCode=encrypt($INTERNAL_AUTH_KEY, $voteCode, $keySet[$BOOTH_KEY]);

                $stmt2=$conn->prepare("UPDATE Booth SET vote_code=? WHERE booth_id=?");
                $stmt2->bind_param("ss", $voteCode, $boothId);
                $stmt2->execute();

                $stmt2->close();


                //SHOW APPROPRIATE VOTING PANEL
                $response['sub']=showPanelOptions($INTERNAL_AUTH_KEY, $conn, $boothId);

                $stmt2=$conn->prepare("UPDATE Govt_Approval SET panel_count=1 WHERE booth_id=?");
                $stmt2->bind_param("s", $boothId);
                $stmt2->execute();
                $stmt2->close();

            }    
            else
            {
                $stmt->close();
            }                
        }
        
    }
        
    
}
$conn->close();

echo json_encode($response);


?>
