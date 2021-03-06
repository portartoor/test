<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CIntranetInviteDialog
{
	public static $bSendPassword = false;

	public static function ShowInviteDialogLink($arParams = array())
	{
		CJSCore::Init(array('popup'));
		$arParams["MESS"] = array(
			"BX24_INVITE_TITLE_INVITE" => GetMessage("BX24_INVITE_TITLE_INVITE"),
			"BX24_INVITE_TITLE_ADD" => GetMessage("BX24_INVITE_TITLE_ADD"),
			"BX24_INVITE_BUTTON" => GetMessage("BX24_INVITE_BUTTON"),
			"BX24_CLOSE_BUTTON" => GetMessage("BX24_CLOSE_BUTTON"),
			"BX24_LOADING" => GetMessage("BX24_LOADING"),
		);
		return "B24.Bitrix24InviteDialog.ShowForm(".CUtil::PhpToJSObject($arParams).")";
	}

	public static function setSendPassword($value)
	{
		self::$bSendPassword = $value;
	}

	public static function getSendPassword()
	{
		return self::$bSendPassword;
	}

	public static function AddNewUser($SITE_ID, $arFields, &$strError)
	{
		global $APPLICATION, $USER;

		$ID_ADDED = 0;

		$iDepartmentId = intval($arFields["DEPARTMENT_ID"]);

		$siteIdByDepartmentId = self::getUserSiteId(array(
			"UF_DEPARTMENT" => $iDepartmentId,
			"SITE_ID" => $SITE_ID
		));

		$bExtranet = ($iDepartmentId <= 0);
		$arGroups = self::getUserGroups($siteIdByDepartmentId, $bExtranet);

		$strEmail = trim($arFields["ADD_EMAIL"]);
		$strName = trim($arFields["ADD_NAME"]);
		$strLastName = trim($arFields["ADD_LAST_NAME"]);
		$strPosition = trim($arFields["ADD_POSITION"]);

		if (strlen($strEmail) > 0)
		{
			$rsUser = CUser::GetList(
				($by="id"),
				($order="asc"),
				array(
					"=EMAIL"=> $strEmail
				),
				array(
					"FIELDS" => array("ID", "EXTERNAL_AUTH_ID")
				)
			);

			if ($arUser = $rsUser->Fetch())
			{
				if ($arUser["EXTERNAL_AUTH_ID"] == 'email')
				{
					$ID_TRANSFERRED = self::TransferEmailUser($arUser["ID"], array(
						"GROUP_ID" => $arGroups,
						"UF_DEPARTMENT" => $iDepartmentId,
						"SITE_ID" => $siteIdByDepartmentId,
						"NAME" => $strName,
						"LAST_NAME" => $strLastName,
						"POSITION" => $strPosition
					));

					if (!$ID_TRANSFERRED)
					{
						if($e = $APPLICATION->GetException())
						{
							$strError = $e->GetString();
						}
						return false;
					}
					else
					{
						return $ID_TRANSFERRED;
					}
				}
				else
				{
					$strError = GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR");
				}
			}
		}

		if (strlen($strEmail) <= 0)
		{
			if (
				!isset($arFields["ADD_MAILBOX_ACTION"])
				|| !in_array($arFields["ADD_MAILBOX_ACTION"], array("create", "connect"))
				|| strlen($arFields['ADD_MAILBOX_USER']) <= 0
				|| strlen($arFields['ADD_MAILBOX_DOMAIN']) <= 0
			)
			{
				$strError = GetMessage("BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL");
			}
			else
			{
				// email from mailbox
				$strEmail = $arFields['ADD_MAILBOX_USER']."@".$arFields['ADD_MAILBOX_DOMAIN'];
			}
		}

		if (!$strError)
		{
			$strPassword = self::GeneratePassword($siteIdByDepartmentId, $bExtranet);
			self::setSendPassword($arFields["ADD_SEND_PASSWORD"] == "Y");

			$arUser = array(
				"LOGIN" => $strEmail,
				"NAME" => $strName,
				"LAST_NAME" => $strLastName,
				"EMAIL" => $strEmail,
				"PASSWORD" => $strPassword,
				"GROUP_ID" => $arGroups,
				"WORK_POSITION" => $strPosition,
				"LID" => $siteIdByDepartmentId,
				"UF_DEPARTMENT" => ($iDepartmentId > 0 ? array($iDepartmentId) : array())
			);

			if (!self::getSendPassword())
			{
				$arUser["CONFIRM_CODE"] = randString(8);
			}

			$obUser = new CUser;
			$ID_ADDED = $obUser->Add($arUser);

			if (!$ID_ADDED)
			{
				if($e = $APPLICATION->GetException())
				{
					$strError = $e->GetString();
				}
				else
				{
					$strError = $obUser->LAST_ERROR;
				}
			}
			else
			{
				if (self::getSendPassword())
				{
					$db_events = GetModuleEvents("main", "OnUserInitialize", true);
					foreach($db_events as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, array($ID_ADDED, $arUser));
					}
				}

				if (
					$bExtranet
					&& !IsModuleInstalled("extranet")
					&& !IsModuleInstalled("bitrix24")
				)
				{
					$bExtranet = false;
				}

				$messageText = (
					($userMessageText = CUserOptions::GetOption((IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet"), "invite_message_text"))
						? $userMessageText
						: GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT")
				);

				$event = new CEvent;
				if (self::getSendPassword())
				{
					$rsSites = CSite::GetByID($siteIdByDepartmentId);
					$arSite = $rsSites->Fetch();
					$serverName = (
						strlen($arSite["SERVER_NAME"]) > 0
							? $arSite["SERVER_NAME"]
							: (
								defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
									? SITE_SERVER_NAME
									: COption::GetOptionString("main", "server_name", "")
							)
					);

					$url = (CMain::IsHTTPS() ? "https" : "http")."://".$serverName.$arSite["DIR"];
					$event->SendImmediate("INTRANET_USER_ADD", $siteIdByDepartmentId, array(
						"EMAIL_TO" => $arUser["EMAIL"],
						"LINK" => $url,
						"PASSWORD" => $strPassword,
						"USER_TEXT" => $messageText
					));
				}
				else
				{
					$dbUser = CUser::GetByID($ID_ADDED);
					$arUser = $dbUser->Fetch();

					if ($bExtranet)
					{
						$event->SendImmediate("EXTRANET_INVITATION", $siteIdByDepartmentId, array(
							"USER_ID" => $arUser["ID"],
							"CHECKWORD" => $arUser["CONFIRM_CODE"],
							"EMAIL" => $arUser["EMAIL"],
							"USER_TEXT" => ''
						));
					}
					elseif (IsModuleInstalled("bitrix24"))
					{
						$event->SendImmediate("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, array(
							"EMAIL_FROM" => $USER->GetEmail(),
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
							"USER_TEXT" => $messageText
						));
					}
					else
					{
						$event->SendImmediate("INTRANET_USER_INVITATION", $siteIdByDepartmentId, array(
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
							"USER_TEXT" => $messageText
						));
					}
				}
			}
		}

		return $ID_ADDED;
	}

	public static function RegisterNewUser($SITE_ID, $arFields, &$arError)
	{
		global $APPLICATION;

		$arCreatedUserId = array();

		$arEmailToRegister = array();
		$arEmailToReinvite = array();
		$arEmailUserId = array();

		$arEmailExist = array();
		$bExtranetUser = false;
		$bExtranetInstalled = (IsModuleInstalled("extranet") && strlen(COption::GetOptionString("extranet", "extranet_site")) > 0);

		if ($arFields["EMAIL"] <> '')
		{
			$arEmailOriginal = preg_split("/[\n\r\t\\,;\\ ]+/", trim($arFields["EMAIL"]));

			$arEmail = $errorEmails = array();
			$emailCnt = 0;

			foreach($arEmailOriginal as $addr)
			{
				if ($emailCnt >= 100)
				{
					$arError = array(GetMessage("BX24_INVITE_DIALOG_EMAIL_LIMIT_EXCEEDED"));
					return false;
				}

				if(strlen($addr) > 0 && check_email($addr))
				{
					$arEmail[] = $addr;
					$emailCnt++;
				}
				else
				{
					$errorEmails[] = $addr;
				}
			}
			if (count($arEmailOriginal) > count($arEmail))
			{
				$arError = array(GetMessage("BX24_INVITE_DIALOG_EMAIL_ERROR").implode("<br/>", $errorEmails));
				return false;
			}

			foreach($arEmail as $email)
			{
				$rsUser = CUser::GetList(
					($by="id"),
					($order="asc"),
					array(
						"=EMAIL"=>$email
					),
					array(
						"FIELDS" => array("ID", "LAST_LOGIN", "CONFIRM_CODE", "EXTERNAL_AUTH_ID"),
						"SELECT" => array("UF_DEPARTMENT")
					)
				);
				$bFound = false;
				while ($arUser = $rsUser->Fetch())
				{
					$bFound = true;

					if ($arUser["EXTERNAL_AUTH_ID"] == 'email')
					{
						$arEmailUserId[] = $arUser["ID"];
					}
					elseif (
						$arUser["CONFIRM_CODE"] != ""
						&& (
							!$bExtranetInstalled
							|| ( // both intranet
								isset($arFields["DEPARTMENT_ID"])
								&& intval($arFields["DEPARTMENT_ID"]) > 0
								&& isset($arUser["UF_DEPARTMENT"])
								&& (
									(
										is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"][0]) > 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"]) <= 0
									)
								)
							)
							||
							(	// both extranet
								(
									!isset($arFields["DEPARTMENT_ID"])
									|| intval($arFields["DEPARTMENT_ID"]) <= 0
								)
								&& (
									!isset($arUser["UF_DEPARTMENT"])
									|| (
										is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"]) <= 0
									)
								)
							)
						)
					)
					{
						$arEmailToReinvite[] = array(
							"EMAIL" => $email,
							"REINVITE" => true,
							"ID" => $arUser["ID"],
							"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
							"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"]
						);
					}
					else
					{
						$arEmailExist[] = $email;
					}
				}

				if (!$bFound)
				{
					$arEmailToRegister[] = array(
						"EMAIL" => $email,
						"REINVITE" => false
					);
				}
			}
		}

		$moduleID = (IsModuleInstalled("bitrix24")? "bitrix24" : "intranet");
		$messageText = (isset($arFields["MESSAGE_TEXT"]) ? ($arFields["MESSAGE_TEXT"]) : GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT"));
		if (isset($arFields["MESSAGE_TEXT"]))
		{
			CUserOptions::SetOption($moduleID, "invite_message_text", $arFields["MESSAGE_TEXT"]);
		}

		if (
			count($arEmailToRegister) <= 0
			&& count($arEmailToReinvite) <= 0
			&& count($arEmailUserId) <= 0
		)
		{
			$arError = array(GetMessage(!empty($arEmailExist) ? "BX24_INVITE_DIALOG_USER_EXIST_ERROR" : "BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL_LIST"));
			return false;
		}

		//reinvite users
		foreach ($arEmailToReinvite as $userData)
		{
			self::InviteUser($userData, $messageText);
		}

		$siteIdByDepartmentId = $arGroups = false;

		if (
			!empty($arEmailToRegister)
			|| !empty($arEmailUserId)
		)
		{
			if (isset($arFields["DEPARTMENT_ID"]))
			{
				$arFields["UF_DEPARTMENT"] = $arFields["DEPARTMENT_ID"];
			}

			if (
				!(
					isset($arFields["UF_DEPARTMENT"])
					&& intval($arFields["UF_DEPARTMENT"]) > 0
				)
			)
			{
				if (!$bExtranetInstalled)
				{
					if (CModule::IncludeModule('iblock'))
					{
						$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
						$arIBlock = $rsIBlock->Fetch();
						$iblockID = $arIBlock["ID"];

						$db_up_department = CIBlockSection::GetList(
							array(),
							array(
								"SECTION_ID" => 0,
								"IBLOCK_ID" => $iblockID
							)
						);
						if ($ar_up_department = $db_up_department->Fetch())
						{
							$arFields["UF_DEPARTMENT"] = $ar_up_department['ID'];
						}
					}
				}
				else
				{
					$bExtranetUser = true;
				}
			}

			$siteIdByDepartmentId = self::getUserSiteId(array(
				"UF_DEPARTMENT" => (!$bExtranetUser ? $arFields["UF_DEPARTMENT"] : false),
				"SITE_ID" => $SITE_ID
			));

			$arGroups = self::getUserGroups($siteIdByDepartmentId, $bExtranetUser);
		}

		// transfer email users to employees or extranet
		if (!empty($arEmailUserId))
		{
			foreach ($arEmailUserId as $emailUserId)
			{
				$ID_TRANSFERRED = self::TransferEmailUser($emailUserId, array(
					"GROUP_ID" => $arGroups,
					"UF_DEPARTMENT" => $arFields["UF_DEPARTMENT"],
					"SITE_ID" => $siteIdByDepartmentId
				));

				if (!$ID_TRANSFERRED)
				{
					if($e = $APPLICATION->GetException())
					{
						$arError[] = $e->GetString();
					}
					return false;
				}
				else
				{
					$arCreatedUserId[] = $ID_TRANSFERRED;
				}
			}
		}

		//register users
		if (!empty($arEmailToRegister))
		{
			foreach ($arEmailToRegister as $userData)
			{
				$userData["CONFIRM_CODE"] = randString(8);
				$userData["GROUP_ID"] = $arGroups;
				$userData["UF_DEPARTMENT"] = $arFields["UF_DEPARTMENT"];
				$ID = self::RegisterUser($userData, $siteIdByDepartmentId);

				if(is_array($ID))
				{
					$arError = $ID;
					return false;
				}
				else
				{
					$arCreatedUserId[] = $ID;
					$userData['ID'] = $ID;
					self::InviteUser($userData, $messageText);
				}
			}
		}

		if (!empty($arEmailExist))
		{
			$arError = array(GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR"));
			return false;
		}
		else
		{
			return $arCreatedUserId;
		}
	}

	public static function getUserGroups($SITE_ID, $bExtranetUser = false)
	{
		$arGroups = array();

		if (
			$bExtranetUser
			&& CModule::IncludeModule("extranet")
		)
		{
			$extranetGroupID = CExtranet::GetExtranetUserGroupID();
			if (intval($extranetGroupID) > 0)
			{
				$arGroups[] = $extranetGroupID;
			}
		}
		else
		{
			$rsGroups = CGroup::GetList(
				$o="",
				$b="",
				array(
					"STRING_ID" => "EMPLOYEES_".$SITE_ID
				)
			);
			while($arGroup = $rsGroups->Fetch())
			{
				$arGroups[] = $arGroup["ID"];
			}
		}

		return $arGroups;
	}

	public static function checkUsersCount($cnt)
	{
		if (CModule::IncludeModule("bitrix24"))
		{
			$UserMaxCount = intval(COption::GetOptionString("main", "PARAM_MAX_USERS"));
			$currentUserCount = CBitrix24::ActiveUserCount();
			return $UserMaxCount <= 0 || $cnt <= $UserMaxCount - $currentUserCount;
		}
		return true;
	}

	public static function RegisterUser($userData, $SITE_ID = SITE_ID)
	{
		$bExtranetUser = (!isset($userData['UF_DEPARTMENT']) || empty($userData['UF_DEPARTMENT']));
		$strPassword = self::GeneratePassword($SITE_ID, $bExtranetUser);

		$arUser = array(
			"LOGIN" => isset($userData["LOGIN"]) ? $userData["LOGIN"] : $userData["EMAIL"],
			"EMAIL" => $userData["EMAIL"],
			"PASSWORD" => $strPassword,
			"CONFIRM_CODE" => $userData['CONFIRM_CODE'],
			"GROUP_ID" => $userData['GROUP_ID'],
			"LID" => $SITE_ID,
			"UF_DEPARTMENT" => (intval($userData["UF_DEPARTMENT"]) > 0 ? array($userData["UF_DEPARTMENT"]) : array())
		);

		if(isset($userData["ACTIVE"]))
		{
			$arUser["ACTIVE"] = $userData["ACTIVE"];
		}

		$obUser = new CUser;
		$res = $obUser->Add($arUser);
		return ($res? $res : preg_split("/<br>/", $obUser->LAST_ERROR));
	}

	public static function InviteUser($arUser, $messageText)
	{
		global $USER;

		$bExtranet = (
			IsModuleInstalled('extranet')
			&& (
				!isset($arUser["UF_DEPARTMENT"])
				|| (
					is_array($arUser["UF_DEPARTMENT"])
					&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
				)
				|| (
					!is_array($arUser["UF_DEPARTMENT"])
					&& intval($arUser["UF_DEPARTMENT"]) <= 0
				)
			)
		);

		$siteIdByDepartmentId = self::getUserSiteId(array(
			"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"],
			"SITE_ID" => SITE_ID
		));

		$event = new CEvent;
		if ($bExtranet)
		{
			$event->SendImmediate("EXTRANET_INVITATION", $siteIdByDepartmentId, array(
				"USER_ID" => $arUser["ID"],
				"CHECKWORD" => $arUser["CONFIRM_CODE"],
				"EMAIL" => $arUser["EMAIL"],
				"USER_TEXT" => $messageText
			));
		}
		elseif (IsModuleInstalled("bitrix24"))
		{
			$event->SendImmediate("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, array(
				"EMAIL_FROM" => $USER->GetEmail(),
				"EMAIL_TO" => $arUser["EMAIL"],
				"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
				"USER_TEXT" => $messageText,
			));
		}
		else
		{
			$event->SendImmediate("INTRANET_USER_INVITATION", $siteIdByDepartmentId, array(
				"EMAIL_TO" => $arUser["EMAIL"],
				"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
				"USER_TEXT" => $messageText,
			));
		}
	}

	public static function ReinviteUser($SITE_ID, $USER_ID)
	{
		$USER_ID = intval($USER_ID);

		$rsUser = CUser::GetList(
			($o = "ID"),
			($b = "DESC"),
			array("ID_EQUAL_EXACT" => $USER_ID),
			array("SELECT" => array("UF_DEPARTMENT"))
		);
		if($arUser = $rsUser->Fetch())
		{
			$moduleID = (IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet");
			$messageText = (($userMessageText = CUserOptions::GetOption($moduleID, "invite_message_text")) ? $userMessageText : GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT"));
			self::InviteUser($arUser, $messageText);
			return true;
		}
		return false;
	}

	public static function ReinviteExtranetUser($SITE_ID, $USER_ID)
	{
		$USER_ID = intval($USER_ID);

		$rsUser = CUser::GetList(
			($o = "ID"),
			($b = "DESC"),
			array("ID_EQUAL_EXACT" => $USER_ID)
		);

		if($arUser = $rsUser->Fetch())
		{
			$moduleID = (IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet");
			$messageText = (($userMessageText = CUserOptions::GetOption($moduleID, "invite_message_text")) ? $userMessageText : GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT"));

			$event = new CEvent;
			$arFields = Array(
				"USER_ID" => $USER_ID,
				"CHECKWORD" => $arUser["CONFIRM_CODE"],
				"EMAIL" => $arUser["EMAIL"],
				"USER_TEXT" => $messageText
			);
			$event->Send("EXTRANET_INVITATION", $SITE_ID, $arFields);
			return true;
		}
		return false;
	}

	public static function RequestToSonetGroups($arUserId, $arGroupCode, $arGroupName, $bExtranetUser = false)
	{
		global $APPLICATION, $USER;

		$arGroupToAdd = array();
		$strError = false;

		if (!is_array($arUserId))
		{
			$arUserId = array($arUserId);
		}

		if (
			is_array($arGroupCode)
			&& !empty($arGroupCode)
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			foreach($arGroupCode as $group_code)
			{
				if(
					$bExtranetUser
					&& preg_match('/^(SGN\d+)$/', $group_code, $match)
					&& is_array($arGroupName)
					&& isset($arGroupName[$match[1]])
					&& strlen($arGroupName[$match[1]]) > 0
					&& CModule::IncludeModule("extranet")
					&& (
						CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
						|| $APPLICATION->GetGroupRight("socialnetwork", false, "Y", "Y", array(CExtranet::GetExtranetSiteID(), false)) >= "K"
					)
				)
				{
					// check and create group, for extranet only

					$dbSubjects = CSocNetGroupSubject::GetList(
						array("SORT"=>"ASC", "NAME" => "ASC"),
						array("SITE_ID" => CExtranet::GetExtranetSiteID()),
						false,
						false,
						array("ID")
					);
					if ($arSubject = $dbSubjects->GetNext())
					{
						$arSocNetGroupFields = array(
							"NAME" => $arGroupName[$match[1]],
							"DESCRIPTION" => "",
							"VISIBLE" => "N",
							"OPENED" => "N",
							"CLOSED" => "N",
							"SUBJECT_ID" => $arSubject["ID"],
							"INITIATE_PERMS" => "E",
							"SPAM_PERMS" => "K",
							"SITE_ID" => array(SITE_ID, CExtranet::GetExtranetSiteID())
						);

						if ($group_id = CSocNetGroup::CreateGroup(
							$USER->GetID(),
							$arSocNetGroupFields,
							false
						))
						{
							$arGroupToAdd[] = $group_id;
						}
						elseif ($e = $APPLICATION->GetException())
						{
							$strError = $e->GetString();
						}
					}
				}
				elseif(preg_match('/^SG(\d+)$/', $group_code, $match))
				{
					$group_id = $match[1];
					if (
						($arGroup = CSocNetGroup::GetByID($group_id))
						&& ($arCurrentUserPerms = CSocNetUserToGroup::InitUserPerms($USER->GetID(), $arGroup, CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)))
						&& $arCurrentUserPerms["UserCanInitiate"]
						&& $arGroup["CLOSED"] != "Y"
					)
					{
						$arGroupToAdd[] = $group_id;
					}
				}
			}

			if (!$strError)
			{
				$arAccessCodes = array();
				foreach($arGroupToAdd as $group_id)
				{
					foreach($arUserId as $user_id)
					{
						if (!CSocNetUserToGroup::SendRequestToJoinGroup($USER->GetID(), $user_id, $group_id, "", false))
						{
							if ($e = $APPLICATION->GetException())
							{
								$strError .= $e->GetString();
							}
						}
					}

					$arAccessCodes[] = 'SG'.$group_id;
				}

				if (!empty($arAccessCodes))
				{
					\Bitrix\Main\FinderDestTable::merge(array(
						"CONTEXT" => "USER_INVITE",
						"CODE" => $arAccessCodes
					));
				}
			}
		}

		return $strError;
	}

	public static function OnAfterUserAuthorize($arParams)
	{
		global $CACHE_MANAGER;

		if (
			isset($arParams['update'])
			&& $arParams['update'] === false
		)
		{
			return false;
		}

		if ($arParams['user_fields']['ID'] <= 0)
		{
			return false;
		}

		if (
			array_key_exists('LAST_LOGIN', $arParams['user_fields'])
			&& strlen(trim($arParams['user_fields']['LAST_LOGIN'])) <= 0 // do not check CONFIRM_CODE, please
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			$dbRelation = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $arParams['user_fields']['ID'],
					"ROLE" => SONET_ROLES_REQUEST,
					"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);
			while ($arRelation = $dbRelation->Fetch())
			{
				if (CSocNetUserToGroup::UserConfirmRequestToBeMember($arParams['user_fields']['ID'], $arRelation["ID"], false))
				{
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->ClearByTag("sonet_user2group_G".$arRelation["GROUP_ID"]);
						$CACHE_MANAGER->ClearByTag("sonet_user2group_U".$arParams['user_fields']['ID']);
					}

					if (CModule::IncludeModule("im"))
					{
						CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".$arParams['user_fields']['ID']."|".intval($arRelation["ID"]));
					}
				}
			}
		}
	}

	private function GeneratePassword($SITE_ID, $bExtranetUser)
	{
		global $USER;

		$arGroupID = self::getUserGroups($SITE_ID, $bExtranetUser);
		$arPolicy = $USER->GetGroupPolicy($arGroupID);

		$password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);
		if($password_min_length <= 0)
		{
			$password_min_length = 6;
		}

		$password_chars = array(
			"abcdefghijklnmopqrstuvwxyz",
			"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
			"0123456789",
		);

		if($arPolicy["PASSWORD_PUNCTUATION"] === "Y")
		{
			$password_chars[] = ",.<>/?;:'\"[]{}\\|`~!@#\$%^&*()-_+=";
		}

		$password = randString($password_min_length, $password_chars);

		return $password;
	}

	public static function TransferEmailUser($userId, $arParams = array())
	{
		global $APPLICATION, $USER;

		$userId = intval($userId);

		if ($userId <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_USER_ID_NO_EXIST_ERROR"));
			return false;
		}

		$dbUser = CUser::GetByID($userId);
		$arUser = $dbUser->Fetch();
		if (!$arUser)
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_USER_ID_NO_EXIST_ERROR"));
			return false;
		}

		$dbUser = CUser::GetList(
			$o = "ID",
			$b = "ASC",
			array(
				"=EMAIL" => $arUser["EMAIL"],
				"EXTERNAL_AUTH_ID" => "",
			),
			array("FIELDS" => array("ID"))
		);
		if ($arUser = $dbUser->Fetch())
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR"));
			return false;
		}

		if (
			!isset($arParams["SITE_ID"])
			|| empty($arParams["SITE_ID"])
		)
		{
			$arParams["SITE_ID"] = SITE_ID;
		}

		$bExtranetUser = (
			!isset($arParams['UF_DEPARTMENT'])
			|| empty($arParams['UF_DEPARTMENT'])
		);

		if (
			!isset($arParams["GROUP_ID"])
			|| empty($arParams["GROUP_ID"])
		)
		{
			$arParams["GROUP_ID"] = self::getUserGroups($arParams["SITE_ID"], $bExtranetUser);
		}

		self::$bSendPassword = true;
		$strPassword = self::GeneratePassword($arParams["SITE_ID"], $bExtranetUser);

		$arFields = array(
			"EXTERNAL_AUTH_ID" => '',
			"GROUP_ID" => $arParams['GROUP_ID'],
			"PASSWORD" => $strPassword,
			"EMAIL" => $arUser["EMAIL"]
		);

		if (
			isset($arParams["UF_DEPARTMENT"])
			&& intval($arParams["UF_DEPARTMENT"]) > 0
		)
		{
			$arFields["UF_DEPARTMENT"] = array($arParams["UF_DEPARTMENT"]);
		}

		if (
			isset($arParams["NAME"])
			&& strlen($arParams["NAME"]) > 0
		)
		{
			$arFields["NAME"] = $arParams["NAME"];
		}
		else
		{
			$arFields["NAME"] = $arUser["NAME"];
		}

		if (
			isset($arParams["LAST_NAME"])
			&& strlen($arParams["LAST_NAME"]) > 0
		)
		{
			$arFields["LAST_NAME"] = $arParams["LAST_NAME"];
		}
		else
		{
			$arFields["LAST_NAME"] = $arUser["LAST_NAME"];
		}

		if (
			isset($arParams["POSITION"])
			&& strlen($arParams["POSITION"]) > 0
		)
		{
			$arFields["POSITION"] = $arParams["POSITION"];
		}

		foreach(GetModuleEvents("intranet", "OnTransferEMailUser", true) as $arEvent)
		{
			if(!ExecuteModuleEventEx($arEvent, array(&$arFields)))
			{
				return false;
			}
		}

		$obUser = new CUser;
		if ($obUser->Update($userId, $arFields))
		{
			$dbUser = CUser::GetByID($userId);
			$arUser = $dbUser->Fetch();

			$arFields['ID'] = $userId;
			foreach(GetModuleEvents("intranet", "OnAfterTransferEMailUser", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arUser));
			}

			$siteIdToSend = self::getUserSiteId(array(
				"UF_DEPARTMENT" => $arParams["UF_DEPARTMENT"],
				"SITE_ID" => $arParams["SITE_ID"]
			));

			$messageText = (
				($userMessageText = CUserOptions::GetOption((IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet"), "invite_message_text"))
					? $userMessageText
					: GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT")
			);

			$event = new CEvent;
			if(self::$bSendPassword)
			{
				$rsSites = CSite::GetByID($siteIdToSend);
				$arSite = $rsSites->Fetch();
				$serverName = (
					strlen($arSite["SERVER_NAME"]) > 0
						? $arSite["SERVER_NAME"]
						: (
					defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
						? SITE_SERVER_NAME
						: COption::GetOptionString("main", "server_name", "")
					)
				);

				$event->SendImmediate("INTRANET_USER_ADD", $arParams["SITE_ID"], array(
					"EMAIL_TO" => $arUser["EMAIL"],
					"LINK" => (CMain::IsHTTPS() ? "https" : "http")."://".$serverName.$arSite["DIR"],
					"PASSWORD" => $strPassword,
					"USER_TEXT" => $messageText
				));
			}
			else
			{
				if(IsModuleInstalled("bitrix24"))
				{
					$event->SendImmediate("BITRIX24_USER_INVITATION", $arParams["SITE_ID"], array(
						"EMAIL_FROM" => $USER->GetEmail(),
						"EMAIL_TO" => $arUser["EMAIL"],
						"LINK" => self::getInviteLink($arUser, $siteIdToSend),
						"USER_TEXT" => $messageText
					));
				}
				else
				{
					$event->SendImmediate("INTRANET_USER_INVITATION", $arParams["SITE_ID"], array(
						"EMAIL_TO" => $arUser["EMAIL"],
						"LINK" => self::getInviteLink($arUser, $siteIdToSend),
						"USER_TEXT" => $messageText
					));
				}
			}

			return $userId;
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_ERROR_USER_TRANSFER"));
			return false;
		}
	}

	public static function getUserSiteId($arParams = array())
	{
		$bExtranet = (
			!isset($arParams["UF_DEPARTMENT"])
			|| intval($arParams["UF_DEPARTMENT"]) <= 0
		);

		if (
			$bExtranet
			&& CModule::IncludeModule("extranet")
		)
		{
			$siteId = CExtranet::GetExtranetSiteID();
		}
		elseif (IsModuleInstalled("bitrix24"))
		{
			$siteId = (
				isset($arParams["SITE_ID"])
				&& !empty($arParams["SITE_ID"])
					? $arParams["SITE_ID"]
					: SITE_ID
			);
		}
		else
		{
			CModule::IncludeModule('socialnetwork');
			$arSite = CSocNetLogComponent::GetSiteByDepartmentId(intval($arParams["UF_DEPARTMENT"]));
			$siteId = $arSite["LID"];
		}

		return $siteId;
	}

	public static function getInviteLink($arUser, $siteId)
	{
		$rsSites = CSite::GetByID($siteId);
		$arSite = $rsSites->Fetch();
		$serverName = (
			strlen($arSite["SERVER_NAME"]) > 0
				? $arSite["SERVER_NAME"]
				: (
			defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
				? SITE_SERVER_NAME
				: COption::GetOptionString("main", "server_name", "")
			)
		);

		return CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$arUser["ID"]."&checkword=".urlencode($arUser["CONFIRM_CODE"]), $serverName);
	}
}
