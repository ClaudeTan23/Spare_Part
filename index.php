<?php 
require_once("./db.php");
require_once("./Modules/security.php");

$tables = $conn->query("SHOW TABLES");
$tables = $tables->fetch_all(MYSQLI_NUM);
$sortedTables = [];
$columns = [];
$columnsType = [];
$curTable = (!empty($_GET["table"])) ? $_GET["table"] : "";
$curColumn = (!empty($_GET["col"])) ? $_GET["col"] : "";
$startDate = (!empty($_GET["startDate"])) ? $_GET["startDate"] : "";
$endDate = (!empty($_GET["endDate"])) ? $_GET["endDate"] : "";
$verifiedIndex = 3;
$ignoreColumns = 3;
$sortVerifiedByIndex = 3;
$queryString = $_SERVER["QUERY_STRING"];

if(isset($_POST["check"]) && !empty($_POST["check"]) && !empty($_POST["check_row_id"]) &&
   !empty($_POST["check_table_name"]) && !empty($_POST["check_id_name"]))
{
        $rowId = $_POST["check_row_id"];
        $checkType = $_POST["check"];
        $tableName = $_POST["check_table_name"];
        $idName = $_POST["check_id_name"];

        $getResult = $conn->query("SELECT * FROM `".$tableName."` WHERE `".$idName."` = '".$rowId."'");

        if($getResult->num_rows > 0)
        {
            $row = $getResult->fetch_assoc();
            $IsIn = ($checkType == "checkin") ? true : false;

            if($IsIn)
            {
                if($row["Check Status"] == "OUT")
                {
                    if($conn->query("UPDATE `".$tableName."` SET `Check Status` = 'IN' WHERE `".$idName."` = '".$rowId."' "))
                    {
                        echo "<script>alert('You have CheckIn! Row id ".$rowId."'); location.href = './?table=".$tableName."';</script>";
                        

                    } else 
                    {
                        echo "<script>alert('Mysql error');</script>";
                    }

                } else 
                {
                    echo "<script>alert('Row with id ".$rowId." already CheckIn');</script>";
                }

            }  else 
            {
                if($row["Check Status"] == "IN")
                {
                    if($conn->query("UPDATE `".$tableName."` SET `Check Status` = 'OUT' WHERE `".$idName."` = '".$rowId."' "))
                    {
                        echo "<script>alert('You have CheckOut! Row id ".$rowId."'); location.href = './?table=".$tableName."';</script>";

                    } else 
                    {
                        echo "<script>alert('Mysql error');</script>";
                    }

                } else 
                {
                    echo "<script>alert('Row with id ".$rowId." already CheckOut');</script>";
                }
            }

        } else 
        {
            echo "<script>alert('This row doens't exist');</script>";
        }
}

if(isset($_POST["delete_table"]))
{
    if($user["role"] != "Engineer")
    {
        echo "<script>alert('Unauthorized Transaction');</script>";

    } else 
    {
        $tableName = $_POST["deleteTableName"];

        if($conn->query("DROP TABLE `".$tableName."`"))
        {
            echo "<script>alert('Table \"".$tableName."\" deleted'); window.location.href = './';</script>";

        } else 
        {
            echo "<script>alert('Mysql error');</script>";
        }
    }
}

if(isset($_POST["delete_row"]))
{
    $rowID = $_POST["rowId"];
    $rowTable = $_POST["rowTable"];
    $rowIdName = $_POST["colIdName"];

    $getResult = $conn->query("SELECT * FROM `".$rowTable."` WHERE `".$rowIdName."` = '".$rowID."'");

    if($getResult->num_rows > 0)
    {
        $row = $getResult->fetch_assoc();

        if($row["AddedBy"] == $user["employeeID"] || $user["role"] == "Engineer")
        {
            if($conn->query("DELETE FROM `".$rowTable."` WHERE `".$rowIdName."` = '".$rowID."'")) 
            {
                echo "<script>alert('Row deleted'); location.href = './?table=".$rowTable."';</script>";

            } else 
            {
                echo "<script>alert('Mysql error');</script>";
            }

        } else 
        {
            echo "<script>alert('Authorized Transaction');</script>";
        }

    } else 
    {
        echo "<script>alert('This row doens't exist');</script>";
    }
}

