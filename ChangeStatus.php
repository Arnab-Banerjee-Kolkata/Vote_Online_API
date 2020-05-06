<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$postAuthKey1=$_POST["postAuthKey"];
$adminId=$_POST["adminId"];
$electionId=$_POST["electionId"];
$type=$_POST["type"];
$newStatus=$_POST["newStatus"];

$key_name="post_auth_key";

$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validElection']=false;
$response['validType']=false;
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
	
	$stmt1=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=?");
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
			
			$stmt2=$conn->prepare("SELECT COUNT(id) FROM Country_Election WHERE id=?");
			$stmt2->bind_param("d",$electionId);
			$stmt2->execute();
			$stmt2->bind_result($count2);
			
			if($stmt2->fetch() && $count2==1)
			{
				$stmt2->close();
				$count2=-1;
				$response['validElection']=true;
				
				$stmt3=$conn->prepare("SELECT status FROM Country_Election WHERE id=?");
				$stmt3->bind_param("d",$electionId);
				$stmt3->execute();
				$stmt3->bind_result($currentStatus);
				$stmt3->fetch();
					
				if(($newStatus==($currentStatus+1) && ($newStatus!=3 && $currentStatus!=2))||($newStatus==4 && $currentStatus!=4))
					{
						$stmt3->close();
						$response['validStatus']=true;
						
						$stmt4=$conn->prepare("UPDATE Country_Election SET status=? WHERE id=?");
						$stmt4->bind_param("dd",$newStatus,$electionId);
						$stmt4->execute();
						$stmt4->fetch();
						$stmt4->close();
						
						$response['success']=true;
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
		else//if($type=="VIDHAN SABHA")
		{
			$response['validType']=true;
			
			$stmt5=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE id=?");
			$stmt5->bind_param("d",$electionId);
			$stmt5->execute();
			$stmt5->bind_result($count3);
			
			if($stmt5->fetch() && $count3==1)
			{
				$stmt5->close();
				$count3=-1;
				$response['validElection']=true;
				
				$stmt6=$conn->prepare("SELECT status FROM State_Election WHERE id=?");
				$stmt6->bind_param("d",$electionId);
				$stmt6->execute();
				$stmt6->bind_result($currentStatus);
				$stmt6->fetch();
				
				if(($newStatus==($currentStatus+1) && ($newStatus!=3 && $currentStatus!=2))||($newStatus==4 && $currentStatus!=4))
					{
						$stmt6->close();
						$response['validStatus']=true;
						
						$stmt7=$conn->prepare("UPDATE State_Election SET status=? WHERE id=?");
						$stmt7->bind_param("dd",$newStatus,$electionId);
						$stmt7->execute();
						$stmt7->fetch();
						$stmt7->close();
						
						$response['success']=true;
					}
					else
					{
						$stmt6->close();
					}
			}
			else
			{
				$stmt5->close();
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
