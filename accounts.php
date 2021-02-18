<?php
session_start();
if(!$_SESSION["loggedIn"] || $_SESSION["access"] != "*") {
    header("Location: index.php", true, 303);
    die();
}

include "api.php";

$existingAccountsTable = getAccountTable();
$pendingAccountsTable = getAccountTable(true);



?>

<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="resources/bootstrap-4-5/css/bootstrap.min.css">
        <title>LEM Inventory</title>
        <style>
            :root {
                --logout-btn-color: red;
            }
            #logoutBtn {
                border-color: var(--logout-btn-color);
                color: var(--logout-btn-color);
            }
            #logoutBtn:hover {
                background-color: var(--logout-btn-color);
            }
            #helloText, .navBarBtn {
                margin-left: 10px;
                background-color: #f8f9fa;
            }
            .navBarBtn:hover {
                color: white !important;
            }
            .modal-dialog {
                max-width: min(1200px, 95%);
            }
            .mainSectionDivs {
                padding: 15px;
                margin-left: auto;
                margin-right: auto;
                width: min(1500px, 100vw);
            }
            @font-face {
                font-family: myIcons;
                src: url(media/Myicons.ttf);
            }
            .iconFont {
                font-family: myIcons;
            }

            .disabledPermissionsTable {
                opacity: 40%;
                pointer-events: none;
            }

            .container {
                display: block;
                position: relative;
                cursor: pointer;
                font-size: 22px;

            }
            .container input {
                position: absolute;
                opacity: 0;
                cursor: pointer;
            }
            .container:hover input ~ .notChecked {
                color: #ccc;
            }
            .checked {
                display: none;
            }

            .container input:checked ~ .checked {
                display: block;
                background-color: rgba(25, 140, 21,0.5);
                padding: 5px;
                border-radius: 5px;
            }
            .container input:checked ~ .notChecked {
                display: none;
            }

            th {
                text-align: left;
            }

            #infoFooter {
               font-size: 0.5rem;
               color: grey;
               text-align: center;
               margin-top: 10px;
               margin-bottom: 20px;
            }
            .loading {
                border-radius: 50%;
                animation: rotation 1.5s infinite linear;
            	-webkit-animation: rotation 1.5s infinite linear;
                height: 2.8em;
            }

            @keyframes rotation {
        		from {
                    transform: rotate(0deg);
        		}
        		to {
                    transform: rotate(359deg);
        		}
            }
        </style>
    </head>

    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <a class="navbar-brand" href="#">
            <img src="media/lememblem.png" height="40em" style="padding-right: 10px;">
            Inventory
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="main.php">Main</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="export.php">Export</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create.php">Create</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="#">Accounts <span class="sr-only">(current)</span></a>
                </li>
            </ul>
            <div id="helloText" class="d-none d-lg-block">Hello <?=$_SESSION['name']?>.</div>
            <button id="logoutBtn" class="btn navBarBtn iconFont" onclick="logout();" data-toggle="tooltip" data-placement="bottom" title="Sign out">H</button>
        </div>
    </nav>

    <body>
        <div id="existingAccountsDiv" class="mainSectionDivs">
            <h4>Existing Accounts</h4>
            <table class="table">
                <thead>
                    <th scope="col">Full Name</th>
                    <th scope="col">User ID</th>
                    <th scope="col" class="d-none d-md-table-cell">Permissions</th>
                </thead>
                <tbody id="existingAccountsTableBody">
                    <?=$existingAccountsTable?>
                </tbody>
            </table>
        </div>
        <div id="pendingAccountsDiv" class="mainSectionDivs">
            <h4>Pending Accounts</h4>
            <table class="table">
                <thead>
                    <th scope="col">Full Name</th>
                    <th scope="col">User ID</th>
                    <th scope="col" class="d-none d-md-table-cell">Register Key</th>
                    <th scope="col" class="d-none d-md-table-cell">Permissions</th>
                </thead>
                <tbody id="pendingAccountsTableBody">
                    <?=$pendingAccountsTable?>
                </tbody>
                <tbody>
                    <tr id="newAccountTableRow">
                        <td><input type="text" id="newAccountNameField" class="form-control" placeholder="New Account..."></td>
                        <td><input type="text" id="newAccountUserIDField" class="form-control" placeholder="..."></td>
                        <td><input type="text" id="newAccountRegisterKeyField" class="form-control" placeholder="..."></td>
                        <td><button id="saveNewAccountBtn" class="btn btn-warning iconFont" onclick="saveNewAccount();">A</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="infoFooter">
            © 2020 Christophe Luis<br>
            Icons provided by icons8.com
        </div>

        <div class="modal fade" id="myModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h4 id="modHead" class="modal-title"></h4></div>
                    <div id="modConts" class="modal-body"></div>
                    <div id="modFoot" class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
        <button id="modalOpener" data-toggle='modal' data-target="#myModal" style="display:none;"></button>

    </body>

    <script src="resources/jquery-3.5.1.min.js"></script>
    <script src="resources/popper.min.js"></script>
    <script src="resources/bootstrap-4-5/js/bootstrap.js"></script>
    <script>

        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })

        function openModal(title, body, doClick = true) {
            if (!title, !body) { return; }
            $("#modHead").html(title);
            $("#modConts").html(body);
            if (doClick) { $("#modalOpener").click(); }
        }

        function logout() {
            let postObj = {
                func: "logout"
            }
            makeLoadingButton("#logoutBtn");
            $.post("api.php", postObj,
                function(data) {
                    makeLoadingButton("#logoutBtn", true, "H");
                    if (data == "success") {
                        document.location.href = "index.php"
                    } else {
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            );
        }

        function makeLoadingButton(btn, reset = false, resetText = "") {
            if (!btn) { return; }
            if (!reset) {
                $(btn).addClass("btn-danger");
                $(btn).addClass("loading");
                $(btn).html("N");
            } else {
                $(btn).removeClass("btn-danger");
                $(btn).removeClass("loading");
                $(btn).html(resetText);
            }
        }

        function reloadAccountTables(calledFromSaveNewAccount = false, newAccountUserID = "") {
            let postObj = {
                func: "reloadAccountTables"
            }
            $.post("api.php", postObj,
                function (data) {
                    if (calledFromSaveNewAccount) { makeLoadingButton("#saveNewAccountBtn", true, "A");}
                    try {
                        data = JSON.parse(data);
                        $("#existingAccountsTableBody").html(data[0]);
                        $("#pendingAccountsTableBody").html(data[1]);
                        if (calledFromSaveNewAccount) { openAccountPermissions(newAccountUserID, true); }
                    } catch {
                        openModal("Error!", "The server ran into a problem");
                    }
                }
            );
        }

        function openAccountPermissions(userID, pending) {
            if (!userID || userID == "") { return; }
            let postObj = {
                func: "openAccountPermissions",
                userID: userID,
                pending: pending
            }

            makeLoadingButton("#openAccountPermissionsBtn" + userID);

            $.post("api.php", postObj,
                function(data) {
                    makeLoadingButton("#openAccountPermissionsBtn" + userID, true, "K");
                    try {
                        data = JSON.parse(data);
                        let isAdmin = "";
                        if (data[1]) {
                            isAdmin = "checked";
                        }
                        let modifyAccountPermissionsTable = (`
                            <div class="form-inline"><input id="adminCheckBox" type="checkbox" class="form-control mr-1" ${isAdmin}><label for="adminCheckBox" class="form-inline">Admin</label></div>

                            <div class="form-inline" id="permissionsTableDiv">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th scope="col">Departmemt</th>
                                            <th scope="col">None</th>
                                            <th scope="col">Read</th>
                                            <th scope="col">Write</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modifyAccountPermissionsTableBody">
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-inline"><button id="saveAccountPermissionsBtn" class="form-control btn btn-primary" onclick="saveAccountPermissions('${userID}', ${pending})">Save</button></div>
                        `);

                        openModal("Account permissions of: <i>" + userID + "</i>", modifyAccountPermissionsTable);
                        $("#modifyAccountPermissionsTableBody").html(data[0]);

                        if (data[1]) {
                            $("#permissionsTableDiv").toggleClass("disabledPermissionsTable");
                            let isDisabled = $("#permissionsTableDiv").hasClass("disabledPermissionsTable")
                            $("#permissionsTableDiv :input").attr("disabled", isDisabled);
                        }

                        $("#adminCheckBox").change(
                            function () {
                                $("#permissionsTableDiv").toggleClass("disabledPermissionsTable");
                                let isDisabled = $("#permissionsTableDiv").hasClass("disabledPermissionsTable")
                                $("#permissionsTableDiv :input").attr("disabled", isDisabled);
                            }
                        );

                    } catch {
                        openModal("Error!", "Ther server ran into a problem.");
                    }

                }
            );
        }

        function saveAccountPermissions(userID, pending = false) {

            if (!userID || userID == "") { return; }

            let chosenPermissionsArray = [];
            if ($(`#adminCheckBox:checked`).length == 1) {
                chosenPermissionsArray = ["*"];
            } else {
                $("#permissionsTableDiv tr").each(
                    function () {
                        let currDepId = $(this).attr("depid");
                        if (currDepId) {
                            let permissionForDep = $(`input[name="permissionsCheckBox${currDepId}"]:checked`).val();
                            if (permissionForDep != 0) {
                                chosenPermissionsArray.push(currDepId + ";" + permissionForDep);
                            }
                        }
                    }
                );
            }
            if (chosenPermissionsArray.length == 0) {
                chosenPermissionsArray = ["none"];
            }
            let postObj = {
                func: "saveChosenAccountPermissions",
                userID: userID,
                pending: pending,
                'chosenPermissionsArray[]': chosenPermissionsArray
            }

            makeLoadingButton("#saveAccountPermissionsBtn");

            $.post("api.php", postObj,
                function (data) {
                    makeLoadingButton("#saveAccountPermissionsBtn", true, "Save");
                    if (data != "success") {
                        openModal("Error!", "Ther server ran into a problem.", false);
                    }
                }
            );
        }

        function saveNewAccount() {
            let newAccountName = $("#newAccountNameField").val();
            let newAccountUserID = $("#newAccountUserIDField").val();
            let newAccountRegisterKey = $("#newAccountRegisterKeyField").val();
            if (newAccountName == "") { $("#newAccountNameField").css("border-color", "red"); return; }
            if (newAccountUserID == "") { $("#newAccountUserIDField").css("border-color", "red"); return; }
            if (newAccountRegisterKey == "") { $("#newAccountRegisterKeyField").css("border-color", "red"); return; }

            let postObj = {
                func: "saveNewAccount",
                newAccountName: newAccountName,
                newAccountUserID: newAccountUserID,
                newAccountRegisterKey: newAccountRegisterKey
            }

            makeLoadingButton("#saveNewAccountBtn");

            $.post("api.php", postObj,
                function(data) {

                    $("#newAccountNameField").css("border-color", "#ced4da");
                    $("#newAccountUserIDField").css("border-color", "#ced4da");
                    $("#newAccountRegisterKeyField").css("border-color", "#ced4da");

                    if (data == "success") {
                        $("#newAccountNameField").val("");
                        $("#newAccountUserIDField").val("");
                        $("#newAccountRegisterKeyField").val("");
                        reloadAccountTables(true, newAccountUserID);
                    } else if (data == "duplicate") {
                        makeLoadingButton("#saveNewAccountBtn", true, "A");
                        openModal("Error!", "An account with that User ID already exists");
                    } else if (data == "no access") {
                        makeLoadingButton("#saveNewAccountBtn", true, "A");
                        $("#newAccountNameField").val("");
                        $("#newAccountUserIDField").val("");
                        $("#newAccountRegisterKeyField").val("");
                        openModal("Error!", "You do not have permission to add new accounts");
                    } else {
                        makeLoadingButton("#saveNewAccountBtn", true, "A");
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            );
        }

        function confirmDeleteAccount(userID, pending = false) {
            if (!userID || userID == "") { return; }
            openModal("Warning!", `Please confirm your intent to remove this account. <button id='confirmDeleteBtn' data-dismiss='modal' class='btn btn-primary' onclick='deleteAccount("${userID}", ${pending})'>Confirm</button>`);
        }

        function deleteAccount(userID, pending = false) {
            if (!userID || userID == "") { return; }

            let postObj = {
                func: "deleteAccount",
                userID: userID,
                pending: pending
            }

            makeLoadingButton(`#deleteAccountBtn${userID}`);

            $.post("api.php", postObj,
                function(data) {
                    makeLoadingButton(`#deleteAccountBtn${userID}`, true, "I");

                    if (data == "success") {
                        reloadAccountTables();
                    } else if (data == "no access") {
                        $("#newAccountNameField").val("");
                        $("#newAccountUserIDField").val("");
                        $("#newAccountRegisterKeyField").val("");
                        openModal("Error!", "You do not have permission to remove accounts");
                    } else {
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            );
        }
    </script>
</html>
