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
$type=$conn->real_escape_string($_POST["type"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);


$key_name="post_auth_key";
$type=strtoupper($type);


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validType']=false;
$response['validElection']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    adminAutoLogout($INTERNAL_AUTH_KEY, $conn);

    $stmt=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if($count==1)
    {
        $response['validAdmin']=true;
        $count=-1;

        if($type=="LOK SABHA" || $type=="VIDHAN SABHA")
        {
            $response['validType']=true;
            $count=-1;

            $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE id=? AND type=?");
            $stmt->bind_param("ds", $stateElectionId, $type);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if($count==1)
            {
                $response['validElection']=true;

                $phaseElections=array();

                $stmt=$conn->prepare("SELECT phase_code, status FROM Pub_Govt_Election WHERE state_election_id=?");
                $stmt->bind_param("d", $stateElectionId);
                $stmt->execute();
                $stmt->bind_result($phaseCode, $status);
                
                while($stmt->fetch())
                {
                    $phaseElection=array();
                    $phaseElection['phaseCode']=$phaseCode;
                    $phaseElection['status']=$status;

                    array_push($phaseElections, $phaseElection);
                }

                $stmt->close();

                $response['phaseElections']=$phaseElections;
                $response['success']=true;
            }

        }

    }
    

    
    
}
$conn->close();

echo json_encode($response);


?>
