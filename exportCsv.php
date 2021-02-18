<?php
include "api.php";

loggedInCheck();

$depID = $_GET['dep'];
$depName = empty($_GET['depName']) ? "Department name missing" : $_GET['depName'] ;
$room = $_GET['room'];


$allRooms = array();
if ($depID == "*") {
    if ($_SESSION['access'] != "*") { die("no access"); }
} else if (!empty($depID)) {
    if (strpos($_SESSION['access'], $depID) === false && $_SESSION['access'] != "*") { die("no access"); }
} else if (!empty($room)) {
    $roomDepId = getRoomDepartmentID($room);
    if ($roomDepId === false) { die("Nonexisting room."); }
    if (strpos($_SESSION['access'], $roomDepId) === false && $_SESSION['access'] != "*") { die("no access"); }
} else {
    die("Error: Insufficient parameters");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data.csv');


$output = fopen('php://output', 'w');


global $DB_server;
global $DB_user;
global $DB_pwd;
global $DB_db;
$conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
mysqli_set_charset($conn, 'utf8');

if ($conn->connect_error) { die("Connection failed."); }

$sql = "SELECT name, description, FullWarranty, room FROM SingleItems";
if ($depID == "*") {
    $sql .= " ORDER BY room ASC";
} else if(!empty($depID)) {
    $sql .= " WHERE depID = '$depID' ORDER BY room ASC";
} else {
    $sql .= " WHERE room = '$room'";
}
$items = $conn->query($sql);
$items = toNormArr($items);

$conn->close();

if(!empty($items)){

    if(!empty($depName)) { echo $depName . "\n"; }

    $currRoom = "";
    foreach($items as $item){
        if($currRoom != $item["room"]) {
            fputcsv($output, [""]);
            $currRoom = $item["room"];
            fputcsv($output, [$currRoom]);
        }
        $fullWarr = $item["FullWarranty"] == "1" ? "Yes" : "No";
        fputcsv($output, [$item["name"], $item["description"], $fullWarr, $item["room"]]);

    }

}

// Lots of bad coding here (error catching, session verification coould be better, ...), but works for now.
?>
