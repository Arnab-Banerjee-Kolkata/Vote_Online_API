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
$level=$conn->real_escape_string($_POST["level"]);            //COUNTRY / STATE / PHASE
$newStatus=$conn->real_escape_string($_POST["newStatus"]);

$key_name="post_auth_key";
$level=strtoupper($level);

$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validLevel']=false;
$response['validElection']=false;
$response['validStatus']=false;
$response['success']=false;

$type="-1";
$countryElectionId=-1;
$stateElectionId=-1;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);
$stmt->fetch();
$stmt->close();

if($postAuthKey1==$postAuthKey2)
{
	$response['validAuth']=true;

    adminAutoLogout($INTERNAL_AUTH_KEY, $conn);
	
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
		
        $oldStatus=-1;
		if($level=='COUNTRY')
        {
            $response['validLevel']=true;

            $stmt=$conn->prepare("SELECT COUNT(id),status FROM Country_Election WHERE id=?");
            $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($count, $oldStatus);
            $stmt->fetch();
            $stmt->close();

            $type="LOK SABHA";
            $countryElectionId=$electionId;

        }
        else if($level=='STATE')
        {
            $response['validLevel']=true;

            $stmt=$conn->prepare("SELECT COUNT(id),status,type,country_election_id FROM State_Election WHERE id=?");
            $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($count,$oldStatus,$type,$countryElectionId);
            $stmt->fetch();
            $stmt->close();

            $stateElectionId=$electionId;

        }
        else if($level=='PHASE')
        {
            $response['validLevel']=true;

            $stmt=$conn->prepare("SELECT COUNT(id),status,type,state_election_id FROM Pub_Govt_Election WHERE id=?");
            $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($count,$oldStatus,$type,$stateElectionId);
            $stmt->fetch();
            $stmt->close();

            if($type=='LOK SABHA')
            {
                $stmt=$conn->prepare("SELECT country_election_id FROM State_Election WHERE id=?");
                $stmt->bind_param("d", $stateElectionId);
                $stmt->execute();
                $stmt->bind_result($countryElectionId);
                $stmt->fetch();
                $stmt->close();
            }

        }

        if($count==1)
        {
            $count=-1;
            $response['validElection']=true;

            if($level=='COUNTRY')
            {
                if($oldStatus!=3 && $newStatus==4)
                {
                    $response['validStatus']=true;
                }
            }
            else if($level=='STATE')
            {
                if($oldStatus!=3 && $newStatus==4)
                {
                    $response['validStatus']=true;
                }
            }
            else if($level=='PHASE')
            {
                if(($oldStatus==0 && $newStatus==1) || ($oldStatus==1 && $newStatus==2) || ($oldStatus!=3 && $newStatus==4) || ($oldStatus==5 && $newStatus==3))
                {
                    $response['validStatus']=true;
                }
            }

            if($response['validStatus'])
            {
                $affected=0;
                if($level=='COUNTRY')
                {
                    $stmt=$conn->prepare("UPDATE Country_Election SET status=? WHERE id=?");
                    $stmt->bind_param("dd", $newStatus, $electionId);
                    $stmt->execute();
                    $affected=mysqli_affected_rows($conn);
                    $stmt->fetch();
                    $stmt->close();

                    //Cancel subordinate state elections
                    $stmt=$conn->prepare("UPDATE State_Election SET status=4 WHERE type=? AND country_election_id=?");
                    $stmt->bind_param("sd", $type, $countryElectionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();

                    //Cancel subordinate phase elections
                    $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=4 WHERE type=? AND state_election_id IN (
	SELECT id FROM State_Election WHERE country_election_id=?    
)");
                    $stmt->bind_param("sd", $type, $countryElectionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();

                    $response['success']=true;

                }
                else if($level=='STATE')
                {
                    $count=-1;

                    $stmt=$conn->prepare("UPDATE State_Election SET status=? WHERE id=?");
                    $stmt->bind_param("dd", $newStatus, $electionId);
                    $stmt->execute();
                    $affected=mysqli_affected_rows($conn);
                    $stmt->fetch();
                    $stmt->close();

                    //Cancel all subordinate phase elections
                    $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=4 WHERE type=? AND state_election_id=?");
                    $stmt->bind_param("sd", $type, $stateElectionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();


                    //Cancel country election if all siblings are cancelled
                    if($type=="LOK SABHA")
                    {
                        $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE status<>4 AND type=? AND country_election_id = (
	SELECT country_election_id FROM State_Election WHERE id=?    
)");
                        $stmt->bind_param("sd", $type, $electionId);
                        $stmt->execute();
                        $stmt->bind_result($count);
                        $stmt->fetch();
                        $stmt->close();

                        if($count==0)   //All siblings cancelled
                        {
                            $count=-1;
                            $stmt=$conn->prepare("UPDATE Country_Election SET status=4 WHERE id=?");
                            $stmt->bind_param("d", $countryElectionId);
                            $stmt->execute();
                            $stmt->fetch();
                            $stmt->close();
                        }
                        $count=-1;
                    }
                    $response['success']=true;

                }
                else if($level=='PHASE')
                {
                    $count=-1;
                    //Different methods for different types of cases
                    if($newStatus==1)   //case 0->1
                    {
                        //phase
                        $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=1 WHERE id=?");
                        $stmt->bind_param("d", $electionId);
                        $stmt->execute();
                        $stmt->fetch();
                        $stmt->close();

                        //state
                        $stmt=$conn->prepare("UPDATE State_Election SET status=1 WHERE id=?");
                        $stmt->bind_param("d", $stateElectionId);
                        $stmt->execute();
                        $stmt->fetch();
                        $stmt->close();

                        if($type=="LOK SABHA")
                        {
                            //country
                            $stmt=$conn->prepare("UPDATE Country_Election SET status=1 WHERE id=?");
                            $stmt->bind_param("d", $countryElectionId);
                            $stmt->execute();
                            $stmt->fetch();
                            $stmt->close();
                        }

                    }
                    else        //cases 1->2, 5->3, y->4
                    {
                        $count=-1;

                        $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=? WHERE id=?");
                        $stmt->bind_param("dd", $newStatus, $electionId);
                        $stmt->execute();
                        $stmt->fetch();
                        $stmt->close();

                        //check siblings
                        $stmt=$conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election WHERE status<>? AND type=? AND state_election_id=?");
                        $stmt->bind_param("dsd", $newStatus, $type, $stateElectionId);
                        $stmt->execute();
                        $stmt->bind_result($count);
                        $stmt->fetch();
                        $stmt->close();

                        if($count==0)       //All siblings updated
                        {
                            //Update state
                            $stmt=$conn->prepare("UPDATE State_Election SET status=? WHERE id=?");
                            $stmt->bind_param("dd", $newStatus, $stateElectionId);
                            $stmt->execute();
                            $stmt->fetch();
                            $stmt->close();

                            if($type=="LOK SABHA")
                            {
                                $count=-1;

                                //Check siblings
                                $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE status<>? AND type=? AND country_election_id=?");
                                $stmt->bind_param("dsd", $newStatus, $type, $countryElectionId);
                                $stmt->execute();
                                $stmt->bind_result($count);
                                $stmt->fetch();
                                $stmt->close();

                                if($count==0)       //All siblings updated
                                {
                                    $stmt=$conn->prepare("UPDATE Country_Election SET status=? WHERE id=?");
                                    $stmt->bind_param("dd", $newStatus, $countryElectionId);
                                    $stmt->execute();
                                    $stmt->fetch();
                                    $stmt->close();
                                }
                            }
                        }
                    }
                    $response['success']=true;
                }
            }
        }
	}
}
$conn->close();
echo json_encode($response);
?>
