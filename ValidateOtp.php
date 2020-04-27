<?php

include 'Credentials.php';


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$category=$_POST["category"];
$boothId=$_POST["boothId"];
$otp1=$_POST["otp"];
$postAuthKey1=$_POST["postAuthKey"];
$aadhaarNo=$_POST["aadhaarNo"];
$electionId=$_POST["electionId"];


$key_name="post_auth_key";


$response=array();
$response['success']=false;
$response['validAuth']=false;
$response['validCategory']=false;
$response['validBooth']=false;
$response['validStatus']=false;
$response['validApproval']=false;
$response['validOtp']=false;
$response['boothOtp']="-1";

$stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name = ?");
$stmt3->bind_param("s", $key_name);

$stmt3->execute();
$stmt3->bind_result($postAuthKey2);




if($stmt3->fetch() && $postAuthKey1==$postAuthKey2)
{
    $stmt3->close();
    $response['validAuth']=true;
    
    if($category=="voter" || $category=="booth")
    {
        $response['validCategory']=true;

        if($category=="voter")          //Called from RPD
        {
            $stmt=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=? AND status=1");
            $stmt->bind_param("s", $boothId);
            $stmt->execute();
            $stmt->bind_result($count);
            
            if($stmt->fetch() && $count==1)
            {
                $count=-1;
                $stmt->close();
                $response['validBooth']=true;
            
            
                $stmt=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_Vote_Status WHERE election_id=? AND aadhaar_no=?");
                $stmt->bind_param("ss",$electionId, $aadhaarNo);
                $stmt->execute();
                $stmt->bind_result($vts);
                if($stmt->fetch() && $vts==0)
                {
                    $response['validStatus']=true;                
                    $stmt->close();
                    $vts=-1;


                    $stmt = $conn->prepare("SELECT voter_otp FROM Credentials WHERE aadhaar_no=?");
                    $stmt->bind_param("s", $aadhaarNo);
                    $stmt->execute();
                    $stmt->bind_result($otp2);
                    if($stmt->fetch() && $otp1==$otp2)
                    {            
                        $stmt->close();
                        $response['validOtp']=true;

                        $stmt=$conn->prepare("SELECT otp FROM Booth WHERE booth_id=? AND status=1");
                        $stmt->bind_param("s", $boothId);
                        $stmt->execute();
                        $stmt->bind_result($response['boothOtp']);
                        $stmt->fetch();
                        $stmt->close();


                        $response['success']=true;            
                    }
                    else
                        $stmt->close();
                }            
                else
                {
                    $stmt->close();
                }
                $otp=rand(1000, 9999);
                $stmt2=$conn->prepare("UPDATE Credentials SET voter_otp=? WHERE aadhaar_no=?");
                $stmt2->bind_param("ss", $otp, $aadhaarNo);
                $stmt2->execute();

                $stmt2->close();
            }
        }
        else if($category=="booth")         //Called from voting device
        {
            $stmt=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=? AND status=1");
            $stmt->bind_param("s", $boothId);
            $stmt->execute();
            $stmt->bind_result($count);
            
            if($stmt->fetch() && $count==1)
            {
                $count=-1;
                $stmt->close();
                $response['validBooth']=true;
            
            
                $stmt=$conn->prepare("SELECT COUNT(aadhaar_no) FROM Govt_Approval WHERE election_id=(SELECT id FROM Pub_Govt_Election WHERE id=? AND status=1) AND aadhaar_no=?");
                $stmt->bind_param("ss",$electionId, $aadhaarNo);
                $stmt->execute();
                $stmt->bind_result($apr);
                if($stmt->fetch() && $apr!=0)
                {
                    $response['validApproval']=true;
                    $stmt->close();
                    $apr=-1;
                
                

                    $stmt = $conn->prepare("SELECT otp FROM Booth WHERE booth_id=? AND status=1");
                    $stmt->bind_param("s", $boothId);
                    $stmt->execute();
                    $stmt->bind_result($otp2);
                    if($stmt->fetch() && $otp1==$otp2)
                    {                     
                        $stmt->close();
                        $response['validOtp']=true;                    
                        
                        
                        $response['success']=true;             
                    }    
                    else
                    {
                        $stmt->close();
                    }                
                }
                else
                {
                    $stmt->close();
                }
                $otp=rand(1000, 9999);
                $stmt2=$conn->prepare("UPDATE Booth SET otp=? WHERE booth_id=?");
                $stmt2->bind_param("ss", $otp, $boothId);
                $stmt2->execute();

                $stmt2->close();
            }
        }
    }
}
$conn->close();

echo json_encode($response);


?>
