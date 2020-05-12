<?php

function addGarbageVote($internalAuthKey,$constName,$phaseId)
{
	include 'Credentials.php';
	
	// Create connection
	$vbConn = new mysqli($vbServerName, $vbUserName, $vbPassword, $vbDbName);

	// Check connection
	if ($vbConn->connect_error) {
    die("Connection failed: " . $vbConn->connect_error);
}

	$internalAuthKey=$vbConn->real_escape_string($internalAuthKey);
	$constName=$vbConn->real_escape_string($constName);
	$phaseId=$vbConn->real_escape_string($phaseId);
	
	$response=array();
	$response['success']=false;
	$response['validInternalAuth2']=false;
	
	
	if($internalAuthKey==$INTERNAL_AUTH_KEY)
    	{
        	$response['validInternalAuth2']=true;
			
			$stmt=$vbConn->prepare("SELECT COUNT(en_vote),count FROM Govt_Vote WHERE phase_election_id=? AND en_vote=? AND constituency_name=?");
			$stmt->bind_param("dss",$phaseId,$garbage,$constName);
			$stmt->execute();
			$stmt->bind_result($count1,$countInTable);
			$stmt->fetch();
			$stmt->close();
			
			if($count1==1)
			{
				$count1=-1;
				
				$stmt2=$vbConn->prepare("UPDATE Govt_Vote SET count=? WHERE phase_election_id=? AND en_vote=? AND constituency_name=?");
				$stmt2->bind_param("ddss",++$countInTable,$phaseId,$garbage,$constName);
				$stmt2->execute();
                		$stmt2->fetch();
				$stmt2->close();
				
				$response['success']=true;
			}
			else
			{
				$count1=-2;

				$stmt3=$vbConn->prepare("INSERT INTO Govt_Vote (phase_election_id,en_vote,constituency_name,count) VALUES (?,?,?,1)");
				$stmt3->bind_param("dss",$phaseId,$garbage,$constName);
				$stmt3->execute();
                		$stmt3->fetch();
				$stmt3->close();
				
				$response['success']=true;
			}
		}
		
		return $response;
}

?>
