<?php
session_start();

$DB_server = "***";
$DB_user = "***";
$DB_pwd = "***";
$DB_db = "***";

function loggedInCheck() {
    if (!$_SESSION['loggedIn']) {
        die("https://www.youtube.com/watch?v=dQw4w9WgXcQ");
    }
}

function generateID($table = "", $length = 10) {
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $charactersLength = strlen($characters);
    $randomString = "";
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    if ($table != "") {
        $fullTable = getFullTable($table);
        foreach ($fullTable as $row) {
            if ($row["ID"] == $randomString) {
                return generateID($table);
                break;
            }
        }
    }
    return $randomString;
}

function toNormArr($inp) {
    $arrToRet = [];
    while($row = $inp->fetch_assoc()){
        array_push($arrToRet, $row);
    }
    return $arrToRet;
}

function getFullTable($table, $sortByName = false) {
    global $DB_server;
    global $DB_user;
    global $DB_pwd;
    global $DB_db;
    $conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
    mysqli_set_charset($conn, 'utf8');

    if ($conn->connect_error) { die("Connection failed."); }

    $sql = "SELECT * FROM " . $table;
    if($sortByName) {
        $sql .= " ORDER BY name ASC";
    }
    $fullTableArr = $conn->query($sql);
    $fullTableArr = toNormArr($fullTableArr);

    $conn->close();

    return $fullTableArr;
}

function singleItemsTable($searchString = "", $filterType = "", $filterString = "") {

    if($filterType == "room") {
        if(!getRoomDepartmentID($filterString)) {
            die("nonexisting room");
        }
    }

    $items = getFullTable("SingleItems", true);
    if (count($items) == 0 || is_null($items)) { return ""; }

    $tableHTML = "";

    $ignoreSearch = ($searchString == "") ? true : false;

    $fileList = glob('attachments/*.pdf');
    $itemIDsWithAttachments = array();
    foreach($fileList as $filename){
        array_push($itemIDsWithAttachments, pathinfo($filename)["filename"]);
    }

    foreach ($items as $item) {
        if(strpos($_SESSION['access'], $item["depID"]) !== false || $_SESSION['access'] == "*") {
			if ($filterType == "room" && $filterString != $item["room"]) { continue; }
			if ($filterType == "department" && $filterString != $item["depID"]) { continue; }
            // TODO: MAKE THIS BETTER! (Try to write something so that this if doesn't have to happen every iteration) Note: strpos($x, "") always returns false

            if(strpos(strtolower($item["name"]), strtolower($searchString)) !== false || $ignoreSearch) {
                $fullWarranty = ($item["FullWarranty"] == "1") ? "Yes" : "No";
                $tableHTML .= "<tr><th id='itemName$item[ID]' scope='col'>$item[name]</th><td id='itemDesc$item[ID]' class='d-none d-md-table-cell'>$item[description]</td><td id='itemFullWarr$item[ID]'>$fullWarranty</td><td id='itemRoom$item[ID]'>$item[room]</td>";
                if(in_array($item["ID"], $itemIDsWithAttachments)) {
                    $tableHTML .= "<td id='itemLink$item[ID]'><a target='_blank' rel='noopener noreferrer' href='attachments/$item[ID].pdf'>Open...</a></td>";
                } else {
                    $tableHTML .= "<td id='itemLink$item[ID]'>-</td>";
                }
                if(strpos($_SESSION['access'], $item["depID"] . "/w") !== false || $_SESSION['access'] == "*") {
                    $tableHTML .= "<td id='editSingleItemBtnCont$item[ID]' class='singleItemEditBtnConts iconFont'><button class='btn singleItemEditBtns' onclick='editSingleItem(\"$item[ID]\");'>L</button></td></tr>";
                } else {
                    $tableHTML .= "<td class='singleItemEditBtnConts'><button class='btn singleItemEditBtns iconFont'>M</button></td></tr>";
                }
            }
        }
    }
    if ($tableHTML == "") { return "<tr><td>No matching items found...</td></tr>"; }
    return $tableHTML;
}

