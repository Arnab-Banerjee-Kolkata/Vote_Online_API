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
            $stmt=$conn->prepare("SELECT election_id FROM Govt_Approval WHERE booth_id=?");
            $stmt->bind_param("s", $boothId);
            $stmt->execute();
            $stmt->bind_result($phaseElectionId);
            $stmt->fetch();
            $stmt->close();

            $response['phaseId']=$phaseElectionId;
            
            //FETCH PANEL MEMBERS
            
            $response['success']=true;
        }
            
    }
        

    return $response;
    
}

?>
