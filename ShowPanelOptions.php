<?php

function showPanelOptions($internalAuthKey, $conn, $boothId)
{
    include 'Credentials.php';

    $internalAuthKey=$conn->real_escape_string($internalAuthKey);
    $boothId=$conn->real_escape_string($boothId);
    
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $boothId);
    

    $response=array();
    $response['success']=false;
    $response['validInternalAuth']=false;



    if($internalAuthKey==$INTERNAL_AUTH_KEY)
    {
        $response['validInternalAuth']=true;
        
        
        $stmt=$conn->prepare("SELECT election_id, constituency_name FROM Govt_Approval WHERE booth_id=?");
        $stmt->bind_param("s", $boothId);
        $stmt->execute();
        $stmt->bind_result($phaseElectionId, $constituencyName);
        $stmt->fetch();
        $stmt->close();

        
        
        //FETCH PANEL MEMBERS
        
        $stmt=$conn->prepare("SELECT Candidate.id, Candidate.name, Candidate.party_name, Candidate.img, Party.symbol FROM Candidate, Party WHERE election_id=? AND constituency_name=? AND Party.name=Candidate.party_name ORDER BY Candidate.name");
        $stmt->bind_param("ds", $phaseElectionId, $constituencyName);
        $stmt->execute();
        $stmt->bind_result($canId, $canName, $canParty, $canImg, $symbol);
        
        $candidates=array();
        
        while($stmt->fetch())
        {
            $candidate=array();
            $candidate['id']=$canId;
            $candidate['name']=$canName;
            $candidate['party']=$canParty;
            $candidate['img']=$canImg;
            $candidate['symbol']=$symbol;
            
            array_push($candidates, $candidate);
        }
        $stmt->close();
        
        $response['candidates']=$candidates;
        $response['phaseElectionId']=$phaseElectionId;
        $response['constituencyName']=$constituencyName;


        $stmt=$conn->prepare("DELETE FROM Govt_Approval WHERE booth_id=? AND contituency_name=? AND election_id=?");
        $stmt->bind_param("sss", $boothId, $constituencyName, $phaseElectionId);
        $stmt->execute();
        $stmt->fetch();
        $stmt->close();

        $stmt=$conn->prepare("SELECT vote_code FROM Booth WHERE booth_id=?");
        $stmt->bind_param("s", $boothId);
        $stmt->execute();
        $stmt->bind_result($voteCode);
        $stmt->fetch();
        $stmt->close();

        $response['voteCode']=$voteCode;
        $response['success']=true;
        
            
    }
        

    return $response;
    
}

?>
