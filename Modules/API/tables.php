<?php 
//ini_set("display_errors", 1);
$dbPath = str_replace("Modules\\API", "db.php", __DIR__);
$staticPath = str_replace("Modules\\API", "static\\img", __DIR__);
require_once($dbPath);
require_once("../security.php");

date_default_timezone_set("Asia/Kuala_Lumpur");

if(isset($_POST["delete_column"]))
{
    $tableName = trim($_POST["tableName"]);
    $columnName = trim($_POST["column"]);

    if($conn->query("ALTER TABLE `".$tableName."` DROP COLUMN `".$columnName."`"))
    {
        echo json_encode(array("msg" => "Column '".$columnName."' deleted", "result" => true));
        return;

    } else 
    {
        echo json_encode(array("msg" => "Failed to delete the column", "result" => false));
        return;
    }
}

if(isset($_GET["get_table_columns"]))
{

    if(empty(trim($_GET["get_table_columns"]))) 
    {
        echo json_encode(array("msg" => "Empty table name", "result" => false));
        return;
    }

    function getTypes($val)
    {
        return $val["Type"];
    }

    $table = $_GET["get_table_columns"];
    $cols = $conn->query("SELECT * FROM `".$table."`");
    $cols = $cols->fetch_fields();

    $colsType = $conn->query("DESCRIBE `".$table."`");

    $typeRow = $colsType->fetch_all(MYSQLI_ASSOC);
    $typeRow = array_map("getTypes", $typeRow);
    array_splice($typeRow, count($typeRow) - 5, 5);

    $columns = [];

    foreach($cols as $col)
    {
        array_push($columns, $col->name);
    }

    array_splice($columns, count($columns) - 5, 5);

    echo json_encode(array("columns" => $columns, "columnsType" => $typeRow));

}

if(isset($_POST["update_table"]) && !empty($_POST["update_table"]))
{
    try 
    {
        $req = json_decode($_POST["columns"]);

        $addQuery = [];

        $columnType = $req->checkType;
        $typeIndex = 0;

        for($i = 0; $i < count($req->existedColNames); $i++)
        {
            $type = ($columnType[$typeIndex] == true) ? "LONGTEXT" : "TEXT(10000)";
            array_push($addQuery, "CHANGE COLUMN `".$req->existedColNames[$i]."` `".$req->existedVals[$i]."` ".$type);
            $typeIndex++;
        }

        for($j = 0; $j < count($req->newColumns); $j++)
        {
            $type = ($columnType[$typeIndex] == true) ? "LONGTEXT DEFAULT '-'" : "TEXT(10000) NULL DEFAULT ''";
            $afterColumn = ($j == 0) ? $req->existedVals[count($req->existedVals) - 1] : $req->newColumns[count($req->newColumns) - (1 + $j)] ;

            array_push($addQuery, "ADD COLUMN `".$req->newColumns[$j]."` ".$type." AFTER `".$afterColumn."`");
            $typeIndex++;
        }


        if($conn->query("ALTER TABLE `".$req->table."` ".join(", ", $addQuery)))
        {
            echo json_encode(array("msg" => "Table successfully updated", "result" => true));
            return;

        } else 
        {
            echo json_encode(array("msg" => "Failed to update table", "result" => false));
            return;
        }

    } catch (Exception $e)
    {
        echo json_encode(array("msg" => $e->getMessage(), "result" => false));
        return;
    }

}

if(isset($_POST["create_table"]))
{
    try 
    {
        if(empty(trim($_POST["create_table"]))) return;

        $jsonReq = $jsonReq = json_decode($_POST["create_table"]);

        $tableName = trim($jsonReq->tableName);
        $tableColumns = $jsonReq->tableColumns;
        $setImages = $jsonReq->colSetImages;

        $tables = $conn->query("SHOW TABLES");
        $tables = $tables->fetch_assoc();

        if(in_array($tableName, $tables))
        {
            echo json_encode(array("msg" => "Table name '".$tableName."' already created", "result" => false));
            return;
        } 

        $query = "CREATE TABLE `".$tableName."` (";
        $query .= "`id` INT PRIMARY KEY AUTO_INCREMENT,";

        for($i = 0; $i < count($tableColumns); $i++)
        {
            $setImage = ($setImages[$i] == true) ? "LONGTEXT," : "TEXT(10000),";

            $query .= "`".trim($tableColumns[$i])."` ".$setImage."";
        }

        $query .= "`Minimum Quantity` varchar(255) DEFAULT '0',";
        $query .= "`Current Quantity` varchar(255) DEFAULT '0',";
        $query .= "`Added By` varchar(255) NOT NULL,";
        $query .= "`Date Added` varchar(255),";
        $query .= "`Check Status` varchar(255) DEFAULT 'OUT'";
        $query .= ");";

        if($conn->query($query))
        {
            echo json_encode(array("msg" => "Table name '".$tableName."' successfully created", "result" => true));

        } else 
        {
            echo json_encode(array("msg" => "Failed to create table", "result" => false));

        }

    } catch (Exception $e)
    {
        echo json_encode(array("msg" => $e->getMessage(), "result" => false));
        return;
    }
}

