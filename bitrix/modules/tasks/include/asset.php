<?
////////////////////////
// assets

$assetRoot = '/bitrix/js/tasks/';
$langRoot = BX_ROOT."/modules/tasks/lang/".LANGUAGE_ID."/";

$assets = array(
	// basic asset, contains widely-used phrases and js-stuff required everywhere
	// also contains media kit asset, contains sprites and common css used in components
	/*
	 * When doing redesign, implement conditionally added css, like
	 * 'css' => [['condition' => function(){return *some condition*;}, 'file' => '/bitrix/js/tasks/css/media.css']];
	 */
	array(
		'code' => 'tasks',
		'js'  => array(
			$assetRoot.'tasks.js',
		),
		'css'  => array(
			$assetRoot.'css/media.css',
		),
		'lang' => $langRoot.'include.php',
	),
	// util asset, contains fx functions, helper functions and so on
	array(
		'code' => 'tasks_util',
		'js'  => array(
			$assetRoot.'util.js',
		),
	),
	// oop asset, contains basic class for making js oop emulation work
	array(
		'code' => 'tasks_util_base',
		'js'  => array(
			$assetRoot.'util/base.js',
		),
		'css'  => array(
			$assetRoot.'css/media.css',
		),
		'rel' =>  array('core'),
		'lang' => $langRoot.'include.php',
	),
	// widget asset, allows to create widget-based js-controls
	array(
		'code' => 'tasks_util_widget',
		'js'  => array(
			$assetRoot.'util/widget.js',
		),
		'rel' =>  array('tasks_util_base'),
	),
	// components asset, contains logic for components
	array(
		'code' => 'tasks_component',
		'js'  => array(
			$assetRoot.'component.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util_query'),
	),
	// asset that imports an item accumulator
	array(
		'code' => 'tasks_util_datacollection',
		'js'  => array(
			$assetRoot.'util/datacollection.js',
		),
		'rel' =>  array('tasks_util_base'),
	),
	// asset that implements client-side interface for common ajax api
	array(
		'code' => 'tasks_util_query',
		'js'  => array(
			$assetRoot.'util/query.js',
		),
		'rel' =>  array('tasks_util_base', 'ajax'),
		'lang' => $langRoot.'/include/assets/query.php',
	),
	// asset that implements an interface for page rounting
	array(
		'code' => 'tasks_util_router',
		'js'  => array(
			$assetRoot.'util/router.js',
		),
		'rel' =>  array('tasks_util_base'),
	),
	// asset that implements templating mechanism
	array(
		'code' => 'tasks_util_template',
		'js'  => array(
			$assetRoot.'util/template.js',
		),
	),
	// asset that imports datepicker widget
	array(
		'code' => 'tasks_util_datepicker',
		'js'  => array(
			$assetRoot.'util/datepicker.js',
		),
		'rel' =>  array('tasks_util_widget', 'date'),
	),
	// asset that imports an util for implementing drag-n-drop
	array(
		'code' => 'tasks_util_draganddrop',
		'js'  => array(
			$assetRoot.'util/draganddrop.js',
			'/bitrix/js/main/dd.js',
		),
		'rel' =>  array('tasks_util_base', 'tasks_util'),
	),
	// asset that imports a list rendering control (abstract)
	array(
		'code' => 'tasks_util_itemset',
		'js'  => array(
			$assetRoot.'util/itemset.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util_datacollection'),
	),
	// asset that imports a family of scroll pane controls
	array(
		'code' => 'tasks_util_scrollpane',
		'js'  => array(
			$assetRoot.'util/scrollpane.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util_template', 'popup'),
	),
	// asset that imports a family of selector controls
	array(
		'code' => 'tasks_util_selector',
		'js'  => array(
			$assetRoot.'util/selector.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util_scrollpane', 'tasks_util_datacollection'),
	),
	// asset that imports a list rendering control different implementations
	array(
		'code' => 'tasks_itemsetpicker',
		'js'  => array(
			$assetRoot.'itemsetpicker.js',
		),
		'rel' =>  array('tasks_util_itemset', 'tasks_integration_socialnetwork'),
	),
	// asset that imports js-api for interacting with user day plan
	array(
		'code' => 'tasks_dayplan',
		'js'  => array(
			$assetRoot.'dayplan.js',
		),
		'rel' =>  array('tasks_ui_base', 'tasks_util_query'),
	),
	// asset that implements some integration with "socialnetwork" module
	array(
		'code' => 'tasks_integration_socialnetwork',
		'js'  => array(
			$assetRoot.'integration/socialnetwork.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util', 'tasks_util_query', 'socnetlogdest'),
	),
	// shared js parts
	array(
		'code' => 'tasks_shared_form_projectplan',
		'js'  => array(
			$assetRoot.'shared/form/projectplan.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util_datepicker'),
	),

	// assets for implementing gantt js api
	array(
		'code' => 'task_date',
		'js'  => array(
			$assetRoot.'task-date.js',
		),
	),
	array(
		'code' => 'task_calendar',
		'js'  => array(
			$assetRoot.'task-calendar.js',
		),
		'rel' => array('task_date'),
	),
	array(
		'code' => 'task_timeline',
		'js'  => array(
			$assetRoot.'scheduler/util.js',
			$assetRoot.'scheduler/timeline.js',
		),
		'css' => array(
			$assetRoot.'css/gantt.css',
		),
		'rel' => array('task_date', 'task_calendar'),
	),
	array(
		'code' => 'task_scheduler',
		'js'  => array(
			$assetRoot.'scheduler/tree.js',
			$assetRoot.'scheduler/scheduler.js',
		),
		'css' => array(
			$assetRoot.'scheduler/css/scheduler.css',
		),
		'rel' => array('task_timeline'),
	),
	array(
		'code' => 'gantt',
		'js'  => array(
			$assetRoot.'gantt.js',
			'/bitrix/js/main/dd.js',
		),
		'css' => array(
			$assetRoot.'css/gantt.css',
		),
		'rel' => array('popup', 'date', 'task_info_popup', 'task_calendar', 'task_date'),
		'lang' => $langRoot.'gantt.php',
	),

	// deprecated assets
	array(
		'code' => 'task_info_popup',
		'js'  => array(
			$assetRoot.'task-info-popup.js',
		),
		'css' => array(
			$assetRoot.'css/task-info-popup.css',
		),
		'rel' => array('popup', 'tasks_util'),
		'lang' => $langRoot.'task-info-popup.php',
	),
	array(
		'code' => 'task_popups',
		'js'  => array(
			$assetRoot.'task-popups.js',
		),
		'css' => array(
			$assetRoot.'css/task-popups.css',
		),
		'rel' => array('popup'),
		'lang' => $langRoot.'task-popups.php',
	),
	array(
		'code' => 'CJSTask',
		'js'  => array(
			$assetRoot.'cjstask.js',
		),
		'rel' => array('ajax', 'json'),
	),
	array(
		'code' => 'taskQuickPopups',
		'js'  => array(
			$assetRoot.'task-quick-popups.js',
		),
		'rel' => array('popup', 'ajax', 'json', 'CJSTask'),
	),
	array(
		'code' => 'tasks_style_legacy',
		'css'  => array(
			$assetRoot.'css/tasks.css',
		),
	),
);

$js = array();
$css = array();
foreach($assets as $asset)
{
	if(array_key_exists('css', $asset))
	{
		$css = array_merge($css, $asset['css']);
		$asset['css'] = $asset['css'][0];
	}

	if(array_key_exists('js', $asset))
	{
		$js = array_merge($js, $asset['js']);
	}

	CJSCore::registerExt(
		$asset['code'],
		$asset
	);
}

$js = array_merge($js, array(
	$assetRoot.'core_planner_handler.js',
	$assetRoot.'task-iframe-popup.js',
));
$css = array_merge($css, array(
	$assetRoot.'css/core_planner_handler.css',
));

$GLOBALS["APPLICATION"]->addJSKernelInfo(
	'tasks',
	array_unique($js)
);
$GLOBALS["APPLICATION"]->addCSSKernelInfo(
	'tasks',
	array_unique($css)
);