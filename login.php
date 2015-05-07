<?php
require_once 'AccountFunction.php';
$error_msg = null;
$account_function = new AccountFunction($_POST);
session_start();
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
                <input type='hidden' name='session_id' value='<?php echo session_id(); ?>'>
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
            </form>
            <a href='account_registration.php'>新規登録</a>
        <?php endif; ?>
    </body>
</html>