<?php

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
$name=$conn->real_escape_string($_POST["name"]);
$stateElectionId=$conn->real_escape_string($_POST["stateElectionId"]);
$constituencyName=$conn->real_escape_string($_POST["constituencyName"]);
$partyName=$conn->real_escape_string($_POST["partyName"]);
$img=$conn->real_escape_string($_POST["img"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);


$key_name="post_auth_key";
$name=strtoupper($name);
$constituencyName=strtoupper($constituencyName);
$partyName=strtoupper($partyName);


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validElection']=false;
$response['validConstituency']=false;
$response['validParty']=false;
$response['validCandidate']=false;
$response['candidateId']=-1;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    
    $stmt=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count==1)
    {
        $count=-1;
        $stmt->close();
        $response['validAdmin']=true;

        $stmt=$conn->prepare("SELECT COUNT(name) FROM Party WHERE name=?");

        $stmt->bind_param("s", $partyName);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==1)
        {
            $count=-1;
            $stmt->close();
            $response['validParty']=true;

            $stmt=$conn->prepare("SELECT COUNT(id), state_code, type FROM State_Election WHERE id=? AND status=0");
            $stmt->bind_param("d", $stateElectionId);
            $stmt->execute();
            $stmt->bind_result($count, $stateCode, $type);
            $stmt->fetch();
            $stmt->close();

            if($count==1)
            {        
                $count=-1;
                $response['validElection']=true;

                $pattern="";
                if($type=="LOK SABHA")
                {
                    $pattern='LS%';
                }
                else if($type=="VIDHAN SABHA")
                {
                    $pattern='VS%';
                }

                $stmt=$conn->prepare("SELECT COUNT(name), phase_code FROM Constituency WHERE state_code=? AND name=? AND phase_code LIKE ?");
                $stmt->bind_param("sss", $stateCode, $constituencyName, $pattern);
                $stmt->execute();
                $stmt->bind_result($count, $phaseCode);
                $stmt->fetch();
                $stmt->close();

                if($count==1)
                {
                    $count=-1;
                    $response['validConstituency']=true;

                    $stmt=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE state_election_id=? AND phase_code=? AND status=0");
                    $stmt->bind_param("ds", $stateElectionId, $phaseCode);
                    $stmt->execute();
                    $stmt->bind_result($phaseElectionId);
                    $stmt->fetch();
                    $stmt->close();


                    $stmt=$conn->prepare("SELECT COUNT(id) FROM Candidate WHERE election_id=? AND constituency_name=? AND party_name=?");
                    $stmt->bind_param("dss", $phaseElectionId, $constituencyName, $partyName);
                    $stmt->execute();
                    $stmt->bind_result($count);

                    if($stmt->fetch() && $count==0)
                    {
                        $count=-1;
                        $stmt->close();
                        $response['validCandidate']=true;

                        if(strlen($img)>0)
                        {
                            $stmt=$conn->prepare("INSERT INTO Candidate (name, election_id, constituency_name, party_name, img) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("sdsss", $name, $phaseElectionId, $constituencyName, $partyName, $img);                
                        }
                        else
                        {                           
                            $stmt=$conn->prepare("INSERT INTO Candidate (name, election_id, constituency_name, party_name) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("sdss", $name, $phaseElectionId, $constituencyName, $partyName); 
                        }       
                        $stmt->execute();
                        $countInserted=mysqli_affected_rows($conn);
                        $stmt->fetch();
                        $stmt->close();                 

                        if($countInserted==1)
                        {

                            $stmt=$conn->prepare("SELECT id FROM Candidate WHERE election_id=? AND party_name=? AND constituency_name=?");
                            $stmt->bind_param("dss", $phaseElectionId, $partyName, $constituencyName);
                            $stmt->execute();
                            $stmt->bind_result($candidateId);
                            $stmt->fetch();
                            $stmt->close();

                            $response['candidateId']=$candidateId;
                            $response['success']=true;

                        }

                    }
                }


            }
        }
    }
    

    
}
$conn->close();

echo json_encode($response);


?>
