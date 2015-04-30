<?php
require_once 'AccountFunction.php';
$error_msg = null;
$account_function = new AccountFunction($_POST);
?>
<!DOCTYPE html>
    <head>
        <title>アカウント登録</title>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    </head>
    <body>
        <?php if ($account_function->isError()): ?>
            <pre><?php echo $account_function->getErrorMessage(); ?></pre>
        <?php endif; ?>
        <?php if (! $account_function->isRegistered()): ?>
<!--         登録画面 -->
        <form method='post' action='account_registration.php'>
            E-mailアドレス
            <br>
            <input type='text' size=30 name='address' value=
            <?php echo $account_function->getAddress(); ?>
            >
            <br>
            パスワード
            <br>
            <input type='password' size=30 name='password'>
            <br>
            もう一度パスワード
            <br>
            <input type='password' size=30 name='check_password'>
            <br>
            <input type='submit' name='register' value='登録'>
        </form>
        <?php endif; ?>
        <?php if ($account_function->isRegistered()): ?>
<!--         登録完了画面 -->
        登録が完了しました。
        <br>
        <a href=>ログイン</a>
        <a href='calendar.php'>カレンダーに戻る</a>
        <?php endif; ?>
    </body>
</html>