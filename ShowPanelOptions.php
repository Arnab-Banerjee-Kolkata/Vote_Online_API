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
					
					//phaseElectionId starts here
					
					$stmt5=$conn->prepare("SELECT lok_sabha_constituency FROM Govt_DB WHERE aadhaar_no = ?");
					$stmt5->bind_param("s",$aadhaarNo);
					$stmt5->execute();
					$stmt5->bind_result($ls_const);
                    			$stmt5->fetch();
					$stmt5->close();
					
					$stmt6=$conn->prepare("SELECT phase_code,state_code FROM Constituency WHERE name = ?");
					$stmt6->bind_param("s",$ls_const);
					$stmt6->execute();
					$stmt6->bind_result($p_code,$s_code);
                    			$stmt6->fetch();
					$stmt6->close();
					
					$stmt7=$conn->prepare("SELECT id FROM State_Election WHERE state_code = ? AND status = 1 AND country_election_id = ?");
					$stmt7->bind_param("ss",$s_code,$electionId);
					$stmt7->execute();
					$stmt7->bind_result($s_id);
                    			$stmt7->fetch();
					$stmt7->close();
					
					$stmt8=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE state_election_id = ? AND phase_code = ? AND status = 1");
					$stmt8->bind_param("ds",$s_id,$p_code);
					$stmt8->execute();
					$stmt8->bind_result($p_id);
                    			$stmt8->fetch();
					$stmt8->close();
					
					$response['phaseElectionId']=$p_id;
					//phaseElectionId ends here
                
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
        else
        {
		$stmt2->close();
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
