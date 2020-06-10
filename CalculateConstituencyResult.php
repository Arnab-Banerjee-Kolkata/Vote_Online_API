<?php

// Shows declared constituencies list and to be declared list

include 'Credentials.php';
include 'StoreResult.php';
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
$constituencyName=$conn->real_escape_string($_POST["constituencyName"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);


$key_name="post_auth_key";


$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validType']=false;
$response['validElection']=false;
$response['validConstituency']=false;
$response['validResult']=false;

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
        
        $phaseElectionId=-1;
        $stateElectionId=-1;
        $countryElectionId=-1;


        if($type=="LOK SABHA")
        {
            $response['validType']=true;
            $countryElectionId=$electionId;

            $stmt=$conn->prepare("SELECT COUNT(id) FROM Country_Election WHERE id=? AND status=2");
            $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($count);

            if($stmt->fetch() && $count==1)
            {
                $stmt->close();
                $response['validElection']=true;
                $count=-1;


                $stmt=$conn->prepare("SELECT COUNT(name) FROM Constituency WHERE name=? AND phase_code LIKE 'LS%'");
                $stmt->bind_param("s", $constituencyName);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
                
                if($count==1)
                {
                    $response['validConstituency']=true;
                    $count=-1;
                    
                    
                    
                    $stmt=$conn->prepare("SELECT id FROM State_Election WHERE country_election_id=? AND state_code = (
	SELECT state_code FROM Constituency WHERE name=?    
)");
                    $stmt->bind_param("ds", $electionId, $constituencyName);
                    $stmt->execute();
                    $stmt->bind_result($stateElectionId);
                    $stmt->fetch();
                    $stmt->close();
                    
                    
                    
                    
                    $stmt=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE type='LOK SABHA' AND state_election_id= (
	SELECT id FROM State_Election WHERE country_election_id=? AND state_code = (
    	SELECT state_code FROM Constituency WHERE name=?
    )
)AND state_code=(
	SELECT state_code FROM Constituency WHERE name=?    
) AND phase_code = (
	SELECT phase_code FROM Constituency WHERE name=?    
)");
                    $stmt->bind_param("dsss", $electionId, $constituencyName, $constituencyName, $constituencyName);
                    $stmt->execute();
                    $stmt->bind_result($phaseElectionId);
                    $stmt->fetch();
                    $stmt->close();                   
                    
                }

            }
        }
        else if($type="VIDHAN SABHA")
        {
            $response['validType']=true;
            $stateElectionId=$electionId;
            

            $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE id=? AND status=2 AND type=?");
            $stmt->bind_param("ds", $electionId, $type);
            $stmt->execute();
            $stmt->bind_result($count);

            if($stmt->fetch() && $count==1)
            {
                $stmt->close();
                $response['validElection']=true;
                $count=-1;

                $stmt=$conn->prepare("SELECT COUNT(name) FROM Constituency WHERE name=? AND phase_code LIKE 'VS%' AND state_code = (
	SELECT state_code FROM State_Election WHERE id=?    
)");
                $stmt->bind_param("sd", $constituencyName, $electionId);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
                
                if($count==1)
                {
                    $response['validConstituency']=true;
                    $count=-1;
                    
                    
                    $stmt=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE type='VIDHAN SABHA' AND state_election_id=? AND state_code=(
	SELECT state_code FROM Constituency WHERE name=?    
) AND phase_code = (
	SELECT phase_code FROM Constituency WHERE name=?    
)");
                    $stmt->bind_param("dss", $electionId, $constituencyName, $constituencyName);
                    $stmt->execute();
                    $stmt->bind_result($phaseElectionId);
                    $stmt->fetch();
                    $stmt->close();
                    
                }
            }
        }
        
        if(!($response['validAuth'] && $response['validAdmin'] && $response['validType'] && $response['validElection'] && $response['validConstituency']))
        {
            goto end;
        }
        
        //CHECK IF RESULT IS ALREADY CALCULATED WITH stateElectionId AND constituencyName
        $count=-1;
        
        $stmt=$conn->prepare("SELECT COUNT(constituency_name) FROM Constituency_Result WHERE state_election_id=? AND constituency_name=?");
        $stmt->bind_param("ds", $stateElectionId, $constituencyName);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if($count==0)
        {
            $response['validResult']=true;
            
            //ADD to RESULT
            $result=array();
            $result=storeResult($INTERNAL_AUTH_KEY, $conn, $countryElectionId, $stateElectionId, $phaseElectionId, $type, $constituencyName);
            
            
            $response['sub']=$result;            
        }
             
        end:
    }
}
$conn->close();

echo json_encode($response);


?>
