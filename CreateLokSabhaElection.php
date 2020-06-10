<?php

include 'Credentials.php';
include 'Protection.php';

checkServerIp($INTERNAL_AUTH_KEY);
foreach($_POST as $element)
{
    checkForbiddenPhrase($INTERNAL_AUTH_KEY, $element);
}
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ");
}


$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$electionName=$conn->real_escape_string($_POST["electionName"]);
$electionYear=$conn->real_escape_string($_POST["electionYear"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validName']=false;
$response['validYear']=false;
$response['validElection']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);

if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    adminAutoLogout($INTERNAL_AUTH_KEY, $conn);
    
    $stmt=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count==1)
    {
        $count=-1;
        $stmt->close();
        $response['validAdmin']=true;

        $electionName=trim($electionName);
        $electionName=strtoupper($electionName);

        if(strlen($electionName)!=0 && ctype_alpha($electionName[0]))   //Has to start with a letter
        {
            $response['validName']=true;

            $currentYear=date("Y");

            if($electionYear>=$currentYear)     //Cannot be less than present year
            {
                $response['validYear']=true;

                $stmt=$conn->prepare("SELECT COUNT(id) FROM Country_Election WHERE name = ? AND year = ?");

                $stmt->bind_param("sd", $electionName, $electionYear);
                $stmt->execute();
                $stmt->bind_result($count);

                if($stmt->fetch() && $count==0)
                {
                    $stmt->close();
                    $count=-1;
                    $response['validElection']=true;

                    //CREATE COUNTRY ELECTION
                    $stmt=$conn->prepare("INSERT INTO Country_Election (name, status, year) VALUES (?, 0, ?)");
                    $stmt->bind_param("sd", $electionName, $electionYear);
                    $stmt->execute();
                    $stmt->close();


                    $stmt=$conn->prepare("SELECT id FROM Country_Election WHERE name = ? AND year= ?");
                    $stmt->bind_param("sd", $electionName, $electionYear);
                    $stmt->execute();
                    $stmt->bind_result($countryElectionId);
                    $stmt->fetch();
                    $stmt->close();


                    $stateCode=array();

                    $stmt=$conn->prepare("SELECT code FROM State");
                    $stmt->execute();
                    $stmt->bind_result($code);
                    
                    while($stmt->fetch())
                    {
                        array_push($stateCode, $code);
                    }

                    $stmt->close();

                    //CREATE STATE ELECTIONS

                    foreach($stateCode as $code)
                    {
                        $stmt=$conn->prepare("INSERT INTO State_Election (type, state_code, country_election_id, status, year) VALUES ('LOK SABHA', ?, ?, 0, ?)");
                        $stmt->bind_param("sdd", $code, $countryElectionId,$electionYear);
                        $stmt->execute();
                        $stmt->fetch();
                        $stmt->close();

                        $stmt=$conn->prepare("SELECT id FROM State_Election WHERE type='LOK SABHA' AND state_code=? AND country_election_id=? AND status=0 AND year=?");
                        $stmt->bind_param("sdd", $code, $countryElectionId, $electionYear);
                        $stmt->execute();
                        $stmt->bind_result($stateElectionId);
                        $stmt->fetch();
                        $stmt->close();


                        $phases=array();

                        $stmt=$conn->prepare("SELECT DISTINCT(phase_code) FROM Constituency WHERE state_code=? AND phase_code LIKE 'LS%'");
                        $stmt->bind_param("s", $code);
                        $stmt->execute();
                        $stmt->bind_result($phaseCode);
                        
                        while($stmt->fetch())
                        {
                            array_push($phases, $phaseCode);
                        }

                        $stmt->close();

                        //CREATE PHASE ELECTIONS
                        foreach($phases as $phaseCode)
                        {
                            $stmt=$conn->prepare("INSERT INTO Pub_Govt_Election (state_code, phase_code, type, status, state_election_id) VALUES (?, ?, 'LOK SABHA', 0, ?)");
                            $stmt->bind_param("ssd", $code, $phaseCode, $stateElectionId);
                            $stmt->execute();
                            $stmt->fetch();
                            $stmt->close();

                            $response['success']=true;
                        }
                    }
                }
            }

        }

    }
}
$conn->close();

echo json_encode($response);


?>