<?php
namespace InvisibleCommerce\ShippedSuite\Setup;

use InvisibleCommerce\ShippedSuite\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private State $state;
    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private Filesystem $filesystem;

    public function __construct(
        State $state,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        Filesystem $filesystem
    ) {
        $this->state = $state;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->filesystem = $filesystem;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->state->emulateAreaCode(
            Area::AREA_GLOBAL,
            [$this, "addProducts"],
        );
    }

    public function addProducts()
    {
        foreach (Product::MANAGE_PRODUCTS as $productInfo) {
            $this->createProduct($productInfo);
        }
    }

    private function createProduct(array $productInfo): void
    {
        $product = $this->productFactory->create();
        $product->setSku($productInfo['sku']);
        $product->setName($productInfo['name']);
        $product->setTypeId(Type::TYPE_VIRTUAL);
        $product->setVisibility(4);
        $product->setPrice(0);
        $product->setAttributeSetId(4);
        $product->setStatus(Status::STATUS_ENABLED);
        $product->setWeight(0);

        $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $product->addImageToMediaGallery(
            $mediaPath . 'shipped_suite/' . $productInfo['image'],
            ['image', 'small_image', 'thumbnail'],
            false,
            false
        );

        $product->setStockData([
            'use_config_manage_stock' => 0,
            'is_in_stock' => 1,
            'qty' => 0,
            'manage_stock' => 0,
            'use_config_notify_stock_qty' => 0
        ]);

        $this->productRepository->save($product);
    }
}
