<?php

include 'Credentials.php';

//ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validElectionId']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $phase=array();


    $stmt=$conn->prepare("SELECT State.name, Pub_Govt_Election.phase_code, Pub_Govt_Election.type, Pub_Govt_Election.status,        Pub_Govt_Election.start_time, Pub_Govt_Election.end_time FROM Pub_Govt_Election, State WHERE Pub_Govt_Election.id=? AND State.code=Pub_Govt_Election.state_code");
    $stmt->bind_param("d", $electionId);
    $stmt->execute();
    $stmt->bind_result($stateName, $phaseCode, $type, $status, $startDateTime, $endDateTime);

    if($stmt->fetch())
    {
        $response['validElectionId']=true;
        $stmt->close();

        $response['stateName']=$stateName;
        $response['phaseCode']=$phaseCode;
        $response['type']=$type;
        $response['status']=$status;
        $response['startDateTime']=$startDateTime;
        $response['endDateTime']=$endDateTime;

        $response['success']=true;

    }
    

    
    
}
$conn->close();

echo json_encode($response);


?>