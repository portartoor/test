<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!is_array($arResult['LEAD']) || !($USERS_CNT = count($arResult['LEAD'])))
{
	echo(GetMessage('ERROR_LEAD_IS_EMPTY'));
}
else
{
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arHeaders[$arHead['id']] = $arHead;
	}

	$showProductRows = false;
	// Display headers
	foreach($arResult['SELECTED_HEADERS'] as $headerID)
	{
		$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
		if(!$arHead)
		{
			continue;
		}

		// Special logic for PRODUCT_ROWS headers: expand product in 3 columns
		if($headerID === 'PRODUCT_ID')
		{
			$showProductRows = true;
			echo '"', GetMessage('CRM_COLUMN_PRODUCT_NAME'),'";';
			echo '"', GetMessage('CRM_COLUMN_PRODUCT_PRICE'),'";';
			echo '"', GetMessage('CRM_COLUMN_PRODUCT_QUANTITY') ,'";';
		}
		else
		{
			echo '"', str_replace('"', '""', $arHead['name']),'";';
		}
	}
	echo "\n";

	// Display data
	foreach ($arResult['LEAD'] as $i => &$arLead)
	{
		// Serialize each product row as deal with single product
		$productRows = $showProductRows && isset($arLead['PRODUCT_ROWS']) ? $arLead['PRODUCT_ROWS'] : array();
		if(count($productRows) == 0)
		{
			// Deal has no product rows (or they are not displayed) - we have to create dummy for next loop by product rows only
			$productRows[] = array();
		}

		$leadData = array();
		foreach($productRows as $productRow)
		{
			foreach($arResult['SELECTED_HEADERS'] as $headerID)
			{
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if(!$arHead)
				{
					continue;
				}

				$headerID = $arHead['id'];
				if($headerID === 'PRODUCT_ID')
				{
					// Special logic for PRODUCT_ROWS: expand product in 3 columns
					echo '"', (isset($productRow['PRODUCT_ID']) && intval($productRow['PRODUCT_ID']) > 0 ? str_replace('"', '""', CCrmProductRow::GetProductName($productRow)) : ''), '";';
					echo '"', CCrmProductRow::GetPrice($productRow, ''), '";';
					echo '"', CCrmProductRow::GetQuantity($productRow, ''), '";';

					continue;
				}
				elseif($headerID === 'OPPORTUNITY')
				{
					// Special logic for OPPORTUNITY: replace it by product row sum if it specified
					if(isset($productRow['PRODUCT_ID']) && intval($productRow['PRODUCT_ID']) > 0)
					{
						echo '"', round(CCrmProductRow::GetPrice($productRow) * CCrmProductRow::GetQuantity($productRow), 2), '";';
					}
					else
					{
						echo '"', isset($arLead['OPPORTUNITY']) ? strval($arLead['OPPORTUNITY']) : '', '";';
					}

					continue;
				}

				if(!isset($leadData[$headerID]))
				{
					switch($headerID)
					{
						case 'STATUS_ID':
						{
							$statusID = !empty($arLead['STATUS_ID']) ? $arLead['STATUS_ID'] : '';
							$leadData['STATUS_ID'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;
							break;
						}
						case 'SOURCE_ID':
						{
							$sourceID = !empty($arLead['SOURCE_ID']) ? $arLead['SOURCE_ID'] : '';
							$leadData['SOURCE_ID'] = isset($arResult['SOURCE_LIST'][$sourceID]) ? $arResult['SOURCE_LIST'][$sourceID] : $sourceID;
							break;
						}
						case 'CURRENCY_ID':
						{
							$leadData['CURRENCY_ID'] = CCrmCurrency::GetCurrencyName($arLead['CURRENCY_ID']);
							break;
						}
						default:
						{
							if(isset($arResult['LEAD_UF'][$i]) && isset($arResult['LEAD_UF'][$i][$headerID]))
							{
								$leadData[$headerID] = $arResult['LEAD_UF'][$i][$headerID];
							}
							elseif (is_array($arLead[$headerID]))
							{
								$leadData[$headerID] = implode(', ', $arLead[$headerID]);
							}
							else
							{
								$leadData[$headerID] = strval($arLead[$headerID]);
							}
						}
					}
				}

				if(isset($leadData[$headerID]))
				{
					echo '"', str_replace('"', '""', htmlspecialcharsback($leadData[$headerID])), '";';
				}
			}
			echo "\n";
		}
	}
}
?>