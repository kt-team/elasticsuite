<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Model\Relevance\Config\Reader;

use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Config\Scope\Converter;
use Magento\Framework\App\Config\ScopePool;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config\Data\Collection\ScopedFactory;
use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;

/**
 * Configuration reader for Store Container level : Configuration for a given container on a given store
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ContainerStore
{
    /**
     * @var Initial
     */
    protected $initialConfig;

    /**
     * @var \Magento\Store\Model\Config\Converter
     */
    protected $converter;

    /**
     * @var ScopedFactory
     */
    protected $collectionFactory;

    /**
     * @var RequestContainerInterface
     */
    protected $containerInterface;

    /**
     * @var Container
     */
    protected $containerReader;

    /**
     * Constructor
     *
     * @param Initial                   $initialConfig      Initial Configuration
     * @param Converter                 $converter          Configuration Converter
     * @param ScopedFactory             $collectionFactory  Configuration Collection Factory
     * @param RequestContainerInterface $containerInterface Request Containers interface
     * @param Container                 $containerReader    Parent level configuration reader
     * @param StoreManagerInterface     $storeManager       Magento Store Manager interface
     *
     * @internal param \Magento\Framework\App\Config\ScopePool $scopePool Scoped Configuration reader
     */
    public function __construct(
        Initial $initialConfig,
        Converter $converter,
        ScopedFactory $collectionFactory,
        RequestContainerInterface $containerInterface,
        Container $containerReader,
        StoreManagerInterface $storeManager
    ) {
        $this->initialConfig = $initialConfig;
        $this->converter = $converter;
        $this->collectionFactory = $collectionFactory;
        $this->containerInterface = $containerInterface;
        $this->containerReader = $containerReader;
        $this->storeManager = $storeManager;
    }

    /**
     * Read configuration by code
     *
     * @param null|string $code The container code
     *
     * @return array
     */
    public function read($code = null)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        list($containerCode, $storeId) = explode("|", $code);
        $store = $this->storeManager->getStore($storeId);

        $config = array_replace_recursive(
            $this->containerReader->read($containerCode),
            $this->initialConfig->getData("containers|stores|{$store->getCode()}")
        );

        $collection = $this->collectionFactory->create(
            ['scope' => RequestContainerInterface::SCOPE_STORE_CONTAINERS, 'scopeCode' => $code]
        );

        $logger->info("ITS ME THE READER ---> CONTAINER STORE");

        $dbStoreConfig = [];
        foreach ($collection as $item) {
            $dbStoreConfig[$item->getPath()] = $item->getValue();
        }

        $dbStoreConfig = $this->converter->convert($dbStoreConfig);
        $config = array_replace_recursive($config, $dbStoreConfig);

        $logger->info(print_r($config['smile_elasticsuite_relevance'], true));
        $logger->info("THAT WAS THE CONTAINER STORE READER");

        return $config;
    }
}
