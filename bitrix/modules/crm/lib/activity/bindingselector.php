<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Integrity\ActualEntitySelector;
use Bitrix\Main\ArgumentException;
use Bitrix\Crm\Integrity\DuplicateCriterion;

/**
 * Class BindingSelector
 * @package Bitrix\Crm\Activity
 */
class BindingSelector
{
	/** @var array Binding list */
	protected $bindings = array();

	/** @var ActualEntitySelector $entitySelector */
	protected $entitySelector;


	/**
	 * Create instance of class.
	 *
	 * @param array $fields Entity fields
	 * @param array $searchParameters Search parameters for searching duplicates
	 * @return static
	 * @throws ArgumentException
	 */
	public static function create(array $fields, array $searchParameters)
	{
		$selector = ActualEntitySelector::create($fields, $searchParameters);
		return new static($selector);
	}

	/**
	 * Sort bindings.
	 * First - deal, second - lead, next - other.
	 *
	 * @param array $bindings Binding list
	 * @return array
	 */
	public static function sortBindings(array $bindings)
	{
		$list = array();
		$uniqueList = array();
		foreach ($bindings as $binding)
		{
			$uniqueList[$binding['OWNER_TYPE_ID']] = $binding;
		}

		$orderByTypeId = array(\CCrmOwnerType::Deal, \CCrmOwnerType::Lead);
		foreach ($orderByTypeId as $typeId)
		{
			if (!isset($uniqueList[$typeId]))
			{
				continue;
			}

			$list[] = $uniqueList[$typeId];
			unset($uniqueList[$typeId]);
		}

		ksort($uniqueList);
		foreach ($uniqueList as $binding)
		{
			$list[] = $binding;
		}

		return $list;
	}

	/**
	 * Constructor.
	 *
	 * @param ActualEntitySelector $selector Actual entity selector.
	 */
	public function __construct(ActualEntitySelector $selector = null)
	{
		if ($selector)
		{
			$this->setEntitySelector($selector);
		}
	}

	/**
	 * Return true if bindings are found.
	 *
	 * @return bool
	 */
	public function hasBindings()
	{
		return count($this->bindings) > 0;
	}

	/**
	 * Get binding entity id by entity type id.
	 *
	 * @param integer $entityTypeId Entity type id
	 * @return integer|null
	 */
	public function getBindingEntityId($entityTypeId)
	{
		foreach ($this->bindings as $binding)
		{
			if ($binding['OWNER_TYPE_ID'] == $entityTypeId)
			{
				return $binding['OWNER_ID'];
			}
		}

		return null;
	}

	/**
	 * Get ordered bindings.
	 *
	 * @return array
	 */
	public function getBindings()
	{
		return $this->bindings;
	}

	/**
	 * Get actual entity selector.
	 *
	 * @return ActualEntitySelector
	 */
	public function getEntitySelector()
	{
		return $this->entitySelector;
	}

	/**
	 * Set actual entity selector.
	 *
	 * @param ActualEntitySelector $entitySelector
	 */
	protected function setEntitySelector(ActualEntitySelector $entitySelector)
	{
		$this->entitySelector = $entitySelector;
		$this->bindings = self::findBindings($this->entitySelector);
	}

	/**
	 * Return list of bindings by ActualEntitySelector.
	 *
	 * @param ActualEntitySelector $selector Entity Selector
	 * @return array
	 */
	public static function findBindings(ActualEntitySelector $selector)
	{
		$list = array();
		$isDealAdded = false;
		$isReturnCustomerLeadAdded = false;

		/**
		 * append company
		 */
		if ($selector->getCompanyId())
		{
			if ($selector->getCompanyDealId())
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
					'OWNER_ID' => $selector->getCompanyDealId()
				);

				$isDealAdded = true;
			}
			elseif ($selector->getCompanyReturnCustomerLeadId())
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
					'OWNER_ID' => $selector->getCompanyReturnCustomerLeadId()
				);

				$isReturnCustomerLeadAdded = true;
			}

			$list[] = array(
				'OWNER_TYPE_ID' => \CCrmOwnerType::Company,
				'OWNER_ID' => $selector->getCompanyId()
			);
		}

		/**
		 * append contact
		 */
		if (!$selector->getCompanyId())
		{
			// if no company
			$isContactNeedAdd = true;
		}
		else if ($selector->getCompanyId() === $selector->getContactCompanyId())
		{
			// if company found and it is related with contact
			$isContactNeedAdd = true;
		}
		else
		{
			$isContactNeedAdd = false;
		}

		if ($selector->getContactId() && $isContactNeedAdd)
		{
			if ($selector->getContactDealId() && !$isDealAdded)
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
					'OWNER_ID' => $selector->getContactDealId()
				);

				$isDealAdded = true;
			}

			if ($selector->getContactReturnCustomerLeadId() && !$isReturnCustomerLeadAdded && !$isDealAdded)
			{
				$list[] = array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
					'OWNER_ID' => $selector->getContactReturnCustomerLeadId()
				);
			}

			$list[] = array(
				'OWNER_TYPE_ID' => \CCrmOwnerType::Contact,
				'OWNER_ID' => $selector->getContactId()
			);
		}


		/**
		 * append lead
		 */
		$isLeadNeedAdd = !$selector->getCompanyId() && !$selector->getContactId();
		if ($selector->getLeadId() && $isLeadNeedAdd)
		{
			$list[] = array(
				'OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
				'OWNER_ID' => $selector->getLeadId()
			);
		}

		return self::sortBindings($list);
	}
}