<?php

include 'Credentials.php';
include 'StoreApproval.php';
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
$boothId=$conn->real_escape_string($_POST["boothId"]);
$aadhaarNo=$conn->real_escape_string($_POST["aadhaarNo"]);
$voterOtp=$conn->real_escape_string($_POST["voterOtp"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);
$type=$conn->real_escape_string($_POST["type"]);

$key_name="post_auth_key";


$response=array();
$response['validAuth']=false;
$response['validBooth']=false;
$response['validAadhaar']=false;
$response['validApproval']=false;
$response['validOtp']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=? AND status=1");
	$stmt2->bind_param("s",$boothId);
	$stmt2->execute();
	$stmt2->bind_result($count1);
	
	if($stmt2->fetch() && $count1==1)
	{
		$stmt2->close();
		$count1=-1;
		$response['validBooth']=true;
		
		$stmt3=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_DB WHERE aadhaar_no=?");
		$stmt3->bind_param("s",$aadhaarNo);
		$stmt3->execute();
		$stmt3->bind_result($count2);
		
		if($stmt3->fetch() && $count2==1)
		{
			$stmt3->close();
			$count2=-1;
			$response['validAadhaar']=true;
			
			
				
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
            
            if($count4==0)
            {
                $count4=-1;
                $response['validApproval']=true;
                
                $stmt6=$conn->prepare("SELECT voter_otp FROM Credentials WHERE aadhaar_no=?");
                $stmt6->bind_param("s",$aadhaarNo);
                $stmt6->execute();
                $stmt6->bind_result($otp);

                $voterOtp=encrypt($INTERNAL_AUTH_KEY, $voterOtp, $keySet[8]);
                
                if($stmt6->fetch() && $voterOtp==$otp)
                {
                    $stmt6->close();
                    $response['validOtp']=true;                      

                    //StoreApproval
                    $response['returnValue']=storeApproval($conn,$INTERNAL_AUTH_KEY,$aadhaarNo,$electionId,$type,$boothId);
                    if($response['returnValue']['garbageVoted']['success'])
                    {
                        $boothOtp=generateOtp($INTERNAL_AUTH_KEY);
                        $enOtp=encrypt($INTERNAL_AUTH_KEY, $boothOtp, $keySet[8]);

                        $stmt=$conn->prepare("UPDATE Booth SET otp=? WHERE booth_id=? AND status=1");
                        $stmt->bind_param("ss", $enOtp, $boothId);
                        $stmt->execute();
                        $stmt->fetch();
                        $stmt->close();  

                        $response['boothOtp']=$boothOtp;
                    }
                }
                else
                {
                    $stmt6->close();
                }
                
                $newOtp=generateOtp($INTERNAL_AUTH_KEY);
                $newOtp=encrypt($INTERNAL_AUTH_KEY, $newOtp, $keySet[8]);
    
                $stmt7=$conn->prepare("UPDATE Credentials SET voter_otp=? WHERE aadhaar_no=?");
                $stmt7->bind_param("ss",$newOtp,$aadhaarNo);
                $stmt7->execute();
                $stmt7->close();
            }
            else
            {
                $stmt5->close();
            }
			
		}
		else
		{
			$stmt3->close();
		}
	}
	else
	{
		$stmt2->close();
	}
}
else
{
	$stmt->close();
}

$conn->close();
echo json_encode($response);
?>
