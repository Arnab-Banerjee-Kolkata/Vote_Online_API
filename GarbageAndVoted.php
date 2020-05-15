<?php

function garbageAndVoted($internalAuthKey,$constName,$phaseId,$aadhaarNo,$boothId)
{
	include 'Credentials.php';
	
    
	// Create connection
	$vbConn = new mysqli($vbServerName, $vbUserName, $vbPassword, $vbDbName);
	
	$internalAuthKey=$vbConn->real_escape_string($internalAuthKey);
	$constName=$vbConn->real_escape_string($constName);
	$phaseId=$vbConn->real_escape_string($phaseId);
	$aadhaarNo=$vbConn->real_escape_string($aadhaarNo);
	$boothId=$vbConn->real_escape_string($boothId);
	

	// Check connection
	if ($vbConn->connect_error) {
    die("Connection failed: " . $vbConn->connect_error);
    }


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
				
			}
			else
			{
				$count1=-2;

				$stmt3=$vbConn->prepare("INSERT INTO Govt_Vote (phase_election_id,en_vote,constituency_name,count) VALUES (?,?,?,1)");
				$stmt3->bind_param("dss",$phaseId,$garbage,$constName);
				$stmt3->execute();
				$stmt3->fetch();
				$stmt3->close();
				
			}
			
			$conn = new mysqli($servername, $username, $password, $dbname);

			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			
			$stmt4=$conn->prepare("INSERT INTO Govt_Vote_Status (election_id,aadhaar_no,booth_id) VALUES (?,?,?)");
			$stmt4->bind_param("dss",$phaseId,$aadhaarNo,$boothId);
			$stmt4->execute();
			$stmt4->fetch();
			$stmt4->close();
			
			$response['success']=true;
		}
		
		$conn->close();
		$vbConn->close();
		return $response;
		
}

?>
