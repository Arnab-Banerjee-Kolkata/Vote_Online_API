<?php

include 'Credentials.php';
include 'StoreApproval.php';
include 'Protection.php';
include 'EncryptionKeys.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$boothId=$conn->real_escape_string($_POST["boothId"]);
$aadhaarNo=$conn->real_escape_string($_POST["aadhaarNo"]);
$voterOtp=$conn->real_escape_string($_POST["voterOtp"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);
$type=$conn->real_escape_string($_POST["type"]);

$key_name="post_auth_key";
checkServerIp($INTERNAL_AUTH_KEY);


$response=array();
$response['validAuth']=false;
$response['validBooth']=false;
$response['validAadhaar']=false;
$response['validVoteStatus']=false;
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
	
	$stmt2=$conn->prepare("SELECT COUNT(booth_id), otp FROM Booth WHERE booth_id=? AND status=1");
	$stmt2->bind_param("s",$boothId);
	$stmt2->execute();
	$stmt2->bind_result($count1, $boothOtp);
	
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
			
			$stmt4=$conn->prepare("SELECT COUNT(aadhaar_no) FROM  Govt_Vote_Status WHERE aadhaar_no=?");
			$stmt4->bind_param("s",$aadhaarNo);
			$stmt4->execute();
			$stmt4->bind_result($count3);
			
			if($stmt4->fetch() && $count3==0)
			{
				$stmt4->close();
				$count3=-1;
				$response['validVoteStatus']=true;
				
				$stmt5=$conn->prepare("SELECT COUNT(booth_id) FROM Govt_Approval WHERE booth_id=?");
				$stmt5->bind_param("s",$boothId);
				$stmt5->execute();
				$stmt5->bind_result($count4);
				
				if($stmt5->fetch() && $count4==0)
				{
					$stmt5->close();
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

                        //return booth OTP
                        $boothOtp=decrypt($INTERNAL_AUTH_KEY, $boothOtp, $keySet[8]);                        

                        //StoreApproval
                        $response['returnValue']=storeApproval($conn,$INTERNAL_AUTH_KEY,$aadhaarNo,$electionId,$type,$boothId);
                        if($response['returnValue']['garbageAndVoted']['success'])
                        {
                            $response['boothOtp']=$boothOtp;
                        }
					}
					else
					{
						$stmt6->close();
					}
					
					$times=mt_rand(1,12);
					while($times>0)
					{
						$newOtp=mt_rand(1000, mt_rand(1001,9999));
						$times--;
					}

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
				$stmt4->close();
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
