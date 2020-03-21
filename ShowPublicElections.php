<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);



if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $stmt=$conn->prepare("SELECT Pub_Govt_Election.id, State.name, Pub_Govt_Election.phase_code, Pub_Govt_Election.type, Pub_Govt_Election.status, Pub_Govt_Election.start_time, Pub_Govt_Election.end_time FROM Pub_Govt_Election, State WHERE Pub_Govt_Election.state_code=State.code ORDER BY Pub_Govt_Election.end_time");

    $stmt->execute();
    $stmt->bind_result($electionId, $state, $phaseCode, $type, $status, $startTime, $endTime);

    $election=array();

    while($stmt->fetch())
    {
        $temp=array();
        $temp['electionId']=$electionId;
        $temp['state']=$state;
        $temp['phaseCode']=$phaseCode;
        $temp['type']=$type;
        $temp['status']=$status;
        $temp['startTime']=$startTime;
        $temp['endTime']=$endTime;
        array_push($election, $temp);
    }
    $stmt->close();

    $response['success']=true;

    $response['elections']=$election;

    
}
$conn->close();

echo json_encode($response);


?>