function executeStandardSQLQuery($sqlStmt) {
    global $DB_server;
    global $DB_user;
    global $DB_pwd;
    global $DB_db;
    $conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
    mysqli_set_charset($conn, 'utf8');
    if ($conn->connect_error) { return "Connection failed."; }
    $conn->query($sqlStmt);
    $conn->close();
    return true;
}

function getRoomDepartmentID($room) {
    global $DB_server;
    global $DB_user;
    global $DB_pwd;
    global $DB_db;
    if (!isset($room) || $room == "") {return false;}
    $conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
    mysqli_set_charset($conn, 'utf8');

    if ($conn->connect_error) { return false; }

    if ($stmt = $conn->prepare("SELECT departmentID FROM Rooms WHERE ID=?")) {
        $stmt->bind_param("s", $room);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($roomDepId);
            $stmt->fetch();
            $stmt->close();
            $conn->close();
            return $roomDepId;
        } else {
            $conn->close();
            $stmt->close();
            return false;
        }
    } else {
        $conn->close();
        return false;
    }
}

function accessableDepartmentIDs() {
    if($_SESSION['access'] == "*") { return ["*"]; }

    $accessableDepIDs = preg_split("/\/[a-z]\;/", $_SESSION['access']);
    array_pop($accessableDepIDs);

    return $accessableDepIDs;
    // RETURNS Array
}
function accessableDepartmentNames() {
    if($_SESSION['access'] == "*") { return ["Global"]; }

    $accessableDepIDs = accessableDepartmentIDs();

    $allDeps = getFullTable("Departments");

    $accessableDepNames = array();
    foreach ($allDeps as $dep) {
        if(in_array($dep["ID"], $accessableDepIDs)) {
            array_push($accessableDepNames, $dep["name"]);
        }
    }
    return $accessableDepNames;
    // RETURNS Array
}

function bulkItemsTable($filterType = "init", $filterString = "") {
    global $DB_server;
    global $DB_user;
    global $DB_pwd;
    global $DB_db;

    if($filterType == "room") {
        $roomDepId = getRoomDepartmentID($filterString);
        if($roomDepId === false) { die("nonexisting room"); }
        if(strpos($_SESSION['access'], $roomDepId) === false && $_SESSION['access'] != "*") { die("no access"); }
    } else if ($filterType == "department") {
        if(strpos($_SESSION['access'], $filterString) === false && $_SESSION['access'] != "*") { die("no access"); }
    }

    if($filterType == "") { $filterType = "init"; }

    $conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
    mysqli_set_charset($conn, 'utf8');

    if ($conn->connect_error) { die("Connection failed."); }

    $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'Rooms'";
    $tableInfo = $conn->query($sql);
    $tableInfo = toNormArr($tableInfo);

    $conn->close();

    if (count($tableInfo) == 2) { return "No exisiting bulk items."; }

    $bulkTypes = array();
    for($i = 2; $i < count($tableInfo); $i++) {
        array_push($bulkTypes, $tableInfo[$i]["COLUMN_NAME"]);
    }

    $fullRoomTable = getFullTable("Rooms");

    $bulkTypeCounts = array_fill_keys($bulkTypes, 0);

    if ($filterType == "room" || $filterType == "department") {
        if ($filterString == "") { die("no filter string"); }
        foreach ($fullRoomTable as $room) {

            $columnToCheck = ($filterType == "room") ? "ID" : "departmentID";
            if($room[$columnToCheck] == $filterString) {

                foreach ($bulkTypes as $bulkType) {
                    $bulkTypeCounts[$bulkType] += $room[$bulkType];
                }
                if($filterType == "room") { break; }
            }
        }
    } else if ($filterType == "init") {
        $accessableDepIDs = accessableDepartmentIDs();
        foreach ($fullRoomTable as $room) {
            if(in_array($room["departmentID"], $accessableDepIDs) || $accessableDepIDs[0] == "*") {
                foreach ($bulkTypes as $bulkType) {
                    $bulkTypeCounts[$bulkType] += $room[$bulkType];
                }
                if($filterType == "room") { break; }
            }
        }
    }



    $returnHTML = "";
    foreach ($bulkTypes as $bulkType) {
        $bulkTypeID = strtolower(str_replace(" ", "", $bulkType));
        $returnHTML .= "<tr><td><div class='form-inline'><b><span id='bulkItemTitle$bulkTypeID' class='bulkItemTitles'>$bulkType</span></b>:&nbsp;<span id='bulkItemCount$bulkTypeID' class='bulkItemCounts'>$bulkTypeCounts[$bulkType]</span>";
        if ($filterType == "room") {
            $roomDepId = getRoomDepartmentID($filterString);
            if (strpos($_SESSION['access'], $roomDepId . "/w") !== false || $_SESSION['access'] == "*") {
                $returnHTML .= "<span id='bulkItemEditBtnCont$bulkTypeID'><button id='bulkItemEditBtn$bulkTypeID' class='btn bulkItemEditBtns iconFont' onclick='editBulkItem(\"$bulkTypeID\")'>L</button></span></td></tr>";
            } else {
                $returnHTML .= "<span id='bulkItemEditBtnCont$bulkTypeID'></span><button id='bulkItemEditBtn$bulkTypeID' class='btn bulkItemEditBtns iconFont'>M</button></td><tr>";
            }
        }
        $returnHTML .= "</div>";
    }
    return $returnHTML;



}

