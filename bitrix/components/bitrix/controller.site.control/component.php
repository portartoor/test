<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*************************************************************************
	Processing of received parameters
*************************************************************************/

$arParams["ORIG_SITE_URL"] = array();
if(!is_array($arParams["SITE_URL"]))
	$arParams["SITE_URL"] = array($arParams["SITE_URL"]);
foreach($arParams["SITE_URL"] as $key=>$value)
{
	$arParams["ORIG_SITE_URL"][$key] = $value;
	$value = trim($APPLICATION->ConvertCharset($value, "UTF-8", LANG_CHARSET));
	if(strlen($value))
	{
		if(substr($value, 0, 7) != "http://" && substr($value, 0, 8) != "https://")
			$arParams["SITE_URL"][$key] = array("http://".$value, "https://".$value);
	}
	else
		unset($arParams["SITE_URL"][$key]);
}
$arParams["COMMAND"] = trim($arParams["COMMAND"]);
$arParams["ACTION"] = trim($arParams["ACTION"]);
$arParams["NOTES"] = trim($arParams["NOTES"]);
$arParams["SEPARATOR"] = trim($arParams["SEPARATOR"]);
if(!strlen($arParams["SEPARATOR"]))
	$arParams["SEPARATOR"] = ",";

if($arParams["ACCESS_RESTRICTION"]!="IP" && $arParams["ACCESS_RESTRICTION"]!="NONE")
	$arParams["ACCESS_RESTRICTION"] = "GROUP";
if(!is_array($arParams["GROUP_PERMISSIONS"]))
	$arParams["GROUP_PERMISSIONS"] = array(1);

