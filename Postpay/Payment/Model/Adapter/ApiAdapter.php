<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Model\Adapter;

use DateTime;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;
use Postpay\Exceptions\ApiException;
use Postpay\Payment\Gateway\Config\Config;
use Postpay\PostpayFactory;
use Postpay\Serializers\Date;
use Postpay\Serializers\Decimal;
use Psr\Log\LoggerInterface;

/**
 * Postpay Api Adapter.
 */
class ApiAdapter
{
    /**
     * @var \Postpay\Payment
     */
    protected $client;

    /**
     * @var PostpayFactory
     */
    protected $postpayFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Logger
     */
    protected $customLogger;

    /**
     * Constructor.
     *
     * @param PostpayFactory $postpayFactory
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     */
    public function __construct(
        PostpayFactory $postpayFactory,
        Config $config,
        LoggerInterface $logger,
        Logger $customLogger
    ) {
        $this->postpayFactory = $postpayFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->customLogger = $customLogger;

        if ($this->config->isAvailable()) {
            $this->client = $this->postpayFactory->create([
                'config' => [
                    'merchant_id' => $this->config->getMerchantId(),
                    'secret_key' => $this->config->getSecretKey(),
                    'sandbox' => $this->config->isSandbox(),
                    'client_handler' => 'guzzle'
                ]
            ]);
        }
    }

    /**
     * Send a request and return the response.
     *
     * @param string $method
     * @param string $path
     * @param array $params
     *
     * @return array
     *
     * @throws ApiException
     * @throws LocalizedException
     */
    public function request($method, $path, array $params = [])
    {
        if ($this->client === null) {
            throw new LocalizedException(__('Postpay is not properly configured.'));
        }

        try {
            /** @var \Postpay\Http\Response $response */
            $response = $this->client->request($method, $path, $params);
        } catch (ApiException $e) {
            $this->logger->critical($e->getMessage());
            $response = $e->getResponse();
            throw $e;
        } finally {
            $this->customLogger->debug([
                'path' => $path,
                'request' => $params,
                'response' => $response->json()
            ]);
        }
        return $response->json();
    }

    /**
     * Convert float to JSON serializable instance.
     *
     * @param float $value
     *
     * @return Decimal
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function decimal($value)
    {
        return Decimal::fromFloat($value);
    }

    /**
     * Convert date to JSON serializable instance.
     *
     * @param string $value
     *
     * @return Date
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function date($value)
    {
        return Date::fromDate(new DateTime($value));
    }

    /**
     * Convert datetime to JSON serializable instance.
     *
     * @param string $value
     *
     * @return Date
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function datetime($value)
    {
        return Date::fromDateTime(new DateTime($value));
    }
}
