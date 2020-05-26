<?php

function storeApproval($conn,$internalAuthKey,$aadhaarNo,$electionId,$type,$boothId)
{	
	include 'Credentials.php';
    include 'GarbageAndVoted.php';

    date_default_timezone_set("Asia/Kolkata");


    $internalAuthKey=$conn->real_escape_string($internalAuthKey);
    $aadhaarNo=$conn->real_escape_string($aadhaarNo);
    $electionId=$conn->real_escape_string($electionId);
    $type=$conn->real_escape_string($type);
    $boothId=$conn->real_escape_string($boothId);
    
	
	$response=array();
	$response['validInternalAuth']=false;
    $response['validVoterStatus']=false;
	
	if($internalAuthKey==$INTERNAL_AUTH_KEY)
    {
        $response['validInternalAuth']=true;
		
		if($type=="VIDHAN SABHA")
		{
			
			$stmt=$conn->prepare("SELECT vidhan_sabha_constituency FROM Govt_DB WHERE aadhaar_no=?");
			$stmt->bind_param("s",$aadhaarNo);
			$stmt->execute();
			$stmt->bind_result($vsConst);
			$stmt->fetch();
			$stmt->close();
			
			$stmt2=$conn->prepare("SELECT phase_code,state_code FROM Constituency WHERE name=?");
			$stmt2->bind_param("s",$vsConst);
			$stmt2->execute();
			$stmt2->bind_result($phaseCode,$stateCode);
			$stmt2->fetch();
			$stmt2->close();
			
			$stmt3=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE state_code=? AND phase_code=? AND state_election_id=? AND status=1 AND type=?");
            		$stmt3->bind_param("ssds",$stateCode,$phaseCode,$electionId,$type);
			$stmt3->execute();
			$stmt3->bind_result($phaseId);
			$stmt3->fetch();
			$stmt3->close();
			
            $count=-1;
            $stmt4=$conn->prepare("SELECT COUNT(aadhaar_no) FROM  Govt_Vote_Status WHERE aadhaar_no=? AND election_id=?");
			$stmt4->bind_param("sd",$aadhaarNo, $phaseId);
			$stmt4->execute();
			$stmt4->bind_result($count);
            $stmt4->fetch();
            $stmt4->close();

            if($count==0)
            {
                $count=-1;
                $response['validVoterStatus']=true;

                $stmt4=$conn->prepare("INSERT INTO Govt_Approval (election_id,booth_id,constituency_name,approved_at) 
                VALUES (?,?,?,?)");
                $stmt4->bind_param("dsss",$phaseId,$boothId,$vsConst,date("Y-m-d H:i:s"));
                $stmt4->execute();
                $stmt4->close();
                
                
                $constName=$vsConst;
            }
		}
		elseif($type=="LOK SABHA")
		{
			$stmt5=$conn->prepare("SELECT lok_sabha_constituency FROM Govt_DB WHERE aadhaar_no=?");
			$stmt5->bind_param("s",$aadhaarNo);
			$stmt5->execute();
			$stmt5->bind_result($lsConst);
			$stmt5->fetch();
			$stmt5->close();
			
			$stmt6=$conn->prepare("SELECT phase_code,state_code FROM Constituency WHERE name=?");
			$stmt6->bind_param("s",$lsConst);
			$stmt6->execute();
			$stmt6->bind_result($phaseCode,$stateCode);
			$stmt6->fetch();
			$stmt6->close();
			
			$stmt8=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE Pub_Govt_Election.state_election_id=(SELECT id FROM State_Election WHERE State_Election.state_code=? AND 
            State_Election.country_election_id=? AND State_Election.status=1) 
            AND Pub_Govt_Election.status=1 AND Pub_Govt_Election.state_code=? AND Pub_Govt_Election.phase_code=? AND Pub_Govt_Election.type=?");
			$stmt8->bind_param("sdsss",$stateCode,$electionId,$stateCode,$phaseCode,$type);
			$stmt8->execute();
			$stmt8->bind_result($phaseId);
			$stmt8->fetch();
			$stmt8->close();

            $count=-1;
            $stmt4=$conn->prepare("SELECT COUNT(aadhaar_no) FROM  Govt_Vote_Status WHERE aadhaar_no=? AND election_id=?");
			$stmt4->bind_param("sd",$aadhaarNo, $phaseId);
			$stmt4->execute();
			$stmt4->bind_result($count);
            $stmt4->fetch();
            $stmt4->close();

            if($count==0)
            {
                $count=-1;
                $response['validVoterStatus']=true;
			
                $stmt9=$conn->prepare("INSERT INTO Govt_Approval (election_id,booth_id,constituency_name,approved_at)
                VALUES (?,?,?,?)");
                $stmt9->bind_param("dsss",$phaseId,$boothId,$lsConst,date("Y-m-d H:i:s"));
                $stmt9->execute();
                $stmt9->fetch();
                $stmt9->close();
						
			    $constName=$lsConst;
            }
			
		}

        if($response['validVoterStatus'])
		    $response['garbageVoted']=garbageAndVoted($INTERNAL_AUTH_KEY,$constName,$phaseId,$aadhaarNo,$boothId);
	}
    return $response;
}

?>
