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
    <title><?=$curTable?></title>
</head>
<body class="flex flex-col bg-slate-400 h-screen">
    <?php require_once("./Components/header.php"); ?>

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
                        </tr>
                    <?php } ?>
                </tbody>
            <?php } ?>
        </table>
    </div>
    <script>
        const displayImage = (e) =>
        {
            const srcImg = e.target.src;
            const imgModal = document.createElement("div");
            imgModal.innerHTML = 
            `
            <div class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-10" id="imageModal">
                <svg fill="#000000" class="fixed top-0 right-0 cursor-pointer bg-white rounded-lg" height="50px" width="50px" onclick="(() => document.getElementById('imageModal').remove())()" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-49 -49 588.00 588.00" xml:space="preserve" stroke="#000000" stroke-width="30" transform="matrix(-1, 0, 0, -1, 0, 0)rotate(0)"><g id="SVGRepo_bgCarrier" stroke-width="0" transform="translate(0,0), scale(1)"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="7.840000000000001"></g><g id="SVGRepo_iconCarrier"> <polygon points="456.851,0 245,212.564 33.149,0 0.708,32.337 212.669,245.004 0.708,457.678 33.149,490 245,277.443 456.851,490 489.292,457.678 277.331,245.004 489.292,32.337 "></polygon> </g></svg>
                <img src="${srcImg}" class=" rounded-lg shadow-xl">
            </div>
            `;

            document.body.appendChild(imgModal);
        }
    </script>
</body>
</html>