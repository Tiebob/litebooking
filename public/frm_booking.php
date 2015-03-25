<?php
include_once "config.inc.php";
include_once "function.php";

$action = '';
$loan_id = '';
$schoolno = '';
$isbn = '';

if (isset($_GET["action"]) ) $action = $_GET["action"];
if (isset($_GET["loan_id"]) ) $loan_id = $_GET["loan_id"];
if (isset($_GET["schoolno"]) ) {
    $schoolno = $_GET["schoolno"];
    $_POST["schoolno"] = $_GET["schoolno"];
}


if( $action === 'restore' and is_numeric($loan_id) ){
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
            <?php include "head.php" ?>
    </div>

    <!--網頁內容-->
    <div id="content" style="margin-top:1.2em;">

        <form action="" method="POST" role="form">
            <legend>借書作業</legend>

            <input type="hidden" name="action" value="loan"/>

            <?php
                if ($_POST['action'] == 'loan'){
                    $schoolno = $_POST['schoolno'];
                    $isbn = $_POST['isbn'];
                }
//                ($_POST['action'] == 'loan')? $schoolno = $_POST['schoolno'] : $schoolno =='';
            ?>


            <?php
                if($userinfo = get_reader_info($schoolno)){
            ?>
                    <div class="form-group">
                        <label for="schoolno">【學　　　號】</label>
                        <input type="text" class="form-control" name="schoolno" id="schoolno" readonly="true" value="<?=$schoolno?>" />
                    </div>
                    <div class="form-group">
                        <label for="schoolno">【姓　　　名】</label>
                        <input type="text" class="form-control" name="cname" readonly="true" value="<?=$userinfo["name"]?>" />
                    </div>

                    <div class="form-group ">
                        <label for="schoolno">【圖書ISBN碼】</label>
                        <input type="text" class="form-control" name="isbn" id="isbn" size="60" placeholder="請輸入圖書的ISBN碼">
                        <?php
                        if($_POST["isbn"] != ""){
                            $isbn = str_replace("-", "", $_POST["isbn"]);
                            if($book_marc = get_book_marc($isbn)){
                                $stmt = sprintf("Insert Into loan(reader_id, year, book_isbn, book_title, book_author, publisher, operator) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')", $schoolno, get_this_year(), $isbn, $book_marc['BookTitle'], $book_marc['Author'], $book_marc['Publisher'], 'admin');
//                                echobr($stmt);
                                $db = get_connection();
                                if(!book_loaned($schoolno, $isbn)){
                                    if($db->Execute($stmt)){
                                        echo "<p class='message alert alert-info'>" . $userinfo["cname"] . "已完成借閱 【" . $book_marc['BookTitle'] ."】 手續 </p>";
                                    }else{
                                        echo "<p class='message alert alert-danger'>" . $userinfo["cname"] . "借閱 【" . $book_marc['BookTitle'] ."】 手續失敗 </p>";
                                    }
                                }


                            }

//                            echo sprintf("<table class='booklist' style='margin-left:7.4em;margin-top:0.8em;'><tr><td>%s</td><td>%s</td></tr></table>",
//                                        $book_marc['BookTitle'],
//                                        $book_marc['Author']
//                            );
                        }
                        ?>
                    </div>

            <?php
                }else{
            ?>
                    <div class="form-group">
                        <label for="schoolno">【學　　　號】</label>
                        <input type="text" class="form-control" name="schoolno" id="schoolno" placeholder="請輸入學生學號">
                    </div>
            <?php
                }
            ?>




            <button type="submit" class="btn btn-primary">提交</button>
        </form>


        <div>
            <?=reader_loan_table($schoolno) ?>
        </div>
        <div>
            <?=reader_loan_table($schoolno, 1) ?>
        </div>

    </div>

    <!--頁尾-->
    <div id="footer">
        <?php include "footer.php";?>
    </div>

    <script >
        if($("#schoolno") != null) $("#schoolno").focus();
        if($("#isbn") != null) $("#isbn").focus();
    </script>
</div>
</body>
</html>