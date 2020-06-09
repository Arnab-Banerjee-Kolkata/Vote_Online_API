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
$response['tieCount']=0;

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
        $results=array();


        

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

            $stmt=$conn->prepare("SELECT COUNT(id), status, state_code FROM State_Election WHERE id=? AND type=? AND (status=2 OR status=3)");
            $stmt->bind_param("ds", $electionId, $type);
            $stmt->execute();
            $stmt->bind_result($count, $status, $stateCode);

            if($stmt->fetch() && $count>=1)
            {
                $response['validElection']=true;
                $response['status']=$status;
                $stmt->close();
                $count=-1;

                $stmt=$conn->prepare("SELECT State_Election_Result.party_name, State_Election_Result.seats_won, Party.symbol, Party.alliance FROM State_Election_Result, Party WHERE state_election_id=? AND Party.name=State_Election_Result.party_name ORDER BY State_Election_Result.seats_won DESC");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($partyName, $seatsWon, $partySymbol, $alliance);

                while($stmt->fetch())
                {
                    $result=array();
                    $result['partyName']=$partyName;
                    $result['seatsWon']=$seatsWon;
                    $result['partySymbol']=$partySymbol;
                    $result['alliance']=$alliance;
                    array_push($results, $result);
                }
                $stmt->close();

                $stmt=$conn->prepare("SELECT COUNT(DISTINCT(constituency_name)) FROM Candidate WHERE election_id IN (
	SELECT id FROM Pub_Govt_Election WHERE state_election_id=?	    
)");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($response['totalSeats']);
                $stmt->fetch();

                $stmt->close();


                $stmt=$conn->prepare("SELECT COUNT(ties) FROM (
    SELECT constituency_name, COUNT(*) as ties FROM Constituency_Result 
    WHERE state_election_id = ?
    GROUP BY (constituency_name)
    HAVING COUNT(*)>1    
) t");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($response['tieCount']);
                $stmt->fetch();
                $stmt->close();


                $response['success']=true;

                $response['results']=$results;

            }


        }

        else if(strlen($stateCode)!=0 && $count==1)   //Lok Sabha with state
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

                $stmt=$conn->prepare("SELECT State_Election_Result.party_name, State_Election_Result.seats_won, Party.symbol, Party.alliance FROM State_Election_Result, Party WHERE State_Election_Result.state_election_id=? AND State_Election_Result.country_election_id=? AND Party.name=State_Election_Result.party_name ORDER BY State_Election_Result.seats_won DESC");
                $stmt->bind_param("dd", $stateElectionId, $electionId);
                $stmt->execute();
                $stmt->bind_result($partyName, $seatsWon, $partySymbol, $alliance);

                while($stmt->fetch())
                {
                    $result=array();
                    $result['partyName']=$partyName;
                    $result['seatsWon']=$seatsWon;
                    $result['partySymbol']=$partySymbol;
                    $result['alliance']=$alliance;
                    array_push($results, $result);
                }
                $stmt->close();

                $stmt=$conn->prepare("SELECT COUNT(DISTINCT(constituency_name)) FROM Candidate WHERE election_id IN (
	SELECT id FROM Pub_Govt_Election WHERE state_election_id= (
    	    SELECT id FROM State_Election WHERE state_code=? AND country_election_id=?
    )
)");
                $stmt->bind_param("sd", $stateCode, $electionId);
                $stmt->execute();
                $stmt->bind_result($response['totalSeats']);
                $stmt->fetch();

                $stmt->close();     


                $stmt=$conn->prepare("SELECT COUNT(ties) FROM (
    SELECT constituency_name, COUNT(*) as ties FROM Constituency_Result 
    WHERE state_election_id = (
        SELECT id FROM State_Election WHERE country_election_id=? AND state_code=?    
    ) 
    GROUP BY (constituency_name)
    HAVING COUNT(*)>1
    
) t");
                $stmt->bind_param("ds", $electionId, $stateCode);
                $stmt->execute();
                $stmt->bind_result($response['tieCount']);
                $stmt->fetch();
                $stmt->close();           

                $response['success']=true;

                $response['results']=$results;
            }

        }
        else if(strlen($stateCode)==0)        //Lok sabha without state
        {
            $stmt->close();
            $count=-1;

            $stmt=$conn->prepare("SELECT COUNT(id), status FROM Country_Election WHERE id=? AND (status=2 OR status=3)");
            $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($count,$status);

            if($stmt->fetch() && $count==1)
            {
                $response['validElection']=true;
                $response['status']=$status;
                $stmt->close();
                $count=-1;

                $stmt=$conn->prepare("SELECT Country_Election_Result.party_name, Country_Election_Result.seats_won, Party.symbol, Party.alliance FROM Country_Election_Result, Party WHERE Country_Election_Result.country_election_id=? AND Party.name=Country_Election_Result.party_name ORDER BY Country_Election_Result.seats_won DESC");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($partyName, $seatsWon, $partySymbol, $alliance);

                while($stmt->fetch())
                {
                    $result=array();
                    $result['partyName']=$partyName;
                    $result['seatsWon']=$seatsWon;
                    $result['partySymbol']=$partySymbol;
                    $result['alliance']=$alliance;
                    array_push($results, $result);
                }
                $stmt->close();

                $stmt=$conn->prepare("SELECT COUNT(DISTINCT(constituency_name)) FROM Candidate WHERE election_id IN (
	SELECT id FROM Pub_Govt_Election WHERE state_election_id IN (
    	    SELECT id FROM State_Election WHERE country_election_id=?
    )
)");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($response['totalSeats']);
                $stmt->fetch();

                $stmt->close();


                $stmt=$conn->prepare("SELECT COUNT(ties) FROM (
    SELECT constituency_name, COUNT(*) as ties FROM Constituency_Result 
    WHERE state_election_id IN (
        SELECT id FROM State_Election WHERE country_election_id=?    
    ) 
    GROUP BY (constituency_name)
    HAVING COUNT(*)>1
    
) t");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($response['tieCount']);
                $stmt->fetch();
                $stmt->close();


                $response['success']=true;

                $response['results']=$results;

            }
        }

        $allianceList=array();

        foreach($results as $result)
        {
            if(strlen($result['alliance'])!=0)      //HAS ALLIANCE
            {
                if(isset($allianceList[$result['alliance']]))
                    $allianceList[$result['alliance']]+=$result['seatsWon'];
                else
                    $allianceList[$result['alliance']]=$result['seatsWon'];
            }
            else                //INDEPENDENT
            {
                if($result['seatsWon']>=$ALLIANCE_CUTOFF)
                {
                    $allianceList[$result['partyName']]=$result['seatsWon'];
                }
                else
                {
                    if(isset($allianceList['OTHERS']))
                        $allianceList['OTHERS']+=$result['seatsWon'];
                    else
                        $allianceList['OTHERS']=$result['seatsWon'];
                }
            }
        }

        $response['allianceList']=$allianceList;

    }

    
}
$conn->close();

echo json_encode($response);


?>
