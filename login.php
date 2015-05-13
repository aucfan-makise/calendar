<?php
require_once 'AccountFunction.php';
session_start();
$error_msg = null;
$account_function = new AccountFunction($_POST);
?>
<!DOCTYPE html>
    <head>
        <title>ログイン</title>
        <?php if ($account_function->isLogoutSuccessed()): ?>
            <meta http-equiv='refresh' content='5;URL=calendar.php'>
        <?php else: ?>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <?php endif; ?>
    </head>
    <body>
        <?php if ($account_function->isError()): ?>
            <pre><?php echo $account_function->getErrorMessage(); ?></pre>
        <?php endif; ?>
        <?php if ($account_function->isLoginSuccessed()): ?>
<!--         ログイン成功 -->
            ログインしました。
            <form method='get' action='calendar.php'>
                <input type='submit' name='calendar' value='カレンダー'>
            </form>
        <?php elseif ($account_function->isLogoutSuccessed()): ?>
<!--         ログアウト成功 -->
            ログアウトしました。５秒後にカレンダーページに戻ります。
        <?php else: ?>
<!--         ログイン画面 -->
            <form method='post' action='login.php'>
                E-Mailアドレス
                <br>
                <input type='text' size=30 name='address'>
                <br>
                パスワード
                <br>
                <input type='password' size=30 name='password'>
                <br>
                <input type='submit' name='login' value='ログイン'>
                <input type='hidden' name='token' value='
                    <?php echo $account_function->cryptSessionId(session_id()); ?>'>
            </form>
            <a href='account_registration.php'>新規登録</a>
        <?php endif; ?>
    </body>
</html>