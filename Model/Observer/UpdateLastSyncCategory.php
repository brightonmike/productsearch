<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 * @method setIsProductSyncScheduled($flag)
 * @method bool getIsProductSyncScheduled()
 */

namespace Klevu\Search\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdateLastSyncCategory implements ObserverInterface
{

    /**
     * @var \Klevu\Search\Model\Product\MagentoProductActionsInterface
     */
    protected $_magentoProductActionsInterface;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_magentoFrameworkFilesystem;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_frameworkModelResource;

    public function __construct(
        \Klevu\Search\Model\Product\MagentoProductActionsInterface $magentoProductActionsInterface,
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Magento\Framework\App\ResourceConnection $frameworkModelResource
    )
    {

        $this->_magentoProductActionsInterface = $magentoProductActionsInterface;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperData = $searchHelperData;
        $this->_frameworkModelResource = $frameworkModelResource;
    }

    /**
     * When products are updated in bulk, update products so that they will be synced.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $category_ids[] = $observer->getEvent()->getCategory()->getId();
            if (empty($category_ids)) {
                return;
            }

            $category_ids = implode(',', $category_ids);
            $where = sprintf("product_id IN(%s) OR parent_id IN(%s)", $category_ids, $category_ids);
            $resource = $this->_frameworkModelResource;
            $resource->getConnection('core_write')
                ->update(
                    $resource->getTableName('klevu_product_sync'),
                    ['last_synced_at' => '0'],
                    $where
                );

            $product_ids = $observer->getEvent()->getCategory()->getProductCollection()->getAllIds();
            if (empty($product_ids)) {
                return;
            }
            $this->_magentoProductActionsInterface->updateSpecificProductIds($product_ids);
        } catch (\Exception $e) {
            $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Error while updating date for category in klevu product sync:\n%s", $e->getMessage()));
        }
    }
}
