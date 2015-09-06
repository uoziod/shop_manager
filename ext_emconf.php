<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "shop_manager".
 *
 * Auto generated 07-07-2015 14:19
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Shop System Manager Module',
	'description' => 'Shop System (tt_products) management system. This is backend module with 2 sections. With "Catalog Items" you can manage shop categories and items pretty simple. "Categories Manager" is a useful order status manager with customers e-mail notification support.',
	'category' => 'module',
	'version' => '1.0.7',
	'state' => 'stable',
	'uploadfolder' => true,
	'createDirs' => '',
	'clearcacheonload' => true,
	'author' => 'Semyon Vyskubov',
	'author_email' => 'sv@rv7.ru',
	'author_company' => '',
	'constraints' => 
	array (
		'depends' => 
		array (
			'php' => '5.2.0-5.5.99',
			'typo3' => '4.1.0-6.2.99',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

