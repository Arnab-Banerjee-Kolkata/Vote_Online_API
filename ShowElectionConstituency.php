<?php

include 'Credentials.php';
include 'Protection.php';

checkServerIp($INTERNAL_AUTH_KEY);
foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}
//ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ");
}


$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$stateElectionId=$conn->real_escape_string($_POST["stateElectionId"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validElection']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $stmt=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if($count==1)
    {
        $response['validAdmin']=true;
        $count=-1;

        $stmt=$conn->prepare("SELECT COUNT(id), state_code, type FROM State_Election WHERE id=?");
        $stmt->bind_param("d", $stateElectionId);
        $stmt->execute();
        $stmt->bind_result($count, $stateCode, $type);
        $stmt->fetch();
        $stmt->close();

        if($count==1)
        {
            $response['validElection']=true;
            $count=-1;

            $constituencyList=array();

            $pattern="";
            if($type=="LOK SABHA")
            {
                $pattern='LS%';
            }
            else if($type="VIDHAN SABHA")
            {
                $pattern='VS%';
            }

            $stmt=$conn->prepare("SELECT name FROM Constituency WHERE state_code=? AND phase_code LIKE ?");
            $stmt->bind_param("ss",$stateCode, $pattern);
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
}
$conn->close();

echo json_encode($response);


?>
