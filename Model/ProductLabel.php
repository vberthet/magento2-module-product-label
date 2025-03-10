<?php

declare(strict_types=1);

namespace Smile\ProductLabel\Model;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ProductLabel\Api\Data\ProductLabelInterface;
use Smile\ProductLabel\Model\ImageLabel\FileInfo;
use Smile\ProductLabel\Model\ResourceModel\ProductLabel as ProductLabelResource;

/**
 * Product Label Model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductLabel extends AbstractModel implements IdentityInterface, ProductLabelInterface
{
    public const CACHE_TAG = 'smile_productlabel';

    protected StoreManagerInterface $storeManager;

    private ?ImageUploader $imageUploader;

    protected FileInfo $fileInfo;

    /**
     * Media directory object (writable).
     */
    protected WriteInterface $mediaDirectory;

    /**
     * @var string|array|bool
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * ProductLabel constructor.
     *
     * @param Context $context Context
     * @param Registry $registry Registry
     * @param StoreManagerInterface $storeManager Store Manager
     * @param Filesystem $filesystem FileSystem Helper
     * @param AbstractResource|null $resource Resource
     * @param AbstractDb|null $resourceCollection Resource Collection
     * @param ImageUploader $imageUploader Image uploader
     * @param array $data Object Data
     */
    public function __construct(
        Context               $context,
        Registry              $registry,
        StoreManagerInterface $storeManager,
        Filesystem            $filesystem,
        ?AbstractResource     $resource = null,
        ?AbstractDb           $resourceCollection = null,
        ?ImageUploader        $imageUploader = null,
        array                 $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->imageUploader = $imageUploader;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG];
    }

    /**
     * Get field: is_active.
     */
    public function isActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Get field: product_label_id.
     */
    public function getProductLabelId(): ?int
    {
        return (int) $this->getId();
    }

    /**
     * Get field: store_id.
     *
     * @return int[]
     */
    public function getStores(): array
    {
        $stores = $this->hasData('stores') ? $this->getData('stores') : $this->getData('store_id');

        if (is_numeric($stores)) {
            $stores = [$stores];
        }

        return $stores ?? [];
    }

    /**
     * Get field: name.
     */
    public function getName(): string
    {
        return (string) $this->getData(self::PRODUCTLABEL_NAME);
    }

    /**
     * Get field: attribute_id
     */
    public function getAttributeId(): int
    {
        return (int) $this->getData(self::ATTRIBUTE_ID);
    }

    /**
     * Get field: option_id
     */
    public function getOptionId(): int
    {
        return (int) $this->getData(self::OPTION_ID);
    }

    /**
     * Get field: image
     */
    public function getProductLabelImage(): string
    {
        return (string) $this->getData(self::PRODUCTLABEL_IMAGE);
    }

    /**
     * Get field: position_category_list
     */
    public function getPositionCategoryList(): string
    {
        return (string) $this->getData(self::PRODUCTLABEL_POSITION_CATEGORY_LIST);
    }

    /**
     * Get field: position_product_view
     */
    public function getPositionProductView(): string
    {
        return (string) $this->getData(self::PRODUCTLABEL_POSITION_PRODUCT_VIEW);
    }

    /**
     * Get field: display_on
     *
     * @return array
     */
    public function getDisplayOn(): array
    {
        $values = $this->getData(self::PRODUCTLABEL_DISPLAY_ON);
        if (is_numeric($values)) {
            $values = [$values];
        }

        return $values ? $values : [];
    }

    /**
     * Get Alternative caption
     */
    public function getAlt(): string
    {
        return (string) $this->getData(self::PRODUCTLABEL_ALT);
    }

    /**
     * Set product label status
     *
     * @param bool $status The product label status
     */
    public function setIsActive(bool $status): ProductLabelInterface
    {
        return $this->setData(self::IS_ACTIVE, (bool) $status);
    }

    /**
     * Set product label Id
     *
     * @param int $value The value
     */
    public function setProductLabelId(int $value): ProductLabelInterface
    {
        return $this->setId((int) $value);
    }

    /**
     * Set Name
     *
     * @param string $value The value
     */
    public function setName(string $value): ProductLabelInterface
    {
        return $this->setData(self::PRODUCTLABEL_NAME, (string) $value);
    }

    /**
     * Set attribute Id.
     *
     * @param int $value The attribute Id
     */
    public function setAttributeId(int $value): ProductLabelInterface
    {
        return $this->setData(self::ATTRIBUTE_ID, $value);
    }

    /**
     * Set option Id.
     *
     * @param int $value The option Id
     */
    public function setOptionId(int $value): ProductLabelInterface
    {
        return $this->setData(self::OPTION_ID, $value);
    }

    /**
     * Set Image.
     *
     * @param string $value The product label Image
     */
    public function setImage(string $value): ProductLabelInterface
    {
        return $this->setData(self::PRODUCTLABEL_IMAGE, $value);
    }

    /**
     * Set position_category_list.
     *
     * @param int $value The option Id
     */
    public function setPositionCategoryList(int $value): ProductLabelInterface
    {
        return $this->setData(self::PRODUCTLABEL_POSITION_CATEGORY_LIST, $value);
    }

    /**
     * Set field: position_product_view.
     *
     * @param int $value The position product view
     * @return $this
     */
    public function setPositionProductView(int $value): ProductLabelInterface
    {
        return $this->setData(self::PRODUCTLABEL_IMAGE, $value);
    }

    /**
     * Set field: display_on.
     *
     * @param array $value Field value
     */
    public function setDisplayOn(array $value): ProductLabelInterface
    {
        return $this->setData(self::PRODUCTLABEL_DISPLAY_ON, $value);
    }

    /**
     * Set Alternative Caption
     *
     * @param string $value The value
     */
    public function setAlt(string $value): ProductLabelInterface
    {
        return $this->setData(self::PRODUCTLABEL_ALT, $value);
    }

    /**
     * Populate from array
     *
     * @param array $values Form values
     */
    public function populateFromArray(array $values): void
    {
        $this->setData(self::IS_ACTIVE, (bool) $values['is_active']);
        $this->setData(self::PRODUCTLABEL_NAME, (string) $values['name']);
        $this->setData(self::ATTRIBUTE_ID, (int) $values['attribute_id']);
        $this->setData(self::OPTION_ID, (int) $values['option_id']);
        $this->setData(self::PRODUCTLABEL_IMAGE, $values['image'][0]['name']);
        $this->setData(self::PRODUCTLABEL_POSITION_CATEGORY_LIST, (string) $values['position_category_list']);
        $this->setData(self::PRODUCTLABEL_POSITION_PRODUCT_VIEW, (string) $values['position_product_view']);
        $this->setData(self::PRODUCTLABEL_DISPLAY_ON, implode(',', $values['display_on']));
        $this->setData(self::PRODUCTLABEL_ALT, (string) $values['alt']);
        $this->setData(self::STORE_ID, implode(',', $values['stores'] ?? $values['store_id']));
    }

    /**
     * Get image url
     *
     * @return bool|string
     * @throws LocalizedException
     */
    public function getImageUrl()
    {
        $url   = false;
        $image = $this->getData('image');
        if ($image) {
            if (is_string($image)) {
                /** @var Store $store */
                $store = $this->storeManager->getStore();

                $isRelativeUrl = substr($image, 0, 1) === '/';

                $mediaBaseUrl = $store->getBaseUrl(
                    UrlInterface::URL_TYPE_MEDIA
                );

                $url = $mediaBaseUrl
                    . ltrim(FileInfo::ENTITY_MEDIA_PATH, '/')
                    . '/'
                    . $image;

                if ($isRelativeUrl) {
                    $url = $image;
                }
            }
        }

        return $url;
    }

    /**
     * After save
     *
     * @return $this
     */
    public function afterSave()
    {
        $imageName = $this->getData('image');
        $path = $this->getImageUploader()->getFilePath($this->imageUploader->getBaseTmpPath(), $imageName);

        if ($this->mediaDirectory->isExist($path)) {
            $this->getImageUploader()->moveFileFromTmp($imageName);
        }

        return parent::afterSave();
    }

    /**
     * Construct.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ProductLabelResource::class);
    }

    /**
     * Get image uploader
     */
    private function getImageUploader(): ImageUploader
    {
        if ($this->imageUploader === null) {
            // @phpstan-ignore-next-line
            $this->imageUploader = ObjectManager::getInstance()->get(
                \Smile\ProductLabel\ProductLabelImageUpload::class
            );
        }

        return $this->imageUploader;
    }
}
