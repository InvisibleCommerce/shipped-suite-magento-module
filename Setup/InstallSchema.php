<?php
namespace InvisibleCommerce\ShippedSuite\Setup;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    private Reader $reader;
    private Filesystem $filesystem;

    public function __construct(
        Reader $reader,
        Filesystem $filesystem
    ) {
        $this->reader = $reader;
        $this->filesystem = $filesystem;
    }
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->moveDirToMediaDir();
    }

    private function moveDirToMediaDir()
    {
        $type = DirectoryList::MEDIA;
        $tempFilePath = $this->filesystem->getDirectoryRead($type)->getAbsolutePath() . 'shipped_suite/';

        $modulePath = $this->reader->getModuleDir('', 'InvisibleCommerce_ShippedSuite');
        foreach (['shield.png', 'green.png'] as $file) {
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
}
