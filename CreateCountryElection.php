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
    die("Connection failed: " . $conn->connect_error);
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
$response['electionId']=-1;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);

if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    
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

                    $stmt=$conn->prepare("INSERT INTO Country_Election (name, status, year) VALUES (?, 0, ?)");
                    $stmt->bind_param("sd", $electionName, $electionYear);
                    $stmt->execute();

                    $stmt->close();

                    $stmt=$conn->prepare("SELECT id FROM Country_Election WHERE name = ? AND year= ?");
                    $stmt->bind_param("sd", $electionName, $electionYear);
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
$conn->close();

echo json_encode($response);


?>
