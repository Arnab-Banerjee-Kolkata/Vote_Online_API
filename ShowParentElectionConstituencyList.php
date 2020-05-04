<?php

// Shows declared constituencies list and to be declared list

include 'Credentials.php';

//ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$electionId=$_POST["electionId"];
$type=$_POST["type"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
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


    if($type=="LOK SABHA")
    {
        $response['validType']=true;
        
        $stmt=$conn->prepare("SELECT COUNT(id) FROM Country_Election WHERE id=? AND status=2");
        $stmt->bind_param("d", $electionId);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==1)
        {
            $stmt->close();
            $response['validElection']=true;
            $count=-1;
            

            $constituencyList=array();

            $stmt=$conn->prepare("SELECT Constituency.name, State.name FROM Constituency, State WHERE Constituency.phase_code LIKE 'LS%' AND State.code=Constituency.state_code");
            $stmt->execute();
            $stmt->bind_result($name, $stateName);

            while($stmt->fetch())
            {
                $constituency=array();
                $constituency['name']=$name;
                $constituency['stateName']=$stateName;
                
                array_push($constituencyList, $constituency);
            }
            $stmt->close();

            $response['constituencyList']=$constituencyList;
            
            
            $stmt=$conn->prepare("SELECT constituency_name FROM Constituency_Result WHERE state_election_id IN (
	SELECT id FROM State_Election WHERE country_election_id=?    
)");        $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($name);
            
            $declaredList=array();
            while($stmt->fetch())
            {
                $constituency=array();
                $constituency['name']=$name;
                
                array_push($declaredList, $constituency);
            }
            $stmt->close();
            
            $response['declaredList']=$declaredList;
            
            

            $response['success']=true;
        }
    }
    else if($type="VIDHAN SABHA")
    {
        $response['validType']=true;
        
        $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE id=? AND status=2 AND type=?");
        $stmt->bind_param("ds", $electionId, $type);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==1)
        {
            $stmt->close();
            $response['validElection']=true;
            $count=-1;

            $constituencyList=array();

            $stmt=$conn->prepare("SELECT name FROM Constituency WHERE phase_code LIKE 'VS%' AND state_code = (
	SELECT state_code FROM State_Election WHERE id = ?    
)");
            $stmt->bind_param("d",$electionId);
            $stmt->execute();
            $stmt->bind_result($name);

            while($stmt->fetch())
            {
                $constituency=array();
                $constituency['name']=$name;
                
                array_push($constituencyList, $constituency);
            }
            $stmt->close();

            $response['constituencyList']=$constituencyList;
            
            
            $stmt=$conn->prepare("SELECT constituency_name FROM Constituency_Result WHERE state_election_id=?");        $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($name);
            
            $declaredList=array();
            while($stmt->fetch())
            {
                $constituency=array();
                $constituency['name']=$name;
                
                array_push($declaredList, $constituency);
            }
            $stmt->close();
            
            $response['declaredList']=$declaredList;
            

            $response['success']=true;
        }
    }
}
$conn->close();

echo json_encode($response);


?>