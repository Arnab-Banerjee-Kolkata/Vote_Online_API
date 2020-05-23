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
$stateElectionId=$conn->real_escape_string($_POST["stateElectionId"]);
$phaseCode=$conn->real_escape_string($_POST["phaseCode"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validStateElection']=false;
$response['validPhaseCode']=false;
$response['validElection']=false;
$response['electionId']=-1;

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
    
    
        $stmt=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count==1)
    {
        $count=-1;
        $stmt->close();
        $response['validAdmin']=true;
    

        $stmt=$conn->prepare("SELECT COUNT(id), state_code, type from State_Election WHERE id=?");

        $stmt->bind_param("d", $stateElectionId);
        $stmt->execute();
        $stmt->bind_result($count, $stateCode, $type);

        if(strlen($type)==0)
            $type="LOK SABHA";

        if($stmt->fetch() && $count==1)
        {
            $stmt->close();
            $response['validStateElection']=true;
            $count=-1;


            $stmt=$conn->prepare("SELECT COUNT(code) FROM Phase WHERE code=? AND state_code=? AND type=?");
            $stmt->bind_param("sss", $phaseCode, $stateCode, $type);
            $stmt->execute();
            $stmt->bind_result($count);

            if($stmt->fetch() && $count==1)
            {
                $stmt->close();
                $count=-1;
                $response['validPhaseCode']=true;


                $stmt=$conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election WHERE state_election_id=? AND phase_code=?");
                $stmt->bind_param("ds", $stateElectionId, $phaseCode);
                $stmt->execute();
                $stmt->bind_result($count);

                if($stmt->fetch() && $count==0)
                {
                    $stmt->close();
                    $count=-1;
                    $response['validElection']=true;            


                    $stmt=$conn->prepare("INSERT INTO Pub_Govt_Election (state_code, phase_code, type, status, state_election_id) VALUES (?, ?, ?, 0, ?)");
                    $stmt->bind_param("sssd", $stateCode, $phaseCode, $type, $stateElectionId);
                    $stmt->execute();

                    $stmt->close();

                    $stmt=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE state_election_id=? AND phase_Code=?");
                    $stmt->bind_param("ds", $stateElectionId, $phaseCode);
                    $stmt->execute();
                    $stmt->bind_result($response['electionId']);
                    $stmt->fetch();
                    $stmt->close();

                    if($response['electionId']!=-1)
                        $response['success']=true;
                }
            }
        }
    }

    
}
$conn->close();

echo json_encode($response);


?>
