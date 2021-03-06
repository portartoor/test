<?
/**
 * Class implements all further interactions with "extranet" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Intranet;

use Bitrix\Main\Loader;

final class Department extends \Bitrix\Tasks\Integration\Intranet
{
	/**
	 * Returns a list of department IDs that are under $userId direction
	 *
	 * @param int $userId
	 * @param bool $recursive
	 * @return array
	 */
	public static function getSubordinateIds($userId = 0, $recursive = false)
	{
		$result = array();

		if(static::includeModule())
		{
			if(!$userId)
			{
				$userId = \Bitrix\Tasks\Util\User::getId();
			}
			if(!$userId)
			{
				return $result;
			}

			$result = \CIntranetUtils::getSubordinateDepartments($userId, $recursive);
		}

		return $result;
	}

	/**
	 * Returns a list of sub-department IDs for the department $id
	 *
	 * @param $id
	 * @param bool $direct
	 * @param bool $flat
	 * @return array
	 */
	public static function getSubIds($id, $direct = true, $flat = false)
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result;
		}

		if($direct)
		{
			$result = \CIntranetUtils::getSubDepartments($id);
		}
		else
		{
			$result = \CIntranetUtils::getDeparmentsTree($id, $flat);
		}

		if(!is_array($result))
		{
			$result = array();
		}

		return $result;
	}

	/**
	 * Returns basic data for department IDs passed
	 *
	 * @param array $departmentIds
	 * @return array
	 */
	public static function getData(array $departmentIds)
	{
		$result = array();

		if(!static::includeModule() || empty($departmentIds))
		{
			return $result; // no module = no departments
		}

		$res = static::getIBlockSections();
		$sections = static::replaceIBSField($res['SECTIONS']);
		if(!empty($sections))
		{
			foreach($sections as $item)
			{
				if(in_array($item['ID'], $departmentIds))
				{
					$result[$item['ID']] = $item;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the complete company structure
	 *
	 * @return array
	 */
	public static function getCompanyStructure()
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result; // no module = no departments
		}

		$result = static::getIBlockSections();
		return static::replaceIBSField($result['SECTIONS']);
	}

	private static function getIBlockSections(array $select = array())
	{
		$result = array("SECTIONS" => array(), "STRUCTURE" => array());

		if(!static::includeModule() || !Loader::includeModule('iblock') || !empty($ids))
		{
			return $result;
		}

		$iblockId = intval(\COption::getOptionInt('intranet', 'iblock_structure'));
		if(!$iblockId)
		{
			return $result;
		}

		$filter = array('IBLOCK_ID' => $iblockId);
		$select = array_merge($select, array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN'));

		$cache = new \CPHPCache();
		$cacheDir = '/tasks/subordinatedeps';

		$structure = array();
		$sections = array();

		if($cache->initCache(32100113, md5(serialize($filter)), $cacheDir))
		{
			$vars = $cache->getVars();
			$sections = $vars["SECTIONS"];
			$structure = $vars["STRUCTURE"];
		}
		elseif ($cache->startDataCache())
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->startTagCache($cacheDir);
			$CACHE_MANAGER->registerTag("iblock_id_".$iblockId);

			$res = \CIBlockSection::getList(
				array('left_margin' => 'asc'), 		// order as full expanded tree
				$filter,
				false, 								// don't count
				$select
			);

			while ($item = $res->fetch())
			{
				$iblockSectionID = intval($item['IBLOCK_SECTION_ID']);

				if (!is_array($structure[$iblockSectionID]))
				{
					$structure[$iblockSectionID] = array($item['ID']);
				}
				else
				{
					$structure[$iblockSectionID][] = $item['ID'];
				}

				$sections[$item['ID']] = $item;
			}
			$CACHE_MANAGER->endTagCache();
			$cache->endDataCache(array("SECTIONS" => $sections, "STRUCTURE" => $structure));
		}

		$result['SECTIONS'] = $sections;
		$result['STRUCTURE'] = $structure;

		return $result;
	}

	private static function replaceIBSField(array $sections)
	{
		foreach($sections as $k => $v)
		{
			$sections[$k]['PARENT_ID'] = intval($sections[$k]['IBLOCK_SECTION_ID']);
			unset($sections[$k]['IBLOCK_SECTION_ID']);

			$sections[$k]['L'] = intval($sections[$k]['LEFT_MARGIN']);
			unset($sections[$k]['LEFT_MARGIN']);

			$sections[$k]['R'] = intval($sections[$k]['RIGHT_MARGIN']);
			unset($sections[$k]['RIGHT_MARGIN']);
		}

		return $sections;
	}
}