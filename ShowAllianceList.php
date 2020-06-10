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
    die("Connection failed: ");
}


$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    adminAutoLogout($INTERNAL_AUTH_KEY, $conn);    
    
    $stmt=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $stmt->bind_result($count);

     if($stmt->fetch() && $count==1)
     {
        $count=-1;
        $stmt->close();
        $response['validAdmin']=true;
    

        $stmt=$conn->prepare("SELECT DISTINCT(alliance) FROM Party WHERE LENGTH(alliance)>0");
        $stmt->execute();
        $stmt->bind_result($name);

        $allianceList=array();
        
        while($stmt->fetch())
        {
            array_push($allianceList, $name);
        }

        $stmt->close();

        $response['allianceList']=$allianceList;
        $response['success']=true;
     }

    
}
$conn->close();

echo json_encode($response);


?>