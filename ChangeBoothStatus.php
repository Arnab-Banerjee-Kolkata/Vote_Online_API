<?php

include 'Credentials.php';
include 'Protection.php';
include 'EncryptionKeys.php';

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

date_default_timezone_set("Asia/Kolkata");

$postAuthKey1=$conn->real_escape_string($_POST["postAuthKey"]);
$adminId=$conn->real_escape_string($_POST["adminId"]);
$boothId=$conn->real_escape_string($_POST["boothId"]);
$newStatus=$conn->real_escape_string($_POST["newStatus"]);

$key_name="post_auth_key";

$response=array();
$response['validAuth']=false;
$response['validAdmin']=false;
$response['validBooth']=false;
$response['validStatus']=false;
$response['success']=false;

$stmt=$conn->prepare("SELECT key_value FROM Authenticate_Keys WHERE name=?");
$stmt->bind_param("s",$key_name);
$stmt->execute();
$stmt->bind_result($postAuthKey2);

if($stmt->fetch() && $postAuthKey1==$postAuthKey2)
{
	$stmt->close();
	$response['validAuth']=true;

    adminAutoLogout($INTERNAL_AUTH_KEY, $conn);
    boothAutoLogout($INTERNAL_AUTH_KEY, $conn);
	
	$stmt1=$conn->prepare("SELECT COUNT(id) FROM Admin_Credentials WHERE id=? AND status=1");
	$stmt1->bind_param("s",$adminId);
	$stmt1->execute();
	$stmt1->bind_result($count1);
	
	if($stmt1->fetch() && $count1==1)
	{
		$stmt1->close();
		$count1=-1;
		$response['validAdmin']=true;

        $stmt=$conn->prepare("SELECT COUNT(booth_id),status FROM Booth WHERE booth_id=?");
        $stmt->bind_param("s", $boothId);
        $stmt->execute();
        $stmt->bind_result($count, $oldStatus);
        $stmt->fetch();
        $stmt->close();

        if($count==1)
        {
            $response['validBooth']=true;
            $count=-1;

            if(($oldStatus==2 && $newStatus==0) || ($oldStatus==0 && $newStatus==2) || ($oldStatus==1 && $newStatus==2))
            {
                $response['validStatus']=true;

                $stmt=$conn->prepare("UPDATE Booth SET status=? WHERE booth_id=?");
                $stmt->bind_param("ds", $newStatus, $boothId);
                $stmt->execute();
                $affectedRows=mysqli_affected_rows($conn);
                $stmt->fetch();
                $stmt->close();

                if($affectedRows==1)
                {
                    $response['success']=true;
                }
            }
        }
	}
	
	else
	{
		$stmt1->close();
	}
}
else
{
	$stmt->close();
}
$conn->close();
echo json_encode($response);

?>
