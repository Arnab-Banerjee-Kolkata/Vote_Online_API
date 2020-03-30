<?php

include 'Credentials.php';

//ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$aadhaarNo=$_POST["aadhaarNo"];
$electionId=$_POST["electionId"];
$postAuthKey1=$_POST["postAuthKey"];
$stateCode=$_POST["stateCode"];
$phaseCode=$_POST["phaseCode"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validElection']=false;
$response['validAadhaar']=false;
$response['validAuth']=false;
$response['validConstituency']=false;
$response['validApproval']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $candidate=array();
    $type="";
    $constituencyName="";
    $countElec=0;
    $countCons=0;


    // prepare and bind
    $stmt = $conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election where id=? AND status=1 AND state_code=? AND phase_code=?");
    $stmt->bind_param("sss", $electionId, $stateCode, $phaseCode);

    $stmt->execute();

    $stmt->bind_result($countElec);

    if($stmt->fetch() && $countElec==1)
    {      
            $stmt->close();
            $response['validElection']=true;

            $stmt2=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_DB WHERE aadhaar_no=?");
            $stmt2->bind_param("s", $aadhaarNo);
            
            $stmt2->execute();
            $stmt2->bind_result($count2);
            
            if($stmt2->fetch() && $count2==1)
            {
                $stmt2->close();
                $response['validAadhaar']=true;

                $stmt=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_Approval WHERE aadhaar_no=? AND election_id=?");
                $stmt->bind_param("sd", $aadhaarNo, $electionId);
                $stmt->execute();
                $stmt->bind_result($count);

                if($stmt->fetch() && $count==1)
                {
                    $stmt->close();
                    $count=0;
                    $response['validApproval']=true;



                    $stmt=$conn->prepare("SELECT type from Pub_Govt_Election where id=?");
                    $stmt->bind_param("s", $electionId);
                    $stmt->execute();
                    $stmt->bind_result($type);
                    $stmt->fetch();
                    $stmt->close();

                    if($type=="LOK SABHA")
                    {

                        $stmt=$conn->prepare("SELECT lok_sabha_constituency FROM Govt_DB where aadhaar_no=?");
                        $stmt->bind_param("s", $aadhaarNo);
                        $stmt->execute();
                        $stmt->bind_result($constituencyName);
                        $stmt->fetch();
                        $stmt->close();
                    }
                    else
                    {
                        $stmt=$conn->prepare("SELECT vidhan_sabha_constituency FROM Govt_DB where aadhaar_no=?");
                        $stmt->bind_param("s", $aadhaarNo);
                        $stmt->execute();
                        $stmt->bind_result($constituencyName);
                        $stmt->fetch();
                        $stmt->close();         
                    }

                    $stmt=$conn->prepare("SELECT count(name) FROM Constituency WHERE phase_code=? AND state_code=? AND name=?");
                    $stmt->bind_param("sss", $phaseCode, $stateCode, $constituencyName);
                    $stmt->execute();
                    $stmt->bind_result($countCons);
                    
                    if($stmt->fetch() && $countCons==1)
                    {
                        
                        $response['validConstituency']=true;
                        $stmt->close();

                        $stmt=$conn->prepare("SELECT Candidate.id, Candidate.name, Candidate.party_name, Candidate.img, Party.symbol FROM Candidate, Party WHERE Candidate.election_id=? AND Candidate.constituency_name=? AND Party.name=Candidate.party_name");
                        $stmt->bind_param("ss", $electionId, $constituencyName);
                        $stmt->execute();
                        $stmt->bind_result($candidateId, $candidateName, $partyName, $imgPath, $symbol);
                    

                        while($stmt->fetch())
                        {
                            $temp=array();
                            $temp['symbol']=$symbol;
                            $temp['partyName']=$partyName;
                            $temp['candidateName']=$candidateName;
                            $temp['candidateId']=$candidateId;
                            $temp['imgPath']=$imgPath;
                            array_push($candidate, $temp);
                        }

                        $stmt->close();

                        $stmt=$conn->prepare("DELETE FROM Govt_Approval WHERE election_id=? AND aadhaar_no=?");
                        $stmt->bind_param("ds",$electionId, $aadhaarNo);
                        $stmt->execute();
                        
                        $stmt->close();

                        $response['success']=true;

                        $response['candidates']=$candidate;
                    }
                }
            }
    }

    
    
}
$conn->close();

echo json_encode($response);


?>
