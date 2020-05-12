<?php

include 'Credentials.php';
include 'Protection.php';

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

    $stmt=$conn->prepare("SELECT id, name, status, year FROM Country_Election WHERE status= 2 OR status=3");

    $stmt->execute();
    $stmt->bind_result($electionId, $name, $status, $year);

    $election=array();

    while($stmt->fetch())
    {
        $temp=array();
        $temp['electionId']=$electionId;
        $temp['name']=$name;
        $temp['status']=$status;
        $temp['type']="LOK SABHA";
        $temp['year']=$year;
        array_push($election, $temp);

        $response['success']=true;
    }
    $stmt->close();


    $stmt=$conn->prepare("SELECT id, name, status, year FROM State_Election WHERE (status=2 OR status=3) AND (country_election_id IS NULL OR country_election_id='')");

    $stmt->execute();
    $stmt->bind_result($electionId, $name, $status, $year);

    while($stmt->fetch())
    {
        $temp=array();
        $temp['electionId']=$electionId;
        $temp['name']=$name;
        $temp['status']=$status;
        $temp['type']="VIDHAN SABHA";
        $temp['year']=$year;
        array_push($election, $temp);
    }
    $stmt->close();

    $response['elections']=$election;

    
}
$conn->close();

echo json_encode($response);


?>