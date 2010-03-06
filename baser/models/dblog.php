<?php
/* SVN FILE: $Id$ */
/**
 * DBログモデル
 *
 * PHP versions 4 and 5
 *
 * BaserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2009, Catchup, Inc.
 *								9-5 nagao 3-chome, fukuoka-shi 
 *								fukuoka, Japan 814-0123
 *
 * @copyright		Copyright 2008 - 2009, Catchup, Inc.
 * @link			http://basercms.net BaserCMS Project
 * @package			baser.models
 * @since			Baser v 0.1.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://basercms.net/license/index.html
 */
/**
 * DBログモデル
 *
 * @package			baser.models
 */
class Dblog extends AppModel {
/**
 * クラス名
 *
 * @var		string
 * @access 	public
 */
   	var $name = 'Dblog';
/**
 * データベース接続
 *
 * @var     string
 * @access  public
 */
        var $useDbConfig = 'baser';
/**
 * beforeValidate
 *
 * @return	boolean
 * @access	public
 */
	function beforeValidate(){
		
		$this->validate['name'] = array(array('rule' => array('minLength',1),
											'message' => ">> ログ内容を入力して下さい",
											'required' => true));
		return true;
		
	}

}
?>