<?php

function updateParentStatus($internalAuthKey, $conn, $countryElectionId, $stateElectionId, $phaseElectionId, $type, $constituencyName)
{
    include 'Credentials.php';
    
    
    $internalAuthKey=$conn->real_escape_string($internalAuthKey);
    $countryElectionId=$conn->real_escape_string($countryElectionId);
    $stateElectionId=$conn->real_escape_string($stateElectionId);
    $phaseElectionId=$conn->real_escape_string($phaseElectionId);
    $type=$conn->real_escape_string($type);
    $constituencyName=$conn->real_escape_string($constituencyName);
    
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $countryElectionId);
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $stateElectionId);
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $phaseElectionId);
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $type);
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $constituencyName);


    $validParentOp=false;
    if($internalAuthKey==$INTERNAL_AUTH_KEY)
    {
    
    //CHECK IF THE CURRENT PHASE IS COMPLETE

        $count=-1;
        $count1=-1;
        $count2=-1;
        $stmt=$conn->prepare("SELECT COUNT(constituency_name), COUNT(DISTINCT(constituency_name)) FROM Constituency_Result WHERE state_election_id=? AND constituency_name IN (
    SELECT name FROM Constituency WHERE state_code = (
        SELECT state_code FROM Constituency WHERE name=?    
    ) AND phase_code = (
        SELECT phase_code FROM Constituency WHERE name=?    
    )
)");
        $stmt->bind_param("dss", $stateElectionId, $constituencyName, $constituencyName);
        $stmt->execute();
        $stmt->bind_result($count, $count1);
        $stmt->fetch();
        $stmt->close();

        //Calculate total constituencies in the current phase and store in count2

        $stmt=$conn->prepare("SELECT COUNT(name) FROM Constituency WHERE state_code = (
    SELECT state_code FROM Constituency WHERE name=?    
) AND phase_code = (
    SELECT phase_code FROM Constituency WHERE name=? 
)");
        $stmt->bind_param("ss", $constituencyName, $constituencyName);
        $stmt->execute();
        $stmt->bind_result($count2);
        $stmt->fetch();
        $stmt->close();


        //If all is done then change status to 3

        if($count==$count1 && $count1==$count2 && $count>0)
        {
            $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=3 WHERE id=? AND status<>5");
            $stmt->bind_param("d", $phaseElectionId);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();


            $count=$count1=$count2=-1;
            $stmt=$conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election WHERE state_election_id=?");
            $stmt->bind_param("d", $stateElectionId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();


            $stmt=$conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election WHERE state_election_id=? AND status=3");
            $stmt->bind_param("d", $stateElectionId);
            $stmt->execute();
            $stmt->bind_result($count1);
            $stmt->fetch();
            $stmt->close();

            if($count==$count1)     //Update state
            {
                $stmt=$conn->prepare("UPDATE State_Election SET status=3 WHERE id=?");
                $stmt->bind_param("d", $stateElectionId);
                $stmt->execute();
                $stmt->fetch();
                $stmt->close();
                
                if($type=="LOK SABHA")
                {
                    $count=$count1=-1;
                    
                    $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE country_election_id=1 AND status=3");
                    $stmt->bind_param("d", $countryElectionId);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();
                    
                    
                    $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE country_election_id=1");
                    $stmt->bind_param("d", $countryElectionId);
                    $stmt->execute();
                    $stmt->bind_result($count1);
                    $stmt->fetch();
                    $stmt->close();
                    
                    if($count==$count1)
                    {
                        $stmt=$conn->prepare("UPDATE Country_Election SET status=3 WHERE id=?");
                        $stmt->bind_param("d", $countryElectionId);
                        $stmt->execute();
                        $stmt->fetch();
                        $stmt->close();
                    }
                }
            }
        }
        else if($count>$count1 && $count1>0)    //else if there is a tie then change status to 5
        {
            $stmt=$conn->prepare("UPDATE Pub_Govt_Election SET status=5 WHERE id=?");
            $stmt->bind_param("d", $phaseElectionId);
            $stmt->execute();
            $stmt->fetch();
            $stmt->close();
        }
        
    
        $validOp=true;
    }
    
    return $validParentOp;
}

?>
