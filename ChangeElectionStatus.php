<?php

include 'Credentials.php';
include 'Protection.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);
$type=$conn->real_escape_string($_POST["type"]);
$newStatus=$conn->real_escape_string($_POST["newStatus"]);

$key_name="post_auth_key";
checkServerIp($INTERNAL_AUTH_KEY);

$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validType']=false;
$response['validElection']=false;
$response['validStatus']=false;
$response['success']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt1=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
	$stmt1->bind_param("s",$adminId);
	$stmt1->execute();
	$stmt1->bind_result($count1);
	
	if($stmt1->fetch() && $count1==1)
	{
		$stmt1->close();
		$count1=-1;
		$response['validAdmin']=true;
		
		if($type=="LOK SABHA")
		{
			$response['validType']=true;
			
			$stmt2=$conn->prepare("SELECT COUNT(id),status FROM Country_Election WHERE id=?");
			$stmt2->bind_param("d",$electionId);
			$stmt2->execute();
			$stmt2->bind_result($count2,$currentStatus);
			
			if($stmt2->fetch() && $count2==1)
			{
				$stmt2->close();
				$count2=-1;
				$response['validElection']=true;
					
				if((($newStatus==($currentStatus+1) && $newStatus!=3)||($newStatus==4 && $currentStatus!=4)) && ($newStatus>=0 && $newStatus<=4))
					{
						$response['validStatus']=true;
						
						$stmt3=$conn->prepare("UPDATE Country_Election SET status=? WHERE id=?");
						$stmt3->bind_param("dd",$newStatus,$electionId);
						$stmt3->execute();
						$stmt3->fetch();
						$stmt3->close();
						
						$response['success']=true;
					}
			}
			else
			{
				$stmt2->close();
			}
		}
		elseif($type=="VIDHAN SABHA")
		{
			$response['validType']=true;
			
			$stmt4=$conn->prepare("SELECT COUNT(id),status FROM State_Election WHERE id=?");
			$stmt4->bind_param("d",$electionId);
			$stmt4->execute();
			$stmt4->bind_result($count3,$currentStatus);
			
			if($stmt4->fetch() && $count3==1)
			{
				$stmt4->close();
				$count3=-1;
				$response['validElection']=true;
				
				if((($newStatus==($currentStatus+1) && $newStatus!=3)||($newStatus==4 && $currentStatus!=4)) && ($newStatus>=0 && $newStatus<=4))
					{
						$response['validStatus']=true;
						
						$stmt5=$conn->prepare("UPDATE State_Election SET status=? WHERE id=?");
						$stmt5->bind_param("dd",$newStatus,$electionId);
						$stmt5->execute();
						$stmt5->fetch();
						$stmt5->close();
						
						$response['success']=true;
					}
			}
			else
			{
				$stmt4->close();
			}			
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