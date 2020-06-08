<?php

include 'Credentials.php';
include 'Protection.php';

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
$stateElectionId=$conn->real_escape_string($_POST["stateElectionId"]);
$constituencyName=$conn->real_escape_string($_POST["constituencyName"]);


$key_name="post_auth_key";
$constituencyName=strtoupper($constituencyName);


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validElection']=false;
$response['validConstituency']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);



if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE id=? AND (status=2 OR status=3)");
    $stmt->bind_param("d", $stateElectionId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if($count==1)
    {
        $response['validElection']=true;
        $count=-1;

        $stmt=$conn->prepare("SELECT COUNT(name) FROM Constituency WHERE name=? AND state_code = (
	SELECT state_code FROM State_Election WHERE id=?    
)");
        $stmt->bind_param("sd", $constituencyName, $stateElectionId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if($count==1)
        {
            $response['validConstituency']=true;
            $count=-1;

            $stmt=$conn->prepare("SELECT Govt_Result.no_of_votes, Candidate.name, Candidate.party_name, Candidate.img, Party.symbol FROM Govt_Result, Candidate, Party WHERE Candidate.id=Govt_Result.candidate_id AND Govt_Result.constituency_name=? AND Govt_Result.state_election_id=? AND Party.name=Candidate.party_name ORDER BY Govt_Result.no_of_votes DESC");
            $stmt->bind_param("sd", $constituencyName, $stateElectionId);
            $stmt->execute();
            $stmt->bind_result($noOfVotes, $candidateName, $partyName, $image, $symbol);
            
            $candidates=array();

            while($stmt->fetch())
            {
                $candidate=array();
                $candidate['noOfVotes']=$noOfVotes;
                $candidate['name']=$candidateName;
                $candidate['partyName']=$partyName;
                $candidate['image']=$image;
                $candidate['partySymbol']=$symbol;

                array_push($candidates, $candidate);
            }

            $stmt->close();

            $response['detailResult']=$candidates;

            $response['success']=true;
        }
    }    
}
$conn->close();

echo json_encode($response);


?>
