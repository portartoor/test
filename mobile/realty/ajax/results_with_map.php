<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
$arRequest = $_POST;
$arSort = Array();
//Очищаем запрос от лишних полей, иначе при поиске будут не правильные результаты
foreach ($arRequest as $key => $requestField)
{
	if (in_array($key, array("update_labels", "delete_not_in_kladr_values", "web_form_submit")))
	{
		unset($arRequest[$key]);
	}
	else
	{
		if (empty($requestField))
		{
			unset($arRequest[$key]);
		}	
	}
}
//Если указан ID ищем по нему
if(isset($arRequest["UF_ID"]))
{
	$dbResult = HlBlockElement::GetList(2, array(), array("UF_ID" => $arRequest["UF_ID"]), array(), 1);
}
//Иначе задаем фильтр для поиска
else
{
	$dbResult = HlBlockElement::GetList(3, array(), array(), array(), 100);
	while($arElement = $dbResult->Fetch()) {
		$obj_type_arr[$arElement["UF_OBJ_TYPE_ID"]] = $arElement["UF_OBJ_TYPE_NAME"];
	}
	foreach ($arRequest as $key => $requestField) 
	{
		if ($key == "sort_field")
		{
			foreach ($requestField as $sortKey => $sortType)
			{
				$arSort[$sortType] = $arRequest["sort_direction"][$sortKey];
			}
			unset($arRequest["sort_field"]);
			unset($arRequest["sort_direction"]);
		}
		//Изменяем названия полей возвращаемых компонентом КЛАДР
		else if ($key == "location")
		{
			$arRequest["UF_CITY_ID"] = "%".$requestField."%";	
			unset($arRequest[$key]);
		}
		else if ($key == "street")
		{
			$arRequest["UF_ADDR_STREET"] = "%".$requestField."%";	
			unset($arRequest[$key]);
		}
		//Создаем range для поиска в базе
		else if ((strpos($key, "_FROM") || strpos($key, "_TO")))
		{
			$fieldName = str_replace(array("_FROM", "_TO"), "", $key);
			if ($fieldName == "UF_SQUARE") 
				$fieldName = "UF_TOTAL_SQUARE";
			$fieldName = (strpos($key, "_FROM")) ? (">=" . $fieldName) : ("<=" . $fieldName);
			$arRequest[$fieldName] = $requestField;
			unset($arRequest[$key]);
		}
	}
	//Добавленные в базу заявки
	$arRequest[">=UF_INNER_STATUS"] = 0;
	$count=0;
	$dbResult = HlBlockElement::GetList(2, array("ID","UF_ID","UF_PHOTO_PREVIEW","UF_ADDR_STREET","UF_CITY_REGION","UF_CITY_ID","UF_ADDR_STREET","UF_PRICE","UF_OBJ_TYPE","UF_ROOMS","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE","UF_KITCHEN_SQUARE","UF_GARAGE_SQUARE","UF_CELLAR_SQUARE","UF_LOT_SQUARE","UF_REKLAMA","UF_LATITUDE","UF_LONGITUDE"), $arRequest, $arSort, 10);
	$whole_count = $dbResult->SelectedRowsCount();
	if(isset($_GET["PAGEN_2"])&&$whole_count <= 10*(intval($_GET["PAGEN_2"])-1))die();
}
	?>
	<?
	while($arElement = $dbResult->Fetch()) {
		$count++;
		?>
		<div class="obj_item">
			<div class="obj_image">
			<?
			//Выводим картинку
			if(!empty($arElement["UF_PHOTO_PREVIEW"]))
			{
				$file = CFile::ResizeImageGet($arElement["UF_PHOTO_PREVIEW"], array('width'=>399, 'height'=>297), 
					BX_RESIZE_IMAGE_EXACT, true);
				?>
				<img src="<?=$file["src"]?>"/>
				<?
			}
			else
			{
				?>
				<img src="/bitrix/templates/realty/images/soon.jpg"/>
				<?
			}?>
			</div>
			<div class="obj_text_desc">
				<span class="obj_street"><a href="/realty/view/?REQUEST_ID=<?=$arElement["ID"]?>"><?=$arElement["UF_ADDR_STREET"]?></a></span>
				<span class="obj_city"><?=$arElement["UF_CITY_ID"]?>, <?=$arElement["UF_CITY_REGION"]?></span>
				<div class="obj_desc">
					<?
					//Формируем и выводим описание недвижимости
					$dbResultObj = HlBlockElement::GetList(3, array("UF_OBJ_TYPE_NAME", "UF_OBJ_TYPE_CLASS"), array("UF_OBJ_TYPE_ID" => $arElement["UF_OBJ_TYPE"]), array(), 1);
					$arResultObj = $dbResultObj->Fetch();
					$arElement["UF_OBJ_TYPE"] = $arResultObj;
					$arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"] = mb_convert_case($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"], MB_CASE_LOWER, "UTF-8");
					if ($arElement["UF_TOTAL_SQUARE"] == "" || $arElement["UF_TOTAL_SQUARE"] == 0) $arElement["UF_TOTAL_SQUARE"] = "-";
					if ($arElement["UF_LIVING_SQUARE"] == "" || $arElement["UF_LIVING_SQUARE"] == 0) $arElement["UF_LIVING_SQUARE"] = "-";
					if ($arElement["UF_KITCHEN_SQUARE"] == "" || $arElement["UF_KITCHEN_SQUARE"] == 0) $arElement["UF_KITCHEN_SQUARE"] = "-";
					$description = "";
					switch($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_CLASS"])
					{
						case "Квартира":
						case "Комната":				
							$description = $arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"]." ".$arElement["UF_ROOMS"].
								"-комнатная, ".$arElement["UF_ETAGE"]."/".$arElement["UF_ETAGE_COUNT"]." этаж, ".
								$arElement["UF_TOTAL_SQUARE"]."/".$arElement["UF_LIVING_SQUARE"]."/".
								$arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							break;					
						case "Здание":
						case "Эллинг":
							$description = $arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"]." ".$arElement["UF_ROOMS"].
								"-комнатная, ".$arElement["UF_TOTAL_SQUARE"]."/".$arElement["UF_LIVING_SQUARE"]."/".
								$arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							break;
						case "Помещение":
						case "Земельный участок":
							$description = $arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"]." ".$arElement["UF_TOTAL_SQUARE"]."/".
								$arElement["UF_LIVING_SQUARE"]."/".$arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							break;
						default:
							$description = $arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"]." ".$arElement["UF_TOTAL_SQUARE"]."/".
							$arElement["UF_LIVING_SQUARE"]."/".$arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							break;
					}
					echo $description;
					?>
				</div>
				<span class="obj_price"><?=number_format($arElement["UF_PRICE"], 0, ",", " ")?> руб.</span>
			</div>
		</div>
		<?
	}
	if ($count==0)
		echo "<div class=\"not_found\">По вашему запросу ничего не найдено</div>";
	else
	{
		?>
	<script>
	</script>
	<?
	}
?>