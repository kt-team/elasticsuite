<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Layer;

/**
 * FilterList customization to support decimal filters.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilterList extends \Magento\Catalog\Model\Layer\FilterList
{
    /**
     * Boolean filter name
     */
    const BOOLEAN_FILTER = 'boolean';

    protected $_loadedFilters = [];

    /**
     * {@inheritDoc}
     */
    protected function getAttributeFilterClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $filterClassName = parent::getAttributeFilterClass($attribute);

        if ($attribute->getBackendType() == 'varchar' && $attribute->getFrontendClass() == 'validate-number') {
            $filterClassName = $this->filterTypes[self::DECIMAL_FILTER];
        }

        if (($attribute->getFrontendInput() == 'boolean')
            && ($attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean')
            && isset($this->filterTypes[self::BOOLEAN_FILTER])) {
            $filterClassName = $this->filterTypes[self::BOOLEAN_FILTER];
        }

        return $filterClassName;
    }


    public function getEnsureFilters(\Magento\Catalog\Model\Layer $layer, array $attributeCodes = [])
    {
        $toAdd = array_diff($attributeCodes, $this->_loadedFilters);

        if (count($toAdd)) {
            if (!isset($this->filters[-1])) {
                $this->filters = [
                    -1 => $this->objectManager->create($this->filterTypes[self::CATEGORY_FILTER], ['layer' => $layer]),
                ];
                $this->_loadedFilters[] = 'category';
            }

            foreach ($this->filterableAttributes->getSpecificList($toAdd) as $attribute) {
                $this->_loadedFilters[] = $attribute->getAttributeCode();
                $this->filters[$attribute->getPosition()*1000+$attribute->getId()] = $this->createAttributeFilter($attribute, $layer);
            }
            ksort($this->filters);
        }

        return $this->filters;
    }
}
