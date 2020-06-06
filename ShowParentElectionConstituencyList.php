<?php

// Shows declared constituencies list and to be declared list

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
$electionId=$conn->real_escape_string($_POST["electionId"]);
$type=$conn->real_escape_string($_POST["type"]);
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

                $stmt=$conn->prepare("SELECT DISTINCT(Candidate.constituency_name), State.name
FROM Candidate
INNER JOIN Constituency ON Candidate.constituency_name = Constituency.name
INNER JOIN State ON Constituency.state_code=State.code
WHERE Candidate.election_id IN (
	SELECT id FROM Pub_Govt_Election WHERE state_election_id IN (
    	    SELECT id FROM State_Election WHERE country_election_id=?
    )
) AND Constituency.name NOT IN (
    SELECT DISTINCT(constituency_name) FROM Constituency_Result WHERE state_election_id IN (
        SELECT id FROM State_Election WHERE country_election_id=?    
    )    
)
");
                $stmt->bind_param("dd", $electionId, $electionId);
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
                
                
                $stmt=$conn->prepare("SELECT DISTINCT(Candidate.constituency_name), State.name 
FROM Candidate 
INNER JOIN Constituency ON Candidate.constituency_name = Constituency.name 
INNER JOIN State ON Constituency.state_code=State.code
WHERE Constituency.name IN (
    SELECT DISTINCT(constituency_name) FROM Constituency_Result WHERE state_election_id IN (
        SELECT id FROM State_Election WHERE country_election_id=?    
    )    
)");        $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($name,$stateName);
                
                $declaredList=array();
                while($stmt->fetch())
                {
                    $constituency=array();
                    $constituency['name']=$name;
                    $constituency['stateName']=$stateName;
                    
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

                $stmt=$conn->prepare("SELECT DISTINCT(constituency_name) FROM Candidate WHERE election_id IN (
	SELECT id FROM Pub_Govt_Election WHERE state_election_id=? AND constituency_name NOT IN (
        SELECT DISTINCT(constituency_name) FROM Constituency_Result WHERE state_election_id=?
    )	    
)");
                $stmt->bind_param("dd",$electionId, $electionId);
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
                
                
                $stmt=$conn->prepare("SELECT DISTINCT(constituency_name) FROM Constituency_Result WHERE state_election_id=?");        
                $stmt->bind_param("d", $electionId);
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
}
$conn->close();

echo json_encode($response);


?>
