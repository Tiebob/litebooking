<?php
include_once "config.inc.php";
include_once "function.php";

$action = '';
$loan_id = '';
$schoolno = '';
$isbn = '';

if (isset($_GET["action"])) {
    $action = $_GET["action"];
}
if (isset($_GET["loan_id"])) {
    $loan_id = $_GET["loan_id"];
}
if (isset($_GET["schoolno"])) {
    $schoolno = $_GET["schoolno"];
    $_POST["schoolno"] = $_GET["schoolno"];
}


if ($action === 'restore' and is_numeric($loan_id)) {
    restore_book($loan_id);
    //header("location:frm_booking.php?schoolno=$schoolno");
}

?>
<!doctype html>
<html lang="zh-TW">
<head>
    <?php include("header.php"); ?>
</head>
<body>
<div id="container">

    <!--標頭-->
    <div id="header">
        <div id="navbar">
            <?php include "head.php" ?>
        </div>
    </div>


    <!--網頁內容-->
    <div id="content" style="margin-top:1.2em;">

        <div>
            <?= reader_loan_table("all") ?>
        </div>

    </div>


    <!--頁尾-->
    <div id="footer">
        <?php include "footer.php"; ?>
    </div>
</div>
</body>
</html>