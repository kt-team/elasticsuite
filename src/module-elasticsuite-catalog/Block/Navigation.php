<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Block;

/**
 * Custom implementation of the navigation block to apply facet coverage rate.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _prepareLayout()
    {
        $this->renderer = $this->getChildBlock('renderer');
        $attributeCodes = array_keys($this->getRequest()->getParams());
        foreach ($this->filterList->getEnsureFilters($this->_catalogLayer, $attributeCodes) as $filter) {
            $filter->apply($this->getRequest());
        }
        $this->getLayer()->apply();

        $this->addFacets();

        return $this;
    }

    /**
     * Append facets to the search requests using the coverage rate defined in admin.
     *
     * @return void
     */
    private function addFacets()
    {
        $productCollection = $this->getLayer()->getProductCollection();
        $countBySetId = $productCollection->getProductCountByAttributeSetId();
        $totalCount = (int)$productCollection->getSize();

        if (count($countBySetId) == 0 ) return;

        $connection = $productCollection->getSelect()->getAdapter();
        $sqlCaseAttributeSet = $connection->getCaseSql('eea.attribute_set_id', $countBySetId, 0);

        $selectSQL = "select ea.attribute_code from eav_attribute as ea
  inner join catalog_eav_attribute as cea ON cea.attribute_id = ea.attribute_id
where exists (
    select 1 from eav_entity_attribute as eea

      where eea.attribute_id = ea.attribute_id

    HAVING sum(100*$sqlCaseAttributeSet/$totalCount)>cea.facet_min_coverage_rate
    )";
        //echo $selectSQL; exit;
        
        $attributeCodes = $connection->fetchCol($selectSQL);

        foreach ($this->filterList->getEnsureFilters($this->_catalogLayer, $attributeCodes) as $filter) {
            $filter->addFacetToCollection();
        }
    }
}
