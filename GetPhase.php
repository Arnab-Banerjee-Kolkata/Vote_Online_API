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
    die("Connection failed: " . $conn->connect_error);
}


$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$stateCode=$conn->real_escape_string($_POST["stateCode"]);
$type=$conn->real_escape_string($_POST["type"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validCombination']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $phase=array();


    $stmt=$conn->prepare("SELECT COUNT(code) FROM Phase WHERE state_code=? AND type=?");
    $stmt->bind_param("ss", $stateCode, $type);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count>=1)
    {
        $response['validCombination']=true;
        $stmt->close();

        $stmt=$conn->prepare("SELECT type, code FROM Phase WHERE state_code=? AND type=?");
        $stmt->bind_param("ss", $stateCode, $type);
        $stmt->execute();
        $stmt->bind_result($type, $code);

        while($stmt->fetch())
        {
            $temp=array();
            $temp['type']=$type;
            $temp['code']=$code;

            array_push($phase, $temp);
        }
        $stmt->close();

        $response['phases']=$phase;

        $response['success']=true;

    }
    

    
    
}
$conn->close();

echo json_encode($response);


?>
