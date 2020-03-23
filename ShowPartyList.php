<?php

include 'Credentials.php';

//ini_set('display_errors', 1);

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

    $party=array();


    $stmt=$conn->prepare("SELECT name, symbol FROM Party");
    $stmt->execute();
    $stmt->bind_result($name, $symbol);

    while($stmt->fetch())
    {
        $temp=array();
        $temp['name']=$name;
        $temp['symbol']=$symbol;

        array_push($party, $temp);
    }
    $stmt->close();

    $response['partyList']=$party;

    $response['success']=true;
}
$conn->close();

echo json_encode($response);


?>