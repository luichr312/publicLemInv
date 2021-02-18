<?php
session_start();
if(!$_SESSION['loggedIn']) {
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
            #infoFooter {
               font-size: 0.5rem;
               color: grey;
               text-align: center;
               margin-top: 10px;
               margin-bottom: 20px;
            }

            #exportDiv {
                border: 1px solid #b7b7b7;
                border-radius: 10px;
                width: min(900px, 95vw);
                margin-top: 15px;
                margin-left: auto;
                margin-right: auto;
                padding: 20px;
                line-height: 3;
                padding-bottom: 0px;
            }

            .container {
                display: inline;
                position: relative;
                cursor: pointer;
                font-size: 22px;
                border: 0;

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
                display: inline;
            }
            .container input:checked ~ .notChecked {
                display: none;
            }
            .form-inline {
                display: inline;
            }
            #exportBtn {
                margin-top: 10px;
                margin-bottom: 10px;
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
                <li class="nav-item active">
                    <a class="nav-link" href="export.php">Export <span class="sr-only">(current)</span></a>
                </li>
                <?php
                    if (strpos($_SESSION['access'], "/w") !== false) {
                        echo '<li class="nav-item"><a class="nav-link" href="create.php">Create</a></li>';
                    }
                    if ($_SESSION['access'] == "*") {
                        echo '<li class="nav-item"><a class="nav-link" href="create.php">Create</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="accounts.php">Accounts</a></li>';
                    }
                ?>
            </ul>
            <div id="helloText" class="d-none d-lg-block">Hello <?=$_SESSION['name']?>.</div>
            <button id="logoutBtn" class="btn navBarBtn iconFont" onclick="logout();" data-toggle="tooltip" data-placement="bottom" title="Sign out">H</button>
        </div>
    </nav>



    <body>

        <div id="exportDiv">
            <h4>Export as Excel-Sheet</h4>
            <?php if($_SESSION['access'] == "*") { echo "
                <label class='container form-control'>
                  <input type='radio' name='exportBy' value='0'>
                  <span class='checked iconFont'>P</span>
                  <span class='notChecked iconFont'>O</span>
                </label>
                Export All <br>
            "; } ?>
            <div class="form-inline">
                <label class="container form-control">
                  <input type='radio' name='exportBy' value='1'>
                  <span class='checked iconFont'>P</span>
                  <span class='notChecked iconFont'>O</span>
                </label>
            Export by Department:
                <select id="depDropDown" class="form-control">
                    <?php
                        foreach ($departments as $dep) {
                            if(strpos($_SESSION['access'], $dep["ID"]) !== false || $_SESSION['access'] == "*") {
                                echo "<option value='" . $dep["ID"] . "'>" . $dep["name"] . "</option>";
                            }
                        }

                    ?>
                </select>
            </div><br>
            <div class="form-inline">
                <label class='container form-control'>
                  <input type='radio' name='exportBy' value='2'>
                  <span class='checked iconFont'>P</span>
                  <span class='notChecked iconFont'>O</span>
                </label>Export By Room:
                <input id="roomInput" class="form-control" type="text" placeholder="X.Y.ZZ">
            </div><br>

            <button id="exportBtn" class="btn btn-primary iconFont" onclick="requestDownload();" data-toggle="tooltip" data-placement="bottom" title="Export">Q</button>
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

        function requestDownload() {
            let exportType = $('input[name="exportBy"]:checked').val();
            let depID = $("#depDropDown").val();
            let depName = $("#depDropDown option:selected").html()
            let room = $("#roomInput").val();
            if (exportType === undefined) { return; }
            console.log(exportType);

            switch (exportType) {
                case "0":
                    window.location.href= "exportCsv.php?dep=*&depName=Global";
                    break;

                case "1":
                    window.location.href= `exportCsv.php?dep=${depID}&depName=${depName}`;
                    break;

                case "2":
                    if (room == "") { $("#roomInput").css('border-color', 'red'); return; }
                    $("#roomInput").css('border-color', '#ced4da');
                    window.location.href= `exportCsv.php?room=${room}`;
                    break;
            }
        }
    </script>
</html>
