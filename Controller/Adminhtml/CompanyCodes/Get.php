<?php
/**
 * @category    ClassyLlama
 * @copyright   Copyright (c) 2018 Classy Llama Studios, LLC
 * @author      sean.templeton
 */

namespace ClassyLlama\AvaTax\Controller\Adminhtml\CompanyCodes;

use ClassyLlama\AvaTax\Exception\AvataxConnectionException;
use Magento\Framework\DataObject;

class Get extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;

    /**
     * @var \ClassyLlama\AvaTax\Framework\Interaction\Rest\Company
     */
    protected $company;

    /**
     * @var \ClassyLlama\AvaTax\Helper\Config
     */
    private $config;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultPageFactory,
        \ClassyLlama\AvaTax\Framework\Interaction\Rest\Company $company,
        \ClassyLlama\AvaTax\Helper\Config $config
    )
    {
        parent::__construct( $context );
        $this->resultPageFactory = $resultPageFactory;
        $this->company = $company;
        $this->config = $config;
    }

    public function execute()
    {
        $companies = [];
        $postValue = $this->getRequest()->getPostValue();
        $isProduction = (bool)$this->getRequest()->getParam( 'mode' );
        $resultJson = $this->resultPageFactory->create();
        $scope = isset( $postValue['scope'] ) ? $postValue['scope'] : null;
        $scopeType = $postValue['scope_type'] === 'global' ? \Magento\Store\Model\ScopeInterface::SCOPE_STORE : $postValue['scope_type'];
        $currentCompanyId = $this->config->getCompanyId( $scope, $scopeType, $isProduction );

        try
        {
            if (!isset( $postValue['license_key'] ))
            {
                $postValue['license_key'] = $this->config->getLicenseKey( $scope, $scopeType, $isProduction );
            }

            $companies = $this->company->getCompaniesWithSecurity(
                $postValue['account_number'],
                $postValue['license_key'],
                null,
                $isProduction
            );
        }
        catch (AvataxConnectionException $e)
        {
        }

        if (\count( $companies ) === 0)
        {
            return $resultJson->setData(
                [
                    'companies'    => [
                        [
                            'company_id'   => null,
                            'company_code' => null,
                            'name'         => __( 'No available companies' ),
                        ]
                    ],
                    'current_id' => $currentCompanyId
                ]
            );
        }

        return $resultJson->setData(
            [
                'companies'    => array_map(
                    function ( $company ) {
                        /** @var DataObject $company */
                        return [
                            'company_id'   => $company->getData( 'id' ),
                            'company_code' => $company->getData( 'company_code' ),
                            'name'         => $company->getData( 'name' ),
                        ];
                    },
                    $companies
                ),
                'current_id' => $currentCompanyId
            ]
        );
    }
}