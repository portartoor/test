<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!is_array($arResult['CONTACT']) || !($USERS_CNT = count($arResult['CONTACT']))):
	echo(GetMessage('ERROR_CONTACT_IS_EMPTY'));
else:
?><meta http-equiv="Content-type" content="text/html;charset=<?echo LANG_CHARSET?>" />
<table border="1">
<thead>
	<tr><?
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
		$arHeaders[$arHead['id']] = $arHead;

	// Display headers
	foreach($arResult['SELECTED_HEADERS'] as $headerID):
		$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
		if($arHead):
			?><th><?=$arHead['name']?></th><?
		endif;
	endforeach;
	?></tr>
</thead>
	<tbody><?


	foreach ($arResult['CONTACT'] as $i => &$arContact):
		?><tr><?
		foreach($arResult['SELECTED_HEADERS'] as $headerID):
			$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
			if(!$arHead)
				continue;

			$headerID = $arHead['id'];
			$result = '';

			switch($headerID):
				case 'TYPE_ID':
					$result = $arResult['TYPE_LIST'][$arContact['TYPE_ID']];
					break ;
				case 'SOURCE_ID':
					$result = $arResult['SOURCE_LIST'][$arContact['SOURCE_ID']];
					break ;
				case 'COMPANY_ID':
					$result = $arResult['CONTACT'][$i]['COMPANY_TITLE'];
					break;
				case 'EXPORT':
					$result = $arResult['EXPORT_LIST'][$arContact['EXPORT']];
					break;
				default:
					if(isset($arResult['CONTACT_UF'][$i]) && isset($arResult['CONTACT_UF'][$i][$headerID])):
						$result = $arResult['CONTACT_UF'][$i][$headerID];
					elseif(is_array($arContact[$headerID])):
						$result = implode(', ', $arContact[$headerID]);
					else:
						$result = strval($arContact[$headerID]);
					endif;
			endswitch;
			?><td><?=$result?></td><?
		endforeach;
	?></tr><?
	endforeach;
?></tbody>
</table><?
endif;