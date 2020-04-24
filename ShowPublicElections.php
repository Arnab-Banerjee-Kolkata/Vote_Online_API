<?php

include 'Credentials.php';

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

    $stmt=$conn->prepare("SELECT Country_Election.id, Country_Election.name, Country_Election.status, Country_Election.year FROM Country_Election");
    $stmt->execute();
    $stmt->bind_result($electionId, $electionName, $status, $year);

    $election=array();

    while($stmt->fetch())
    {
        $temp=array();
        $temp['electionId']=$electionId;
        $temp['name']=$electionName;
        $temp['type']="LOK SABHA";
        $temp['status']=$status;
        $temp['year']=$year;
        array_push($election, $temp);
        $response['success']=true;
    }
    $stmt->close();
	
	
	$stmt2=$conn->prepare("SELECT State_Election.id, State_Election.name, State_Election.type, State_Election.status, State_Election.year FROM State_Election WHERE State_Election.type='VIDHAN SABHA'");
	$stmt2->execute();
	$stmt2->bind_result($electionId, $electionName, $type, $status, $year);
	
	while($stmt2->fetch())
    {
        $temp2=array();
        $temp2['electionId']=$electionId;
        $temp2['name']=$electionName;
        $temp2['type']=$type;
        $temp2['status']=$status;
        $temp2['year']=$year;
        array_push($election, $temp2);
        $response['success']=true;
	}
	$stmt2->close();
	
    $response['elections']=$election;

}
$conn->close();

echo json_encode($response);

?>
