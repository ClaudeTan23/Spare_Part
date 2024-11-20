<?php 
if(empty($_SESSION["spare_part"]["id"])) 
{
    $_SESSION["ict_tracker"]["id"] = "";
    echo "<script>alert('Session Expired');</script>";
    
    header("location: ./login.php");

} else 
{
    $userID = $_SESSION["spare_part"]["id"];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows < 1)
    {
        $_SESSION["spare_part"]["id"] = "";
        echo "<script>alert('Unauthorized Session');</script>";
    
        header("location: ./login.php");
    }

    $user = $result->fetch_array(MYSQLI_ASSOC);

}

?>