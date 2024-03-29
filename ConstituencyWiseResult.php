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
$electionId=$conn->real_escape_string($_POST["electionId"]);
$stateCode=$conn->real_escape_string($_POST["stateCode"]);
$type=$conn->real_escape_string($_POST["type"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validType']=false;
$response['validState']=false;
$response['validElection']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);



if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;


    if($type=="LOK SABHA" || $type=="VIDHAN SABHA")
    {
        $response['validType']=true;
        
        $count=-1;


        

        $stmt=$conn->prepare("SELECT COUNT(code) FROM State WHERE code=?");
        $stmt->bind_param("s",$stateCode);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();


        if($type=="VIDHAN SABHA")
        {   
            $stmt->close();
            $response['validState']=true;
            $count=-1;

            $stmt=$conn->prepare("SELECT COUNT(id), status FROM State_Election WHERE id=? AND type=? AND (status=2 OR status=3)");
            $stmt->bind_param("ds", $electionId, $type);
            $stmt->execute();
            $stmt->bind_result($count, $status);

            if($stmt->fetch() && $count>=1)
            {
                $response['validElection']=true;
                $response['status']=$status;
                $stmt->close();
                $count=-1;

                $stmt=$conn->prepare("SELECT Constituency_Result.constituency_name, Candidate.name, Candidate.party_name, Party.symbol, Constituency_Result.winner_vote_count, Candidate.img FROM Constituency_Result, Candidate, Party WHERE Constituency_Result.state_election_id=? AND Constituency_Result.winner_candidate_id=Candidate.id AND Party.name=Candidate.party_name ORDER BY Constituency_Result.constituency_name");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($constituencyName, $candidateName, $partyName, $partySymbol, $voteCount, $candidateImg);

                $results=array();

                while($stmt->fetch())
                {
                    $result=array();
                    $result['constituencyName']=$constituencyName;
                    $result['candidateName']=$candidateName;
                    $result['candidateImage']=$candidateImg;
                    $result['partyName']=$partyName;
                    $result['partySymbol']=$partySymbol;
                    $result['voteCount']=$voteCount;
                    array_push($results, $result);
                }
                $stmt->close();

                $response['success']=true;

                $response['results']=$results;

            }


        }

        else if(strlen($stateCode)!=0 && $count==1)
        {
            $response['validState']=true;
            $stmt->close();
            $count=-1;

            $stmt=$conn->prepare("SELECT COUNT(id), id, status FROM State_Election WHERE country_election_id=? AND type=? AND state_code=? AND (status=2 OR status=3)");
            $stmt->bind_param("dss", $electionId, $type, $stateCode);
            $stmt->execute();
            $stmt->bind_result($count, $stateElectionId, $status);

            if($stmt->fetch() && $count==1)
            {
                $response['validElection']=true;
                $response['status']=$status;
                $stmt->close();
                $count=-1;

                $stmt=$conn->prepare("SELECT Constituency_Result.constituency_name, Candidate.name, Candidate.party_name, Party.symbol, Constituency_Result.winner_vote_count, Candidate.img FROM Constituency_Result, Candidate, Party WHERE Constituency_Result.state_election_id=? AND Constituency_Result.winner_candidate_id=Candidate.id AND Party.name=Candidate.party_name ORDER BY Constituency_Result.constituency_name");
                $stmt->bind_param("d", $stateElectionId);
                $stmt->execute();
                $stmt->bind_result($constituencyName, $candidateName, $partyName, $partySymbol, $voteCount, $candidateImg);

                $results=array();

                while($stmt->fetch())
                {
                    $result=array();
                    $result['constituencyName']=$constituencyName;
                    $result['candidateName']=$candidateName;
                    $result['candidateImage']=$candidateImg;
                    $result['partyName']=$partyName;
                    $result['partySymbol']=$partySymbol;
                    $result['voteCount']=$voteCount;
                    array_push($results, $result);
                }
                $stmt->close();

                $response['success']=true;

                $response['results']=$results;
            }

        }
        

    }

    
}
$conn->close();

echo json_encode($response);


?>
