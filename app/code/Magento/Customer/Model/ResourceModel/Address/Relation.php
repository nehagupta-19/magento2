<?php
/**
 * Customer address entity resource model
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Model\ResourceModel\Address;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationInterface;

/**
 * Class represents save operations for customer address relations
 */
class Relation implements RelationInterface
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(\Magento\Customer\Model\CustomerFactory $customerFactory)
    {
        $this->customerFactory = $customerFactory;
    }

    /**
     * Process object relations
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return void
     */
    public function processRelation(\Magento\Framework\Model\AbstractModel $object)
    {
        /**
         * @var $object \Magento\Customer\Model\Address
         */
        if (!$object->getIsCustomerSaveTransaction() && $object->getId()) {
            $customer = $this->customerFactory->create()->load($object->getCustomerId());
            $changedAddresses = [];

            if ($object->getIsDefaultBilling()) {
                $changedAddresses['default_billing'] = $object->getId();
            } elseif ($customer->getDefaultBillingAddress()
                && (int)$customer->getDefaultBillingAddress()->getId() === (int)$object->getId()
                && !$object->getIsDefaultBilling()
            ) {
                $changedAddresses['default_billing'] = null;
            }

            if ($object->getIsDefaultShipping()) {
                $changedAddresses['default_shipping'] = $object->getId();
            } elseif ($customer->getDefaultShippingAddress()
                && (int)$customer->getDefaultShippingAddress()->getId() === (int)$object->getId()
                && !$object->getIsDefaultShipping()
            ) {
                $changedAddresses['default_shipping'] = null;
            }

            if ($changedAddresses) {
                $customer->getResource()->getConnection()->update(
                    $customer->getResource()->getTable('customer_entity'),
                    $changedAddresses,
                    $customer->getResource()->getConnection()->quoteInto('entity_id = ?', $customer->getId())
                );
            }
        }
    }

    /**
     * Checks if address has chosen as default and has had an id
     *
     * @deprecated Is not used anymore due to changes in logic of save of address.
     *             If address was default and becomes not default than default address id for customer must be
     *             set to null
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    protected function isAddressDefault(\Magento\Framework\Model\AbstractModel $object)
    {
        return $object->getId() && ($object->getIsDefaultBilling() || $object->getIsDefaultShipping());
    }
}
