<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.toolbar',
	'',
	array(
		'BUTTONS'=>array(

			array(
				'TEXT'=>GetMessage('CRM_FIELDS_TOOLBAR_TYPES'),
				'TITLE'=>GetMessage('CRM_FIELDS_TOOLBAR_TYPES_TITLE'),
				'LINK'=>$arResult['ENTITY_LIST_URL'],
				'ICON'=>'btn-view-elements',
			),
			array(
				'SEPARATOR'=>'Y',
			),
			array(
				'TEXT'=>GetMessage('CRM_FIELDS_TOOLBAR_ADD'),
				'TITLE'=>GetMessage('CRM_FIELDS_TOOLBAR_ADD_TITLE'),
				'LINK'=>$arResult['FIELD_EDIT_URL'],
				'ICON'=>'btn-add-field',
			),

		),

	),
	$component, array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(
		'GRID_ID'=>$arResult['GRID_ID'],
		'HEADERS'=>array(
			array('id'=>'SORT', 'name'=>GetMessage('CRM_FIELDS_LIST_SORT'), 'default'=>true, 'editable'=>array('size'=>3, 'maxlength'=>11), 'align'=>'right'),
			array('id'=>'LIST_COLUMN_LABEL', 'name'=>GetMessage('CRM_FIELDS_LIST_NAME'), 'default'=>true, 'editable'=>false),
			array('id'=>'TYPE', 'name'=>GetMessage('CRM_FIELDS_LIST_TYPE'), 'default'=>true),
			array('id'=>'MANDATORY', 'name'=>GetMessage('CRM_FIELDS_LIST_IS_REQUIRED'), 'default'=>true, 'type'=>'checkbox', 'editable'=>true),
			array('id'=>'MULTIPLE', 'name'=>GetMessage('CRM_FIELDS_LIST_MULTIPLE'), 'default'=>true, 'type'=>'checkbox', 'editable'=>false),
		),
		'ROWS'=>$arResult['ROWS'],
		'ACTIONS'=>array('delete'=>true),
		'ACTION_ALL_ROWS'=>true,
		'AJAX_MODE'=>'Y',
		'AJAX_OPTION_SHADOW'=>'N',
		'AJAX_OPTION_JUMP' => 'N',
	),
	$component, array('HIDE_ICONS' => 'Y')
);?>