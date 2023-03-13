<?php

namespace InvisibleCommerce\ShippedSuite\Setup;

use InvisibleCommerce\ShippedSuite\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    private ProductRepositoryInterface $productRepository;
    private Filesystem $filesystem;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Filesystem $filesystem
    ) {
        $this->productRepository = $productRepository;
        $this->filesystem = $filesystem;
    }

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        foreach (Product::MANAGE_PRODUCTS as $productInfo) {
            $this->deleteProduct($productInfo);
        }
        $this->deleteImages();

        $installer->endSetup();
    }

    private function deleteProduct(array $productInfo): void
    {
        $this->productRepository->deleteById($productInfo['sku']);
    }

    private function deleteImages(): void
    {
        $type = DirectoryList::MEDIA;
        $tempFilePath = $this->filesystem->getDirectoryRead($type)->getAbsolutePath() . 'shipped_suite/';

        foreach (['shield.png', 'green.png'] as $file) {
            $filePath = $tempFilePath . $file;
            if (file_exists($filePath)) {
                unlink($tempFilePath);
            }
        }

        rmdir($tempFilePath);
    }
}
