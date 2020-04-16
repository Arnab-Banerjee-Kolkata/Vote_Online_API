<?php

include 'Credentials.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$postAuthKey1=$_POST["postAuthKey"];
$electionId=$_POST["electionId"];
$stateCode=$_POST["stateCode"];
$type=$_POST["type"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validType']=false;
$response['validState']=false;
$response['validElection']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);



if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;


    if($type=="LOK SABHA" || $type=="VIDHAN SABHA")
    {
        $response['validType']=true;
        
        $count=-1;


        

        $stmt=$conn->prepare("SELECT COUNT(code) FROM State WHERE code=?");
        $stmt->bind_param("s",$stateCode);
        $stmt->execute();
        $stmt->bind_result($count);


        if($type=="VIDHAN SABHA")
        {   
            $stmt->close();

            $stmt=$conn->prepare("SELECT COUNT(id), status FROM State_Election WHERE id=? AND type=? AND (status=2 OR status=3)");
            $stmt->bind_param("ds", $electionId, $type);
            $stmt->execute();
            $stmt->bind_result($count, $status);

            if($stmt->fetch() && $count>=1)
            {
                $response['validElection']=true;
                $response['status']=$status;
                $stmt->close();
                $count=-1;

                $stmt=$conn->prepare("SELECT party_name, seats_won FROM State_Election_Result WHERE state_election_id=?");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($partyName, $seatsWon);

                $results=array();

                while($stmt->fetch())
                {
                    $result=array();
                    $result['partyName']=$partyName;
                    $result['seatsWon']=$seatsWon;
                    array_push($results, $result);
                }
                $stmt->close();

                $response['success']=true;

                $response['results']=$results;

            }


        }

        else if(strlen($stateCode)!=0)   //Lok Sabha with state
        {
            $response['validState']=true;
            $stmt->close();
            $count=-1;

            $stmt=$conn->prepare("SELECT COUNT(id), id, status FROM State_Election WHERE country_election_id=? AND type=? AND state_code=? AND (status=2 OR status=3)");
            $stmt->bind_param("dss", $electionId, $type, $stateCode);
            $stmt->execute();
            $stmt->bind_result($count, $stateElectionId, $status);

            if($stmt->fetch() && $count==1)
            {
                $response['validElection']=true;
                $response['status']=$status;
                $stmt->close();
                $count=-1;

                $stmt=$conn->prepare("SELECT party_name, seats_won FROM State_Election_Result WHERE state_election_id=? AND country_election_id=?");
                $stmt->bind_param("dd", $stateElectionId, $electionId);
                $stmt->execute();
                $stmt->bind_result($partyName, $seatsWon);

                $results=array();

                while($stmt->fetch())
                {
                    $result=array();
                    $result['partyName']=$partyName;
                    $result['seatsWon']=$seatsWon;
                    array_push($results, $result);
                }
                $stmt->close();

                $response['success']=true;

                $response['results']=$results;
            }

        }
        else        //Lok sabha without state
        {
            $stmt->close();
            $count=-1;

            $stmt=$conn->prepare("SELECT COUNT(id), status FROM Country_Election WHERE id=? AND (status=2 OR status=3)");
            $stmt->bind_param("d", $electionId);
            $stmt->execute();
            $stmt->bind_result($count,$status);

            if($stmt->fetch() && $count==1)
            {
                $response['validElection']=true;
                $response['status']=$status;
                $stmt->close();
                $count=-1;

                $stmt=$conn->prepare("SELECT party_name, seats_won FROM Country_Election_Result WHERE country_election_id=?");
                $stmt->bind_param("d", $electionId);
                $stmt->execute();
                $stmt->bind_result($partyName, $seatsWon);

                $results=array();

                while($stmt->fetch())
                {
                    $result=array();
                    $result['partyName']=$partyName;
                    $result['seatsWon']=$seatsWon;
                    array_push($results, $result);
                }
                $stmt->close();

                $response['success']=true;

                $response['results']=$results;

            }
        }

    }

    
}
$conn->close();

echo json_encode($response);


?>