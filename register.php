<?php 
require_once("./db.php");
date_default_timezone_set("Asia/Kuala_Lumpur");

if(isset($_POST["register"]))
{
    if(empty(trim($_POST["username"])) || empty(trim($_POST["employeeID"])) || empty(trim($_POST["permission"])) || empty(trim($_POST["password"])) || empty(trim($_POST["confirm-password"]))) 
    {
        echo "<script>alert('Did not provide full registration data.'); location.href = './register.php';</script>";
        return;
    }

    if(strlen(trim($_POST["employeeID"])) != 8)
    {
        echo "<script>alert('Invalid Employee ID.'); location.href = './register.php';</script>";
        return;
    }

    $username = trim($_POST["username"]);
    $employeeID = trim($_POST["employeeID"]);
    $permission = trim($_POST["permission"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm-password"]);
    $date_registerd = date("Y-m-d H:i:s");

    $query = "SELECT * FROM users WHERE employeeID = '".$employeeID."'";

    $row = $conn->query($query)->num_rows;

    if($row > 0)
    {
        echo "<script>alert('".$employeeID." already registered'); location.href = './register.php';</script>";
        return;
    }

    if($password == $confirm_password)
    {
        $encryptedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, employeeID, password, role, date_registered) VALUES (?,?,?,?,?)");

        $stmt->bind_param("sssss", $username, $employeeID, $encryptedPassword, $permission, $date_registerd);

        if($stmt->execute())
        {
            echo "<script>alert('Registered Successfully')</script>";

        } else 
        {
            echo "<script>alert('Failed to register')</script>";
        }

    } else 
    {
        echo "<script>alert('Password not match with confirm password')</script>";
    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="./static/css/output.css">
</head>
<body class="flex flex-col bg-slate-400 h-screen">
    <?php require_once("./Components/header.php"); ?>
    <div class="flex-1 items-center flex mb-20">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md m-auto">
            <h1 class="text-2xl font-bold mb-6 text-center text-zinc-600">Register</h1>
            <form action="./register.php" method="POST">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="employeeID" class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                    <input type="text" id="employeeID" name="employeeID" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="permission" class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
                    <div class="relative inline-block text-left w-full">
                        <div>
                            <button type="button" id="permission" class="inline-flex w-full justify-between rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span id="span-permission">Engineer</span>
                                <input type="text" hidden name="permission" id="input-permission" value="Engineer"/>
                                <svg class="-mr-1 ml-2 h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="absolute right-0 z-10 mt-2 w-full origin-top-right rounded-md bg-white shadow-lg ring-1 ring-gray-300 focus:outline-none hidden" id="permission-selections">
                            <div class="py-1">
                                <div class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer" onclick="selectPermission(event)">Engineer</div>
                                <div class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer" onclick="selectPermission(event)">Technician</div>
                                <div class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer" onclick="selectPermission(event)">Operator</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                </div>
                <div class="mb-6">
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                </div>
                <button type="submit" name="register" value="register" class="w-full bg-slate-600 text-white px-4 py-2 rounded-md hover:bg-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500">Register</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('permission').addEventListener('click', () =>
        {
            document.getElementById('permission-selections').classList.toggle('hidden');
        });

        const selectPermission = (event) =>
        {
            document.getElementById("span-permission").textContent = event.target.textContent.trim();
            document.getElementById("input-permission").value = event.target.textContent;
            document.getElementById('permission-selections').classList.toggle('hidden');
        }

        document.addEventListener("click", (event) =>
        {
            if(event.target !== document.getElementById('permission') && event.target !== document.getElementById('span-permission')) document.getElementById('permission-selections').classList.add('hidden');

        });

    </script>
</body>
</html>