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
$name=$_POST["name"];
$electionId=$_POST["electionId"];
$constituencyName=$_POST["constituencyName"];
$partyName=$_POST["partyName"];
$img=$_POST["img"];
$boothId=$_POST["boothId"];


$key_name="post_auth_key";
$webIp=getServerIp($INTERNAL_AUTH_KEY);


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;
$response['validElection']=false;
$response['validConstituency']=false;
$response['validParty']=false;
$response['validCandidate']=false;
$response['candidateId']=-1;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2 && $webIp==$WEB_IP)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    
    $stmt=$conn->prepare("SELECT COUNT(booth_id), network_address FROM Booth WHERE booth_id=? AND status=1");
    $stmt->bind_param("s", $boothId);
    $stmt->execute();
    $stmt->bind_result($count, $network);

    if($stmt->fetch() && $count==1)
    {
        $count=-1;
        $stmt->close();
        $response['validBooth']=true;

        $stmt=$conn->prepare("SELECT COUNT(name) FROM Party WHERE name=?");

        $stmt->bind_param("s", $partyName);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==1)
        {
            $count=-1;
            $stmt->close();
            $response['validParty']=true;

            $stmt=$conn->prepare("SELECT state_code, phase_code FROM Pub_Govt_Election WHERE id=? AND status=0");
            $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($stateCode, $phaseCode);

            if($stmt->fetch())
            {        
                $stmt->close();
                $response['validElection']=true;

                $stmt=$conn->prepare("SELECT COUNT(name) FROM Constituency WHERE state_code=? AND phase_code=? AND name=?");
                $stmt->bind_param("sss", $stateCode, $phaseCode, $constituencyName);
                $stmt->execute();
                $stmt->bind_result($count);

                if($stmt->fetch() && $count==1)
                {
                    $count=-1;
                    $stmt->close();
                    $response['validConstituency']=true;

                    $stmt=$conn->prepare("SELECT COUNT(id) FROM Candidate WHERE election_id=? AND constituency_name=? AND party_name=?");
                    $stmt->bind_param("dss", $electionId, $constituencyName, $partyName);
                    $stmt->execute();
                    $stmt->bind_result($count);

                    if($stmt->fetch() && $count==0)
                    {
                        $count=-1;
                        $stmt->close();
                        $response['validCandidate']=true;

                        $stmt=$conn->prepare("INSERT INTO Candidate (name, election_id, constituency_name, party_name, img) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sdsss", $name, $electionId, $constituencyName, $partyName, $img);                

                        if($stmt->execute())
                        {
                            $stmt->close();

                            $stmt=$conn->prepare("SELECT id FROM Candidate WHERE election_id=? AND party_name=? AND constituency_name=?");
                            $stmt->bind_param("dss", $electionId, $partyName, $constituencyName);
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
