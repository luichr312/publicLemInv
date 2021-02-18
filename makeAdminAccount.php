<?php
$DB_server = "***";
$DB_user = "***";
$DB_pwd = "***";
$DB_db = "***";

$conn = new mysqli($DB_server, $DB_user, $DB_pwd, $DB_db);
mysqli_set_charset($conn, 'utf8');
if ($conn->connect_error) {
    die("Connection failed.");
}
$pass = password_hash("***", PASSWORD_DEFAULT);
$conn->query("INSERT INTO Accounts (iam, password, name, access) VALUES ('scienceman','". $pass ."', 'Science Man', '8hhy5TMNc1/w;')");
$conn->close();
echo "yes";


?>
