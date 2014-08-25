<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Semyon Vyskubov <sv@rv7.ru>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

$LANG->includeLLFile('EXT:shop_manager/mod1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

/**
 * Module 'Shop Manager' for the 'shop_manager' extension.
 *
 * @author	Semyon Vyskubov <sv@rv7.ru>
 * @package	TYPO3
 * @subpackage	tx_shopmanager
 */

class tx_shopmanager_module1 extends t3lib_SCbase {



	var $pageinfo;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init() {
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
		parent::init();
	}



	/**
	 * getTrackingInformation analog function of tt_products
	 */
	function getTrackingInformation($orderRow, $templateCode) {

		global $LANG, $TYPO3_DB;

		$extConf = $this->getMergedConfig();

		/**
		 *	Database update
		 */

		$newStatus = t3lib_div::_POST('opt2');
		$newStatusComment = t3lib_div::_POST('opt3');
		$tstamp = time();

		$status_log_element = array(
			'time' => $tstamp,
			'info' => $LANG->getLL('function2.status.'.$newStatus),
			'status' => $newStatus,
			'comment' => $newStatusComment
		);

		$status_log = unserialize($orderRow['status_log']);
		array_push($status_log, $status_log_element);

		$fieldsArray['tstamp'] = $tstamp;
		$fieldsArray['status'] = $newStatus;
		$fieldsArray['status_log'] = serialize($status_log);
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderRow['uid']), $fieldsArray);

		/**
		 *	E-mail notification
		 */

		$orderData = unserialize($orderRow['orderData']);
		$sendername = $extConf['orderEmail_fromName'];
		$senderemail = $extConf['orderEmail_from'];
		$recipients = $orderRow['email'];

		$cObj = t3lib_div::makeInstance("tslib_cObj");
		$emailContent = trim($cObj->getSubpart($templateCode,'###TRACKING_EMAILNOTIFY_TEMPLATE###'));

		$markerArray['###ORDER_STATUS_TIME###'] = date('d.m.Y, H:i:s', $tstamp);
		$markerArray['###ORDER_STATUS###'] = $newStatus;
		$markerArray['###ORDER_STATUS_INFO###'] = $LANG->getLL('function2.status.'.$newStatus);
		$markerArray['###ORDER_STATUS_COMMENT###'] = ($newStatusComment)?$newStatusComment:"-";
		$markerArray['###PID_TRACKING###'] = $extConf['PIDtracking'];
		$markerArray['###PERSON_NAME###'] = $orderData['billing']['name'];
		$markerArray['###DELIVERY_NAME###'] = $orderData['delivery']['name'];
		$markerArray['###ORDER_TRACKING_NO###'] = $orderRow['tracking_code'];
		$markerArray['###ORDER_UID###'] = $orderRow['uid'];
		$markerArray['###DOMAIN###'] = $extConf['domain'];

		$emailContent = $cObj->substituteMarkerArray($emailContent, $markerArray);

		$parts = explode(chr(10), $emailContent, 2);
		$subject = trim($parts[0]);
		$plain_message = trim($parts[1]);

		include_once (PATH_t3lib.'class.t3lib_htmlmail.php');

		$cls = t3lib_div::makeInstanceClassName('t3lib_htmlmail');

		if (class_exists($cls)) {
			$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$Typo3_htmlmail->start();
			$Typo3_htmlmail->mailer = 'TYPO3 HTMLMail';
			$message = html_entity_decode($plain_message);
			if ($Typo3_htmlmail->linebreak == chr(10))
				$message = str_replace(chr(13).chr(10),$Typo3_htmlmail->linebreak,$plain_message);

			$Typo3_htmlmail->subject = $subject;
			$Typo3_htmlmail->from_email = $senderemail;
			$Typo3_htmlmail->from_name = str_replace (',' , ' ', $sendername);
			$Typo3_htmlmail->replyto_email = $Typo3_htmlmail->from_email;
			$Typo3_htmlmail->replyto_name = $Typo3_htmlmail->from_name;
			$Typo3_htmlmail->organisation = '';

			$Typo3_htmlmail->addPlain($plain_message);

			$Typo3_htmlmail->setHeaders();
			$Typo3_htmlmail->setContent();
			$Typo3_htmlmail->setRecipient(explode(',', $recipients));
			$Typo3_htmlmail->sendTheMail();
		}

	}



	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig() {

		global $LANG;

		$extConf = $this->getMergedConfig();

		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $LANG->getLL('function1'),
				'2' => $LANG->getLL('function2')
			)
		);

		if (intval($extConf['swapFunctions']) > 0)
			krsort($this->MOD_MENU['function']);

		parent::menuConfig();

	}



	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main() {

		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

			// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// initialize doc
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('shop_manager') . 'mod1/mod_template.html');
		$this->doc->backPath = $BACK_PATH;
		$docHeaderButtons = $this->getButtons();

			// Draw the form
		$this->doc->form = '<form action="" method="post" enctype="multipart/form-data" id="docForm">';

			// JavaScript and Stylesheets
		$this->doc->JScode = '<link rel="stylesheet" type="text/css" href="' . $BACK_PATH . t3lib_extMgm::extRelPath('shop_manager') . 'mod1/styles.css" media="all">';
		$this->doc->JScode .= '<script type="text/javascript">var extConf = ' . json_encode($this->getMergedConfig()) . '</script>';
		$this->doc->JScode .= '<script type="text/javascript" src="' . $BACK_PATH . t3lib_extMgm::extRelPath('shop_manager') . 'mod1/jquery.js"></script>';
		$this->doc->JScode .= '<script type="text/javascript" src="' . $BACK_PATH . t3lib_extMgm::extRelPath('shop_manager') . 'mod1/jquery.cookie.js"></script>';
		$this->doc->JScode .= '<script type="text/javascript" src="' . $BACK_PATH . t3lib_extMgm::extRelPath('shop_manager') . 'mod1/header.js"></script>';
		$this->doc->postCode = '<script type="text/javascript" src="' . $BACK_PATH . t3lib_extMgm::extRelPath('shop_manager') . 'mod1/footer.js"></script>';

			// Setting default table layout
		$layoutRow = Array(
			'tr' => array('<tr valign="top">', '</tr>'),
			'0' => Array('<td class="td-label">','</td>'),
			'defCol' => Array('<td>', '</td>'),
		);
		$this->doc->tableLayout = Array (
			'defRow' => $layoutRow,
			'defRowOdd' => $layoutRow,
			'defRowEven' => $layoutRow
		);
		$this->doc->tableLayout['defRowOdd']['tr'] = array('<tr valign="top" class="tr-odd">', '</tr>');
		$this->doc->tableLayout['defRowEven']['tr'] = array('<tr valign="top" class="tr-even">', '</tr>');
		$this->doc->table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-usersettings">';

			// Render content:
		$this->moduleContent();

			// If no access or if ID == zero
		$this->content .= $this->doc->spacer(10);

			// compile document
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);

		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content = $this->doc->insertStylesAndJS($this->content);

	}



	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}



	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() {

		global $GLOBALS, $LANG, $TYPO3_CONF_VARS, $TYPO3_DB;

		$extConf = $this->getMergedConfig();
		$funcId = intval($this->MOD_SETTINGS['function']);

		$content = $this->doc->header($LANG->getLL('function' . $funcId)) . $this->doc->spacer(10);

		$timeMask = 'd.m.Y, H:i:s';

		$query = $TYPO3_DB->SELECTquery(
			'*',
			'tt_products_cat',
			'tt_products_cat.pid=' . $extConf['PIDcategories'] . t3lib_BEfunc::deleteClause("tt_products_cat"),
			'',
			'title'
		);
		$res = $TYPO3_DB->sql_query($query);
		$categories = Array();
		while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			$categories[$row['uid']] = $row['title'];
		}
		$TYPO3_DB->sql_free_result($res);

		$possibleStatuses = explode(",", "1,2,10,11,12,13,20,21,30,50,51,60,100,101,200");

		$detailed = intval(t3lib_div::_POST('detailed'));
		if (intval(t3lib_div::_POST('jumpTo')) > 0)
			$detailed = intval(t3lib_div::_POST('jumpTo'));
		$opt1 = t3lib_div::_POST('opt1');
		$opt2 = t3lib_div::_POST('opt2');
		$opt3 = t3lib_div::_POST('opt3');
		$deleteItem = t3lib_div::_POST('deleteItem');

		if (isset($deleteItem)) {
			$TYPO3_DB->exec_UPDATEquery('tt_products', 'uid='.intval($deleteItem), array('deleted' => '1'));
		}

		if ($opt1 == 'statusChange') {

			$extConf = $this->getMergedConfig();

			$query = $TYPO3_DB->SELECTquery(
				'*',
				'sys_products_orders',
				'uid=' . $detailed
			);
			$res = $TYPO3_DB->sql_query($query);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$TYPO3_DB->sql_free_result($res);

			$template_full_path = t3lib_div::getFileAbsFileName($extConf['ttproductsTemplate']);
			if (@is_file($template_full_path))
				$templateCode = t3lib_div::getUrl($template_full_path);

			$this->getTrackingInformation($row, $templateCode);

			$opt1 = '';

		}

		switch($funcId) {
			case 1:

				$content .= '<table cellpadding="0" cellspacing="0" width="100%"><tr valign="top"><td width="60%" class="td-edge">';

					// Items list
				$itemsFilter = intval(t3lib_div::_POST('itemsFilter'));
				if (!t3lib_div::_POST('itemsFilter') && $_COOKIE['typo3-shopmanager-itemsFilter'])
					$itemsFilter = $_COOKIE['typo3-shopmanager-itemsFilter'];
				if ($_COOKIE['typo3-shopmanager-itemsFilter'] != $itemsFilter)
					setcookie('typo3-shopmanager-itemsFilter', $itemsFilter);
				if (t3lib_div::_POST('itemsFilter') == "unset") {
					$itemsFilter = 0;
					setcookie('typo3-shopmanager-itemsFilter', 0);
				}
				if ($itemsFilter > 0)
					$whereApx = " AND tt_products.category=" . $itemsFilter;

				$filterContent = '<div class="pare" style="float: right;">' .
					'	<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tt_products]['.$extConf['PIDstoreRoot'].']=new&defVals[tt_products][category]=' . $itemsFilter, $GLOBALS['BACK_PATH'])).'">' .
					'		<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/new_el.gif').' title="' . $LANG->getLL('act.add_new_record') . '" border="0" alt="" />' .
					'		' . $LANG->getLL('act.add_new_record') .
					'	</a>' .
					'</div>';

				$filterContent .= $LANG->getLL('function1.filter');
				$filterContent .= '&nbsp;<select name="itemsFilter" onchange="this.form.submit()">';
				$filterContent .= '<option value="unset">--- '.$LANG->getLL('function1.filter.unset').' ---</option>';
				foreach ($categories as $key => $val) {
					$apx = "";
					if ($key == $itemsFilter)
						$apx = " selected";
					$filterContent .= '<option'.$apx.' value="'.$key.'">' . $val . '</option>';
				}
				$filterContent .= "</select>";

				$table = Array();
				$table[0][0] = $LANG->getLL('sys.list_empty');

				$layout = $this->doc->tableLayout;
				if ($itemsFilter > 0) {
					$layout['defRow']['1'] = $layout['defRowOdd']['1'] = $layout['defRowEven']['1'] = Array('<td class="td-textLine" style="width: 100%">', '</td>');
					$layout['defRow']['2'] = $layout['defRowOdd']['2'] = $layout['defRowEven']['2'] = Array('<td class="td-icons td-edge">', '</td>');
				} else {
					$layout['defRow']['1'] = $layout['defRowOdd']['1'] = $layout['defRowEven']['1'] = Array('<td class="td-textLine" style="width: 65%">', '</td>');
					$layout['defRow']['2'] = $layout['defRowOdd']['2'] = $layout['defRowEven']['2'] = Array('<td class="td-textLine" style="width: 35%">', '</td>');
					$layout['defRow']['3'] = $layout['defRowOdd']['3'] = $layout['defRowEven']['3'] = Array('<td class="td-icons">', '</td>');
					$layout['defRow']['4'] = $layout['defRowOdd']['4'] = $layout['defRowEven']['4'] = Array('<td class="td-icons td-edge">', '</td>');
				}
				$layout['defRow']['0'] = $layout['defRowOdd']['0'] = $layout['defRowEven']['0'] = Array('<td class="td-image">', '</td>');

				$query = $TYPO3_DB->SELECTquery(
					'*',
					'tt_products',
					'tt_products.pid=' . $extConf['PIDstoreRoot'] . $whereApx . t3lib_BEfunc::deleteClause("tt_products"),
					'',
					'category, title'
				);
				$res = $TYPO3_DB->sql_query($query);
				$counter = 0;
				while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
					if ($extConf['iconsInsteadImages'])
						$table[$counter][0] = '<div class="hiddenImage">' . $this->pictureGenerator($row['image'], $extConf['imagesWidth'], 600) . '</div><span class="t3-icon t3-icon-tcarecords t3-icon-tcarecords-tt_products t3-icon-tt_products-default">&nbsp;</span>';
					else
						$table[$counter][0] = $this->pictureGenerator($row['image'], $extConf['imagesWidth'], 600);
					$table[$counter][1] = $row['title'];
					if ($itemsFilter == 0) {
						$table[$counter][2] = $categories[$row['category']];
					}
					$table[$counter][3] = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tt_products]['.$row['uid'].']=edit',$GLOBALS['BACK_PATH'])).'">'.'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/edit2.gif').' title="' . $LANG->getLL('act.edit') . '" border="0" alt="" /></a>';
					$table[$counter][4] = '<a href="#" onclick="removeItem(\''.$LANG->getLL('act.delete.confirm').'\', '.$row['uid'].', \''.$row['title'].'\');">'.'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/garbage.gif').' title="' . $LANG->getLL('act.delete') . '" border="0" alt="" /></a>';
					$counter++;
				}
				$TYPO3_DB->sql_free_result($res);

				$tabContent = '<div class="tab-content">' . $filterContent . '</div>';
				$tabContent .= $this->doc->table($table, $layout);

				$tabContent .= '<div class="tab-content">
						<div class="pare" style="float: right;">
							<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tt_products]['.$extConf['PIDstoreRoot'].']=new&defVals[tt_products][category]=' . $itemsFilter, $GLOBALS['BACK_PATH'])).'">
								<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/new_el.gif').' title="' . $LANG->getLL('act.add_new_record') . '" border="0" alt="" />
								' . $LANG->getLL('act.add_new_record') . '
							</a>
						</div>
						<div class="clearer"><!-- --></div>
					</div>';

				$content .= $this->doc->getDynTabMenu(Array(
					Array(
						'label'   => $LANG->getLL('function1.items'),
						'content' => $tabContent
					)
				), 'tx_shopmanager_f1_t1');

				$content .= '</td><td>';

					// Categories list

				$table = Array();
				$table[0][0] = $LANG->getLL('sys.list_empty');

				$layout = $this->doc->tableLayout;
				$layout['defRow']['1'] = $layout['defRowOdd']['1'] = $layout['defRowEven']['1'] = Array('<td valign="middle" class="td-icons td-edge">', '</td>');

				$query = $TYPO3_DB->SELECTquery(
					'*',
					'tt_products_cat',
					'tt_products_cat.pid=' . $extConf['PIDcategories'] . t3lib_BEfunc::deleteClause("tt_products_cat"),
					'',
					'title'
				);
				$res = $TYPO3_DB->sql_query($query);
				$counter = 0;
				while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
					$table[$counter][0] = $row['title'];
					$table[$counter][1] = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tt_products_cat]['.$row['uid'].']=edit',$GLOBALS['BACK_PATH'])).'">'.'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/edit2.gif').' title="' . $LANG->getLL('act.edit') . '" border="0" alt="" /></a>';
					$counter++;
				};
				$TYPO3_DB->sql_free_result($res);

				$content .= $this->doc->getDynTabMenu(Array(
					Array(
						'label' => $LANG->getLL('function1.categories'),
						'content' => $this->doc->table($table, $layout)
					)
				), 'tx_shopmanager_f1_t2');

				$content .= '<div class="tab-content">
						<div class="pare">
							<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tt_products_cat]['.$extConf['PIDcategories'].']=new',$GLOBALS['BACK_PATH'])).'">
								<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/new_el.gif').' title="' . $LANG->getLL('act.add_new_record') . '" border="0" alt="" />
								' . $LANG->getLL('act.add_new_record') . '
							</a>
						</div>
					</div>';

				$content .= '</td></tr></table>';

				$content .= '<input type="hidden" name="deleteItem" id="deleteItem" value="0" />';

			break;
			case 2:

				$content .= '<table cellpadding="0" cellspacing="0" width="100%"><tr valign="top"><td width="50%" id="tx_shopmanager_f2_id1" class="td-edge">';

				$layout = $this->doc->tableLayout;
				$layout['defRow']['2'][0] = $layout['defRowOdd']['2'][0] = $layout['defRowEven']['2'][0] = '<td class="td-price">';
				$layout['defRow']['3'][0] = $layout['defRowOdd']['3'][0] = $layout['defRowEven']['3'][0] = '<td class="td-icons td-edge">';

				$excludeStatuses = explode(",", $extConf['excludeStatuses']);

				$query = $TYPO3_DB->SELECTquery(
					'*',
					'sys_products_orders',
					'1=1' . t3lib_BEfunc::deleteClause("sys_products_orders"),
					'',
					'tstamp DESC',
					$extConf['limitItems']
				);
				$res = $TYPO3_DB->sql_query($query);
				while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
					if ($row['status'] > 0) {
						$list_orders[$row['status']][$row['uid']]['feusers_uid'] = $row['feusers_uid'];
						$list_orders[$row['status']][$row['uid']]['name'] = $row['name'];
						$list_orders[$row['status']][$row['uid']]['amount'] = $row['amount'];
					}
				}
				$TYPO3_DB->sql_free_result($res);

				ksort($list_orders);

				foreach ($list_orders as $key_l1 => $value_l1) {

					$table = Array();
					$counter = 0;
					foreach ($value_l1 as $key_l2 => $value_l2) {

						$table[$counter][0] = $key_l2;
						$table[$counter][1] = $value_l2['name'];
						$table[$counter][2] = $value_l2['amount'];
						$table[$counter][3] = '<a href="#" onclick="view_order(' . $key_l2 . ');">'.'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/edit2.gif').' title="' . $LANG->getLL('act.edit') . '" border="0" alt="" /></a>';

						$counter++;

					}

					$hideStatus = false;
					foreach ($excludeStatuses as $excludeStatus) {
						if ($excludeStatus == $key_l1)
							$hideStatus = true;
					}
					if (!$hideStatus) {
						$content .= $this->doc->getDynTabMenu(Array(
							Array(
								'label' => $LANG->getLL('function2.status') . ' ' . $key_l1 . ': ' . $LANG->getLL('function2.status.' . $key_l1),
								'content' => $this->doc->table($table, $layout)
							)
						), 'tx_shopmanager_f2_t' . $key_l1);
						$content .= $this->doc->spacer(15);
					}

				}

				$content .= '</td><td>';

				$jumpTo = '<table><tr valign="middle"><td>' . $LANG->getLL('function2.jumpToText') . '</td>';
				$jumpTo .= '<td><input type="text" size="5" name="jumpTo" /></td>';
				$jumpTo .= '<td><input type="submit" value="' . $LANG->getLL('function2.jump') . '" /></td></tr></table>';
				$content .= $this->doc->section($LANG->getLL('function2.jumpTo'), $jumpTo) . $this->doc->spacer(20);

				if ($detailed > 0) {

						// Get order info
					$query = $TYPO3_DB->SELECTquery(
						'*',
						'sys_products_orders',
						'uid=' . $detailed
					);
					$res = $TYPO3_DB->sql_query($query);
					$row = $TYPO3_DB->sql_fetch_assoc($res);
					$TYPO3_DB->sql_free_result($res);

						// Order details
					$details = Array(
						Array(
							$LANG->getLL('function2.created'),
							date($timeMask, $row['crdate'])
						),
						Array(
							$LANG->getLL('function2.updated'),
							date($timeMask, $row['tstamp'])
						),
						Array(
							$LANG->getLL('function2.customer'),
							$row['name']
						),
						Array(
							$LANG->getLL('function2.address'),
							$row['address']
						),
						Array(
							$LANG->getLL('function2.zip'),
							$row['zip']
						),
						Array(
							$LANG->getLL('function2.city'),
							$row['city']
						),
						Array(
							$LANG->getLL('function2.country'),
							$row['country']
						),
						Array(
							$LANG->getLL('function2.phone'),
							$row['telephone']
						),
						Array(
							$LANG->getLL('function2.email'),
							'<a href="mailto:' . $row['email'] . '">' . $row['email'] . '</a>'
						),
						Array(
							$LANG->getLL('function2.sum'),
							$row['amount']
						),
						Array(
							$LANG->getLL('function2.ip'),
							$row['client_ip']
						),
					);

						// Order items
					$orderItems = unserialize($row['orderData']);
					$orderItemsContent = Array();
					$dlc=0;
					foreach($orderItems['itemArray'] as $dl1) {
						foreach($dl1 as $dl2) {
							foreach($dl2 as $dl3) {
								$orderItemsContent[$dlc]['uid'] = $dl3['rec']['uid'];
								$orderItemsContent[$dlc]['title'] = $dl3['rec']['title'];
								$orderItemsContent[$dlc]['price'] = $dl3['rec']['price'];
								$orderItemsContent[$dlc]['category'] = $dl3['rec']['category'];
								$orderItemsContent[$dlc]['qty'] = $dl3['count'];
								$dlc++;
							}
						}
					}
					$orderItems = Array();
					$counter = 0;
					$sum = 0;
					foreach($orderItemsContent as $item) {
						$orderItems[$counter] = Array(
							$categories[$item['category']],
							$item['title'],
							$item['qty'] . ' * ' . $item['price'],
							$item['qty']*$item['price']
						);
						$sum += $item['qty']*$item['price'];
						$counter++;
					}
					$orderItemsLayout = $this->doc->tableLayout;
					$orderItemsLayout['defRow']['3'] = $orderItemsLayout['defRowOdd']['3'] = $orderItemsLayout['defRowEven']['3'] = Array('<td class="td-10p">', '</td>');

						// Order status changes list
					$statusChange = $this->doc->spacer(9);
					$statusChange .= '<input type="hidden" id="tx_shopmanager_f2_statusCurrent" value="' . $row['status'] . '" />';
					$statusChange .= '<div class="tx_shopmanager_spaced_block" style="padding: 5px 0 8px 0;">';
					$statusChange .= '<select onchange="statusChanged()" id="tx_shopmanager_f2_statusSelect">';
	 				foreach (array_diff($possibleStatuses, $excludeStatuses) as $allowedStatus) {
	 					if ($allowedStatus == $row['status'])
							$statusChange .= '<option selected value="'.$allowedStatus.'">---> ('.$allowedStatus.') '.$LANG->getLL('function2.status.' . $allowedStatus).'</option>';
	 					else
							$statusChange .= '<option value="'.$allowedStatus.'">('.$allowedStatus.') '.$LANG->getLL('function2.status.' . $allowedStatus).'</option>';
	 				}
					$statusChange .= '</select>';
					$statusChange .= '</div>';
					$statusChange .= '<div class="tx_shopmanager_spaced_block" id="tx_shopmanager_f2_status">';
					$statusChange .= '<table class="verticalCenter"><tr><td>' . $LANG->getLL('comment') . '</td>';
					$statusChange .= '<td>&nbsp;<input type="text" id="tx_shopmanager_f2_statusComment" />&nbsp;</td>';
					$statusChange .= '<td><input type="button" onclick="statusChange();" value="' . $LANG->getLL('change') . '" /></td></tr></table>';
					$statusChange .= '</div>';
					$status = Array();
					$counter = 0;
					foreach (array_reverse(unserialize($row['status_log'])) as $key => $value) {
						$status[$counter] = Array(
							date($timeMask, $value['time']),
							$value['status'],
							($value['comment']) ? '<b>' . $value['info'] . '</b>:<br />' . $value['comment'] : $value['info']
						);
						$counter++;
					}
					$statusLayout = $this->doc->tableLayout;
					$statusLayout['defRow']['0'] = $statusLayout['defRowOdd']['0'] = $statusLayout['defRowEven']['0'] = Array('<td class="td-20p">', '</td>');
					$statusLayout['defRow']['1'] = $statusLayout['defRowOdd']['1'] = $statusLayout['defRowEven']['1'] = Array('<td class="td-10p">', '</td>');

					$content .= $this->doc->getDynTabMenu(Array(
						Array(
							'label' => $LANG->getLL('function2.detailed') . $detailed,
							'content' => $this->doc->table($details)
						),
						Array(
							'label' => $LANG->getLL('function2.orderItems'),
							'content' => $this->doc->table($orderItems, $orderItemsLayout)
						),
						Array(
							'label' => $LANG->getLL('function2.status'),
							'content' => $statusChange . $this->doc->table($status, $statusLayout)
						)
					), 'tx_shopmanager_f2_detailed');

				}

				$content .= '</td></tr></table>';

				$content .= '<input type="hidden" name="detailed" id="detailed" value="' . $detailed . '" />';
				$content .= '<input type="hidden" name="opt1" id="opt1" value="' . $opt1 . '" />';
				$content .= '<input type="hidden" name="opt2" id="opt2" value="' . $opt2 . '" />';
				$content .= '<input type="hidden" name="opt3" id="opt3" value="' . $opt3 . '" />';

			break;

		}

		$this->content .= $content;

	}



	/**
	 * Generate image with <img>-tag wrapper from filename
	 *
	 * @return	<img>-element
	 */
	function pictureGenerator($file, $size_w, $size_h) {

		$this->stdGraphic = t3lib_div::makeInstance('t3lib_stdGraphic');
		$this->stdGraphic->init();
		$this->stdGraphic->absPrefix = PATH_site;

		$fileName = explode(',', $file);

		$imageInfo = $this->stdGraphic->imageMagickConvert(
			PATH_site . 'uploads/pics/' . $fileName[0],
			'web',
			$size_w . 'm',
			$size_h . 'm',
			'',
			'',
			array(),
			1
		);
		$imageInfo[3] = '/' . str_replace(PATH_site, '', $imageInfo[3]);
		return $this->stdGraphic->imgTag($imageInfo);

	}



	/**
	 * Get the $extConf modified by using the User TS
	 *
	 * @return	modified $extConf array
	 */
	function getMergedConfig() {

		global $TYPO3_CONF_VARS, $GLOBALS;

		$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['shop_manager']);

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.PIDstoreRoot'))
			$extConf['PIDstoreRoot'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.PIDcategories'))
			$extConf['PIDcategories'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.sys_products_orders'))
			$extConf['sys_products_orders'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.swapFunctions'))
			$extConf['swapFunctions'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.limitItems'))
			$extConf['limitItems'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.excludeStatuses'))
			$extConf['excludeStatuses'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.ttproductsTemplate'))
			$extConf['ttproductsTemplate'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.PIDtracking'))
			$extConf['PIDtracking'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.domain'))
			$extConf['domain'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.orderEmail_fromName'))
			$extConf['orderEmail_fromName'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.orderEmail_from'))
			$extConf['orderEmail_from'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.iconsInsteadImages'))
			$extConf['iconsInsteadImages'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.imagesWidth'))
			$extConf['imagesWidth'] = $value;

		if ($value = $GLOBALS['BE_USER']->getTSConfigVal('mod.user_shopmanager.disableFixedPosition'))
			$extConf['disableFixedPosition'] = $value;

		return $extConf;

	}



	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons() {

		global $LANG;

		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'save' => ''
		);

			// CSH
//		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// SAVE button
		$buttons['save'] = '<input type="image" class="c-inputButton" name="submit" value="Update"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/savedok.gif', '') . ' title="' . $LANG->getLL('act.apply') . '" />';

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;

	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/shop_manager/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/shop_manager/mod1/index.php']);
}

	// Make instance:
$SOBE = t3lib_div::makeInstance('tx_shopmanager_module1');
$SOBE->init();

	// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>