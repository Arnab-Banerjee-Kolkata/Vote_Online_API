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
    die("Connection failed: ");
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

    $stateList=array();


    $stmt=$conn->prepare("SELECT name, code FROM State ORDER BY name");
    $stmt->execute();
    $stmt->bind_result($name, $code);

    while($stmt->fetch())
    {
        $state=array();
        $state['name']=$name;
        $state['code']=$code;

        array_push($stateList, $state);
    }
    $stmt->close();

    $response['stateList']=$stateList;

    $response['success']=true;
}
$conn->close();

echo json_encode($response);


?>
