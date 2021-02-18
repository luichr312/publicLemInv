<?php
session_start();
if((!$_SESSION['loggedIn'] || strpos($_SESSION['access'], "/w") === false) && $_SESSION['access'] != "*") {
    header("Location: index.php", true, 303);
    die();
}

include "api.php";

$departments = getFullTable("Departments", true);

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
            @font-face {
                font-family: myIcons;
                src: url(media/Myicons.ttf);
            }
            .iconFont {
                font-family: myIcons;
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

            #gridDiv {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                width: min(900px, 100vw);
                margin-left: auto;
                margin-right: auto;
                grid-gap: 10px;
                margin-top: 5vh;
            }
            .createHalfsDivs {
                border: 1px solid #b7b7b7;
                border-radius: 5px;
                padding: 15px;
                line-height: 3;
                max-width: 100vw;
            }
            .createBtn {
                float: right;
                margin-top: 20px;
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
                <li class="nav-item active">
                    <a class="nav-link" href="#">Create <span class="sr-only">(current)</span></a>
                </li>
                <?php
                    if ($_SESSION['access'] == "*") {
                        echo '<li class="nav-item"><a class="nav-link" href="accounts.php">Accounts</a></li>';
                    }
                ?>
            </ul>
            <div id="helloText" class="d-none d-lg-block">Hello <?=$_SESSION['name']?>.</div>
            <button id="logoutBtn" class="btn navBarBtn iconFont" onclick="logout();" data-toggle="tooltip" data-placement="bottom" title="Sign out">H</button>
        </div>
    </nav>



    <body>

        <div id="gridDiv">

            <div id="createRoom" class="createHalfsDivs">
                <h4>Create Room</h4>
                <div class="form-inline">
                    <label for="createRoomNameField" class="mr-1">Name:</label>
                    <input id="createRoomNameField" class="form-control" type="text" placeholder="X.Y.ZZ">
                </div>
                <div class="form-inline">
                    <label for="createRoomAssignDepartment" class="mr-1">Assign Department:</label>
                    <select id="createRoomAssignDepartment" class="form-control">
                        <option value="0" selected>Choose...</option>
                        <?php
                            foreach ($departments as $dep) {
                                if (strpos($_SESSION['access'], $dep["ID"] . "/w") !== false || $_SESSION['access'] == "*") {
                                    echo "<option value='" . $dep["ID"] . "'>" . $dep["name"] . "</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
                <button id="createRoomBtn" class="btn btn-primary createBtn iconFont" onclick="create('room');">A</button>
            </div>
            <?php
                if ($_SESSION['access'] == "*") {
                    echo '<div id="createDepartment" class="createHalfsDivs">
                        <h4>Create Department</h4>
                        <div class="form-inline">
                            <label for="createDepartmentNameField" class="mr-1">Name:</label>
                            <input id="createDepartmentNameField" class="form-control" type="text" placeholder="...">
                        </div>
                        <br>
                        <button id="createDepartmentBtn" class="btn btn-primary createBtn iconFont" onclick="create(\'department\');">A</button>
                    </div>';
                }
            ?>

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
                    <div id="modFoot" class="modal-footer"><button id="closeModalBtn" type="button" class="btn btn-danger" data-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>
        <button id="modalOpener" data-toggle='modal' data-target='#myModal' style="display:none;"></button>

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
            )
        }

        function create(type) {
            if(!type || type == "") {return;}
            let name = (type == "room") ? $("#createRoomNameField").val() : $("#createDepartmentNameField").val();
            let depID = (type == "room") ? $("#createRoomAssignDepartment").val() : "1";
            if(name == "" || depID == "0") {
                openModal("Warning!", "Please fill in all fields!");
                return;
            }
            let postObj = {
                func: "create",
                type: type,
                name: name,
                depID: depID
            }
            let btnToChange = (type == "room") ? "#createRoomBtn": "#createDepartmentBtn";
            makeLoadingButton(btnToChange);

            $.post("api.php", postObj,
                function(data) {
                    makeLoadingButton(btnToChange, true, "A");
                    if (data == "success") {
                        $("#closeModalBtn").attr("onclick", "document.location.href=''");
                        openModal("Success!", "The entity was created.");
                    } else if (data == "duplicate") {
                        openModal("Error!", "A room under that name already exists.");
                    } else {
                        openModal("Error!", "The server ran into a problem. Try again.");
                    }
                }

            )
        }
    </script>
</html>
