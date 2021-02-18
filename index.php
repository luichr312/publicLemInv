<?php
session_start();
if($_SESSION['loggedIn']) {
    header("Location: main.php", true, 303);
}
?>
<!-- Hello -->
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="resources/bootstrap-4-5/css/bootstrap.min.css">
        <title>LEM Inventory</title>
        <style>
            #loginDiv {
                border: 1px solid black;
                border-radius: 15px;
                margin-left: auto;
                margin-right: auto;
                width: min(500px, 95vw);
                padding: 20px;
                overflow: hidden;
            }
            #logoImg {
                width: 80%;
                margin-bottom: 15px;
            }
            #buttonDiv {
                float: right;
            }

            .modal-dialog {
                max-width: min(1200px, 95%);
            }

            body {
                display: grid;
                place-items: center;
                height: 100vh;
            }
            #warningNotice {
                color: red;
            }
            @font-face {
                font-family: myIcons;
                src: url(media/Myicons.ttf);
            }
            .iconFont {
                font-family: myIcons;
            }

        </style>
    </head>
    <body>
        <div id="loginDiv">
            <img id="logoImg" src="media/lemlogo.jpg"><br>
            <h4>Inventory</h4>
            <div id="credForm">
                <div class="form-group">
                    <label for="userIDInput">Username:</label>
                    <input  id="userIDInput" class="form-control credInp" type="text" placeholder="...">
                </div>
                <div class="form-group">
                    <label for="passwordInput">Password:</label>
                    <input id="passwordInput" class="form-control credInp" type="password" placeholder="*********">
                </div>
                <div id="buttonDiv">
                    <button id="registerButton" class="btn btn-secondary" onclick="registerForm()">Register</button>
                    <button id="loginButton" class="btn btn-primary" onclick="login()">Log In</button>
                </div>
            </div>
        </div>


        <div class="modal fade" id="myModal">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h4 id="modHead" class="modal-title">Error!</h4>
                    </div>

                    <div id="modConts" class="modal-body"></div>

                    <div id="modFoot" class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>

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
            $('[data-toggle="tooltip"]').tooltip();
            if(credInps = document.getElementsByClassName("credInp")) {
                for (var inp = 0; inp < credInps.length; inp++) {
                    console.log("Hello");
                    credInps[inp].addEventListener("keydown", function (e) {
                        if (e.keyCode === 13) {
                            login();
                        }
                    });
                }
            }
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
                $(btn).addClass("iconFont");
                $(btn).html("N");
            } else {
                $(btn).removeClass("btn-danger");
                $(btn).removeClass("loading");
                $(btn).removeClass("iconFont");
                $(btn).html(resetText);
            }
        }

        function login() {
            let userID = $("#userIDInput").val()
            let password = $("#passwordInput").val()
            let postObj = {
                func: "login",
                userID: userID,
                password: password,
            }
            makeLoadingButton("#loginButton")
            $.post("api.php", postObj,
                function(data) {
                    makeLoadingButton("#loginButton", true, "Login")
                    if (data == "success") {
                        document.location.href = "main.php"
                    } else if (data == "wrongCreds") {
                        openModal("Error!", "Wrong password.");
                    } else if (data == "noAccount") {
                        openModal("Error!", "No account found with this username.");
                    } else {
                        openModal("Error!", "The server ran into a problem while verifying your credentials.");
                    }
                    console.log(data);
                }
            )
        }

        function registerForm() {
            $("#credForm").html(`
                <div class="form-group">
                    <label for="userIDInput">Username:</label>
                    <input  id="userIDInput" class="form-control credInp" type="text" placeholder="...">
                </div>
                <div class="form-group">
                    <label for="registerKeyInput">Register Key:</label>
                    <input id="registerKeyInput" class="form-control credInp" type="password" placeholder="*********">
                </div>
                <div class="form-group">
                    <label for="newPasswordInput">New Password:</label>
                    <input id="newPasswordInput" class="form-control credInp" type="password" placeholder="*********">
                </div>
                <div class="form-group">
                    <label for="verifyPasswordInput">Verify Password:</label>
                    <input id="verifyPasswordInput" class="form-control credInp" type="password" placeholder="*********">
                </div>
                Tip: Use your IAM-password.<br>
                <span id="warningNotice"></span>
                <div id="buttonDiv">
                    <button id="registerButton" class="btn btn-primary" onclick="registerAccount()">Register</button>
                </div>
            `)
        }

        function registerAccount() {
            let userID = $("#userIDInput").val();
            let registerKey = $("#registerKeyInput").val();
            let newPassword = $("#newPasswordInput").val();
            let verifyPassword = $("#verifyPasswordInput").val();
            if (userID == "") { $("#userIDInput").css("border-color", "red"); return; }
            if (registerKey == "") { $("#registerKeyInput").css("border-color", "red"); return; }
            if (newPassword == "") { $("#newPasswordInput").css("border-color", "red"); return; }
            if (verifyPassword == "") { $("#verifyPasswordInput").css("border-color", "red"); return; }

            if (newPassword != verifyPassword) {
                $("#warningNotice").html("Your passwords do not match!");
                return;
            }
            if (newPassword.length < 8) {
                $("#warningNotice").html("The minimum number of characters is 8.");
                return;
            }
            let postObj = {
                func: "register",
                userID: userID,
                registerKey: registerKey,
                newPassword: newPassword
            }
            makeLoadingButton("registerButton")
            $.post("api.php", postObj,
                function(data) {
                    $("#userIDInput").css("border-color", "#ced4da");
                    $("#registerKeyInput").css("border-color", "#ced4da");
                    $("#newPasswordInput").css("border-color", "#ced4da");
                    $("#verifyPasswordInput").css("border-color", "#ced4da");

                    makeLoadingButton("registerButton", true, "Register");
                    if (data == "success") {
                        document.location.href = "main.php"
                    } else if (data == "wrongCreds") {
                        openModal("Error!", "Your User ID or your register key is wrong!");
                    } else {
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            );

        }
    </script>
</html>
