<?php
require_once 'AbstractFunction.php';
require_once 'Properties.php';

class AccountFunction extends AbstractFunction
{

    private $is_registered = false;

    private $is_login_successed = false;

    private $is_logout_successed = false;

    private $address = '';

    private $password = '';

    private $connection;
    
    // ログイン　登録
    private $status = array(
        'login' => false,
        'logout' => false,
        'register' => false
    );

    /**
     * PostがなくてGetがあればAPI
     */
    public function __construct(array $post_data = null, array $api_data = null)
    {
        $this->connectDatabase();
        if (! is_null($post_data)) {
            $this->initialize($post_data);
        } elseif (! is_null($api_data)) {
            $this->apiInitialize($api_data);
        }
    }

    /**
     * api用の初期化
     * 
     * @access private
     * @param array $api_data            
     */
    private function apiInitialize(array $api_data)
    {
        $this->checkLoginData($api_data);
        session_start();
        $this->setSessionData();
    }

    /**
     * カレンダー用の初期化
     * 
     * @access private
     * @param array $post_data            
     */
    private function initialize(array $post_data)
    {
        try {
            if (! empty($post_data)) {
                $this->checkPostData($post_data);
                if ($this->status['register']) {
                    $this->registerAccount();
                    $this->is_registered = true;
                } elseif ($this->status['login']) {
                    $this->is_login_successed = true;
                    session_start();
                    $this->setSessionData();
                } elseif ($this->status['logout']) {
                    session_start();
                    $_SESSION = array();
                    session_write_close();
                    $this->is_logout_successed = true;
                }
            }
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());
        }
    }

    /**
     * ユーザ名とユーザidをSessionの変数に代入する
     * 
     * @access private
     * @throws mysqli_sql_exception
     * @throws Exception
     */
    private function setSessionData()
    {
        $_SESSION['user'] = $this->address;
        
        try {
            $stmt = mysqli_prepare($this->connection, 'SELECT user_id FROM user_accounts WHERE user_name=? AND deleted_at is null');
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 's', $this->address);
            if (! $bind_result)
                throw new mysqli_sql_exception('parameter bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if (! $execute_result)
                throw new mysqli_sql_exception('execute');
            $bind_result = mysqli_stmt_bind_result($stmt, $user_id);
            if (! $bind_result)
                throw new mysqli_sql_exception('result bind');
            mysqli_stmt_fetch($stmt);
            $close_result = mysqli_stmt_close($stmt);
            if (! $close_result)
                throw new mysqli_sql_exception('close');
            
            $_SESSION['user_id'] = $user_id;
        } catch (mysqli_sql_exception $e) {
            throw new Exception('SQL Error:' . $e->getMessage());
        }
    }

    /**
     * データベースに接続する
     * 
     * @access private
     */
    private function connectDatabase()
    {
        $this->connection = new mysqli('localhost', Properties::DATABASE_USER_NAME, '', Properties::DATABASE_NAME);
    }

    /**
     * データベースとの接続を切る
     */
    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * (non-PHPdoc)
     * 
     * @see AbstractFunction::checkPostData()
     */
    protected function checkPostData(array $post_data)
    {
        if (isset($post_data['register']))
            $this->status['register'] = true;
        if (isset($post_data['login']))
            $this->status['login'] = true;
        if (isset($post_data['logout']))
            $this->status['logout'] = true;
        
        try {
            // 処理内容のstatusの確認
            if (! ($this->status['register'] xor $this->status['login'] xor $this->status['logout'])) {
                throw new Exception('モードが変です。');
            }
            
            if ($this->status['register'] === true) {
                $this->checkRegistrationPostData($post_data);
            } elseif ($this->status['login'] === true) {
                $this->checkLoginData($post_data);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * getのデータはない
     * (non-PHPdoc)
     * 
     * @see AbstractFunction::checkGetData()
     */
    protected function checkGetData(array $get_data)
    {}

    /**
     * sessionのデータはない
     * (non-PHPdoc)
     * 
     * @see AbstractFunction::checkSessionData()
     */
    protected function checkSessionData(array $session_data)
    {}

    /**
     * ログインに関するデータのチェック
     *
     * @access private
     * @param array $post_data            
     * @throws Exception
     */
    private function checkLoginData(array $post_data)
    {
        try {
            // アドレスの確認
            $this->checkAddress($post_data['address']);
            if (isset($post_data['address'])) {
                $this->address = $post_data['address'];
            }
            // パスワードの確認
            if (empty($post_data['password'])) {
                throw new Exception('パスワードが入力されていません。');
            }
            $this->password = $post_data['password'];
            
            // アカウント照合
            if (! $this->verifyAccount()) {
                throw new Exception('アドレス/パスワードが間違っています。');
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * アカウント登録に関するデータのチェック
     * 
     * @access private
     * @param array $post_data            
     * @throws Exception
     */
    private function checkRegistrationPostData(array $post_data)
    {
        try {
            // アドレスの確認
            $this->checkAddress($post_data['address']);
            if (isset($post_data['address'])) {
                $this->address = $post_data['address'];
            }
            if ($this->isAccountExisting()) {
                throw new Exception('そのアドレスはすでに存在します。');
            }
            
            // パスワードの確認
            if (empty($post_data['password']) || empty($post_data[('check_password')])) {
                throw new Exception('パスワードが入力されていません。');
            }
            if (strlen($post_data['password']) < 8) {
                throw new Exception('パスワードは8文字以上にしてください。');
            }
            if ($post_data['password'] !== $post_data['check_password']) {
                throw new Exception('確認パスワードが一致しません。');
            }
            $this->password = crypt($post_data['password']);
        } catch (mysqli_sql_exception $e) {
            throw new Exception('SQL Error:' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('入力値が不正です。' . $e->getMessage());
        }
    }

    /**
     * アドレスが入力されているかと不正でないかの確認
     * ダメだったら例外スロー
     * 
     * @param string $address            
     * @throws Exception
     */
    private function checkAddress($address)
    {
        if (empty($address)) {
            throw new Exception('アドレスが入力されていません。');
        }
        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('アドレスが正しくありません。');
        }
    }

    /**
     * アカウントが存在するか確認する
     *
     * @access private
     * @throws mysqli_sql_exception
     * @return boolean
     */
    private function isAccountExisting()
    {
        try {
            $stmt = mysqli_prepare($this->connection, 'SELECT COUNT(*) FROM user_accounts WHERE user_name=? AND deleted_at is null');
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 's', $this->address);
            if (! $bind_result)
                throw new mysqli_sql_exception('parameter bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if (! $execute_result)
                throw new mysqli_sql_exception('execute');
            $bind_result = mysqli_stmt_bind_result($stmt, $count);
            if (! $bind_result)
                throw new mysqli_sql_exception('result bind');
            mysqli_stmt_fetch($stmt);
            $close_result = mysqli_stmt_close($stmt);
            if (! $close_result)
                throw new mysqli_sql_exception('close');
            
            return $count == 0 ? false : true;
        } catch (mysqli_sql_exception $e) {
            throw new mysqli_sql_exception('SQL Error:' . $e->getMessage());
        }
    }

    /**
     * アカウントのパスワードが正しいかどうかを確認する
     *
     * @access private
     * @throws mysqli_sql_exception
     * @return boolean
     */
    private function verifyAccount()
    {
        try {
            $stmt = mysqli_prepare($this->connection, 'SELECT user_passwd FROM user_accounts WHERE user_name=? AND deleted_at is null');
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 's', $this->address);
            if (! $bind_result)
                throw new mysqli_sql_exception('paramenter bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if (! $execute_result)
                throw new mysqli_sql_exception('execute');
            $bind_result = mysqli_stmt_bind_result($stmt, $passwd);
            if (! $bind_result)
                throw new mysqli_sql_exception('result bind');
            mysqli_stmt_fetch($stmt);
            $close_result = mysqli_stmt_close($stmt);
            if (! $close_result)
                throw new mysqli_sql_exception('close');
            
            return crypt($this->password, $passwd) === $passwd ? true : false;
        } catch (mysqli_sql_exception $e) {
            throw new mysqli_sql_exception('verifyAccount - SQL Error:' . $e->getMessage());
        }
    }

    /**
     * アカウントの登録を行う
     * 
     * @access private
     * @throws mysqli_sql_exception
     */
    private function registerAccount()
    {
        try {
            $stmt = mysqli_prepare($this->connection, 'INSERT INTO user_accounts (user_name, user_passwd, created_at, update_at) values(?, ?, now(), now())');
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 'ss', $this->address, $this->password);
            if (! $bind_result)
                throw new mysqli_sql_exception('bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if (! $execute_result)
                throw new mysqli_sql_exception('execute');
            $close_result = mysqli_stmt_close($stmt);
            if (! $close_result)
                throw new mysqli_sql_exception('close');
        } catch (mysqli_sql_exception $e) {
            throw new mysqli_sql_exception('SQL Error:' . $e->getMessage());
        }
    }

    /**
     * アドレスを返す
     * 
     * @access public
     * @return Ambigous <string, array>
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * 登録が完了したかどうかを返す
     * 
     * @access public
     * @return boolean
     */
    public function isRegistered()
    {
        return $this->is_registered;
    }

    /**
     * ログインが成功したかどうかを返す
     * 
     * @access public
     * @return boolean
     */
    public function isLoginSuccessed()
    {
        return $this->is_login_successed;
    }

    /**
     * ログアウトが成功したかどうかを返す
     * 
     * @access public
     * @return boolean
     */
    public function isLogoutSuccessed()
    {
        return $this->is_logout_successed;
    }
}
?>