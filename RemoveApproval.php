<?php

include 'Credentials.php';
include 'Protection.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$boothId=$_POST["boothId"];
$electionId=$_POST["electionId"];

$key_name="post_auth_key";
checkServerIp();

$response=array();
$response['validAuth']=false;
$response['validBooth']=false;
$response['validElection']=false;
$response['success']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;
	
	$stmt2=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=?");
	$stmt2->bind_param("s",$boothId);
	$stmt2->execute();
	$stmt2->bind_result($count);
	
	if($stmt2->fetch() && $count==1)
	{
		$stmt2->close();
		$count=-1;
		$response['validBooth']=true;
		
		$stmt3=$conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election WHERE id=?");
		$stmt3->bind_param("d",$electionId);
		$stmt3->execute();
		$stmt3->bind_result($count2);
		
		if($stmt3->fetch() && $count2==1)
		{
			$stmt3->close();
            $count2=-1;
			$response['validElection']=true;
			
			$stmt4=$conn->prepare("DELETE FROM Govt_Approval WHERE election_id=? AND booth_id=?");
			$stmt4->bind_param("ds",$electionId,$boothId);
			$stmt4->execute();

            if($conn->affected_rows == 1)
            {
                $response['success']=true;
            }
            $stmt4->fetch();
            $stmt4->close();
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