if(isset($_POST["add_row"]))
{
    if(!file_exists($staticPath)) mkdir($staticPath, 0777, true);

    function addQuotes($val)
    {
        return "'".trim($val)."'";
    } 

    function addBackTick($val)
    {
        return "`".trim($val)."`";
    }

    if(empty(trim($_POST["add_row"]))) return;

    $jsonReq = json_decode($_POST["add_row"]);

    $tableName = trim($jsonReq->tableName);
    $columnNames = $jsonReq->colNames;
    $values = $jsonReq->colValues;
    $imgColNames = $jsonReq->imgColNames;
    $date_added = date("Y-m-d H:i:s");

    array_push($columnNames, "Added By", "Date Added");
    array_push($values, $user["employeeID"] ,$date_added);

    if(count($imgColNames) > 0)
    {
        if(empty($_FILES["image"]))
        {
            echo json_encode(array("msg" => "Failed to add new row into table. Upload images but didn't receive any images.", "result" => true));
            return;
        }

        for($i = 0; $i < count($imgColNames); $i++)
        {
            $tmpFile = $_FILES["image"]["tmp_name"][$i];

            $filename = str_replace(".", "", microtime(true)).".png";
            $filePath = $staticPath."\\".$filename;

            if(!move_uploaded_file($tmpFile, $filePath))
            {
                echo json_encode(array("msg" => "Failed to add new row into table", "result" => true));
                return;
            }

            array_push($values, $filename);
            array_push($columnNames, $imgColNames[$i]);
        }
    }

    $values = array_map("addQuotes", $values);
    $columnNames = array_map("addBackTick", $columnNames);

    $query = "INSERT INTO `".$tableName."` (".join(",", $columnNames).") VALUES (".join(",", $values).")";

    if($conn->query($query))
    {
        echo json_encode(array("msg" => "New Row has successfully added", "result" => true));

    } else 
    {
        echo json_encode(array("msg" => "Failed to add new row into table", "result" => true));
    }

}

if(isset($_GET["editRowId"]) && isset($_GET["editTableName"]) && isset($_GET["idColumnName"]))
{
    $rowID = trim($_GET["editRowId"]);
    $table = trim($_GET["editTableName"]);
    $idColName = trim($_GET["idColumnName"]);

    $getResult = $conn->query("SELECT * FROM `".$table."` WHERE `".$idColName."` = '".$rowID."'");
    $getColumnsType = $conn->query("DESCRIBE `".$table."`");

    if($getResult->num_rows > 0)
    {
        function getTypes($val)
        {
            return $val["Type"];
        }

        $row = $getResult->fetch_assoc();
        $typeRow = $getColumnsType->fetch_all(MYSQLI_ASSOC);
        $typeRow = array_map("getTypes", $typeRow);
        array_splice($typeRow, count($typeRow) - 3, 3);

        if($row["Added By"] == $user["employeeID"] || $user["role"] == "Engineer")
        {
            echo json_encode(array("columns" => array_keys($row), "values" => array_values($row), "types" => $typeRow, "result" => true, "msg" => "ok"));

        } else 
        {
            echo json_encode(array("msg" => "Authorized Transaction", "result" => false));
        }

    } else 
    {
        echo json_encode(array("msg" => "This row doens't exist", "result" => false));

    }
}

if(isset($_POST["edit_row"]) && !empty($_POST["edit_row"]) && !empty($_POST["tableName"]) && !empty($_POST["rowID"]) && !empty($_POST["colIdName"]))
{
    $req = json_decode($_POST["edit_row"], true);
    $tableName = trim($_POST["tableName"]);
    $rowID = trim($_POST["rowID"]);
    $colIdName = trim($_POST["colIdName"]);

    // $cols = array_keys($req);
    // $values = array_values($req);
    $cols = $req["properties"];
    $values = $req["values"];
    $imageColNames = $req["imageProperties"];
    $setQuery = [];

    $getResult = $conn->query("SELECT * FROM `".$tableName."` WHERE `".$colIdName."` = '".$rowID."'");

    if($getResult->num_rows > 0)
    {
        $row = $getResult->fetch_assoc();

        if($row["Added By"] == $user["employeeID"] || $user["role"] == "Engineer")
        {
            if(count($imageColNames) > 0)
            {
                if(empty($_FILES["image"]))
                {
                    echo json_encode(array("msg" => "Failed to add new row into table", "result" => true));
                    return;
                }

                for($i = 0; $i < count($imageColNames); $i++)
                {
                    $tmpFile = $_FILES["image"]["tmp_name"][$i];

                    $filename = str_replace(".", "", microtime(true)).".png";
                    $filePath = $staticPath."\\".$filename;

                    if(!move_uploaded_file($tmpFile, $filePath))
                    {
                        echo json_encode(array("msg" => "Failed to add new row into table", "result" => true));
                        return;
                    }

                    array_push($values, $filename);
                    array_push($cols, $imageColNames[$i]);
                }
            }

            for($i = 0; $i < count($cols); $i++) array_push($setQuery, "`".trim($cols[$i])."` = '".trim($values[$i])."'");
        
            if($conn->query("UPDATE `".$tableName."` SET ".join(", ", $setQuery)." WHERE `".$colIdName."` = '".$rowID."'"))
            {
                echo json_encode(array("msg" => "Row updated", "result" => true));

            } else 
            {
                echo json_encode(array("msg" => "Failed to edit the row", "result" => true));
            }

        } else 
        {
            echo json_encode(array("msg" => "Authorized Transaction", "result" => false));
        }

    } else
    {
        echo json_encode(array("msg" => "This row did not exist", "result" => false));
    }

}

?>