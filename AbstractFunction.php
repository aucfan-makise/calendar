<?php
require_once 'Properties.php';

abstract class AbstractFunction
{

    protected $error_msg;

    /**
     * postのデータをチェックする
     * 
     * @access protected
     * @param array $post_data            
     */
    protected abstract function checkPostData(array $post_data);

    /**
     * getのデータをチェックする
     * 
     * @access protected
     * @param array $get_data            
     */
    protected abstract function checkGetData(array $get_data);

    /**
     * sessionのデータをチェックする
     * 
     * @access protected
     * @param array $session_data            
     */
    protected abstract function checkSessionData(array $session_data);

    /**
     * エラーメッセージを追加する
     * 
     * @access protected
     * @param string $str            
     */
    protected function setErrorMessage($str)
    {
        if (is_null($this->error_msg))
            $this->error_msg = array();
        $this->error_msg[] = $str;
    }

    /**
     * エラーメッセージがあるかどうかを確認する
     * 
     * @access public
     * @return boolean
     */
    public function isError()
    {
        return is_null($this->error_msg) ? false : true;
    }

    /**
     * エラーメッセージを返す
     * 
     * @access public
     * @return string
     */
    public function getErrorMessage()
    {
        $str = '';
        foreach ($this->error_msg as $msg) {
            $str = $str . ' ' . $msg;
        }
        return trim($str);
    }
    
    /**
     * CSRF対策として受け取ったsession idの暗号化したものを返す
     * @access protected
     * @param string $session_id
     * @return string
     */
    public function cryptSessionId($session_id){
        $encripted_id = crypt($session_id, Properties::MY_CRYPT_SALT);
        $_SESSION['token'] = $encripted_id;
        return $encripted_id;
    }
    
    /**
     * 受け取ったtokenとsession_idの暗号化したものを比較する
     * @access
     * @param string $token
     * @return boolean
     */
    protected function identifyUser($token){
        return $_SESSION['token'] === $token ? true : false;
    }
    
    /**
     * HTML出力時のエスケープ
     * @param string $str
     * @return string
     */
    public function e($str){
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}