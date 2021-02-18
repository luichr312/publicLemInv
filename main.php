<?php
session_start();
if(!$_SESSION["loggedIn"]) {
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
                --edit-btn-color: purple;
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
            #activateEditModeBtn {
                border-color: var(--edit-btn-color);
                color: var(--edit-btn-color);
            }
            #activateEditModeBtn:hover {
                background-color: var(--edit-btn-color);
            }

            #helloText, .navBarBtn {
                margin-left: 10px;
                background-color: #f8f9fa;
            }

            .navBarBtn:hover {
                color: white !important;
            }



            #filterDiv {
                border: 1px solid #b7b7b7;
                border-radius: 10px;
                width: min(800px, 95vw);
                margin-top: 15px;
                margin-left: auto;
                margin-right: auto;
                padding: 20px;
                line-height: 3;
                padding-bottom: 0px;
            }

            .modal-dialog {
                max-width: min(1200px, 95%);
            }

            td, th {
                text-align: left;
            }

            #singleItemsDiv, #bulkItemsDiv {
                padding: 15px;
                margin-left: auto;
                margin-right: auto;
                width: min(1500px, 100vw);
            }
            #bulkItemsDiv {
                padding: 15px;
                margin-left: auto;
                margin-right: auto;
                width: min(1500px, 100vw);
            }
            #filterActivityNotice {
                color: #b7b7b7;
            }
            #filterActivityNoticeContainer {
                display: grid;
                place-items: center;
            }

            .singleItemEditBtnConts, #newItemTableRow {
                display: none;
            }
            .singleItemEditBtns {
                padding: 0px;
                font-size: 1.3em;
                color: black;
            }

            .bulkItemEditBtns {
                display: none;
                margin-left: 10px;
                padding: 0px;
                font-size: 1.3em;
                color: black;
            }

            .attachmentInputs {
                width: 0.1px;
                height: 0.1px;
                opacity: 0;
                overflow: hidden;
                position: absolute;
                z-index: -1;
            }
            .deleteAttachmentBtns {
                position: absolute;
            }
            .uploadForms {
                display: inline;
                margin-right: 5px;
            }

            #confirmDeleteBtn {
                 margin-left: 10px;
            }
            #bulkItemsEditWarning {
                display: none;
                color: red;
            }

            #sumsOfIndicator {
                font-size: 1.2em;
            }
            #bulkItemsHeading {
                display: inline;
                vertical-align: middle;
            }

            #singleItemsSearchField {
                max-width:250px;
            }
            #infoFooter {
               font-size: 0.5rem;
               color: grey;
               text-align: center;
               margin-top: 10px;
               margin-bottom: 20px;
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
            }
            .container input:checked ~ .notChecked {
                display: none;
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
                <li class="nav-item active">
                    <a class="nav-link" href="#">Main <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="export.php">Export</a>
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
            <?php
                if (strpos($_SESSION['access'], "/w") !== false || $_SESSION['access'] == "*") {
                    echo "<button id='activateEditModeBtn' class='btn navBarBtn d-none d-md-inline-block iconFont' onclick='activateEditMode();' data-toggle='tooltip' data-placement='bottom' title='Edit Mode'>K</button>";
                }
            ?>
            <button id="logoutBtn" class="btn navBarBtn iconFont" onclick="logout();" data-toggle="tooltip" data-placement="bottom" title="Sign out">H</button>
        </div>
    </nav>



    <body>

        <div id="filterDiv">
            <h4>Filter</h4>
            <div class="form-inline">
            	<input id="depOption" class="mr-1" name="filterOptions" value="department" type="radio">
                <label for="depOption" class="mr-1">Department:</label>
                <select id="depDropDown" class="form-control">
                    <?php
                        foreach ($departments as $dep) {
                        	if(strpos($_SESSION['access'], $dep["ID"]) !== false || $_SESSION['access'] == "*") {
								echo "<option value='" . $dep["ID"] . "'>" . $dep["name"] . "</option>";
                        	}
                        }

                    ?>
                </select>
            </div>
            <div class="form-inline">
            	<input id="roomOption" class="mr-1" name="filterOptions" value="room" type="radio">
                <label for="roomOption" class="mr-1">Room:</label>
                <input id="roomInput" class="form-control" type="text" placeholder="X.Y.ZZ">
            </div>
            <div id="filterBtns">
            	<button id="resetFilterBtn" class="btn btn-secondary iconFont" onclick="document.location.href='';" data-toggle="tooltip" data-placement="bottom" title="Reset Filter">J</button>
            	<button id="filterBtn" class="btn btn-primary iconFont" onclick="filter();" data-toggle="tooltip" data-placement="bottom" title="Filter">F</button>
            </div>
            <div id="filterActivityNoticeContainer"><span id="filterActivityNotice">The filter is <span id="filterActivityNoticeNot">not</span> active.</span></div>
        </div>

        <div id="bulkItemsDiv">
            <h4 id="bulkItemsHeading">Bulk Items</h4><?php if($_SESSION['access'] == "*") { echo '<button id="bulkItemsSettingsBtn" class="btn btn-secondary ml-1 d-none d-md-inline-block iconFont" onclick="openModal(\'Loading...\', \'\'); openBulkItemPrefs();">B</button>'; } ?><br>
            <span id="sumsOfIndicator">Counts of: <i><span id="bulkItemsForRoomDep"><?=implode(", ", accessableDepartmentNames())?></span></i></span>
            <table id="bulkItemsContent" class="table">
                <tbody>
                    <?=bulkItemsTable();?>
                </tbody>
            </table>
            <span id="bulkItemsEditWarning">Bulk items are only editable when filtered by room.</span>
        </div>

        <div id="singleItemsDiv">
            <h4>Single Items</h4>
            <div id="searchDiv" class="form-inline">
                <label for="singleItemsSearchField" class="mr-1">Search:</label>
                <input id="singleItemsSearchField" class="form-control mr-1" type="text" placeholder="...">
                <button id="searchBtn" onclick="reloadTables(3, 's')" class="btn btn-primary iconFont">G</button>
            </div>
            <br>
            <table id="singleItemsTable" class="table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col" class="d-none d-md-table-cell">Description</th>
                        <th scope="col">Full Warranty</th>
                        <th scope="col">Room</th>
                        <th scope="col">Attachment</th>
                        <th scope="col" class="singleItemEditBtnConts">Edit</th>
                    </tr>
                </thead>
                <tbody id="singleItemsTableBody">
                    <?=singleItemsTable()?>
                </tbody>
                <tbody>
                    <tr id="newItemTableRow">
                        <td><input type="text" id="newItemNameField" class="form-control" placeholder="New Item..."></td>
                        <td><input type="text" id="newItemDescField" class="form-control" placeholder="..."></td>
                        <td>
                            <label class="container">
                              <input type="checkbox" id="newItemFullWarrCheck" value="1">
                              <span class="checked iconFont">P</span>
                              <span class="notChecked iconFont">O</span>
                            </label>
                        </td>
                        <td><input type="text" id="newItemRoomField" class="form-control" placeholder="..."></td>
                        <td>-</td>
                        <td><button id="saveNewItemBtn" class="btn btn-warning iconFont" onclick="saveNewSingleItem();">A</button></td>
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
        <button id="modalOpener" data-toggle='modal' data-target='#myModal' style="display:none;"></button>

    </body>

    <script src="resources/jquery-3.5.1.min.js"></script>
    <script src="resources/popper.min.js"></script>
    <script src="resources/bootstrap-4-5/js/bootstrap.js"></script>


    <script>

        var filterActive = false;
        var editModeActive = false;



        $(function () {
            $('[data-toggle="tooltip"]').tooltip();

            document.getElementById("singleItemsSearchField").addEventListener("keydown", function(e) {
                if(e.keyCode === 13) {
                    $("#singleItemsSearchField").blur();
                    reloadTables(3, 's');
                }
            });
        })

        window.addEventListener( "pageshow", function ( event ) {
            var historyTraversal = event.persisted || ( typeof window.performance != "undefined" && window.performance.navigation.type === 2 );
            if ( historyTraversal ) {
                window.location.reload()
            }
        });

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

        function filter() {
            if ($("input[name='filterOptions']:checked").length == 0 || ($("input[name='filterOptions']:checked").val() == "room" && $("#roomInput").val() == "")) { return; }
            reloadTables(1, "f", true);
        }

        function reloadTables(whichTables = 1, calledFrom = "", refreshEditMode = false) {
            //whichTables: 1 = both, 2 = bulk, 3 = single
            //calledFrom: "s" = search, "f" = filter -> only used for loading button

            let searchString = $("#singleItemsSearchField").val();
            var filterType = "";
            var filterString = "";
            if ($("input[name='filterOptions']:checked").length != 0 && (filterActive || calledFrom == "f")) {
            	filterType = $("input[name='filterOptions']:checked").val();
            	filterString = (filterType == "room") ? $("#roomInput").val() : $("#depDropDown").val();
			}

            var func = "reloadAllTables";
            if (whichTables === 2) {
                func = "reloadBulkItemsTable";
            } else if (whichTables === 3) {
                func = "reloadSingleItemsTable";
            }

            let btnToChange = "";
            if (calledFrom == "f") {
                btnToChange = "#filterBtn";
                $("#singleItemsSearchField").val("");
                searchString = "";
            } else if (calledFrom == "s") {
                btnToChange = "#searchBtn";
            }

            makeLoadingButton(btnToChange);

            let postObj = {
                func: func,
                filterType: filterType,
                filterString: filterString,
                searchString: searchString
            }

            $.post("api.php", postObj,
                function(data) {

                    let resetHTMLCont = (calledFrom == "s") ? "G" : "F";
                    makeLoadingButton(btnToChange, true, resetHTMLCont);
                    try {
                        data = JSON.parse(data);
                        if (data.length == 2) {
                            if (calledFrom == "f") {
                                $("#filterActivityNotice").css('color', 'red');
                                $("#filterActivityNoticeNot").html("");
                                $("#singleItemsSearchField").val("");
                                filterActive = true;
                            }
                            if(whichTables === 1 || whichTables === 2) {
                                $("#bulkItemsContent").html(data[0]);
                                if (calledFrom == "f") {
                                    $("#filterActivityNotice").css('color', 'red');
                                    $("#filterActivityNoticeNot").html("");
                                    $("#singleItemsSearchField").val("");
                                    filterActive = true;
                                }
                                if (filterActive && $("input[name='filterOptions']:checked").val() == "department") {
                                    $("#bulkItemsForRoomDep").html($("#depDropDown option:selected").html());
                                } else if (filterActive) {
                                    $("#bulkItemsForRoomDep").html(filterString);
                                }
                            }
                            if(whichTables === 1 || whichTables === 3) { $("#singleItemsTableBody").html(data[1]); if ($("#newItemTableRow").css("display") == "table-row") { $(".singleItemEditBtnConts").css("display", "table-cell"); }}
                        } else {
                            openModal("Error!", "The server ran into a problem.");
                        }
                    } catch {
                        if (data == "nonexisting room") {
                            openModal("Error!", "That room does not exist.");
                        } else if (data == "no access") {
                            $("#newItemNameField").val("");
                            $("#newItemDescField").val("");
                            $("#newItemRoomField").val("");
                            openModal("Error!", "This room is assigned to a department you do not have access for.");
                        } else {
                            openModal("Error!", "The server ran into a problem.")
                        }
                    }
                    if(refreshEditMode && editModeActive) { activateEditMode(); }

                }
            );
        }

        function activateEditMode(deactivate = false) {
            if (!deactivate) {
                $(".singleItemEditBtnConts").css("display", "table-cell");
                $("#newItemTableRow").css("display", "table-row");
                if ($("input[name='filterOptions']:checked").val() == "room" && filterActive) {
                    $("#newItemRoomField").val($("#roomInput").val());
                }
                if ($("input[name='filterOptions']:checked").val() != "room") {
                    $("#bulkItemsEditWarning").css("display", "inline-block");
                } else {
                    $("#bulkItemsEditWarning").css("display", "none");
                }
                $(".bulkItemEditBtns").css("display", "inline-block");
                $("#activateEditModeBtn").attr("onclick", "activateEditMode(true);");
                editModeActive = true;
            } else {
                $(".singleItemEditBtnConts").css("display", "none");
                $("#newItemTableRow").css("display", "none");
                $(".bulkItemEditBtns").css("display", "none");
                $("#bulkItemsEditWarning").css("display", "none");
                $("#activateEditModeBtn").attr("onclick", "activateEditMode();");
                editModeActive = false;
                reloadTables(1);
            }

        }

        function editBulkItem(id) {
            if (id == "" || !id) { return; }
            $("#bulkItemCount" + id).html(`<input type='number' id='editBulkItemCount${id}' class='form-control mr-1' value='${$("#bulkItemCount" + id).html()}'>`);
            $("#bulkItemEditBtnCont" +id).html(`<button id='saveEditedBulkItemBtn${id}' class='btn btn-success iconFont' onclick='saveEditedBulkItem("${id}");'>D</button>`);
        }

        function saveEditedBulkItem(id) {
            if (!id || id == "") { return; }


            let editedBulkItemName = $("#bulkItemTitle" + id).html();

            let editedBulkItemCount = $("#editBulkItemCount" + id).val();
            if (editedBulkItemCount == "" || isNaN(parseInt(editedBulkItemCount))) { $("editBulkItemCount" + id).css("border-color", "red"); console.log("Hello"); return; }

            let editedBulkItemRoom = $("#roomInput").val();
            if (editedBulkItemRoom == "") { return; }

            let postObj = {
                func: "saveEditedBulkItem",
                editedBulkItemName: editedBulkItemName,
                editedBulkItemCount: editedBulkItemCount,
                editedBulkItemRoom: editedBulkItemRoom
            }

            makeLoadingButton("#saveEditedBulkItemBtn" + id);

            $.post("api.php", postObj,
                function (data) {

                    makeLoadingButton("#saveEditedBulkItemBtn" + id, true, "D");

                    if (data == "success") {
                        reloadTables(1, "", true);
                    } else {
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            );
        }

        function editSingleItem(id) {
            if (id == "" || !id) { return; }
            let itemRoom = $("#itemRoom" + id).html()
            $("#itemName" + id).html(`<input type='text' id='editSingleItemNameField${id}' class='form-control' value='${$("#itemName" + id).html()}'>`);
            $("#itemDesc" + id).html(`<input type='text' id='editSingleItemDescField${id}' class='form-control' value='${$("#itemDesc" + id).html()}'>`);
            $("#itemFullWarr" + id).html(`<label class='container'>
              <input type='checkbox' id='editSingleItemFullWarrCheck${id}' ${($("#itemFullWarr" + id).html() == "Yes") ? "checked" : ""}>
              <span class='checked iconFont'>P</span>
              <span class='notChecked iconFont'>O</span>
            </label>`);
            $("#itemRoom" + id).html(`<input type='text' id='editSingleItemRoomField${id}' class='form-control' value='${itemRoom}'>`);
            $("#itemLink" + id).html(`<form id='uploadForm${id}' class='uploadForms'><input type='file' name='fileToUpload' id='attachmentInput${id}' class='attachmentInputs' callID='${id}' callRoom='${itemRoom}'><label for='attachmentInput${id}' class='btn btn-secondary labelForAttachmentInputs iconFont'>C</label></form><button class='btn btn-danger mr-1 deleteAttachmentBtns iconFont' onclick='confirmPDFDelete("${id}", "${itemRoom}")'>E</button>`);
            $("#editSingleItemBtnCont" + id).html(`<button id='saveEditedSingleItemBtn${id}' class='btn btn-success mr-1 iconFont' onclick='saveEditedSingleItem("${id}");'>D</button><button id='removeEditedItemBtn' class='btn btn-danger iconFont' onclick='confirmDeleteItem("${id}");'>I</button>`);

            $(".attachmentInputs").change(function(){
                let callerID = $(this).attr("callID");
                let callerRoom = $(this).attr("callRoom");
                uploadPDF(callerID, callerRoom);
            });
        }

        function saveNewSingleItem() {
            let newItemName = $("#newItemNameField").val();
            let newItemDesc = $("#newItemDescField").val();
            let newItemRoom = $("#newItemRoomField").val();
            if (newItemName == "") { $("#newItemNameField").css("border-color", "red"); return; }
            if (newItemDesc == "") { $("#newItemDescField").css("border-color", "red"); return; }
            if (newItemRoom == "") { $("#newItemRoomField").css("border-color", "red"); return; }

            let newItemFullWarr = $("input[id='newItemFullWarrCheck']:checked").length;

            let postObj = {
                func: "saveNewItem",
                newItemName: newItemName,
                newItemDesc: newItemDesc,
                newItemRoom: newItemRoom,
                newItemFullWarr: newItemFullWarr
            }

            makeLoadingButton("#saveNewItemBtn");

            $.post("api.php", postObj,
                function(data) {
                    makeLoadingButton("#saveNewItemBtn", true, "A");

                    $("#newItemNameField").css("border-color", "#ced4da");
                    $("#newItemDescField").css("border-color", "#ced4da");
                    $("#newItemRoomField").css("border-color", "#ced4da");

                    if (data == "success") {
                        $("#newItemNameField").val("");
                        $("#newItemDescField").val("");
                        $("#newItemRoomField").val("");
                        $("#newItemFullWarrCheck").prop("checked", false)
                        openModal("Success!", "The new item was saved.");
                        reloadTables(3);
                    } else if (data == "nonexisting room") {
                        openModal("Error!", "That room does not exist.");
                    } else if (data == "no access") {
                        $("#newItemNameField").val("");
                        $("#newItemDescField").val("");
                        $("#newItemRoomField").val("");
                        $("#newItemFullWarrCheck").prop("checked", false)
                        openModal("Error!", "You do not have permission to add items in this department.");
                    } else {
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            );
        }


        function confirmDeleteItem(id) {
            openModal("Warning!", `Please confirm your intent to remove this item. <button id='confirmDeleteBtn' data-dismiss='modal' class='btn btn-primary' onclick='saveEditedSingleItem("${id}", true)'>Confirm</button>`);
        }

        function saveEditedSingleItem(id, remove = false) {
            if (!id) { return; }


            let editedItemName = $("#editSingleItemNameField" + id).val();
            let editedItemDesc = $("#editSingleItemDescField" + id).val();
            let editedItemRoom = $("#editSingleItemRoomField" + id).val();
            if (editedItemName == "") {$("editSingleItemNameField" +id).css("border-color", "red"); return;}
            if (editedItemDesc == "") {$("editSingleItemDescField" +id).css("border-color", "red"); return;}
            if (editedItemRoom == "") {$("editSingleItemRoomField" +id).css("border-color", "red"); return;}

            let editedItemFullWarr = $(`input[id='editSingleItemFullWarrCheck${id}']:checked`).length

            let postObj = {
                func: "saveEditedSingleItem",
                editedItemId: id,
                editedItemName: editedItemName,
                editedItemDesc: editedItemDesc,
                editedItemFullWarr: editedItemFullWarr,
                editedItemRoom: editedItemRoom,
                remove: remove
            }

            makeLoadingButton("#saveEditedSingleItemBtn" + id)

            $.post("api.php", postObj,
                function (data) {
                    makeLoadingButton("#saveEditedSingleItemBtn" + id, true, "D");
                    if (data == "success") {
                        reloadTables(3);
                    } else if (data == "nonexisting room") {
                        openModal("Error!", "That room does not exist.");
                    } else if (data == "no access") {
                        openModal("Error!", "You do not have permission to perform this action.");
                    } else {
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            );
        }

        function confirmPDFDelete(id, room) {
            if (!id || id == "") { return; }
            openModal("Warning!", `Please confirm your intent to remove this attachment. <button id='confirmDeleteBtn' data-dismiss='modal' class='btn btn-primary' onclick='uploadPDF("${id}", "${room}", true)'>Confirm</button>`);
        }

        function uploadPDF(id, room, remove = false) {

            if(!id || !room) { return; }

            makeLoadingButton(".labelForAttachmentInputs");

            var ajaxParameter = {
                url: "api.php",
                type: "POST",
                success: function(data) {

                    makeLoadingButton(".labelForAttachmentInputs", true, "C");

                    if (data == "success") {
                        openModal("Success!", "The attachment linked to this item was updated.");
                    } else if (data == "not a pdf") {
                        openModal("Error!", "The selected file is not a PDF.");
                    } else if (data == "no access") {
                        openModal("Error!", "You do not have permission to modify the attachments linked to this item.");
                    } else if (data == "unlink error"){
                        openModal("Error!", "This attachment cannot be deleted because it does not exist.");
                    } else {
                        openModal("Error!", "The server ran into a problem.");
                    }
                }
            }

            if (!remove) {
                ajaxParameter.data = new FormData($("#uploadForm" + id).get(0));
                ajaxParameter.data.append("func", "uploadPDF");
                ajaxParameter.data.append("ID", id);
                ajaxParameter.data.append("room", room);
                ajaxParameter.contentType = false;
                ajaxParameter.processData = false;
            } else {
                ajaxParameter.data = {
                    func: "deletePDF",
                    ID: id,
                    room: room
                }
            }
            $.ajax(ajaxParameter);
        }

        function openBulkItemPrefs(onlyReloadTable = false) {
            makeLoadingButton("#bulkItemsSettingsBtn");

            let postObj = {
                func: "bulkItemPrefs"
            };

            $.post("api.php", postObj,
                function(data) {
                    makeLoadingButton("#bulkItemsSettingsBtn", true, "B");
                    if (data == "error" || data == "") {
                        openModal("Error!", "The server ran into a problem.", !onlyReloadTable);
                    } else if (data == "no access") {
                        openModal("Error!", "You do not have permission to open this window.", !onlyReloadTable);
                    } else {
                        openModal("Bulk Item Preferences", data, !onlyReloadTable);
                    }
                }
            );
        }

        function confirmBulkTypeDelete(id) {
            if (!id || id == "") { return; }
            openModal("Warning!", `Are you sure you want to delete this bulk item type and all associated data? <button id='confirmDeleteBtn' class='btn btn-primary' onclick='saveNewBulkItem(true, "${id}")'>Confirm</button>`, false);
        }

        function saveNewBulkItem(remove = false, name = "") {
            var func = "";

            if (!remove) {
                name = $("#newBulkItemNameField").val();
                func = "saveNewBulkItemType";
            } else {
                func = "removeBulkItemType";
            }

            if (name == "") { return; }

            let postObj = {
                func: func,
                name: name
            };

            $.post("api.php", postObj,
                function(data) {
                    if (data == "success") {
                        openBulkItemPrefs(true);
                        reloadTables(2);
                    } else if (data == "no access") {
                        openModal("Error!", "You do not have permission to edit bulk item types.", false);
                    } else {
                        openModal("Error!", "The server ran into a problem.", false);
                    }
                }
            );
        }



    </script>
</html>
