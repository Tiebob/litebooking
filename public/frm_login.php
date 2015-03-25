<?php
include_once "config.inc.php";
include_once "function.php";
?>
<!doctype html>
<html lang="zh-TW">
<head>
    <?php include("header.php"); ?>
    <style>
        /*.container{*/
            /*width: 600px;*/
            /*margin: 0 auto;*/
        /*}*/
    </style>
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
    <div id="content" class="row">
        <div class="col-sm-6 col-md-4 col-md-offset-4">
<!--            <h1 class="text-center login-title">Sign in to continue to Bootsnipp</h1>-->
            <div class="account-wall">
                <img class="profile-img" src="https://lh5.googleusercontent.com/-b0-k99FZlyE/AAAAAAAAAAI/AAAAAAAAAAA/eu7opA4byxI/photo.jpg?sz=120"
                     alt="">
                <form class="form-signin">
                    <input type="text" name="username" class="form-control" placeholder="管理員帳號" required autofocus>
                    <input type="password" name="password" class="form-control" placeholder="管理員密碼" required>
                    <button class="btn btn-lg btn-primary btn-block" type="submit">
                        登入</button>
<!--                    <label class="checkbox pull-left">-->
<!--                        <input type="checkbox" value="remember-me">-->
<!--                        Remember me-->
<!--                    </label>-->
                </form>
            </div>
            <a href="#" class="text-center new-account">Create an account </a>
        </div>
    </div>





    <!--頁尾-->
    <div id="footer">
        <?php include "footer.php";?>
    </div>


</div>

</body>
</html>