function getAccountTable($pending = false) {
    $table = $pending ? "PendingAccountRegisters" : "Accounts";
    $fullTable = getFullTable($table);
    $jsVarPending = $pending ? "true" : "false";
    $returnHTML = "";
    foreach ($fullTable as $account) {
        $registerKeyColumn = "";
        if ($pending) {
            $registerKeyColumn = "<td>$account[registerkey]</td>";
        }
        $registerKeyColumn = $pending ? "<td>$account[registerkey]</td>" : "";
        $returnHTML .= "<tr><td>$account[name]</td><td>$account[userID]</td>$registerKeyColumn<td><button id='openAccountPermissionsBtn$account[userID]' class='btn btn-info mr-1 d-none d-md-inline-block iconFont' data-toggle='tooltip' data-placement='bottom' title='Open Account Permissions' onclick='openAccountPermissions(\"$account[userID]\", $jsVarPending)'>K</button><button id='deleteAccountBtn$account[userID]' class='btn btn-danger mr-1 d-none d-md-inline-block iconFont' data-toggle='tooltip' data-placement='bottom' title='Delete Account' onclick='confirmDeleteAccount(\"$account[userID]\", $jsVarPending)'>I</button></td></tr>";
    }
    if ($returnHTML == "" && $pending) { return "<tr><td>No pending accounts...</td></tr>";}
    return $returnHTML;
}



