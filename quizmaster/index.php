<?php
if (file_exists('install/index.php')) {
    header("location:install/");
    die();
}
session_start();
if (isset($_SESSION['id']) && isset($_SESSION['username'])) {
    header("location:home.php");
    return false;
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Login | Quiz</title>
        <?php include 'include-css.php'; ?>
    </head>
    <body class="login">
        <div>
            <a class="hiddenanchor" id="signup"></a>
            <a class="hiddenanchor" id="signin"></a>
            <div class="login_wrapper">
                <div class="animate form login_form">
                    <section class="login_content">
                        <form method ="POST"  id ="login_form" action="check_login.php">
                            <h1 class="text-center">LOGIN</h1>
                            <div>
                                <input type="text"  id="username" name='username' class="form-control" placeholder="Username" />
                            </div>
                            <div>
                                <input type="password" id="password" name='password' class="form-control" placeholder="Password" />
                            </div>
                            <div class="row mt-20">
                                <input type ="submit" id="login_button" class="btn btn-default text-center" value="Log in">
                            </div>
                            <div class="clearfix"></div>
                            <div style ="display:none;" id="result">
                            </div>
                            <div class="separator">
                                <div class="clearfix"></div>
                                <br>
                                <img src="images/logo-460x114.png" alt="QUIZ" width='300'>
                                <p class="text-center"><br>© <?= date('Y') ?> WRTeam</p>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
        <!-- Including Jquery so All js Can run -->
        <script type="text/javascript" src="js/jquery.min.js"></script>
        <!-- Validadtion js -->
        <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>
        <script>
            $("#login_form").validate({
                rules: {
                    username: "required",
                    password: "required",
                },
                messages: {
                    username: {
                        required: "Please Enter username"
                    },
                    password: {
                        required: "Please Enter password",

                    },
                }
            });
            $(document).on('submit', '#login_form', function (e) {
                e.preventDefault();
                $.ajax({
                    url: $(this).attr('action'),
                    type: "POST",
                    data: $(this).serialize(),
                    beforeSend: function () {
                        $('#login_button').html('Please Wait...');
                    },
                    // data: dataString,
                    success: function (result) {
                        if (result == 1) {
                            window.location.href = "home.php";
                        } else
                        {
                            $('#result').html(result);
                            $('#result').show();
                            $('#login_button').html('Log in');
                        }
                    }
                });
            });
        </script>
    </body>
</html>