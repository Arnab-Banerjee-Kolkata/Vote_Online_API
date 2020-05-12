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
$name=$conn->real_escape_string($_POST["name"]);
$symbol=$conn->real_escape_string($_POST["symbol"]);
$boothId=$conn->real_escape_string($_POST["boothId"]);


$key_name="post_auth_key";
checkServerIp($INTERNAL_AUTH_KEY);


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;
$response['validName']=false;
$response['validSymbol']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    
     $stmt=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=? AND status=1");
     $stmt->bind_param("s", $boothId);
     $stmt->execute();
     $stmt->bind_result($count);

     if($stmt->fetch() && $count==1)
     {
        $count=-1;
        $stmt->close();
        $response['validBooth']=true;
    

        $stmt=$conn->prepare("SELECT COUNT(name) FROM Party WHERE name=?");

        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==0)
        {
            $count=-1;
            $stmt->close();
            $response['validName']=true;

            $stmt=$conn->prepare("SELECT COUNT(name) FROM Party WHERE symbol=?");
            $stmt->bind_param("s", $symbol);
            $stmt->execute();
            $stmt->bind_result($count);

            if($stmt->fetch() && $count==0)
            {        
                $stmt->close();
                $response['validSymbol']=true;

                $stmt=$conn->prepare("INSERT INTO Party (name, symbol) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $symbol);
                $stmt->execute();


                $response['success']=true;            
            }
        }
     }

    
}
$conn->close();

echo json_encode($response);


?>
