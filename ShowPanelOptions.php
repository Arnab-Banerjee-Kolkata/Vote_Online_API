<?php

function showPanelOptions($internalAuthKey, $conn, $boothId, $electionId, $type)
{
    include 'Credentials.php';

    $response=array();
    $response['success']=false;
    $response['validInternalAuth']=false;
    $response['validElection']=false;
    $response['validType']=false;
    $response['validParentElection']=false;



    if($internalAuthKey==$INTERNAL_AUTH_KEY)
    {
        $response['validInternalAuth']=true;
        

        
        //Country Election
        $stmt3=$conn->prepare("SELECT status FROM Country_Election where id = ?");
        $stmt3->bind_param("d",$electionId);

        $stmt3->execute();
        $stmt3->bind_result($status1);

        if($stmt3->fetch() && $status1==1)
        {
            $stmt3->close();
            $response['validElection']=true;

            if($type=="LOK SABHA")
            {
                $response['validType']=true;
                
                
                $stmt=$conn->prepare("SELECT id FROM Country_Election WHERE id = (
	SELECT country_election_id FROM State_Election WHERE id = (
        SELECT state_election_id FROM Pub_Govt_Election WHERE id = (
    		SELECT election_id FROM Govt_Approval WHERE booth_id=?  
       	)
    )
)");
                $stmt->bind_param("s", $boothId);
                $stmt->execute();
                $stmt->bind_result($parentElectionId);
                $stmt->fetch();
                $stmt->close();
                
                if($parentElectionId==$electionId)
                {
                    $response['validParentElection']=true;
                    
                    goto end;
                }
            }
        }
        else
        {
            $stmt3->close();
        }

        $response['validElection']=false;

        $stmt3=$conn->prepare("SELECT status FROM State_Election where id = ?");
        $stmt3->bind_param("d",$electionId);
        $stmt3->execute();
        $stmt3->bind_result($status2);
        	
        if($stmt3->fetch() && $status2==1)
        {
            $stmt3->close();
            $response['validElection']=true;
            //State Election
            if($type=="VIDHAN SABHA")
            {
                $response['validType']=true;
                
                
                $stmt=$conn->prepare("SELECT id FROM State_Election WHERE id = (
        SELECT state_election_id FROM Pub_Govt_Election WHERE id = (
    		SELECT election_id FROM Govt_Approval WHERE booth_id=?  
       	)
    )");
                $stmt->bind_param("s", $boothId);
                $stmt->execute();
                $stmt->bind_result($parentElectionId);
                $stmt->fetch();
                $stmt->close();
                
                if($parentElectionId==$electionId)
                {
                    $response['validParentElection']=true;
                
                    goto end;
                }
            }
        }
        else
        {
            $stmt3->close();
        }
        end:
        
        if($response['validParentElection'])
        {
            $stmt=$conn->prepare("SELECT election_id, constituency_name FROM Govt_Approval WHERE booth_id=?");
            $stmt->bind_param("s", $boothId);
            $stmt->execute();
            $stmt->bind_result($phaseElectionId, $constituencyName);
            $stmt->fetch();
            $stmt->close();

            
            
            //FETCH PANEL MEMBERS
            
            $stmt=$conn->prepare("SELECT Candidate.id, Candidate.name, Candidate.party_name, Candidate.img, Party.symbol FROM Candidate, Party WHERE election_id=? AND constituency_name=? AND Party.name=Candidate.party_name");
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
            $response['success']=true;
        }
            
    }
        

    return $response;
    
}

?>
