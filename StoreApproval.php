<?php

function storeApproval($conn,$internalAuthKey,$aadhaarNo,$electionId,$type,$boothId)
{
	include 'Credentials.php';
	
	$response=array();
	$response['validInternalAuth']=false;
	
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
			
			$stmt3=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE
			state_code=? AND phase_code=? AND state_election_id=? AND status=1");
			$stmt3->bind_param("ssd",$stateCode,$phaseCode,$electionId);
			$stmt3->execute();
			$stmt3->bind_result($phaseId);
			$stmt3->fetch();
			$stmt3->close();
			
			$stmt4=$conn->prepare("INSERT INTO Govt_Approval (election_id,booth_id,constituency_name) 
			VALUES (?,?,?)");
			$stmt4->bind_param("dss",$phaseId,$boothId,$vsConst);
			$stmt4->execute();
			$stmt4->close();
			
			$response['phaseid']=$phaseId;
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
			
			$stmt7=$conn->prepare("SELECT id FROM State_Election WHERE state_code=? AND country_election_id=? AND status=1");
			$stmt7->bind_param("sd",$stateCode,$electionId);
			$stmt7->execute();
			$stmt7->bind_result($stateid);
			$stmt7->fetch();
			$stmt7->close();
			
			$stmt8=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE state_election_id=? AND status=1");
			$stmt8->bind_param("d",$stateid);
			$stmt8->execute();
			$stmt8->bind_result($id1);
			$stmt8->fetch();
			$stmt8->close();
			
			$stmt9=$conn->prepare("INSERT INTO Govt_Approval (election_id,booth_id,constituency_name)
			VALUES (?,?,?)");
			$stmt9->bind_param("dss",$id1,$boothId,$lsConst);
			$stmt9->execute();
			$stmt9->fetch();
			$stmt9->close();
			
			$response['id']=$id1;
			
		}
	}
}
return $response;


?>
