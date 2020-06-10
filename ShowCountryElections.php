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
    $stmt->fetch();
    $stmt->close();

    if($count==1)
    {
        $response['validAdmin']=true;
        $count=-1;

        $stmt=$conn->prepare("SELECT Country_Election.id, Country_Election.name, Country_Election.year FROM Country_Election WHERE status=0 ORDER BY Country_Election.year DESC");
        $stmt->execute();
        $stmt->bind_result($electionId, $electionName, $year);

        $election=array();

        while($stmt->fetch())
        {
            $temp=array();
            $temp['electionId']=$electionId;
            $temp['name']=$electionName;
            $temp['year']=$year;
            array_push($election, $temp);
            $response['success']=true;
        }
        $stmt->close();
        
        
        $response['elections']=$election;
    }

}
$conn->close();

echo json_encode($response);

?>
