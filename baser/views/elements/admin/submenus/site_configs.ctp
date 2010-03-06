<?php
/* SVN FILE: $Id$ */
/**
 * [管理画面] システム設定メニュー
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
 * @package			baser.views
 * @since			Baser v 0.1.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://basercms.net/license/index.html
 */
?>
<div class="side-navi">
    <h2>システム設定<br />共通メニュー</h2>
    <ul>
        <li><?php echo $html->link('サイト基本設定',array('controller'=>'site_configs','action'=>'form')) ?></li>
        <li><?php echo $html->link('グローバルメニュー設定',array('controller'=>'global_menus','action'=>'index')) ?></li>
        <li><?php echo $html->link('プラグイン設定',array('controller'=>'plugins','action'=>'index')) ?></li>
        <?php if(isset($backupEnabled)): ?>
            <li><?php echo $html->link('データバックアップ',array('action'=>'backup_data',1)) ?></li>
        <?php endif ?>
    </ul>
</div>