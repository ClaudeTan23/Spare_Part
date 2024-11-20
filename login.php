<?php 
require_once("./db.php");

if(isset($_POST["login"]))
{
    if(empty(trim($_POST["employeeID"])) || empty(trim($_POST["password"])))
    {
        echo "<script>alert('Empty login input.'); location.href = './login.php';</script>";
        return;
    }

    $employeeID = trim($_POST["employeeID"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT * FROM users WHERE employeeID = ?");
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows <= 0)
    {
        echo "<script>alert('Employee ID: \"".$employeeID."\" not register in database'); location.href = './login.php';</script>";
        return;
    }

    $user = $result->fetch_array(MYSQLI_ASSOC);

    if(password_verify($password, $user["password"]))
    {
        $_SESSION["spare_part"]["id"] = $user["id"];
        echo "<script>location.href = './';</script>";

    } else 
    {
        echo "<script>alert('Incorrect password'); location.href = './login.php';</script>";

    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./static/css/output.css">
</head>
    <body class="flex flex-col bg-slate-400 h-screen">
        <?php require_once("./Components/header.php") ?>
        
        <div class="flex-1 items-center flex mb-20">
            <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-sm m-auto">
                <h1 class="text-2xl text-zinc-600 font-bold mb-6 text-center">Login</h1>
                <form action="./login.php" method="POST">
                    <div class="mb-4">
                        <label for="employeeID" class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                        <input type="text" id="employeeID" name="employeeID" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <button type="submit" value="login" name="login" class="w-full bg-slate-600 text-white px-4 py-2 rounded-md hover:bg-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500">Login</button>
                </form>
            </div>
        </div>
    </body>
</html>