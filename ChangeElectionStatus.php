<?php

//  CANNOT CANCEL ELECTION WHOSE RESULT HAS BEEN DECLARED. CANNOT CHGANGE STATUS OF CANCELLED ELECTION.

include 'Credentials.php';
include 'Protection.php';

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
$adminId=$conn->real_escape_string($_POST["adminId"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);
$type=$conn->real_escape_string($_POST["type"]);            //COUNTRY / STATE / PHASE
$newStatus=$conn->real_escape_string($_POST["newStatus"]);

$key_name="post_auth_key";

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
$stmt->fetch();
$stmt->close();

if($postAuthKey1==$postAuthKey2)
{
	$response['validAuth']=true;
	
	$stmt1=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
	$stmt1->bind_param("s",$adminId);
	$stmt1->execute();
	$stmt1->bind_result($count1);
    $stmt1->fetch();
    $stmt1->close();
	
	if($count1==1)
	{
		$count1=-1;
		$response['validAdmin']=true;
		
		if($type=="PHASE")
        {
            $response['validType']=true;
            
            $stmt=$conn->prepare("SELECT COUNT(id), status FROM Pub_Govt_Election WHERE id=? AND (status<>4 OR status<>3)");
            $stmt->bind_param("d",$electionId);
            $stmt->execute();
            $stmt->bind_result($count, $oldStatus);
            $stmt->fetch();
            $stmt->close();
            
            if($count==1)
            {
                $count=-1;
                $response['validElection']=true;
                
                if(($oldStatus==0 && $newStatus==1) || ($oldStatus==1 && $newStatus==2) || ($oldStatus==5 && $newStatus==3) || (($oldStatus!=3 && $oldStatus!=4) && $newStatus==4))
                {
                    $response['validStatus']=true;
                    
                    $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=? WHERE id=?");
                    $stmt->bind_param("dd",$newStatus, $electionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                    
                    $response['success']=true;
                }
            }
        }
        else if($type=="STATE")
        {
            $response['validType']=true;
            
            $stmt=$conn->prepare("SELECT COUNT(id), status FROM State_Election WHERE id=? AND (status<>4 OR status<>3)");
            $stmt->bind_param("d",$electionId);
            $stmt->execute();
            $stmt->bind_result($count, $oldStatus);
            $stmt->fetch();
            $stmt->close();
            
            if($count==1)
            {
                $count=-1;
                $response['validElection']=true;
                
                if(($oldStatus==0 && $newStatus==1) || ($oldStatus==1 && $newStatus==2) || ($oldStatus==5 && $newStatus==3) || (($oldStatus!=3 && $oldStatus!=4) && $newStatus==4))
                {
                    $response['validStatus']=true;
                    
                    $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=? WHERE state_election_id=?");
                    $stmt->bind_param("dd",$newStatus, $electionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                    
                    $stmt=$conn->prepare("UPDATE State_Election SET status=? WHERE id=?");
                    $stmt->bind_param("dd",$newStatus, $electionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                    
                    $response['success']=true;
                }
            }
        }
        else if($type=="COUNTRY")
        {
            $response['validType']=true;
            
            $stmt=$conn->prepare("SELECT COUNT(id), status FROM Country_Election WHERE id=? AND (status<>4 OR status<>3)");
            $stmt->bind_param("d",$electionId);
            $stmt->execute();
            $stmt->bind_result($count, $oldStatus);
            $stmt->fetch();
            $stmt->close();
            
            if($count==1)
            {
                $count=-1;
                $response['validElection']=true;
                
                if(($oldStatus==0 && $newStatus==1) || ($oldStatus==1 && $newStatus==2) || ($oldStatus==5 && $newStatus==3) || (($oldStatus!=3 && $oldStatus!=4) && $newStatus==4))
                {
                    $response['validStatus']=true;
                    
                    $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=? WHERE state_election_id IN (
	SELECT id FROM State_Election WHERE country_election_id=?
)");
                    $stmt->bind_param("dd",$newStatus, $electionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                    
                    $stmt=$conn->prepare("UPDATE State_Election SET status=? WHERE country_election_id=?");
                    $stmt->bind_param("dd",$newStatus, $electionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                    
                    $stmt=$conn->prepare("UPDATE Country_Election SET status=? WHERE id=?");
                    $stmt->bind_param("dd",$newStatus, $electionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                    
                    $response['success']=true;
                }
            }
        }
	}
}
$conn->close();
echo json_encode($response);
?>
