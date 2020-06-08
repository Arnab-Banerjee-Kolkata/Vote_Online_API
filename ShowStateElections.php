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
$type=$conn->real_escape_string($_POST["type"]);
$countryElectionId=$conn->real_escape_string($_POST["countryElectionId"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);



$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validType']=false;
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
    
        if($type=="VIDHAN SABHA")
        {
            $response['validType']=true;
            
            $stmt=$conn->prepare("SELECT State_Election.id, State_Election.name, State_Election.state_code, State_Election.year, State.name FROM State_Election, State WHERE State_Election.status=0 AND State_Election.type=? AND State.code=State_Election.state_code ORDER BY State_Election.year DESC");
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $stmt->bind_result($id, $name, $stateCode, $year, $stateName);
            
            $election=array();

            while($stmt->fetch())
            {
                $temp=array();
                $temp['electionId']=$id;
                $temp['name']=$name;
                $temp['stateCode']=$stateCode;
                $temp['year']=$year;
                $temp['stateName']=$stateName;
                array_push($election, $temp);
                $response['success']=true;
            }
            $stmt->close();


            $response['elections']=$election;
            
        }
        else if($type=="LOK SABHA")
        {
            $response['validType']=true;
            $count=-1;
            
            $stmt=$conn->prepare("SELECT COUNT(id) FROM Country_Election WHERE id=? AND status=0");
            $stmt->bind_param("d", $countryElectionId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            
            if($count==1)
            {
                $response['validElection']=true;
                $count=-1;
            
                $stmt=$conn->prepare("SELECT State_Election.id, State_Election.name, State_Election.state_code, State_Election.year, State.name FROM State_Election, State WHERE State_Election.country_election_id=? AND State_Election.status=0 AND State_Election.type=? AND State.code=State_Election.state_code ORDER BY State.name");
                $stmt->bind_param("ds", $countryElectionId, $type);
                $stmt->execute();
                $stmt->bind_result($id, $name, $stateCode, $year, $stateName);

                $election=array();

                while($stmt->fetch())
                {
                    $temp=array();
                    $temp['electionId']=$id;
                    $temp['name']=$name;
                    $temp['stateCode']=$stateCode;
                    $temp['year']=$year;
                    $temp['stateName']=$stateName;
                    array_push($election, $temp);
                    $response['success']=true;
                }
                $stmt->close();


                $response['elections']=$election;
            }
        }
    }

}
$conn->close();

echo json_encode($response);

?>
