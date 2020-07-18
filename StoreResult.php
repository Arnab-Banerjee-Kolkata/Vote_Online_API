<?php

function storeResult($internalAuthKey, $conn, $countryElectionId, $stateElectionId, $phaseElectionId, $type, $constituencyName)
{
    include 'Credentials.php';
    include 'EncryptionKeys.php';
    include 'UpdateParentStatus.php';
    
    
    $vbConn=new mysqli($vbServerName, $vbUserName, $vbPassword, $vbDbName);

    $internalAuthKey=$vbConn->real_escape_string($internalAuthKey);
    $countryElectionId=$vbConn->real_escape_string($countryElectionId);
    $stateElectionId=$vbConn->real_escape_string($stateElectionId);
    $phaseElectionId=$vbConn->real_escape_string($phaseElectionId);
    $type=$vbConn->real_escape_string($type);
    $constituencyName=$vbConn->real_escape_string($constituencyName);
    
    

    
    if($vbConn->connect_error)
    {
        die("Connection failed: ");
    }

    $response=array();
    $response['success']=false;
    $response['validInternalAuth']=false;
    $response['validParentOp']=false;
    $response['tie']=true;




    if($internalAuthKey==$INTERNAL_AUTH_KEY)
    {
        $response['validInternalAuth']=true;
        
        $candidateIds=array();
        
        $stmt=$conn->prepare("SELECT id FROM Candidate WHERE election_id=? AND constituency_name=?");
        $stmt->bind_param("ds", $phaseElectionId, $constituencyName);
        $stmt->execute();
        $stmt->bind_result($temp);
        
        while($stmt->fetch())
        {
            array_push($candidateIds, $temp);
        }
        $stmt->close();
        
        $arraySize=sizeof($keySet);
        $votes=array();
        $highest=0;
        foreach($candidateIds as $canId)
        {
            $candidateId=strval($canId);
            $len=strlen($candidateId);
            $a=-1;
            $voteCount=0;
            foreach($keySet as $key)
            {                
                $a++;
                $strA=strval($a);
                $enVote="";
                for($c=0; $c<strlen($strA);$c++)
                {
                    $enVote=$enVote.$key[$strA[$c]];
                }

                for($b=0; $b<$len; $b++)
                {
                    $enVote=$enVote.$key[$candidateId[$b]];
                }
                
                $stmt=$vbConn->prepare("SELECT SUM(count) FROM Govt_Vote WHERE phase_election_id=? AND en_vote=? AND constituency_name=?");
                $stmt->bind_param("dss", $phaseElectionId, $enVote, $constituencyName);
                $stmt->execute();
                $stmt->bind_result($sumVote);
                $stmt->fetch();
                $stmt->close();

                
                if($sumVote==null || $sumVote=='')
                    $sumVote=0;
                $voteCount=$voteCount+$sumVote;

            }
            array_push($votes, $voteCount);
            if($voteCount>$highest)
            {
                $highest=$voteCount;
            }
            //Store number of votes received by each candidate

            $stmt=$conn->prepare("INSERT INTO Govt_Result (state_election_id, constituency_name, no_of_votes, candidate_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("dsdd", $stateElectionId, $constituencyName, $voteCount, $canId);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();

        }
        //Find candidate with highest votes. Store his/her id and number of votes
        $canCount=0;
        $highestCanId=-1;
        for($a=0;$a<sizeof($votes);$a++)
        {
            if($votes[$a]==$highest)
            {
                $stmt=$conn->prepare("INSERT INTO Constituency_Result (state_election_id, constituency_name, winner_candidate_id, winner_vote_count) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("dsdd", $stateElectionId, $constituencyName, $candidateIds[$a], $highest);
                $stmt->execute();
                $stmt->fetch();
                $stmt->close();
                
                $canCount++;
                $highestCanId=$candidateIds[$a];
            }
        }
        
        if($canCount!=1){
            goto end;
        }
        
        $stmt=$conn->prepare("SELECT party_name FROM Candidate WHERE id=?");
        $stmt->bind_param("d", $highestCanId);
        $stmt->execute();
        $stmt->bind_result($partyName);
        $stmt->fetch();
        $stmt->close();
        
        $stmt=$conn->prepare("SELECT seats_won FROM State_Election_Result WHERE state_election_id=? AND party_name = (
	SELECT party_name FROM Candidate WHERE id=?    
)");
        $stmt->bind_param("dd", $stateElectionId, $highestCanId);
        $stmt->execute();
        $stmt->bind_result($seatsWon);
        $stmt->fetch();
        $stmt->close();
            
        if($seatsWon==null || $seatsWon=='' || $seatsWon<=0)    //State does not have party
        {
            if($type=="VIDHAN SABHA")     //Only state
            {
                $stmt=$conn->prepare("INSERT INTO State_Election_Result (state_election_id, party_name, seats_won) VALUES (?, ?, 1)");
                $stmt->bind_param("ds", $stateElectionId, $partyName);
                $stmt->execute();
                $stmt->fetch();
                $stmt->close();
                
                
            }
            else if($type=="LOK SABHA")        //State and country
            {
                $stmt=$conn->prepare("INSERT INTO State_Election_Result (state_election_id, party_name, seats_won, country_election_id) VALUES (?, ?, 1, ?)");
                $stmt->bind_param("dsd", $stateElectionId, $partyName, $countryElectionId);
                $stmt->execute();
                $stmt->fetch();
                $stmt->close();
                
                
                $seatsWon=-1;
                $stmt=$conn->prepare("SELECT seats_won FROM Country_Election_Result WHERE country_election_id=? AND party_name = (
	SELECT party_name FROM Candidate WHERE id=?    
)");
                $stmt->bind_param("dd", $countryElectionId, $highestCanId);
                $stmt->execute();
                $stmt->bind_result($seatsWon);
                $stmt->fetch();
                $stmt->close();
                
                if($seatsWon==null || $seatsWon=='' || $seatsWon<=0)    //Country does not have party
                {
                    $stmt=$conn->prepare("INSERT INTO Country_Election_Result (country_election_id, party_name, seats_won) VALUES (?, ?, 1)");
                    $stmt->bind_param("ds", $countryElectionId, $partyName);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();

                    echo ("here1");
                }
                else        //Country has party
                {
                    echo ("here2 ".$seatsWon);
                    $seatsWon++;
                    $stmt=$conn->prepare("UPDATE Country_Election_Result SET seats_won=? WHERE party_name=? AND country_election_id=?");
                    $stmt->bind_param("dsd", $seatsWon , $partyName, $countryElectionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                }
            }
            
            
            
            
        }
        else    //State has party
        {
            $seatsWon++;
            $stmt=$conn->prepare("UPDATE State_Election_Result SET seats_won=? WHERE party_name=? AND state_election_id=?");
            $stmt->bind_param("dsd", $seatsWon , $partyName, $stateElectionId);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();
            
            
            
            if($type=="LOK SABHA")      //UPDATE COUNTRY
            {
                $seatsWon=-1;
                $stmt=$conn->prepare("SELECT seats_won FROM Country_Election_Result WHERE country_election_id=? AND party_name = (
	SELECT party_name FROM Candidate WHERE id=?    
)");
                $stmt->bind_param("dd", $countryElectionId, $highestCanId);
                $stmt->execute();
                $stmt->bind_result($seatsWon);
                $stmt->fetch();
                $stmt->close();
                
                
                
                $seatsWon++;
                    $stmt=$conn->prepare("UPDATE Country_Election_Result SET seats_won=? WHERE party_name=? AND country_election_id=?");
                    $stmt->bind_param("dsd", $seatsWon , $partyName, $countryElectionId);
                    $stmt->execute();
                    $stmt->fetch();
                    $stmt->close();
                
            }
        }
        $response['tie']=false;
        end:
        $response['validParentOp']=updateParentStatus($INTERNAL_AUTH_KEY, $conn, $countryElectionId, $stateElectionId, $phaseElectionId, $type, $constituencyName);
        
        
        
        if($response['validParentOp'])
        {
            $response['success']=true;
        }
        
    }
    
    $vbConn->close();
        

    return $response;
    
}

?>
