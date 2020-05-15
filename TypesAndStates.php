<?php

include 'Credentials.php';
include 'Protection.php';

foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

    $election=array();
    $state=array();


    $stmt=$conn->prepare("SELECT DISTINCT type FROM Pub_Govt_Election ORDER BY type");
    $stmt->execute();
    $stmt->bind_result($type);
    
    while($stmt->fetch())
    {
        $temp=array();
        $temp['type']=$type;

        array_push($election, $temp);
    }
    $response['electionNames']=$election;

    $stmt->close();

    $stmt=$conn->prepare("SELECT code, name FROM State ORDER BY name");
    $stmt->execute();
    $stmt->bind_result($code, $name);

    while($stmt->fetch())
    {
        $temp=array();
        $temp['code']=$code;
        $temp['name']=$name;

        array_push($state, $temp);
    }
    $response['states']=$state;


    $response['success']=true;
    

    
    
}
$conn->close();

echo json_encode($response);


?>
