<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Model\Request;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ObjectManager;
use Postpay\Payment\Model\Adapter\ApiAdapter;

/**
 * Add customer information to checkout request.
 */
class Customer
{
    /**
     * Build request.
     *
     * @param CustomerInterface $customer
     *
     * @return array
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function build(CustomerInterface $customer)
    {
        switch ($customer->getGender()) {
            case 1:
                $gender = 'male';
                break;
            case 2:
                $gender = 'female';
                break;
            case 3:
            default:
                $gender = 'other';
        }

        $data = [
            'email' => $customer->getEmail(),
            'id' => $customer->getId(),
            'first_name' => $customer->getFirstname() ?: '',
            'last_name' => $customer->getLastname() ?: '',
            'gender' => $gender,
            'account' => 'existing'
        ];

        if ($dateOfBirth = $customer->getDob()) {
            $data['date_of_birth'] = ApiAdapter::date($dateOfBirth);
        }

        if ($createdAt = $customer->getcreatedAt()) {
            $data['date_joined'] = ApiAdapter::datetime($createdAt);
        }

        if ($defaultAddressId = $customer->getDefaultShipping()) {
            $objectManager = ObjectManager::getInstance();
            /** @var AddressRepositoryInterface $addressRepository */
            $addressRepository = $objectManager->get(
                AddressRepositoryInterface::class
            );
            /** @var \Magento\Customer\Model\Data\Address $defaultAddress */
            $defaultAddress = $addressRepository->getById($defaultAddressId);
            $data['default_address'] = CustomerAddress::build($defaultAddress);
        }
        return $data;
    }
}
