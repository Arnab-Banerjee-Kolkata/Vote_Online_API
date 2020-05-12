<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);
$startDateTime=$conn->real_escape_string($_POST["startDateTime"]);
$endDateTime=$conn->real_escape_string($_POST["endDateTime"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validTime']=false;
$response['validElection']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $stmt4=$conn->prepare("SELECT state_code, type FROM Pub_Govt_Election WHERE id=?");

    $stmt4->bind_param("d", $electionId);
    $stmt4->execute();
    $stmt4->bind_result($stateCode, $type);

    if($stmt4->fetch())
    {
        $stmt4->close();
        $response['validElection']=true;

        $stmt=$conn->prepare("SELECT COUNT(id) FROM `Pub_Govt_Election` WHERE ((start_time<=? AND end_time>=?) OR (start_time>=? AND start_time<?) OR (end_time>? AND end_time<=?)) AND state_code=? AND type=? AND id<>?");

        $stmt->bind_param("ssssssssd", $startDateTime, $endDateTime, $startDateTime, $endDateTime, $startDateTime, $endDateTime, $stateCode, $type, $electionId);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==0)
        {
            $stmt->close();
            $response['validTime']=true;

            $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET start_time=?, end_time=? WHERE id=?");
            $stmt->bind_param("ssd", $startDateTime, $endDateTime, $electionId);
            $stmt->execute();
            
            $stmt->close();

            $response['success']=true;
        }
    
    }
    
}
$conn->close();

echo json_encode($response);


?>