<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_LEAD_IMPORT'] = CrmCheckPath('PATH_TO_LEAD_IMPORT', $arParams['PATH_TO_LEAD_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_LEAD_DEDUPE'] = CrmCheckPath('PATH_TO_LEAD_DEDUPE', $arParams['PATH_TO_LEAD_DEDUPE'], $APPLICATION->GetCurPage());

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_lead_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if($arParams['ELEMENT_ID'] > 0)
{
	$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $arParams['ELEMENT_ID'],  'CHECK_PERMISSIONS' => 'N'), false, false, array('ID', 'STATUS_ID', 'IS_RETURN_CUSTOMER'));
	$arFields = $dbRes->Fetch();
}
else
{
	$arFields = array();
}

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;

	$bDedupe = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE')
		&& !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'DELETE');
}
else
{
	$bExport = false;
	$bImport = false;
	$bDedupe = false;

	$bRead   = CCrmLead::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmLead::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmLead::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmLead::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] == 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$pageLink = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => 0
			)
		);
		$pageLink = new \Bitrix\Main\Web\Uri($pageLink);
		$jsCreateMenuItems = \Bitrix\Main\Web\Json::encode(array(
			array(
				'text' => GetMessage('LEAD_ADD_TITLE_PLAIN'),
				'href' => $pageLink->getLocator(),
			),
			array(
				'text' => GetMessage('LEAD_ADD_TITLE_RETURN_CUSTOMER'),
				'href' =>  $pageLink->addParams(array('is_return_customer' => 'Y'))->getLocator(),
			),
		));
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_ADD'),
			'TITLE' => GetMessage('LEAD_ADD_TITLE'),
			//'TYPE' => 'crm-context-menu',
			//'ONCLICK' => "BX.PopupMenu.show('toolbar_lead_list_create_lead', this, {$jsCreateMenuItems});",
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
				array(
					'lead_id' => 0
				)
			),
			'HIGHLIGHT' => true
		);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_IMPORT'),
			'TITLE' => GetMessage('LEAD_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}

	if ($bExport)
	{
		$filterParams = Bitrix\Crm\Widget\Data\LeadDataSource::extractDetailsPageUrlParams($_REQUEST);
		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('LEAD_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('LEAD_EXPORT_CSV'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array_merge(array('type' => 'csv', 'ncc' => '1'), $filterParams)
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('LEAD_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('LEAD_EXPORT_EXCEL'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array_merge(array('type' => 'excel', 'ncc' => '1'), $filterParams)
			),
			'ICON' => 'btn-export'
		);
	}

	if ($bDedupe)
	{
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getDuplicateControlRestriction();
		if($restriction->hasPermission())
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('LEAD_DEDUPE'),
				'TITLE' => GetMessage('LEAD_DEDUPE_TITLE'),
				'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_DEDUPE'], array())
			);
		}
		else
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('LEAD_DEDUPE'),
				'TITLE' => GetMessage('LEAD_DEDUPE_TITLE'),
				'ONCLICK' => $restriction->preparePopupScript(),
				'MENU_ICON' => 'grid-lock'
			);
		}
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& $bWrite
)
{
	$plannerButton = \Bitrix\Crm\Activity\Planner::getToolbarButton($arParams['ELEMENT_ID'], CCrmOwnerType::Lead);
	if($plannerButton)
	{
		CJSCore::Init(array('crm_activity_planner'));
		$arResult['BUTTONS'][] = $plannerButton;
	}
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& CCrmLead::CheckConvertPermission($arParams['ELEMENT_ID'], CCrmOwnerType::Undefined, $CrmPerms))
{
	$schemeID = \Bitrix\Crm\Conversion\LeadConversionManager::create(array(
		'IS_RC' => (isset($arFields['IS_RETURN_CUSTOMER']) && $arFields['IS_RETURN_CUSTOMER'] == 'Y')
	))->getCurrentSchemeID();

	$arResult['BUTTONS'][] = array(
		'TYPE' => 'toolbar-conv-scheme',
		'PARAMS' => array(
			'NAME' => 'lead_converter',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
			'ENTITY_ID' => $arParams['ELEMENT_ID'],
			'SCHEME_ID' => $schemeID,
			'SCHEME_NAME' => \Bitrix\Crm\Conversion\LeadConversionScheme::resolveName($schemeID),
			'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\LeadConversionScheme::getDescription($schemeID),
			'IS_PERMITTED' => true,
			'HINT' => array(
				'title' => GetMessage('LEAD_CREATE_ON_BASIS_HINT_TITLE'),
				'content' => GetMessage('LEAD_CREATE_ON_BASIS_HINT_CONTENT'),
				'disabling' => GetMessage('LEAD_CREATE_ON_BASIS_DISABLE_HINT')
			)
		),
		'CODE' => 'convert',
		'TEXT' => GetMessage('LEAD_CREATE_ON_BASIS'),
		'TITLE' => GetMessage('LEAD_CREATE_ON_BASIS_TITLE'),
		'ICON' => 'btn-convert'
	);
}

if (($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0)
{
	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Lead,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'lead_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#bitrix/components/bitrix/crm.lead.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_LEAD_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_LEAD_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_LEAD_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_LEAD_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if (($arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert') && $bWrite
	&& !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_EDIT'),
		'TITLE' => GetMessage('LEAD_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'convert') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_SHOW'),
		'TITLE' => GetMessage('LEAD_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && ($arParams['TYPE'] == 'list' ||
	($arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3 || ($arFields['STATUS_ID'] == 'CONVERTED' && $qty >= 2))
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if ($bAdd && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert')
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_COPY'),
		'TITLE' => GetMessage('LEAD_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert') && $bDelete
	&& !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_DELETE'),
		'TITLE' => GetMessage('LEAD_DELETE_TITLE'),
		'LINK' => "javascript:lead_delete('".GetMessage('LEAD_DELETE_DLG_TITLE')."', '".GetMessage('LEAD_DELETE_DLG_MESSAGE')."', '".GetMessage('LEAD_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
				array(
					'lead_id' => $arParams['ELEMENT_ID']
				)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd && $arParams['TYPE'] != 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_ADD'),
		'TITLE' => GetMessage('LEAD_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => 0
			)
		),
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>
