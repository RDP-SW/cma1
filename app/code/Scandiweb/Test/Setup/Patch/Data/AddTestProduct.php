<?php
/**
 * @category Scandiweb
 * @package Scandiweb_Test
 * @author Rizki Prasetyo <info@scandiweb.com>
 * @copyright Copyright (c) 2023 Scandiweb, Ltd (http://scandiweb.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

namespace Scandiweb\Test\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * class AddTestProduct
 * @package Scandiweb\Test\Setup\Patch\Data
 *
 * Data patch to create new Test Product with stock and assign existing category
 */
class AddTestProduct implements DataPatchInterface
{
    const TEST_PRODUCT_SKU = 'TEST_PRODUCT_SKU';
    const CATEGORY_NAME = 'Men';

    /**
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productInterfaceFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var State
     */
    protected State $appState;

    /**
     * @var EavSetup
     */
    protected EavSetup $eavSetup;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected CategoryLinkManagementInterface $categoryLink;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $categoryCollectionFactory;

    /**
     * AddTestProduct constructor
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param EavSetup $eavSetup
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param CategoryLinkManagementInterface $categoryLink
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        ProductInterfaceFactory         $productInterfaceFactory,
        ProductRepositoryInterface      $productRepository,
        State                           $appState,
        StoreManagerInterface           $storeManager,
        EavSetup                        $eavSetup,
        SourceItemInterfaceFactory      $sourceItemFactory,
        SourceItemsSaveInterface        $sourceItemsSaveInterface,
        CategoryLinkManagementInterface $categoryLink,
        CollectionFactory               $categoryCollectionFactory,
    )
    {
        $this->appState = $appState;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
        $this->eavSetup = $eavSetup;
        $this->storeManager = $storeManager;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->categoryLink = $categoryLink;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return void
     * @throws Exception
     */
    public function apply(): void
    {
        // run setup in back-end area
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * @throws StateException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws InputException
     */
    public function execute(): void
    {
        $product = $this->productInterfaceFactory->create();

        if ($product->getIdBySku(self::TEST_PRODUCT_SKU)) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');

        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Test Product')
            ->setSku(self::TEST_PRODUCT_SKU)
            ->setPrice(9.99)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED);

        $product = $this->productRepository->save($product);

        $websiteIDs = [$this->storeManager->getStore()->getWebsiteId()];
        $product->setWebsiteIds($websiteIDs);

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(100);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $sourceItems = [$sourceItem];
        $this->sourceItemsSaveInterface->execute($sourceItems);

        $catIds = $this->categoryCollectionFactory->create()
            ->addFieldToFilter('name', self::CATEGORY_NAME)
            ->getAllIds();
        if (count($catIds)) {
            $this->categoryLink->assignProductToCategories($product->getSku(), $catIds);
        }
    }
}
