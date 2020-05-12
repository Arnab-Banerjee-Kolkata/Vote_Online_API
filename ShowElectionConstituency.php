<?php

include 'Credentials.php';
include 'Protection.php';

//ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}

$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validElection']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;


    $stmt=$conn->prepare("SELECT state_code, phase_code FROM Pub_Govt_Election WHERE id=?");
    $stmt->bind_param("d", $electionId);
    $stmt->execute();
    $stmt->bind_result($stateCode, $phaseCode);

    if($stmt->fetch())
    {
        $stmt->close();
        $response['validElection']=true;

        $constituencyList=array();

        $stmt=$conn->prepare("SELECT name FROM Constituency WHERE state_code=? AND phase_code=?");
        $stmt->bind_param("ss",$stateCode, $phaseCode);
        $stmt->execute();
        $stmt->bind_result($name);

        while($stmt->fetch())
        {
            array_push($constituencyList, $name);
        }
        $stmt->close();

        $response['constituencyList']=$constituencyList;

        $response['success']=true;
    }
}
$conn->close();

echo json_encode($response);


?>