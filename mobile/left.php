<?
define('BX_DONT_SKIP_PULL_INIT', true);
define('BX_DONT_INCLUDE_MOBILE_TEMPLATE_CSS', true);
require($_SERVER["DOCUMENT_ROOT"] . "/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
CJSCore::Init(array("ls"));
//viewport rewrite
CMobile::getInstance()->setLargeScreenSupport(false);
CMobile::getInstance()->setScreenCategory("NORMAL");
$frame = \Bitrix\Main\Page\Frame::getInstance();
$frame->setEnable();
$frame->setUseAppCache();

\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("api_version", CMobile::getApiVersion());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("platform", CMobile::getPlatform());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("version", "v5");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("user", $USER->GetID());
$frame->startDynamicWithID("menu");
$arParams = array();
$APPLICATION->IncludeComponent("bitrix:mobile.menu", in_array(32, CUser::GetUserGroup($USER->GetID()))?"custom_domofey":"custom", $arParams, false, Array("HIDE_ICONS" => "Y"));
$frame->finishDynamicWithID("menu", $stub = "", $containerId = null, $useBrowserStorage = true);
$APPLICATION->IncludeComponent("bitrix:mobile.rtc", "", array(), false, Array("HIDE_ICONS" => "Y"));
?>
<script type="text/javascript">
	app.enableSliderMenu(true);
</script>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php") ?>
