<?php

declare(strict_types=1);

namespace Smile\ProductLabel\Block\ProductLabel;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ProductLabel\Api\Data\ProductLabelInterface;
use Smile\ProductLabel\Model\ImageLabel\Image;
use Smile\ProductLabel\Model\ResourceModel\ProductLabel\CollectionFactory as ProductLabelCollectionFactory;

/**
 * Class ProductLabel template
 */
class ProductLabel extends Template implements IdentityInterface
{
    protected Registry $registry;

    protected ProductLabelCollectionFactory $productLabelCollectionFactory;

    protected Image $imageHelper;

    protected ?ProductInterface $product;

    private CacheInterface $cache;

    private StoreManagerInterface $storeManager;

    /**
     * ProductLabel constructor.
     *
     * @param Context $context Block context
     * @param Registry $registry Registry
     * @param Image $imageHelper Image Helper
     * @param ProductLabelCollectionFactory $productLabelCollectionFactory Product Label Collection Factory
     * @param CacheInterface $cache Cache Interface
     * @param ?ProductInterface $product Product interface
     * @param array $data Block data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Image $imageHelper,
        ProductLabelCollectionFactory $productLabelCollectionFactory,
        CacheInterface $cache,
        ?ProductInterface $product,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->imageHelper = $imageHelper;
        $this->productLabelCollectionFactory = $productLabelCollectionFactory;
        $this->cache = $cache;
        $this->storeManager = $context->getStoreManager();
        $this->product = $product;
        parent::__construct($context, $data);
    }

    /**
     * Get Current View
     */
    public function getCurrentView(): int
    {
        $view = ProductLabelInterface::PRODUCTLABEL_DISPLAY_LISTING;

        /** @var \Magento\Framework\View\Element\AbstractBlock $request */
        $request = $this->getRequest();

        /** @var \Magento\Framework\Webapi\Rest\Request\Proxy $request */
        $controller = $request->getControllerName();

        if ($controller == 'product') {
            $view = ProductLabelInterface::PRODUCTLABEL_DISPLAY_PRODUCT;
        }

        return $view;
    }

    /**
     * Get labels block wrapper class
     */
    public function getWrapperClass(): string
    {
        $class = 'listing';

        if ($this->getCurrentView() === ProductLabelInterface::PRODUCTLABEL_DISPLAY_PRODUCT) {
            $class = 'product';
        }

        return $class;
    }

    /**
     * Set Product
     *
     * @param ProductInterface $product The product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get Product
     *
     * @return Product|ProductInterface|null
     */
    public function getProduct()
    {
        if (empty($this->product->getId())) {
            $this->product = $this->registry->registry('current_product');
        }

        return $this->product;
    }

    /**
     * Get Attributes Of Current Product
     *
     * @return array
     */
    public function getAttributesOfCurrentProduct(): array
    {
        $attributesList = [];
        $attributeIds   = array_column($this->getProductLabelsList(), 'attribute_id');
        $product = $this->getProduct();

        // @phpstan-ignore-next-line
        $collection = $product->getResourceCollection();
        $productEntity  = $collection->getEntity();

        foreach ($attributeIds as $attributeId) {
            $attribute = $productEntity->getAttribute($attributeId);
            if ($attribute) {
                $optionIds = $this->getProduct()->getCustomAttribute($attribute->getAttributeCode());

                $attributesList[$attribute->getId()] = [
                    'id'      => $attribute->getId(),
                    'label'   => $attribute->getFrontend()->getLabel(),
                    'options' => $optionIds ? $optionIds->getValue() : '',
                ];
            }
        }

        return $attributesList;
    }

    /**
     * Check if product has product labels
     *
     * If it has, return an array of product labels
     *
     * @return array
     */
    public function getProductLabels(): array
    {
        $productLabels     = [];
        $productLabelList  = $this->getProductLabelsList();
        $attributesProduct = $this->getAttributesOfCurrentProduct();

        foreach ($productLabelList as $productLabel) {
            $attributeIdLabel = $productLabel['attribute_id'];
            $optionIdLabel    = $productLabel['option_id'];
            foreach ($attributesProduct as $attribute) {
                if (isset($attribute['id']) && ($attributeIdLabel == $attribute['id'])) {
                    $options = $attribute['options'] ?? [];
                    if (!is_array($options)) {
                        $options = explode(',', $options);
                    }
                    if (
                        in_array($optionIdLabel, $options) &&
                        in_array($this->getCurrentView(), $productLabel['display_on'])
                    ) {
                        $productLabel['class'] = $this->getCssClass($productLabel);
                        $productLabel['image'] = $this->getImageUrl($productLabel['image']);
                        $class = $this->getCssClass($productLabel);
                        $productLabels[$class][] = $productLabel;
                    }
                }
            }
        }

        return $productLabels;
    }

    /**
     * Get Image URL of product label
     *
     * @param string $imageName Image Name
     */
    public function getImageUrl(string $imageName): string
    {
        return $this->imageHelper->getBaseUrl() . '/' . $imageName;
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $identities = [];

        /** @var IdentityInterface|null $product */
        $product = $this->getProduct();

        if ($product === null) {
            return [\Smile\ProductLabel\Model\ProductLabel::CACHE_TAG];
        }

        return array_merge(
            $identities,
            $product->getIdentities(),
            [\Smile\ProductLabel\Model\ProductLabel::CACHE_TAG]
        );
    }

    /**
     * Fetch proper css class according to current label and view.
     *
     * @param array $productLabel A product Label
     */
    private function getCssClass(array $productLabel): string
    {
        $class = '';

        if ($this->getCurrentView() === ProductLabelInterface::PRODUCTLABEL_DISPLAY_PRODUCT) {
            $class = $productLabel['position_product_view'] . ' product';
        }

        if ($this->getCurrentView() === ProductLabelInterface::PRODUCTLABEL_DISPLAY_LISTING) {
            $class = $productLabel['position_category_list'] . ' category';
        }

        return $class;
    }

    /**
     * Fetch product labels list : the list of all enabled product labels.
     *
     * Fetched only once and put in cache.
     *
     * @return array
     */
    private function getProductLabelsList(): array
    {
        $storeId          = $this->getStoreId();
        $cacheKey         = 'smile_productlabel_frontend_' . $storeId;
        $productLabelList = $this->cache->load($cacheKey);

        if (is_string($productLabelList)) {
            $productLabelList = json_decode($productLabelList, true);
        }

        if ($productLabelList === false) {
            /** @var \Smile\ProductLabel\Model\ResourceModel\ProductLabel\CollectionFactory */
            $productLabelsCollection = $this->productLabelCollectionFactory->create();

            // @phpstan-ignore-next-line
            $productLabelList = $productLabelsCollection
                ->addStoreFilter($storeId)
                ->addFieldToFilter('is_active', true)
                ->getData();

            $productLabelList = array_map(function ($label) {
                $label['display_on'] = explode(',', $label['display_on']);

                return $label;
            }, $productLabelList);

            $this->cache->save(
                json_encode($productLabelList),
                $cacheKey,
                [\Smile\ProductLabel\Model\ProductLabel::CACHE_TAG]
            );
        }

        return $productLabelList;
    }

    /**
     * Get current store Id.
     */
    private function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
}
