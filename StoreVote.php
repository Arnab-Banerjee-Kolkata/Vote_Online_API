<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$phaseElectionId=$_POST["phaseElectionId"];
$constituencyName=$_POST["constituencyName"];
$boothId=$_POST["boothId"];
$enVote=$_POST["enVote"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;
$response['validPhaseElection']=false;
$response['validConstituency']=false;
$response['validIntegrity']=false;
$response['validApproval']=false;


$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);

$startDT=$startDate." ".$startTime;
$endDT=$endDate." ".$endTime;

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
    

        $stmt=$conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election WHERE id=? AND status=1");

        $stmt->bind_param("d", $phaseElectionId);
        $stmt->execute();
        $stmt->bind_result($count);


        if($stmt->fetch() && $count==1)
        {
            $stmt->close();
            $response['validPhaseElection']=true;
            $count=-1;


            $stmt=$conn->prepare("SELECT COUNT(name) FROM Constituency WHERE name=? AND state_code = (
	SELECT state_code FROM Pub_Govt_Election WHERE id=?    
) AND phase_code = (
	SELECT phase_code FROM Pub_Govt_Election WHERE id=?    
)");
            $stmt->bind_param("sdd", $constituencyName, $phaseElectionId, $phaseElectionId);
            $stmt->execute();
            $stmt->bind_result($count);

            if($stmt->fetch() && $count==1)
            {
                $stmt->close();
                $count=-1;
                $response['validConstituency']=true;


                $stmt=$conn->prepare("SELECT COUNT(en_vote) FROM Govt_Vote WHERE phase_election_id=?");
                $stmt->bind_param("d", $phaseElectionId);
                $stmt->execute();
                $stmt->bind_result($count1);
                $stmt->fetch();
                $stmt->close();
                
                $stmt=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_Vote_Status WHERE election_id=?");
                $stmt->bind_param("d", $phaseElectionId);
                $stmt->execute();
                $stmt->bind_result($count2);
                $stmt->fetch();
                $stmt->close();
                

                if($count1+1==$count2)
                {
                    $count1=-1;
                    $count2=-1;
                    $response['validIntegrity']=true;         
                    
                    
                    $stmt=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_Approval WHERE booth_id=? AND election_id=? AND aadhaar_no IN (
	SELECT aadhaar_no FROM Govt_DB WHERE lok_sabha_constituency=? OR vidhan_sabha_constituency=?    
)");
                    $stmt->bind_param("sdss", $boothId, $phaseElectionId, $constituencyName, $constituencyName);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();

                    if($count==1)
                    {
                        $count=-1;
                        $response['validApproval']=true;
                        
                        
                        $stmt=$conn->prepare("DELETE FROM Govt_Approval WHERE booth_id=? AND election_id=? AND aadhaar_no IN (
	SELECT aadhaar_no FROM Govt_DB WHERE lok_sabha_constituency=? OR vidhan_sabha_constituency=?    
) LIMIT 1");
                        $stmt->bind_param("sdss", $boothId, $phaseElectionId, $constituencyName, $constituencyName);
                        $stmt->execute();                        
                        $stmt->fetch();
                        $stmt->close();
                        
                        

                        $stmt=$conn->prepare("INSERT INTO Govt_Vote (phase_election_id, en_vote) VALUES (?, ?)");
                        $stmt->bind_param("ds", $phaseElectionId, $enVote);
                        $stmt->execute();
                        $stmt->fetch();
                        $stmt->close();               

                        $response['success']=true;
                    }
                }
            }
        }
    }

    
}
$conn->close();

echo json_encode($response);


?>