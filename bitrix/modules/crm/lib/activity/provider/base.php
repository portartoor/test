<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use \Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Base
{
	const COMMUNICATION_TYPE_UNDEFINED = '';
	const COMMUNICATION_TYPE_PHONE = 'PHONE';
	const COMMUNICATION_TYPE_EMAIL = 'EMAIL';

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}
	
	public static function getName()
	{
		return '';
	}
	
	public static function getId()
	{
		return 'CRM_BASE';
	}

	/**
	 * Checks provider status.
	 * @return bool
	 */
	public static function isActive()
	{
		return true;
	}

	/**
	 * Provider status anchor (active, inactive, settings URL etc.)
	 * @return array
	 */
	public static function getStatusAnchor()
	{
		return array(
			'TEXT' => '',
			'URL' => '',
			//'HTML' => '' TEXT & URL or HTML
		);
	}
	
	/**
	 * @return array Supported types list.
	 * Example:
	 * array(
	 * 		array(
	 *			'NAME' => 'My activity', // required
	 * 			'PROVIDER_TYPE_ID' => 'MY_PROVIDER_TYPE_ID', // required
	 * 			'DIRECTIONS' => array(
	 * 				\CCrmActivityDirection::Incoming => 'My activity (incoming)',
	 * 				\CCrmActivityDirection::Outgoing => 'My activity (outgoing)',
	 * 			) // required
	 * 		)
	 * )
	 */
	public static function getTypes()
	{
		return array();
	}

	/**
	 * @return array Types filter presets list.
	 * Example:
	 * array(
	 * 		array(
	 *			'NAME' => 'Incoming activity', // required
	 * 			'PROVIDER_TYPE_ID' => 'MY_TYPE', // optional
	 * 			'DIRECTION' => \CCrmActivityDirection::Incoming // optional
	 * 		),
	 * 		array(
	 *			'NAME' => 'Outgoing activity', // required
	 * 			'PROVIDER_TYPE_ID' => 'MY_TYPE', // optional
	 * 			'DIRECTION' => \CCrmActivityDirection::Outgoing // optional
	 * 		)
	 * )
	 */
	public static function getTypesFilterPresets()
	{
		return array();
	}

	public static function getTypeId(array $activity)
	{
		return isset($activity['PROVIDER_TYPE_ID']) ? (string)$activity['PROVIDER_TYPE_ID'] : '';
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		$name = '';
		$types = static::getTypes();
		foreach ($types as $type)
		{
			if (isset($type['PROVIDER_TYPE_ID']) && $type['PROVIDER_TYPE_ID'] === $providerTypeId)
			{
				$name = isset($type['NAME']) ? (string)$type['NAME'] : '';

				if (
					isset($type['DIRECTIONS'])
					&& is_array($type['DIRECTIONS'])
					&& array_key_exists($direction, $type['DIRECTIONS'])
				)
					$name = (string)$type['DIRECTIONS'][$direction];
				break;
			}
		}
		return $name;
	}

	public static function getTypeDirections($providerTypeId = null)
	{
		$directions = array();
		$types = static::getTypes();
		foreach ($types as $type)
		{
			if (isset($type['PROVIDER_TYPE_ID']) && $type['PROVIDER_TYPE_ID'] === $providerTypeId && isset($type['DIRECTIONS']))
			{
				$directions = is_array($type['DIRECTIONS']) ? $type['DIRECTIONS'] : array();
				break;
			}
		}
		return $directions;
	}

	/**
	 * @param string $action Action ADD or UPDATE.
	 * @param array $fields Activity fields.
	 * @param int $id Activity ID.
	 * @param null|array $params Additional parameters.
	 * @return Main\Result Check fields result.
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		return new Main\Result();
	}
	
	public static function canUseCalendarEvents($providerTypeId = null)
	{
		return false;
	}
	
	public static function canKeepCompletedInCalendar($providerTypeId = null)
	{
		return false;
	}
	
	public static function canKeepReassignedInCalendar($providerTypeId = null)
	{
		return false;
	}

	public static function canCompleteOnView($providerTypeId = null)
	{
		return false;
	}

	public static function getCommunicationType($providerTypeId = null)
	{
		return static::COMMUNICATION_TYPE_UNDEFINED;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @param array|null $replace Message replace templates.
	 * @return string
	 */
	public static function generateSubject($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined, array $replace = null)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BASE_SUBJECT', $replace);
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		if ($direction === \CCrmActivityDirection::Incoming)
			return false;

		return true;
	}
	/**
	 * @return int
	 */
	public static function prepareToolbarButtons(array &$buttons, array $params = null)
	{
		return 0;
	}

	/**
	 * @param array $params Activity params.
	 * @return array Actions list.
	 * Example:
	 * array(
	 * 		array(
	 * 			'NAME' => 'My activity',
	 * 			'TYPE_ID' => \CCrmActivityType::Provider
	 * 			'PROVIDER_ID' => '<PROVIDER_ID>',
	 * 			'PROVIDER_TYPE_ID' => '<PROVIDER_TYPE_ID>',
	 * 		)
	 * )
	 */
	public static function getPlannerActions(array $params = null)
	{
		return array();
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Title.
	 */
	public static function getPlannerTitle(array $activity)
	{
		return '';	
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Rendered html view for specified mode.
	 */
	public static function renderView(array $activity)
	{
		return '';
	}

	/**
	 * @param array $activity Activity data.
	 * @return array Fields.
	 */
	public static function getFieldsForEdit(array $activity)
	{
		return array(
			array(
				'LABEL' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_COMMUNICATIONS_LABEL'),
				'TYPE' => 'COMMUNICATIONS'
			)
		);
	}

	public static function getAdditionalFieldsForEdit(array $activity)
	{
		return array(
			array('TYPE' => 'DESCRIPTION'),
			array('TYPE' => 'PROVIDER_TYPE'),
			array('TYPE' => 'FILE'),
			array('TYPE' => 'DEAL'),
			array('TYPE' => 'RESPONSIBLE'),
		);
	}

	/**
	 * @param array $activity
	 */
	public static function fillDefaultActivityFields(array &$activity)
	{
		
	}

	/**
	 * 
	 * @return bool
	 */
	public static function checkOwner()
	{
		return true;
	}

	/**
	 * @param array $activity Activity data.
	 * @param array $formData Request post data.
	 * @return Main\Result Post result.
	 */
	public static function postForm(array &$activity, array $formData)
	{
		return new Main\Result();
	}

	/**
	 * @param int $ID Activity ID.
	 * @param array $data Activity data.
	 * @return Main\Result Save result.
	 */
	public static function saveAdditionalData($ID, array $data)
	{
		return new Main\Result();
	}
	/**
	 * @param int $entityId Associated entity id.
	 * @param array $activity Activity data.
	 * @param array $options Update operation options.
	 * @return Main\Result Operation result.
	 */
	public static function updateAssociatedEntity($entityId, array $activity, array $options = array())
	{
		return new Main\Result();
	}

	/**
	 * @param int $entityId Associated entity id.
	 * @param array $activity Activity data.
	 * @param array $options Delete operation options.
	 * @return Main\Result Operation result.
	 */
	public static function deleteAssociatedEntity($entityId, array $activity, array $options = array())
	{
		return new Main\Result();
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @return bool
	 */
	public static function canUseLiveFeedEvents($providerTypeId = null)
	{
		return false;
	}

	/**
	 * @param int $entityId Associated entity id.
	 * @param array $activity Activity data.
	 * @param array $logFields Live feed log fields.
	 * @return int Log event id.
	 */
	public static function createLiveFeedLog($entityId, array $activity, array &$logFields)
	{
		return 0;
	}

	/**
	 * @param int $entityId Associated entity id.
	 * @param array $activity Activity data.
	 * @param int $userId Target user id.
	 * @return null|bool
	 */
	public static function checkCompletePermission($entityId, array $activity, $userId)
	{
		return null;
	}
	
	public static function canUseCommunicationStatistics($statisticsType)
	{
		$all = static::getSupportedCommunicationStatistics();
		return in_array($statisticsType, $all);
	}
	
	public static function getSupportedCommunicationStatistics()
	{
		return array();
	}

	public static function getResultSources()
	{
		return array(
			CommunicationStatistics::DEFAULT_SOURCE => Loc::getMessage('CRM_ACTIVITY_PROVIDER_BASE_SOURCE_NONE')
		);
	}

	/**
	 * @param array $activityFields
	 * return null
	 */
	public static function onAfterAdd($activityFields)
	{

	}
}