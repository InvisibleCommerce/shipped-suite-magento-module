<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use InvisibleCommerce\ShippedSuite\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class Install implements DataPatchInterface
{
    const FILES = ['shield.png', 'green.png'];
    private ModuleDataSetupInterface $moduleDataSetup;
    private State $state;
    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private Reader $reader;
    private Filesystem $filesystem;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        State $state,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        Reader $reader,
        Filesystem $filesystem
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->state = $state;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->reader = $reader;
        $this->filesystem = $filesystem;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->moveDirToMediaDir();
        $this->state->emulateAreaCode(
            Area::AREA_GLOBAL,
            [$this, "addProducts"],
        );

        $this->moduleDataSetup->endSetup();
    }

    public function revert()
    {
        $this->deleteImages();
        $this->state->emulateAreaCode(
            Area::AREA_GLOBAL,
            [$this, "deleteProducts"],
        );
    }

    private function moveDirToMediaDir()
    {
        $type = DirectoryList::MEDIA;
        $tempFilePath = $this->filesystem->getDirectoryRead($type)->getAbsolutePath() . 'shipped_suite/';

        $modulePath = $this->reader->getModuleDir('', 'InvisibleCommerce_ShippedSuite');
        foreach (self::FILES as $file) {
            $mediaFile = $modulePath . '/Assets/' . $file;
            if (!file_exists($tempFilePath)) {
                mkdir($tempFilePath, 0777, true);
            }
            $filePath = $tempFilePath . $file;
            if (!file_exists($filePath)) {
                if (file_exists($mediaFile)) {
                    copy($mediaFile, $filePath);
                }
            }
        }
    }

    public function addProducts()
    {
        foreach (Product::MANAGE_PRODUCTS as $productInfo) {
            $this->createProduct($productInfo);
        }
    }

    private function deleteProducts()
    {
        foreach (Product::MANAGE_PRODUCTS as $productInfo) {
            $this->deleteProduct($productInfo);
        }
    }

    private function deleteProduct(array $productInfo): void
    {
        $this->productRepository->deleteById($productInfo['sku']);
    }

    private function deleteImages(): void
    {
        $type = DirectoryList::MEDIA;
        $tempFilePath = $this->filesystem->getDirectoryRead($type)->getAbsolutePath() . 'shipped_suite/';

        foreach (self::FILES as $file) {
            $filePath = $tempFilePath . $file;
            if (file_exists($filePath)) {
                unlink($tempFilePath);
            }
        }

        rmdir($tempFilePath);
    }

    public static function getVersion()
    {
        return '3.0.0';
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