if(count($tables) > 1)
{
    foreach($tables as $table)
    {
        array_push($sortedTables, $table[0]);
    }

    $index = array_search("users", $sortedTables);
    array_splice($sortedTables, $index, 1);
    
}

// if(count($sortedTables) > 0 && empty($curTable)) header("location: ./?table=".$sortedTables[0]);

if(count($sortedTables) > 0 && empty($curTable)) echo "<script>location.href = './?table=".$sortedTables[0]."'</script>";

if(count($sortedTables) > 0 && !empty($curTable))
{   
    if(!in_array($curTable, $sortedTables)) echo "<script>alert('Invalid table, table ".$curTable." does not exist'); location.href= './?table=".$sortedTables[0]."'</script>";
    
    $cols = $conn->query("SELECT * FROM `".$curTable."`");
    $cols = $cols->fetch_fields();

    $colsType = $conn->query("DESCRIBE `".$curTable."`");
    $colsType->fetch_all(MYSQLI_ASSOC);

    foreach($colsType as $colType)
    {
        array_push($columnsType, $colType["Type"]);
    }

    foreach($cols as $col)
    {
        array_push($columns, $col->name);
    }
}

if(count($sortedTables) > 0 && !empty($curTable) && !empty($curColumn) && !empty($_GET["val"]))
{
    $betweenQuery = (!empty($startDate) && !empty($endDate)) ? " AND `Date Added` BETWEEN '".$startDate."' AND '".$endDate."'" : "";
    
    $query = "SELECT a.*, CONCAT(b.`employeeID`, ' (', b.`username`, ')') AS `Verifier` FROM `".$curTable."` AS a LEFT JOIN `users` AS b ON a.`Added By` = b.`employeeID` WHERE `".$curColumn."` LIKE '%".$_GET["val"]."%'";
    $query .= $betweenQuery;
    $query .= " ORDER BY `".$columns[0]."` DESC";

    $results = $conn->query($query);

    $results = $results->fetch_all(MYSQLI_NUM);

    function sortArray($val)
    {
        $val[count($val) - 4] = $val[count($val) - 1];
        array_splice($val, count($val) - 1, 1);

        return $val;
    }

    $results = array_map("sortArray", $results);
    

} else if(count($sortedTables) > 0 && !empty($curTable) && (empty($curColumn) || empty($_GET["val"])))
{
    $betweenQuery = (!empty($startDate) && !empty($endDate)) ? " WHERE `Date Added` BETWEEN '".$startDate."' AND '".$endDate."'" : "";
    // $query = "SELECT * FROM `".$curTable."`";
    $query = "SELECT a.*, CONCAT(b.`employeeID`, ' (', b.`username`, ')') AS `Verifier` FROM `".$curTable."` AS a LEFT JOIN `users` AS b ON a.`Added By` = b.`employeeID`";
    $query .= $betweenQuery;
    $query .= " ORDER BY `".$columns[0]."` DESC";

    $results = $conn->query($query);
    $results = $results->fetch_all(MYSQLI_NUM);

    function sortArray($val)
    {
        $val[count($val) - 4] = $val[count($val) - 1];
        array_splice($val, count($val) - 1, 1);

        return $val;
    }

    $results = array_map("sortArray", $results);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./static/css/output.css">
    <title>Home</title>
</head>
<body class="flex flex-col bg-slate-400 h-screen">
    <?php require_once("./Components/header.php"); ?>

    <?php if($user["role"] == "Engineer") { ?>
        <form id="modal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-10 hidden" action="./Modules/API/tables.php" method="POST">
            <div class="bg-white rounded-lg shadow-lg w-[800px] max-w-[820px]:w-[500px] mx-auto p-6 max-h-[90vh] overflow-y-auto animate-popsUp">
                <div class="flex justify-between items-start">
                    <h1 class="text-2xl font-semibold mb-4 px-4 py-2">Create Table</h1>
                    <button type="button" class="px-4 py-2 rounded bg-slate-700 text-white" onclick="addColumn()">Add Column</button>
                </div>
                <div class="flex flex-col gap-2 py-1">
                    <label for="tableName" class="font-semibold">Table Name</label>
                    <input type="text" id="tableName" placeholder="Table Name" name="tablename" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                </div>
                <div id="add-cols-container">
                    <div class="flex flex-col gap-2 py-1">
                        <div class="flex justify-between items-end">
                            <label for="col1" class="font-semibold">Column 1</label>
                            <div class="flex justify-end gap-2">
                                <div class=" gap-1 flex items-center">
                                    <span class=" font-semibold">Set As Image: </span>
                                    <input type="checkbox" name="col-set-image" class="cursor-pointer form-checkbox h-5 w-5 text-blue-600" />
                                </div>
                            </div>
                        </div> 
                        <input type="text" id="col1" placeholder="Column Name" name="cols" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                    </div>
                </div>

                <div class="flex justify-between pt-3">
                    <button type="submit" name="create_table" class="bg-green-600 text-white px-4 py-2 rounded w-5/12">Create</button>
                    <button type="button" onclick="toggleModal()" class="bg-red-500 text-white px-4 py-2 rounded w-5/12">Close</button>
                </div>
            </div>
        </form>
    <?php } ?>
    
    <form id="modal-add-row" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-10 hidden" action="./Modules/API/tables.php" method="POST">
        <div class="animate-popsUp bg-white rounded-lg shadow-lg w-[800px] max-w-[820px]:w-[500px] mx-auto p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start">
                <h1 class=" text-2xl font-bold mb-4 py-2">Add Row</h1>
                <input type="text" hidden name="table_name" value="<?=$curTable?>" />
            </div>
          
            <?php for($i = 1; $i < count($columns)-$ignoreColumns; $i++) { ?>
                <div class="flex flex-col gap-2 py-1">
                    <label for="colValue<?=$i?>" class="font-semibold"><?=$columns[$i]?>:</label>
                    <?php if($columnsType[$i] == "longtext") { ?>
                        <img src="" class="w-56 h-40 object-fit rounded-lg shadow-lg transition-transform duration-300 ease-in-out transform hover:scale-110 cursor-pointer hidden" >
                        <div class="flex gap-4">
                            <label for="add-img-<?=$i?>" class="flex text-white px-4 py-2 rounded w-5/12 bg-slate-600 justify-center gap-2 cursor-pointer shadow-md">
                                <span>Add Attachment</span>
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 16 5-7 6 6.5m6.5 2.5L16 13l-4.286 6M14 10h.01M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                                </svg>
                            </label>
                            <button type="button" class="bg-red-500 text-white px-4 py-2 rounded w-5/12 flex gap-2 justify-center hidden" onclick="removeImage(event)">Remove Attachment
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 16 5-7 6 6.5m6.5 2.5L16 13l-4.286 6M14 10h.01M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                                </svg>
                            </button>
                            <input type="text" hidden name="colNameImg" value="<?=$columns[$i]?>"/>
                            <input type="file" name="image" hidden id="add-img-<?=$i?>" accept="image/*" oninput="addImage(event, <?=$i?>)"/>
                        </div>
                    <?php } else { ?>
                        <input type="text" hidden name="colName" value="<?=$columns[$i]?>"/>
                        <?php if($columns[$i] == "Minimum Quantity" || $columns[$i] == "Current Quantity") { ?>
                            <input id="colValue<?=$i?>" type="number" autocomplete="off" name="colValue" required class=" max-h-[100px] w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                        <?php } else { ?>
                            <textarea id="colValue<?=$i?>" autocomplete="off" name="colValue" required class=" max-h-[100px] w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium"></textarea>
                        <?php } ?>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="flex justify-between pt-3">
                <button type="submit" name="add_row" class="bg-green-600 text-white px-4 py-2 rounded w-5/12">Add Row</button>
                <button type="button" onclick="toggleAddRowModal()" class="bg-red-500 text-white px-4 py-2 rounded w-5/12">Close</button>
            </div>
        </div>
    </form>

    <?php if(count($sortedTables) > 0) { ?>
        <form id="modal-edit-row" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-10 hidden" action="./Modules/API/tables.php" method="POST">
            <div class="animate-popsUp bg-white rounded-lg shadow-lg w-[800px] max-w-[820px]:w-[500px] mx-auto p-6 max-h-[90vh] overflow-y-auto">
              
            </div>
        </form>

        <form id="modal-edit-table" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-10 hidden" action="./Modules/API/tables.php" method="POST" onsubmit="updateTable(event, '<?=$curTable?>')">
            <div class="bg-white rounded-lg shadow-lg w-[800px] max-w-[820px]:w-[500px] mx-auto p-6 max-h-[90vh] overflow-y-auto animate-popsUp">
                <div class="flex justify-between items-start">
                    <h1 class="text-2xl font-semibold mb-4 px-4 py-2">Edit Table</h1>
                    <button type="button" class="px-4 py-2 rounded bg-slate-700 text-white" onclick="addNewColumn()">Add New Column</button>
                </div>
                <div class="flex flex-col gap-2 py-1">
                    <label for="tableName" class="font-semibold">Table Name</label>
                    <input type="text" id="tableName" placeholder="Table Name" name="tablename" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                </div>
                <div id="edit-table-cols">
                    <div class="flex flex-col gap-2 py-1">
                        <label for="col1" class="font-semibold">Column 1</label>
                        <input type="text" id="col1" placeholder="Column Name" name="cols" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                    </div>
                </div>

                <div class="flex justify-between pt-3">
                    <button type="submit" name="create_table" class="bg-green-600 text-white px-4 py-2 rounded w-5/12">Create</button>
                    <button type="button" onclick="toggleEditModal('<?=$curTable?>')" class="bg-red-500 text-white px-4 py-2 rounded w-5/12">Close</button>
                </div>
            </div>
        </form>

    <?php } ?>
    
    <div class="w-full p-4 flex justify-between gap-5 flex-wrap">
        <div class="flex w-auto items-center gap-5 flex-wrap">
            <input type="text" id="search" autocomplete="off" placeholder="Search..." name="val" class=" min-w-[400px] px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
            <div class="relative inline-block text-left">
                <div>
                    <button type="button" id="selection-cols" class="inline-flex w-full justify-between rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span id="col-span"><?=$curColumn?></span>
                        <svg issvg="true" class="-mr-1 ml-2 h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path issvg="true" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <div class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-gray-300 focus:outline-none hidden" id="cols">
                    <div class="py-1">
                        <?php foreach($columns as $col) { ?>
                            <div class="block px-4 py-2 text-sm font-semibold cursor-pointer text-gray-700 hover:bg-gray-100" onclick="(() => document.getElementById('col-span').textContent = '<?=$col?>')()"><?=$col?></div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-5">
            <?php if(count($sortedTables) > 0) { ?>
                <a class=" bg-slate-100 text-zinc-800 font-semibold px-4 py-2 rounded hover:bg-slate-300" href="./list.php?<?=$queryString?>" target="_blank" >View List</a>
                <?php if($user["role"] == "Engineer") { ?>
                    <button id="open-modal" class=" bg-slate-100 text-zinc-800 font-semibold px-4 py-2 rounded hover:bg-slate-300" onclick="toggleAddRowModal()">Add Row</button>
                <?php } ?>
            <?php } ?>
            <div class="relative inline-block text-left">
                <div>
                    <button type="button" id="selection-stations" class="inline-flex w-full justify-between rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span id="station-span"><?=(count($sortedTables) > 0) ? $curTable : "" ?></span>
                        <svg class="-mr-1 ml-2 h-5 w-5" issvg="true" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path issvg="true" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <div class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-gray-300 focus:outline-none hidden" id="stations">
                    <div class="py-1">
                    <?php foreach($sortedTables as $table) { ?>
                        <div class="block px-4 py-2 text-sm font-semibold cursor-pointer text-gray-700 hover:bg-gray-100" onclick="(() => window.location.href = `./?table=<?=$table?>`)()"><?=$table?></div>
                    <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-2 w-full flex justify-between">
        <div class="px-2 gap-5">
            <input type="date" autocomplete="off" id="startDate" class="bg-slate-100 text-zinc-800 font-semibold px-4 py-2 rounded hover:bg-slate-300 cursor-pointer">
            <span class="text-zinc-700 font-semibold px-1 py-2">between</span>
            <input type="date" autocomplete="off" id="endDate" class="bg-slate-100 text-zinc-800 font-semibold px-4 py-2 rounded hover:bg-slate-300 cursor-pointer">
            <button class=" bg-slate-100 text-zinc-800 font-semibold px-4 py-2 rounded hover:bg-slate-300" onclick="searchQuery()">Search</button>
        </div>
        <div class="flex gap-5">
            <?php if($user["role"] == "Engineer") { ?>
                <button id="open-modal" class="bg-slate-600 text-white px-4 py-2 font-semibold rounded hover:bg-slate-500" onclick="toggleModal()">Create New Table</button>
                <?php if(count($sortedTables) > 0) { ?>
                    <button id="open-modal" class=" bg-slate-100 text-zinc-800 font-semibold px-4 py-2 rounded hover:bg-slate-300" onclick="toggleEditModal('<?=$curTable?>')">Edit Table</button>
                    <button id="deleteTableBtn" class="bg-red-600 text-white font-semibold px-4 py-2 rounded hover:bg-red-500" onclick="deleteTable('<?=$curTable?>')">Delete Table</button>
                    <form action="./" method="POST" class="hidden" id="deleteForm">
                        <input type="text" name="deleteTableName" value="<?=$curTable?>" />
                        <input type="submit" name="delete_table" value="delete_table" />
                    </form>
                <?php } ?>
            <?php } ?>
        </div>

    </div>

    <div class="p-2 w-full flex">
        <h2 class="font-bold text-2xl text-gray-700">Table: <?=(count($sortedTables) <= 0 ? "No Table" : $curTable)?></h2>
    </div>

    <div class="overflow-x-auto flex-auto">
        <table class="min-w-full bg-white border border-gray-300 rounded-lg shadow-md">
            <?php if(count($sortedTables) > 0) { ?>
                <thead class="bg-slate-600 sticky top-[-2px]" style="z-index: 1;">
                <tr class="text-slate-200 border-b border-gray-300">
                    <?php foreach($columns as $col) { ?>
                        <th class="px-2 py-3 text-left border border-slate-800 break-words whitespace-pre"><?=$col?></th>
                    <?php } ?>
                    <th class="px-6 py-3 text-left border border-slate-800">Actions</th>
                </tr>
                </thead>
                <tbody>
                    <?php for($i = 0; $i < count($results); $i++) { ?>
                        <?php if(intval($results[$i][count($results[$i]) - 5]) < intval($results[$i][count($results[$i]) - 4])) { ?>
                            <tr class=" bg-red-800 border-gray-200 hover:bg-red-400 text-white">
                        <?php } else { ?>
                            <tr class="odd:bg-white even:bg-slate-200 border-b border-gray-200 hover:bg-gray-100">
                        <?php } ?>
                            <?php for($j = 0; $j < count($results[$i]); $j++) { ?>
                                <td class="p-2 border border-slate-800 text-start">
                                    <?php if($columnsType[$j] == "longtext") { ?>
                                        <?php if($results[$i][$j] == "-" || $results[$i][$j] == "") { ?>
                                            <span>-</span>
                                        <?php } else { ?>
                                            <div class=" overflow-hidden w-56 h-40 rounded-lg shadow-lg">
                                                <img src="./static/img/<?=$results[$i][$j]?>" class="w-56 h-40 object-fit rounded-lg shadow-lg transition-transform duration-300 ease-in-out transform hover:scale-110 cursor-pointer" onclick="displayImage(event)"/>
                                            </div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if(($j+1) == count($results[$i])) { ?>
                                            <?php if($results[$i][$j] == "OUT") { ?>
                                                <div class="max-w-[800px] overflow-y-auto font-bold text-red-600">
                                                    <?=$results[$i][$j]?>
                                                </div>
                                            <?php } else { ?>
                                                <div class="max-w-[800px] overflow-y-auto font-bold text-green-600">
                                                    <?=$results[$i][$j]?>
                                                </div>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <?php if($results[$i][$j] == "") {?>
                                                <div class="max-w-[800px] whitespace-pre break-words overflow-y-auto">-</div>
                                            <?php } else { ?>
                                                <div class="max-w-[800px] whitespace-pre break-words overflow-y-auto"><?=$results[$i][$j]?></div>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                            <td class="px-2 border border-slate-800 align-middle">
                                <div class="flex flex-row py-2 w-60">
                                    <?php if($user["role"] == "Engineer" || $user["employeeID"] == $results[$i][$j-$verifiedIndex-1]) { ?>
                                        <button class="bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600" onclick="editFetchRow('<?=$results[$i][0]?>', '<?=$curTable?>', '<?=$columns[0]?>')">Edit</button>
                                        <button onclick="deleteRow('<?=$results[$i][0]?>')" class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 ml-2">Delete</button>
                                        <form action="./" method="POST" hidden>
                                            <input type="text" name="rowId" value="<?=$results[$i][0]?>" />
                                            <input type="text" name="rowTable" value="<?=$curTable?>" />
                                            <input type="text" name="colIdName" value="<?=$columns[0]?>"/>
                                            <button type="submit" name="delete_row" value="delete_row" id="row-delete-<?=$results[$i][0]?>" class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 ml-2">Delete</button>
                                        </form>
                                    <?php } ?>

                                    <?php if($user["role"] == "Engineer") { ?>
                                        <form action="./" method="POST">
                                            <input type="text" name="check_row_id" value="<?=$results[$i][0]?>" hidden />
                                            <input type="text" name="check_id_name" value="<?=$columns[0]?>" hidden />
                                            <input type="text" name="check_table_name" value="<?=$curTable?>" hidden />
                                                <?php if($results[$i][count($results[$i]) - 1] == "OUT") { ?>
                                                    <button type="submit" name="check" value="checkin" class=" bg-green-500 text-white px-3 py-2 rounded hover:bg-green-400 ml-2">Check In</button>
                                                <?php } else { ?>
                                                    <button type="submit" name="check" value="checkout" class=" bg-red-700 text-white px-3 py-2 rounded hover:bg-red-800 ml-2">Check Out</button>
                                                <?php } ?>
                                        </form>
                                    <?php } else { ?>
                                        <form action="./" method="POST">
                                            <input type="text" name="check_row_id" value="<?=$results[$i][0]?>" hidden />
                                            <input type="text" name="check_id_name" value="<?=$columns[0]?>" hidden />
                                            <input type="text" name="check_table_name" value="<?=$curTable?>" hidden />
                                                <?php if($results[$i][count($results[$i]) - 1] == "OUT") { ?>
                                                    <button type="submit" name="check" value="checkin" class=" bg-green-500 text-white px-3 py-2 rounded hover:bg-green-400 ml-2">Check In</button>
                                                <?php } else { ?>
                                                    <button type="submit" name="check" value="checkout" class=" bg-red-700 text-white px-3 py-2 rounded hover:bg-red-800 ml-2">Check Out</button>
                                                <?php } ?>
                                        </form>
                                    <?php } ?>
                                </div>
                            </td>   
                        </tr>
                    <?php } ?>
                </tbody>
            <?php } ?>
        </table>
    </div>
    <script async src="./static/js/index.js"></script>
</body>
</html>