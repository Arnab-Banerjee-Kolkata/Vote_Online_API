<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$boothId=$_POST["boothId"];
$aadhaarNo=$_POST["aadhaarNo"];
$electionId=$_POST["electionId"];
$type=$_POST["type"];



$key_name="post_auth_key";

$response=array();
$response['validAuth']=false;
$response['validBooth']=false;
$response['validAadhaar']=false;
$response['validElection']=false;
$response['validType']=false;
$response['validApproval']=false;
$response['success']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);

$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt1=$conn->prepare("SELECT status FROM Booth WHERE booth_id=?");
	$stmt1->bind_param("s",$boothId);
	$stmt1->execute();
	$stmt1->bind_result($status0);
	
	if($stmt1->fetch() && $status0==1)
	{
		$stmt1->close();
		$response['validBooth']=true;
	
		$stmt2=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_DB WHERE aadhaar_no=?");
		$stmt2->bind_param("s",$aadhaarNo);
	
		$stmt2->execute();
		$stmt2->bind_result($count1);
		
		if($stmt2->fetch() && $count1==1)
		{
			$stmt2->close();
			$count1=-1;
			$response['validAadhaar']=true;
			//Country Election
			$stmt3=$conn->prepare("SELECT status FROM Country_Election where id = ?");
			$stmt3->bind_param("d",$electionId);
			
			$stmt3->execute();
			$stmt3->bind_result($status1);

			if($stmt3->fetch() && $status1==1)
			{
				$stmt3->close();
				$response['validElection']=true;
                    
				if($type=="LOK SABHA")
				{
					$response['validType']=true;
                
					$stmt4=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_Approval where election_id = ? AND aadhaar_no = ?");
					$stmt4->bind_param("ds",$electionId,$aadhaarNo);
                
					$stmt4->execute();
					$stmt4->bind_result($count2);
                
					if($stmt4->fetch() && $count2==1)
					{
						$stmt4->close();
						$count2=-1;
						$response['validApproval']=true;
						$response['success']=true;
						goto end;
					}
					else
					{
						$stmt4->close();
					}
				}
			}
			else
			{
				$stmt3->close();
			}
		

			$stmt3=$conn->prepare("SELECT status FROM State_Election where id = ?");
			$stmt3->bind_param("d",$electionId);
			
			$stmt3->execute();
			$stmt3->bind_result($status2);
			$response['validElection']=false;	
			if($stmt3->fetch() && $status2==1)
			{
				$stmt3->close();
				$response['validElection']=true;
				//State Election
				if($type=="VIDHAN SABHA")
				{
					$response['validType']=true;
				
					$stmt4=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_Approval where election_id = ? AND aadhaar_no = ?");
					$stmt4->bind_param("ds",$electionId,$aadhaarNo);
				
					$stmt4->execute();
					$stmt4->bind_result($count3);
				
					if($stmt4->fetch() && $count3==1)
					{
						$stmt4->close();
						$count3=-1;
						$response['validApproval']=true;
						$response['success']=true;
					}
					else
					{
						$stmt4->close();
					}
				}
			}
			else
			{
				$stmt3->close();
			}
		end:
		}
	}
	else
	{
		$stmt1->close();
	}
}
else
{
	$stmt->close();
}
$conn->close();
echo json_encode($response);

?>
