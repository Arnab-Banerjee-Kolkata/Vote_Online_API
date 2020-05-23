<?php
//Removes approval, removes nota and adds vote.

include 'Credentials.php';
include 'Protection.php';

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$vbConn=new mysqli($vbServerName, $vbUserName, $vbPassword, $vbDbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ");
}

if($vbConn->connect_error){
    die("Connection failed: ");
}


$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$boothId=$conn->real_escape_string($_POST["boothId"]);
$enVote=$vbConn->real_escape_string($_POST["enVote"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;
$response['validIntegrity']=false;
$response['validApproval']=false;
$response['deleteApproval']=false;
$response['validGarbage']=false;


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
        
        
        $stmt=$conn->prepare("SELECT election_id, constituency_name FROM Govt_Approval WHERE booth_id=?");
        $stmt->bind_param("s", $boothId);
        $stmt->execute();
        $stmt->bind_result($phaseElectionId, $constituencyName);
        $stmt->fetch();
        $stmt->close();   



        $stmt=$vbConn->prepare("SELECT SUM(count) FROM Govt_Vote WHERE phase_election_id=?");
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


        if($count1==$count2)
        {
            $count1=-1;
            $count2=-1;
            $response['validIntegrity']=true;         


            $stmt=$conn->prepare("SELECT COUNT(booth_id) FROM Govt_Approval WHERE booth_id=? AND election_id=? AND constituency_name=?");
            $stmt->bind_param("sds", $boothId, $phaseElectionId, $constituencyName);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if($count==1)
            {
                $count=-1;
                $response['validApproval']=true;


                $stmt=$conn->prepare("DELETE FROM Govt_Approval WHERE booth_id=? AND election_id=?");
                $stmt->bind_param("sd", $boothId, $phaseElectionId);
                $stmt->execute(); 
                $deletedRows= $conn->affected_rows;                      
                $stmt->fetch();
                $stmt->close();

                if($deletedRows == 1)
                {
                    $response['deleteApproval']=true;
                    $deletedRows=-1;

                    
                    $stmt=$vbConn->prepare("SELECT count FROM Govt_Vote WHERE phase_election_id=? AND en_vote=? AND constituency_name=?");
                    $stmt->bind_param("dss", $phaseElectionId, $garbage, $constituencyName);
                    $stmt->execute();   
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();
                    
                    if($count>=1)
                    {
                        $response['validGarbage']=true;
                    
                    
                        $count--;
                    
                        $stmt=$vbConn->prepare("UPDATE Govt_Vote SET count=? WHERE en_vote=? AND phase_election_id=?");
                        $stmt->bind_param("dsd", $count, $garbage, $phaseElectionId);
                        $stmt->execute();                        
                        $stmt->fetch();
                        $stmt->close();
                        
                        
                        $count=-1;
                        
                        
                        $stmt=$vbConn->prepare("SELECT count FROM Govt_Vote WHERE phase_election_id=? AND en_vote=? AND constituency_name=?");
                        $stmt->bind_param("dss", $phaseElectionId, $enVote, $constituencyName);
                        $stmt->execute();   
                        $stmt->bind_result($count);
                        $stmt->fetch();
                        $stmt->close();
                        
                        if($count>=1)
                        {                
                            $count++;            
                            $stmt=$vbConn->prepare("UPDATE Govt_Vote SET count=? WHERE phase_election_id=? AND constituency_name=? AND en_vote=?");
                            $stmt->bind_param("ddss", $count, $phaseElectionId, $constituencyName, $enVote);
                            $stmt->execute();
                            $stmt->fetch();
                            $stmt->close();               

                            $response['success']=true;
                        }
                        else
                        {

                            $stmt=$vbConn->prepare("INSERT INTO Govt_Vote (phase_election_id, en_vote, constituency_name, count) VALUES (?, ?, ?, 1)");
                            $stmt->bind_param("dss", $phaseElectionId, $enVote, $constituencyName);
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

    
}
$conn->close();
$vbConn->close();

echo json_encode($response);


?>
