<?php

require('textlocal.class.php');
require('Credentials.php');

include 'Credentials.php';
include 'Protection.php';
include 'EncryptionKeys.php';

function sendOTP($conn, $internalAuthKey, $countryCode, $regMobNo, $voterOTP, $API_KEY, $key)
{
    include 'Credentials.php';
 
    if($internalAuthKey==$INTERNAL_AUTH_KEY)
    {
        $voterOTP=decrypt($internalAuthKey, $voterOTP, $key);
        $Textlocal = new Textlocal(false, false, $API_KEY);

        $numbers = array($countryCode.$regMobNo);
        $sender = 'RMVOTE';
        if($voterOTP=="-1")
            $voterOTP="Corrupt OTP. Contact office";
        $message = 'This is your VOTER OTP: '.$voterOTP;

            try{

        $result =$Textlocal->sendSms($numbers, $message, $sender);
            }catch(Exception $e)
            {
                $conn->close();
                die('Error: '.$e->getMessage());
            }

        if($result->status=="success")
            return true;
        else
            return false;
    }
    return false;
}


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


$aadhaarNo=$conn->real_escape_string($_POST["aadhaarNo"]);
$smsAuthKey1=$conn->real_escape_string($_POST["smsAuthKey"]);
$boothId=$conn->real_escape_string($_POST["boothId"]);
$electionId=$conn->real_escape_string($_POST["electionId"]);
$type=$conn->real_escape_string($_POST["type"]);




$response=array();
$response['success']=false;
$response['validBooth']=false;
$response['validAadhaar']=false;
$response['validSmsAuth']=false;
$response['validType']=false;
$response['validVoter']=false;
$response['validLimit']=false;


$stmt=$conn->prepare("SELECT COUNT(booth_id) FROM Booth WHERE booth_id=? AND status=1");
$stmt->bind_param("s", $boothId);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();


if($count==1)
{
    $response['validBooth']=true;
    $count=-1;

    // prepare and bind
    $stmt = $conn->prepare("SELECT country_code, reg_mob_no from Govt_DB where aadhaar_no=?");
    $stmt->bind_param("s", $aadhaarNo);

    $stmt->execute();

    $stmt->bind_result($countryCode, $regMobNo);

    if($stmt->fetch())
    {      
            $stmt->close();

            $newOtp=generateOtp($INTERNAL_AUTH_KEY);
            $newOtp=encrypt($INTERNAL_AUTH_KEY, $newOtp, $keySet[8]);

            $stmt7=$conn->prepare("UPDATE Credentials SET voter_otp=? WHERE aadhaar_no=?");
            $stmt7->bind_param("ss",$newOtp,$aadhaarNo);
            $stmt7->execute();
            $stmt7->close();

            $stmt2=$conn->prepare("SELECT voter_otp from Credentials where aadhaar_no=?");
            $stmt2->bind_param("s", $aadhaarNo);
            
            $stmt2->execute();
            $stmt2->bind_result($voterOTP);
            
            if($stmt2->fetch())
            {
                    $stmt2->close();
                    $response['validAadhaar']=true;

                    $key_name="sms_auth_key";

                    $stmt3=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name =?");
                    $stmt3->bind_param("s", $key_name);

                    $stmt3->execute();
                    $stmt3->bind_result($smsAuthKey2);


                    if($stmt3->fetch() && $smsAuthKey1==$smsAuthKey2)
                    {
                        $stmt3->close();
                        $response['validSmsAuth']=true;
                        
                        boothAutoLogout($INTERNAL_AUTH_KEY, $conn);

                        $phaseElectionId="";
                        if($type=="LOK SABHA")
                        {
                            $response['validType']=true;
                            $stmt=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE status=1 AND phase_code = (
                                SELECT phase_code FROM Constituency WHERE name=(
                                    SELECT lok_sabha_constituency FROM Govt_DB WHERE aadhaar_no=?   
                                )
                            ) AND type=? AND state_election_id = (
                                SELECT id FROM State_Election WHERE status=1 AND state_code= (
                                    SELECT state_code FROM Constituency WHERE name=(
                                        SELECT lok_sabha_constituency FROM Govt_DB WHERE aadhaar_no=?   
                                    )    
                                ) AND country_election_id=?
                            )");
                            $stmt->bind_param("sssd", $aadhaarNo, $type, $aadhaarNo, $electionId);
                            $stmt->execute();
                            $stmt->bind_result($phaseElectionId);
                            $stmt->fetch();
                            $stmt->close();
                        }
                        else if($type=="VIDHAN SABHA")
                        {
                            $response['validType']=true;
                            $stmt=$conn->prepare("SELECT id FROM Pub_Govt_Election WHERE status=1 AND phase_code = (
                                SELECT phase_code FROM Constituency WHERE name=(
                                    SELECT vidhan_sabha_constituency FROM Govt_DB WHERE aadhaar_no=?   
                                )
                            ) AND type=? AND state_election_id = (
                                SELECT id FROM State_Election WHERE status=1 AND id=?
                            )");
                            $stmt->bind_param("ssd", $aadhaarNo, $type, $electionId);
                            $stmt->execute();
                            $stmt->bind_result($phaseElectionId);
                            $stmt->fetch();
                            $stmt->close();

                        }


                        if($response['validType'])
                        {
                            $count=-1;

                            $stmt=$conn->prepare("SELECT COUNT(election_id) FROM Govt_Vote_Status WHERE election_id=? AND aadhaar_no=?");
                            $stmt->bind_param("ds", $phaseElectionId, $aadhaarNo);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $stmt->close();

                            if($count==0)
                            {
                                $response['validVoter']=true;
                                $count=-1;

                                $stmt=$conn->prepare("SELECT COUNT(sms_count), sms_count FROM otp_count WHERE booth_id=? AND aadhaar_no=? AND election_id=? AND type=?");
                                $stmt->bind_param("ssds", $boothId, $aadhaarNo, $electionId, $type);
                                $stmt->execute();
                                $stmt->bind_result($count, $smsCount);
                                $stmt->fetch();
                                $stmt->close();
                        
                                if($smsCount<4 || $count==0)
                                {
                                    $response['validLimit']=true;                                    
                                    $smsCount++;

                                    if(sendOTP($conn, $INTERNAL_AUTH_KEY, $countryCode, $regMobNo, $voterOTP, $API_KEY, $keySet[8]))
                                    {
                                        if($count==0)
                                        {
                                            $stmt=$conn->prepare("INSERT INTO otp_count (booth_id, aadhaar_no, election_id, type, sms_count) VALUES (?,?,?,?,1)");
                                            $stmt->bind_param("ssds", $boothId, $aadhaarNo, $electionId, $type);
                                            $stmt->execute();
                                            $stmt->fetch();
                                            $stmt->close();
                                        }
                                        else
                                        {
                                            $stmt=$conn->prepare("UPDATE otp_count SET sms_count=? WHERE booth_id=? AND aadhaar_no=? AND election_id=? AND type=?");
                                            $stmt->bind_param("dssds", $smsCount, $boothId, $aadhaarNo, $electionId, $type);
                                            $stmt->execute();
                                            $stmt->fetch();
                                            $stmt->close();
                                        }

                                        $response['success']=true;
                                    }
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
