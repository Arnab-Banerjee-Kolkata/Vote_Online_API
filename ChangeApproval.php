<?php

include 'Credentials.php';

//create
$conn=new mysqli($servername,$username,$password,$dbname);

//check
if ($conn->connect_error){
	die("Connection failed: " . $conn->connect_error);
}

$postAuthKey=$_POST["postAuthKey"];
$aadhaarNo=$_POST["aadhaarNo"];
$approvalState=$_POST["approvalState"];
$electionId=$_POST["electionId"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validElection']=false;
$response['validAadhaar']=false;

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);


if($stmt3->fetch() && $postAuthKey==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;

    $stmt=$conn->prepare("SELECT COUNT(id) FROM Pub_Govt_Election WHERE id=? AND status=1");
    $stmt->bind_param("d", $electionId);
    $stmt->execute();
    $stmt->bind_result($count);

    if($stmt->fetch() && $count==1)
    {
        $count=0;
        $stmt->close();
        $response['validElection']=true;

        $stmt=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_DB WHERE aadhaar_no=?");
        $stmt->bind_param("s", $aadhaarNo);
        $stmt->execute();
        $stmt->bind_result($count);

        if($stmt->fetch() && $count==1)
        {
            $stmt->close();
            $response['validAadhaar']=true;
            $count=0;
	
            if($approvalState==1)
            {
                $stmt=$conn->prepare("INSERT INTO Govt_Approval (election_id, aadhaar_no) VALUES (?, ?)");
                $stmt->bind_param("ss", $electionId, $aadhaarNo);
                $stmt->execute();
                
                $stmt->close();
            }
            else
            {
                $stmt=$conn->prepare("DELETE FROM Govt_Approval WHERE election_id=? AND aadhaar_no=?");
                $stmt->bind_param("ss", $electionId, $aadhaarNo);
                $stmt->execute();
                
                $stmt->close();
            }
            
            $response['success']=true;
        }
    }
}
$conn->close();	

echo json_encode($response);


?>
