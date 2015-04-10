<?php
class RequesterException extends Exception{
	public function __construct(){
		parent::__construct('データの取得に失敗しました。');
	}
}
?>