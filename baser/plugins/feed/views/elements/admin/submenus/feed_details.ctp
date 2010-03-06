<?php
/* SVN FILE: $Id$ */
/**
 * [管理画面] フィード詳細メニュー
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
 * @package			baser.plugins.feed.views
 * @since			Baser v 0.1.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://basercms.net/license/index.html
 */
?>
<div class="side-navi">
<h2>フィードメニュー</h2>
<ul>
<?php if($this->params['controller']=='feed_details'): ?>
<li><?php echo $html->link('読込フィードを追加',array('controller'=>'feed_details','action'=>'add',$form->value('FeedDetail.feed_config_id'))) ?></li>
<li><?php echo $html->link('フィード設定に戻る',array('controller'=>'feed_configs','action'=>'edit',$form->value('FeedDetail.feed_config_id'))) ?></li>
<?php else: ?>
<li><?php echo $html->link('読込フィードを追加',array('controller'=>'feed_details','action'=>'add',$form->value('FeedConfig.id'))) ?></li>
<?php endif; ?>
</ul>
</div>