$bUSER_HAVE_ACCESS = false;
if($arParams["ACCESS_RESTRICTION"] == "GROUP")
{
	if(isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"]) && $USER->IsAuthorized())
	{
		$arUserGroupArray = $GLOBALS["USER"]->GetUserGroupArray();
		foreach($arParams["GROUP_PERMISSIONS"] as $PERM)
		{
			if(in_array($PERM, $arUserGroupArray))
			{
				$bUSER_HAVE_ACCESS = true;
				break;
			}
		}
	}
}
elseif($arParams["ACCESS_RESTRICTION"] == "IP")
{
	$bUSER_HAVE_ACCESS = $_SERVER["REMOTE_ADDR"] == $arParams["IP_PERMISSIONS"];
}
elseif($arParams["ACCESS_RESTRICTION"] == "NONE")
{
	$bUSER_HAVE_ACCESS = true;
}
else
{
	$bUSER_HAVE_ACCESS = false;
}

$bDesignMode = $GLOBALS["APPLICATION"]->GetShowIncludeAreas()
		&& strlen($arParams["COMMAND"]) <= 0
		&& is_object($GLOBALS["USER"])
		&& $GLOBALS["USER"]->IsAdmin();

if(!$bDesignMode)
{
	if(!strlen(implode("", $arParams["SITE_URL"]).$arParams["COMMAND"].$arParams["ACTION"]))
		return;
	$APPLICATION->RestartBuffer();
	header("Pragma: no-cache");
}

ob_start();

if(!$bUSER_HAVE_ACCESS)
{
	echo "403 ER\n";
}
elseif(!CModule::IncludeModule('controller'))
{
	echo "401 ER\n",GetMessage("CC_BCSC_ERROR_MODULE");
}
else
{
	$ID = false;
	foreach($arParams["SITE_URL"] as $key => $site_url)
	{
		$arFilter = array(
			"=URL" => $site_url,
		);
		$rsMember = CControllerMember::GetList(Array($by=>$order), $arFilter);
		if($arMemeber = $rsMember->Fetch())
			$ID = $arMemeber["ID"];
		break;
	}
	if(
		$ID
		|| ($arParams["COMMAND"] == "reserve")
		|| ($arParams["COMMAND"] == "list")
		|| ($arParams["COMMAND"] == "listall")
		|| ($arParams["COMMAND"] == "getlist")
	)
	{
		switch($arParams["COMMAND"])
		{
			case "reserve":
				if($ID)
				{
					echo "401 ER\n",GetMessage("CC_BCSC_RESERVE_ERROR_ALREADY" , array("#ID#"=>$ID));
				}
				elseif(count($arParams["SITE_URL"]) != 1)
				{
					echo "401 ER\n",GetMessage("CC_BCSC_RESERVE_ERROR_SITE_URL");
				}
				else
				{
					foreach($arParams["SITE_URL"] as $key => $site_url)
					{
						$arFields = Array(
							"URL" => is_array($site_url)? $site_url[0]: $site_url,
							"SHARED_KERNEL" => "Y",
							"DISCONNECTED" => "I",
							"NAME" => is_array($site_url)? substr($site_url[0], strlen("http://")): $site_url, // w/o http://
							"ACTIVE" => "Y",
						);
						if(!CControllerMember::Add($arFields))
						{
							echo "401 ER\n";
							if($e = $APPLICATION->GetException())
								echo "401 ER\n",GetMessage("CC_BCSC_RESERVE_ERROR" , array("#MESSAGE#"=>$e->GetString()));
							else
								echo "500 ER\n";
						}
						else
						{
							echo "200 OK\n";
						}
						break;
					}
				}
				break;
			case "active":
				$arFields = Array(
					"ACTIVE" => ($arParams['ACTION']=="Y"? "Y": "N"),
				);
				if($arMemeber["ACTIVE"] != $arFields["ACTIVE"])
				{
					if(!CControllerMember::Update($ID, $arFields, $arParams["NOTES"]))
					{
						if($e = $APPLICATION->GetException())
							echo "401 ER\n",GetMessage("CC_BCSC_UPDATE_ERROR" , array("#ID#"=>$ID,"#MESSAGE#"=>$e->GetString()));
						else
							echo "500 ER\n";
					}
					else
					{
						echo "200 OK\n";
					}
				}
				else
				{
					echo "200 OK\n";
				}
				break;
			case "group":
				$rsGroup = CControllerGroup::GetList(Array("SORT"=>"ASC"), Array("NAME"=>$arParams["ACTION"]));
				if($arGroup = $rsGroup->Fetch())
				{
					if($arMemeber["CONTROLLER_GROUP_ID"]!=$arGroup["ID"])
					{
						$arFields = Array(
							"CONTROLLER_GROUP_ID" => $arGroup['ID'],
						);
						if(!CControllerMember::Update($arMemeber["ID"], $arFields, $arParams["NOTES"]))
						{
							echo "401 ER\n";
							if($e = $APPLICATION->GetException())
								echo "401 ER\n",GetMessage("CC_BCSC_UPDATE_ERROR" , array("#ID#"=>$ID,"#MESSAGE#"=>$e->GetString()));
							else
								echo "500 ER\n";
						}
						else
						{
							CControllerMember::UpdateCounters($arMemeber["ID"]);
							echo "200 OK\n";
						}
					}
					elseif(strlen($arParams["NOTES"]))
					{
						CControllerMember::addHistoryNote($arMemeber["ID"], $arParams["NOTES"]);
						CControllerMember::UpdateCounters($arMemeber["ID"]);
						echo "200 OK\n";
					}
					else
					{
						echo "200 OK\n";
					}
				}
				else
				{
					echo "401 ER\n",GetMessage("CC_BCSC_UNKNOWN_GROUP");
				}
				break;
			case "update_counters":
				CControllerMember::UpdateCounters($arMemeber["ID"]);
				echo "200 OK\n";
				break;
			case "delete":
				@set_time_limit(0);
				$DB->StartTransaction();
				if(!CControllerMember::Delete($ID))
				{
					$DB->Rollback();
					echo "401 ER\n",GetMessage("CC_BCSC_DELETE_ERROR" , array("#ID#"=>$ID));
				}
				else
				{
					echo "200 OK\n";
				}
				$DB->Commit();
				break;
			case "email":
				$query = '
					$obUser = new CUser;
					$arFields = array(
						"EMAIL" => "'.$arParams["ACTION"].'",
					);
					$obUser->Update(1, $arFields);
					echo $obUser->LAST_ERROR;
				';
				$result = CControllerMember::RunCommandWithLog($ID, $query);
				if($result===false)
				{
					if($e = $APPLICATION->GetException())
						echo "401 ER\n",GetMessage("CC_BCSC_EMAIL_ERROR" , array("#ID#"=>$ID,"#MESSAGE#"=>$e->GetString()));
					else
						echo "500 ER\n";
				}
				elseif(strlen($result))
				{
					echo "401 ER\n".$result;
				}
				else
				{
					echo "200 OK\n";
				}
				break;
			case "password":
				if(strlen($arParams["ACTION"]))
				{
					$query = '
						$obUser = new CUser;
						$arFields = array(
							"PASSWORD" => "'.$arParams["ACTION"].'",
							"CONFIRM_PASSWORD" => "'.$arParams["ACTION"].'",
						);
						$obUser->Update(1, $arFields);
						echo $obUser->LAST_ERROR;
					';
				}
				else
				{
					$query = '
						$obUser = new CUser;
						$rsUser = $obUser->GetByID(1);
						$arUser = $rsUser->Fetch();
						echo $arUser["PASSWORD"];
					';
				}
				$result = CControllerMember::RunCommandWithLog($ID, $query);
				if($result===false)
				{
					if($e = $APPLICATION->GetException())
						echo "401 ER\n",GetMessage("CC_BCSC_PASSWORD_ERROR" , array("#ID#"=>$ID,"#MESSAGE#"=>$e->GetString()));
					else
						echo "500 ER\n";
				}
				elseif(!strlen($arParams["ACTION"]) && strlen($result))
				{
					echo "210 OK\n".$result;
				}
				elseif(strlen($result))
				{
					echo "401 ER\n".$result;
				}
				else
				{
					echo "200 OK\n";
				}
				break;
			case "list":
				if(count($arParams["ORIG_SITE_URL"]))
					echo "210 OK\n";
				else
					echo "200 OK\n";
				foreach($arParams["ORIG_SITE_URL"] as $key => $orig_site_url)
				{
					$arRes = array($orig_site_url);
					$arMemeber = false;

					if(array_key_exists($key, $arParams["SITE_URL"]))
					{
						$rsMember = CControllerMember::GetList(Array($by=>$order), Array("=URL" => $arParams["SITE_URL"][$key]));
						if($arMemeber = $rsMember->Fetch())
							$arRes[]="FOUND";
						else
							$arRes[]="NOTFOUND";
					}
					else
					{
						$arRes[]="NOTFOUND";
					}

					if($arMemeber && ($arMemeber["DISCONNECTED"] == "I"))
						$arRes[] = "R";
					elseif($arMemeber && ($arMemeber["ACTIVE"] == "Y"))
						$arRes[] = "Y";
					else
						$arRes[] = "N";

						echo implode($arParams["SEPARATOR"], $arRes),"\n";
				}
				break;
			case "listall":
				$arFilter = array();
				if($arParams["ACTION"] == "Y")
				{
					$arFilter["ACTIVE"] = "Y";
					$arFilter["!DISCONNECTED"] = "I";
				}
				elseif($arParams["ACTION"] == "N")
				{
					$arFilter["ACTIVE"] = "N";
					$arFilter["!DISCONNECTED"] = "I";
				}
				elseif($arParams["ACTION"] == "R")
				{
					$arFilter["DISCONNECTED"] = "I";
				}
				$rsMember = CControllerMember::GetList(Array($by=>$order), $arFilter);
				if($arMemeber = $rsMember->Fetch())
				{
					echo "210 OK\n";
					do {
						if(strncmp($arMemeber["URL"], "http://", 7)===0)
							$arMemeber["URL"] = substr($arMemeber["URL"], 7);
						echo
							$arMemeber["URL"]
							,$arParams["SEPARATOR"],"FOUND"
							,$arParams["SEPARATOR"],($arMemeber["DISCONNECTED"]=="I"? "R": $arMemeber["ACTIVE"])
							,"\n";
						;
					} while ($arMemeber = $rsMember->Fetch());
				}
				else
				{
					echo "200 OK\n";
				}
				break;

			case "getlist":
				if(isset($_REQUEST["date_format"]))
					$arOptions = array("date_format" => $_REQUEST["date_format"]);
				else
					$arOptions = array();

				$rsMember = CControllerMember::GetList($_REQUEST["order"], $_REQUEST["filter"], $_REQUEST["select"], $arOptions);
				if($arMemeber = $rsMember->Fetch())
				{
					echo "210 OK\n";
					echo "<sitelist date_format=\"".htmlspecialcharsbx($_REQUEST["date_format"])."\">\n";

					do {
						echo "\t<site>\n";
						foreach($arMemeber as $key => $value)
							echo "\t\t<", $key, ">", htmlspecialcharsbx($value), "</", $key, ">\n";
						echo "\t</site>\n";
					} while ($arMemeber = $rsMember->Fetch());
					echo "</sitelist>";
				}
				else
				{
					echo "200 OK\n";
				}
				break;
			default:
				echo "400 ER\n";
				break;
		}
	}
	else
	{
		echo "404 ER\n";
	}
}

$contents = ob_get_contents();
ob_end_clean();

if(!$bDesignMode)
{
	header("Content-Type: text/html; charset=utf-8");
	echo $APPLICATION->ConvertCharset($contents, LANG_CHARSET, "UTF-8");
	die();
}
else
{
	?>
	<h4><?echo GetMessage("CC_BCSC_TITLE")?></h4>
	<?
}
?>
