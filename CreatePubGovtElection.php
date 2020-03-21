<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$stateCode=$_POST["stateCode"];
$phaseCode=$_POST["phaseCode"];
$type=$_POST["type"];
$startTime=$_POST["startTime"];
$startDate=$_POST["startDate"];
$endTime=$_POST["endTime"];
$endDate=$_POST["endDate"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validTime']=false;
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

    $stmt=$conn->prepare("SELECT COUNT(id) FROM `Pub_Govt_Election` WHERE ((start_time<=? AND end_time>=?) OR (start_time>=? AND start_time<?) OR (end_time>? AND end_time<=?)) AND state_code=? AND type=?");

    $stmt->bind_param("ssssssss", $startDT, $endDT, $startDT, $endDT, $startDT, $endDT, $stateCode, $type);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count==0)
    {
        $stmt->close();
        $response['validTime']=true;

        $stmt=$conn->prepare("INSERT INTO Pub_Govt_Election (state_code, phase_code, type, status, start_time, end_time) VALUES (?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("sssss", $stateCode, $phaseCode, $type, $startDT, $endDT);
        $stmt->execute();
        
        $stmt->close();

        $stmt=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE state_code=? AND phase_Code=? AND type=? AND status=0 AND start_time=? AND end_time=?");
        $stmt->bind_param("sssss", $stateCode, $phaseCode, $type, $startDT, $endDT);
        $stmt->execute();
        $stmt->bind_result($response['electionId']);
        $stmt->fetch();
        $stmt->close();

        $response['success']=true;
    }
    

    
}
$conn->close();

echo json_encode($response);


?>