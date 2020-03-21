<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$aadhaarNo=$_POST["aadhaarNo"];
$boothId=$_POST["boothId"];
$postAuthKey1=$_POST["postAuthKey"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validBooth']=false;
$response['validAadhaar']=false;
$response['validAuth']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);



if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;


    // prepare and bind
    $stmt = $conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=?");
    $stmt->bind_param("s", $boothId);

    $stmt->execute();

    $stmt->bind_result($count1);

    if($stmt->fetch() && $count1==1)
    {      
            $stmt->close();
            $response['validBooth']=true;

            $stmt2=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_DB WHERE aadhaar_no=?");
            $stmt2->bind_param("s", $aadhaarNo);
            
            $stmt2->execute();
            $stmt2->bind_result($count2);
            
            if($stmt2->fetch() && $count2==1)
            {
                    $stmt2->close();
                    $response['validAadhaar']=true;

                    $response['success']=true;
                    
            }
    }

    
    
}
$conn->close();

echo json_encode($response);


?>