if (isset($_POST["func"])) {
    $func = $_POST["func"];
    switch($func) {

        case "login":

            $conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
            mysqli_set_charset($conn, 'utf8');

            if ($conn->connect_error) { die("Connection failed."); }
            if ( empty($_POST['userID']) || empty($_POST['password']) ) { die("No userID or password set"); }

            if ($stmt = $conn->prepare("SELECT name, password, access FROM Accounts WHERE userID=?")) {
                $stmt->bind_param("s", strtolower($_POST['userID']));

                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($name, $password, $access);
                    $stmt->fetch();
                    if(password_verify($_POST['password'], $password)) {
                        session_regenerate_id();
                        $_SESSION['loggedIn'] = TRUE;
                        $_SESSION['userID'] = strtolower($_POST['userID']);
                        $_SESSION['name'] = $name;
                        $_SESSION['access'] = $access;
                        echo "success";
                    } else {
                        echo "wrongCreds";
                    }
                } else {
                    echo "noAccount";
                }

                $stmt->close();

            } else {
                echo "Server Error";
            }

            $conn->close();
            break;

        case "logout":
            session_destroy();
            echo "success";
            break;

        case "register":

            if ( empty($_POST['userID']) || empty($_POST['registerKey']) || empty($_POST['newPassword'])) {
                die("Not all parameters set.");
            }
            $userID = strtolower($_POST['userID']);
            $pendingAccountTable = getFullTable("PendingAccountRegisters");
            $pendingAccountToRegister = array();
            foreach ($pendingAccountTable as $pendingAccount) {
                if ($pendingAccount['userID'] == $userID) {
                    $pendingAccountToRegister = $pendingAccount;
                }
            }

            if ($pendingAccountToRegister['registerkey'] != $_POST['registerKey']) {
                die("wrongCreds");
            }
            $_SESSION['loggedIn'] = TRUE;
            $_SESSION['userID'] = strtolower($userID);
            $_SESSION['name'] = $pendingAccountToRegister["name"];
            $_SESSION['access'] = $pendingAccountToRegister["access"];
            $pass = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);
            $sqlStmt = "INSERT INTO Accounts (userID, password, name, access) VALUES ('$userID','$pass','$pendingAccountToRegister[name]','$pendingAccountToRegister[access]')";
            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "suc";
            } else {
                die("server error");
            }
            $sqlStmt = "DELETE FROM PendingAccountRegisters WHERE userID='$userID'";
            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "cess";
            } else {
                die("server error");
            }


            break;


        case "reloadAllTables":
            loggedInCheck();
            $filterType = (isset($_POST['filterType'])) ? $_POST['filterType'] : "";
            $filterString = (isset($_POST['filterString'])) ? $_POST['filterString'] : "";
            echo json_encode([bulkItemsTable($filterType, $filterString), singleItemsTable($_POST['searchString'], $filterType, $filterString)]);
            break;

        case "reloadBulkItemsTable":
            loggedInCheck();
            $filterType = (isset($_POST['filterType'])) ? $_POST['filterType'] : "";
            $filterString = (isset($_POST['filterString'])) ? $_POST['filterString'] : "";
            echo json_encode([bulkItemsTable($filterType, $filterString), ""]);
            break;

        case "reloadSingleItemsTable":
            loggedInCheck();
        	$filterType = (isset($_POST['filterType'])) ? $_POST['filterType'] : "";
        	$filterString = (isset($_POST['filterString'])) ? $_POST['filterString'] : "";
            echo json_encode(["", singleItemsTable($_POST['searchString'], $filterType, $filterString)]);
            break;

        case "create":

            if ( empty($_POST['type']) || empty($_POST['name']) ) {
                die("Insufficient");
            }


            // $_POST['type'] is 'department' or 'rooms', $_POST['name'] is new name of dep or room. DepId comes only through with new room
            $sqlStmt = "";
            if ($_POST['type'] == "department" && $_SESSION['access'] == "*") {
                $sqlStmt = "INSERT INTO Departments (ID, name) VALUES ('" . generateID("Departments") . "','$_POST[name]')";
            } else if ($_POST['type'] == "department" && $_SESSION['access'] != "*")  {
                die("no access");
            }

            if ($_POST['type'] == "room") {
                if(empty($_POST['depID'])) { die("Insufficient"); }
                if(strpos($_SESSION['access'], $_POST['depID'] . "/w") === false && $_SESSION['access'] != "*") { die("no access"); }
                $existingRooms = getFullTable("Rooms");
                foreach ($existingRooms as $exRoom) {
                    if($exRoom['ID'] == $_POST['name']) { die("duplicate"); }
                }
                $sqlStmt = "INSERT INTO Rooms (ID, departmentID) VALUES ('$_POST[name]','$_POST[depID]')";
            }
            if ($sqlStmt == "") {
                die("error");
            }

            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }


            break;

        case "saveNewItem":
            loggedInCheck();
            if (empty($_POST['newItemName']) || empty($_POST['newItemDesc']) || empty($_POST['newItemRoom']) || isset($_POST['newItemFullWarrCheck']) === true) { die("Not all parameters set"); }
            $newItemRoomDepID = getRoomDepartmentID($_POST['newItemRoom']);
            if ($newItemRoomDepID === false) {die("nonexisting room");}
            if(strpos($_SESSION['access'], $newItemRoomDepID . "/w") === false && $_SESSION['access'] != "*") { die("no access"); }

            $insertSQLStmt = "INSERT INTO SingleItems (ID, name, description, FullWarranty, room, depID) VALUES ('" . generateID("SingleItems") . "','$_POST[newItemName]','$_POST[newItemDesc]',$_POST[newItemFullWarr],'$_POST[newItemRoom]','$newItemRoomDepID')";
            if(executeStandardSQLQuery($insertSQLStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }
            break;


        case "saveEditedSingleItem":
            loggedInCheck();
            if (empty($_POST['editedItemName']) || empty($_POST['editedItemDesc']) || empty($_POST['editedItemRoom']) || empty($_POST['remove']) || empty($_POST['editedItemId']) || isset($_POST['editedItemFullWarr']) === false) {die("Not all parameters set");}
            $editedItemDepID = getRoomDepartmentID($_POST['editedItemRoom']);
            if ($editedItemDepID === false) { die("nonexisting room"); }
            if(strpos($_SESSION['access'], $editedItemDepID . "/w") === false && $_SESSION['access'] != "*") { die("no access"); }

            $sqlStmt = "UPDATE SingleItems SET name='$_POST[editedItemName]', description='$_POST[editedItemDesc]', FullWarranty=$_POST[editedItemFullWarr], room='$_POST[editedItemRoom]', depID='$editedItemDepID' WHERE ID='$_POST[editedItemId]'";
            if($_POST['remove'] == "true") { $sqlStmt = "DELETE FROM SingleItems WHERE ID='$_POST[editedItemId]'"; }

            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }

            break;

        case "saveEditedBulkItem":
            loggedInCheck();
            if (empty($_POST['editedBulkItemName']) || !isset($_POST['editedBulkItemCount']) || empty($_POST['editedBulkItemRoom'])) { die("Not all parameters set"); }
            $editedBulkItemDepID = getRoomDepartmentID($_POST['editedBulkItemRoom']);
            if ($editedBulkItemDepID === false) { die("nonexisting room"); }
            if(strpos($_SESSION['access'], $editedBulkItemDepID . "/w") === false && $_SESSION['access'] != "*") { die("no access"); }

            $sqlStmt = "UPDATE Rooms SET $_POST[editedBulkItemName]='$_POST[editedBulkItemCount]' WHERE ID='$_POST[editedBulkItemRoom]'";
            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }
            break;

        case "uploadPDF":
            loggedInCheck();
            if(empty($_POST['ID']) || empty($_POST['room']) || !isset($_FILES['fileToUpload'])) { die("error"); }

            $accordingRoomID = getRoomDepartmentID($_POST['room']);
            if(strpos($_SESSION['access'], $accordingRoomID . "/w") === false && $_SESSION['access'] != "*") { die("no access"); }

            $target_dir = "attachments/";
            $target_file = $target_dir . $_POST['ID'] . ".pdf";

            $uploadedFileType = strtolower(pathinfo($_FILES['fileToUpload']['name'], PATHINFO_EXTENSION));
            if ($uploadedFileType != "pdf") { die("not a pdf"); }

            if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file)) {
                echo "success";
            }
            break;

        case "deletePDF":
            loggedInCheck();
            if(empty($_POST['ID']) || empty($_POST['room'])) { die("error"); }

            $accordingRoomID = getRoomDepartmentID($_POST['room']);
            if(strpos($_SESSION['access'], $accordingRoomID . "/w") === false && $_SESSION['access'] != "*") { die("no access"); }

            if(unlink("attachments/" . $_POST['ID'] . ".pdf")) {
                echo "success";
            } else {
                echo "unlink error";
            }
            break;

        case "bulkItemPrefs":
            if ($_SESSION['access'] != "*") { die("no access"); }

            $conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
            mysqli_set_charset($conn, 'utf8');

            if ($conn->connect_error) { die("Connection failed."); }

            $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'Rooms'";
            $tableInfo = $conn->query($sql);
            $tableInfo = toNormArr($tableInfo);

            $conn->close();

            $htmlToReturn = "<table id='bulkItemPrefsTable' class='table'>";
            for ($columnInd = 2; $columnInd < count($tableInfo); $columnInd++) {
                $column = $tableInfo[$columnInd];
                $htmlToReturn .= "<tr><td>$column[COLUMN_NAME]</td><td><button class='btn btn-danger' onclick='confirmBulkTypeDelete(\"$column[COLUMN_NAME]\");'>&#x1F5D1;</button></td></tr>";
            }

            $htmlToReturn .= "<tr><td><input type='text' id='newBulkItemNameField' class='form-control' placeholder='...'></td><td><button id='saveNewBulkItemBtn' class='btn btn-warning' onclick='saveNewBulkItem();'>&#x271A;</button></td></tr>";
            $htmlToReturn .= "</table>";

            echo $htmlToReturn;
            break;

        case "saveNewBulkItemType":
            if ($_SESSION['access'] != "*") { die("no access"); }
            if (empty($_POST['name'])) { die("Insufficient"); }

            $sqlStmt = "ALTER TABLE Rooms ADD $_POST[name] int(10) UNSIGNED NOT NULL";

            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }
            break;

        case "removeBulkItemType":
            if ($_SESSION['access'] != "*") { die("no access"); }
            if (empty($_POST['name'])) { die("Insufficient"); }

            $sqlStmt = "ALTER TABLE Rooms DROP COLUMN $_POST[name]";

            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }
            break;

        case "openAccountPermissions":

            if ($_SESSION['access'] != "*") { die("no access"); }


            if (empty($_POST['userID']) || empty($_POST['pending'])) { die("error"); }

            $accessInfo = [];
            $conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
            mysqli_set_charset($conn, 'utf8');

            if ($conn->connect_error) { die("Connection failed."); }

            $tableToCheck = ($_POST['pending'] == "true") ? "PendingAccountRegisters" : "Accounts";

            if ($stmt = $conn->prepare("SELECT access FROM $tableToCheck WHERE userID=?")) {
                $stmt->bind_param("s", strtolower($_POST['userID']));

                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($access);
                    $stmt->fetch();
                    if ($access == "*") {
                        $accessInfo = ["*"];
                    }
                    $depIDsAccess = explode(";", $access);

                    foreach ($depIDsAccess as $depIDAccess) {
                        if ($depIDAccess == "") { continue; }
                        $accessArr = explode("/", $depIDAccess);
                        $accessNum = 0;
                        if (count($accessArr) == 1) {
                            array_push($accessArr, "");
                        } else if ($accessArr[1] == "r") {
                            $accessNum = 1;
                        } else if ($accessArr[1] == "w") {
                            $accessNum = 2;
                        }
                        $accessInfo[$accessArr[0]] = $accessNum;
                    }

                } else {
                    $stmt->close();
                    $conn->close();
                    die("noAccount");
                }

                $stmt->close();

            } else {
                $conn->close();
                die("Server Error");
            }

            $conn->close();

            $returnHTML = "";
            $depFullTable = getFullTable("Departments", true);

            $isAdmin = ($accessInfo[0] == "*");

            foreach ($depFullTable as $dep) {
                $checkedArr = ["checked", "", ""];
                if (array_key_exists($dep["ID"], $accessInfo) && !$isAdmin) {
                    if ($accessInfo[$dep["ID"]] == 1) {
                        $checkedArr = ["", "checked", ""];
                    } else if ($accessInfo[$dep["ID"]] == 2) {
                        $checkedArr = ["", "", "checked"];
                    }
                }
                $returnHTML .= "<tr depID='$dep[ID]'>
                      <th scope='row'>$dep[name]</th>
                      <td>
                        <label class='container'>
                          <input type='radio' class='permissionsCheckBoxBtns' name='permissionsCheckBox$dep[ID]' value='0' $checkedArr[0]>
                          <span class='checked iconFont'>P</span>
                          <span class='notChecked iconFont'>O</span>
                        </label>
                      </td>
                      <td>
                        <label class='container'>
                          <input type='radio' class='permissionsCheckBoxBtns' name='permissionsCheckBox$dep[ID]' value='1' $checkedArr[1]>
                          <span class='checked iconFont'>P</span>
                          <span class='notChecked iconFont'>O</span>
                        </label>
                      </td>
                      <td>
                        <label class='container'>
                          <input type='radio' class='permissionsCheckBoxBtns' name='permissionsCheckBox$dep[ID]' value='2' $checkedArr[2]>
                          <span class='checked iconFont'>P</span>
                          <span class='notChecked iconFont'>O</span>
                        </label>
                      </td>
                    </tr>";
            }

            echo json_encode([$returnHTML, $isAdmin]);
            break;

        case "saveChosenAccountPermissions":
            if ($_SESSION['access'] != "*") { die("no access"); }
            if (empty($_POST['userID'])) { die("error1"); }
            if (empty($_POST['pending'])) { die("error2"); }
            if (count($_POST['chosenPermissionsArray']) == 0) { die("error3"); } // TODO: Cannot make account with all permissions on none

            $newAccess = "";
            if ($_POST['chosenPermissionsArray'][0] == "*") {
                $newAccess = "*";
            } else if ($_POST['chosenPermissionsArray'][0] == "none") {
                $newAccess = "";
            } else {
                foreach ($_POST['chosenPermissionsArray'] as $currDep) {
                    $exploded = explode(";", $currDep);
                    $newAccess .= $exploded[0];
                    if ($exploded[1] == 1) {
                        $newAccess .= "/r;";
                    } else if ($exploded[1] == 2) {
                        $newAccess .= "/w;";
                    }
                }
            }

            $tableToUpdate = ($_POST['pending'] == "false") ? "Accounts" : "PendingAccountRegisters";
            $sqlStmt = "UPDATE $tableToUpdate SET access='$newAccess' WHERE userID='$_POST[userID]'";
            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }

            break;

        case "saveNewAccount":
            if ($_SESSION['access'] != "*") { die("no access"); }

            if (empty($_POST['newAccountName']) || empty($_POST['newAccountUserID']) || empty($_POST['newAccountRegisterKey'])) { die("error"); }

            $fullAccountTable = getFullTable("Accounts");
            foreach ($fullAccountTable as $account) {
                if ($account["userID"] == $_POST['newAccountUserID']) { die("duplicate"); }
            }
            $newAccountUserID = strtolower($_POST['newAccountUserID']);
            $sqlStmt = "INSERT INTO PendingAccountRegisters (userID, name, access, registerkey) VALUES ('$newAccountUserID', '$_POST[newAccountName]', '', '$_POST[newAccountRegisterKey]')";
            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }

            break;

        case "deleteAccount":
            if ($_SESSION['access'] != "*") { die("no access"); }
            if (empty($_POST['userID']) || empty($_POST['pending'])) { die("error"); }

            $tableToUse = ($_POST['pending'] == "true") ? "PendingAccountRegisters" : "Accounts";
            $sqlStmt = "DELETE FROM $tableToUse WHERE userID='$_POST[userID]'";
            if(executeStandardSQLQuery($sqlStmt) === TRUE) {
                echo "success";
            } else {
                die("server error");
            }

            break;

        case "reloadAccountTables":
            if ($_SESSION['access'] != "*") { die("no access"); }
            echo json_encode([getAccountTable(), getAccountTable(true)]);
    }
}



?>
