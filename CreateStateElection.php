<?php

include 'Credentials.php';
include 'Protection.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$electionName=$_POST["electionName"];
$electionYear=$_POST["electionYear"];
$countryElectionId=$_POST["countryElectionId"];
$stateCode=$_POST["stateCode"];
$type=$_POST["type"];
$boothId=$_POST["boothId"];


$key_name="post_auth_key";
checkServerIp($INTERNAL_AUTH_KEY);


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validBooth']=false;
$response['validState']=false;
$response['validType']=false;
$response['validName']=false;
$response['validYear']=false;
$response['validCountryElection']=false;
$response['validElection']=false;
$response['electionId']=-1;


$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    
    $stmt=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=? AND status=1");
    $stmt->bind_param("s", $boothId);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count==1)
    {
        $count=-1;
        $stmt->close();
        $response['validBooth']=true;


        $stmt=$conn->prepare("SELECT COUNT(code) FROM State WHERE code=?");
        $stmt->bind_param("s", $stateCode);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==1)
        {
            $count=-1;
            $response['validState']=true;
            $stmt->close();

            if($type=="LOK SABHA")
            {
                $response['validType']=true;


                $stmt=$conn->prepare("SELECT COUNT(id) FROM Country_Election WHERE id=? AND status=0");
                $stmt->bind_param("d", $countryElectionId);
                $stmt->execute();
                $stmt->bind_result($count);

                if($stmt->fetch() && $count==1)
                {
                    $count=-1;
                    $response['validCountryElection']=true;
                    $stmt->close();


                    $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE country_election_id=? AND state_code=?");

                    $stmt->bind_param("ds", $countryElectionId, $stateCode);
                    $stmt->execute();
                    $stmt->bind_result($count);

                    if($stmt->fetch() && $count==0)
                    {
                        $stmt->close();
                        $count=-1;
                        $response['validElection']=true;

                        $stmt=$conn->prepare("INSERT INTO State_Election (type, state_code, country_election_id, status, year) VALUES (?, ?, ?, 0, (
    SELECT year FROM Country_Election WHERE id=?
    ))");
                        $stmt->bind_param("ssdd", $type, $stateCode, $countryElectionId, $countryElectionId);
                        $stmt->execute();

                        $stmt->close();

                        $stmt=$conn->prepare("SELECT id FROM State_Election WHERE state_code=? AND country_election_id=?");
                        $stmt->bind_param("sd", $stateCode, $countryElectionId);
                        $stmt->execute();
                        $stmt->bind_result($response['electionId']);
                        $stmt->fetch();
                        $stmt->close();

                        if($response['electionId']!=-1)
                            $response['success']=true;
                    }
                }
            }
            else if($type=="VIDHAN SABHA")
            {
                $response['validType']=true;

                $electionName=trim($electionName);
                $electionName=strtoupper($electionName);

                if(strlen($electionName)!=0 && ctype_alpha($electionName[0]))   //Has to start with a letter
                {
                    $response['validName']=true;

                    $currentYear=date("Y");

                    if($electionYear>=$currentYear)     //Cannot be less than present year
                    {
                        $response['validYear']=true;

                        $stmt=$conn->prepare("SELECT COUNT(id) FROM State_Election WHERE name = ? AND year = ? AND type=?");

                        $stmt->bind_param("sds", $electionName, $electionYear, $type);
                        $stmt->execute();
                        $stmt->bind_result($count);

                        if($stmt->fetch() && $count==0)
                        {
                            $stmt->close();
                            $count=-1;
                            $response['validElection']=true;

                            $stmt=$conn->prepare("INSERT INTO State_Election (name, type, state_code, status, year) VALUES (?, ?,?, 0, ?)");
                            $stmt->bind_param("sssd", $electionName, $type, $stateCode, $electionYear);
                            $stmt->execute();

                            $stmt->close();

                            $stmt=$conn->prepare("SELECT id FROM State_Election WHERE state_code=? AND name=? AND year=?");
                            $stmt->bind_param("ssd", $stateCode, $electionName, $electionYear);
                            $stmt->execute();
                            $stmt->bind_result($response['electionId']);
                            $stmt->fetch();
                            $stmt->close();

                            if($response['electionId']!=-1)
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
