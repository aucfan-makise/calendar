<?php

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
     * CSRF対策として受け取ったsession idと現在のsession idが同一か確認する
     * @access protected
     * @param string $session_id
     * @return boolean
     */
    protected function identifyUser($session_id){
        session_start();
        return $session_id === session_id() ? true : false;
    }
}