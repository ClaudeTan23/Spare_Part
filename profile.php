<?php 
require_once("./db.php");
require_once("./Modules/security.php");

if(isset($_POST["update_profile"]))
{
    if(!empty(trim($_POST["userID"])) && !empty(trim($_POST["username"])) && !empty(trim($_POST["employeeID"])) && !empty(trim($_POST["update_profile"])))
    {
        $userID = trim($_POST["userID"]);
        $username = trim($_POST["username"]);
        $employeeID = trim($_POST["employeeID"]);

        if(empty(trim($_POST["password"])) || empty(trim($_POST["confirmPassword"])))
        {
            if($conn->query("UPDATE `users` SET username = '".$username."' WHERE id = '".$userID."'"))
            {
                echo "<script>alert('User profile updated'); location.href = './profile.php';</script>";

            } else 
            {
                echo "<script>alert('Update failed, MYSQL error'); location.href = './profile.php';</script>";
            }

        } else 
        {
            if(trim($_POST["password"]) == trim($_POST["confirmPassword"]))
            {
                $hashPassword = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);

                if($conn->query("UPDATE `users` SET username = '".$username."', password = '".$hashPassword."' WHERE id = '".$userID."' "))
                {
                    echo "<script>alert('User profile updated'); location.href = './profile.php';</script>";

                } else 
                {
                    echo "<script>alert('Update failed, MYSQL error'); location.href = './profile.php';</script>";
                }

            } else 
            {
                echo "<script>alert('Password and confirm password not match'); location.href = './profile.php';</script>";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$user["username"]?></title>
    <link rel="stylesheet" href="./static/css/output.css">
</head>
<body class="flex flex-col h-screen bg-slate-300">
    <?php require_once("./Components/header.php") ?>
    <form class="flex flex-auto items-center flex-col" action="./profile.php" method="POST">
        <div class="animate-popsUp bg-white rounded-lg shadow-lg w-[800px] max-[820px]:w-[500px] max-w-[820px]:bg-gray-400 p-6 min-h-[500px] max-h-[600px] overflow-y-auto mt-16">
            <div class="flex justify-between items-start">
                <h1 class=" text-2xl font-bold mb-4 py-2">User Profile</h1>
            </div>
          
                <div class="flex gap-2 py-1 flex-col">
                    <label for="userID" class="font-bold">User ID: </label>
                    <input id="userID" name="userID" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm font-semibold focus:no-underline focus:bg-none" readonly value="<?=$user["id"]?>" />
                </div>

                <div class="flex flex-col gap-2 py-1">
                    <label for="username" class="font-bold">Username:</label>
                    <input id="username" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm font-semibold focus:no-underline focus:bg-none" placeholder="<?=$user["username"]?>" value="<?=$user["username"]?>" />
                </div>

                <div class="flex gap-2 py-1 flex-col">
                    <label for="employeeID" class="font-bold">Employee ID: </label>
                    <input id="employeeID" name="employeeID" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm font-semibold focus:no-underline focus:bg-none" readonly value="<?=$user["employeeID"]?>" />
                </div>

                <div class="flex flex-col gap-2 py-1">
                    <label for="password" class="font-bold">Password:</label>
                    <div class="flex flex-col gap-2" id="password-container">
                        <div class="flex w-full gap-2">
                            <input id="password" autocomplete="off" required class=" max-h-[80px] w-[80%] px-4 py-2 border font-semibold border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" value="******" readonly/>
                            <button type="button" id="change-btn" onclick="changePassword()" class="bg-slate-600 text-white px-4 py-2 rounded w-1/5 hover:cursor-pointer hover:bg-slate-500">Change</button>
                        </div>
                    </div>
                    
                </div>

                <div class="flex flex-row gap-5 py-4 justify-between flex-wrap">
                    <label class="font-semibold">Role: <span class="px-2"><?=$user["role"]?></span></label>
                    <label class="font-semibold">Date Registered: <span class="px-2"><?=$user["date_registered"]?></span></label>
                </div>

            <div class="flex justify-between pt-3">
                <button type="submit" name="update_profile" value="update_profile" class="bg-green-600 text-white px-4 py-2 rounded w-full hover:cursor-pointer hover:bg-green-500">Update</button>
            </div>
        </div>
    </form>
    <script>
        const changePassword = () =>
        {
            document.getElementById("password-container").innerHTML = 
                `<div class="flex w-full gap-2">
                    <input type="password" name="password" autocomplete="off" required class=" max-h-[80px] w-[80%] px-4 py-2 border font-semibold border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                    <button type="button" id="change-btn" class="bg-red-600 text-white px-4 py-2 rounded w-1/5 hover:cursor-pointer hover:bg-red-500" onclick="cancelPassword()">Cancel</button>
                </div>
                <div class="flex w-full">
                    <input type="password" name="confirmPassword" autocomplete="off" required class=" max-h-[80px] w-[79%] px-4 py-2 border font-semibold border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                </div>`;
        }

        const cancelPassword = () =>
        {
            document.getElementById("password-container").innerHTML = 
                `<div class="flex w-full gap-2">
                    <input id="password" autocomplete="off" required class=" max-h-[80px] w-[80%] px-4 py-2 border font-semibold border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" value="******" readonly/>
                    <button type="button" id="change-btn" class="bg-slate-600 text-white px-4 py-2 rounded w-1/5 hover:cursor-pointer hover:bg-slate-500" onclick="changePassword()">Change</button>
                </div>`
        }
    </script>
</body>
</html>