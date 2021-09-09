<?php
class Mobbex_Mobbex_Block_Adminhtml_Catalog_Product_Tab extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /** Common plans fields data. */
	public $commonPlans = [];

	/** Advanced plans fields data. */
	public $advancedPlans = [];

	public function _construct()
	{
		parent::_construct();

		$id = Mage::registry('current_product') ? Mage::registry('current_product')->getId() : false;

		if (empty($id))
			return;

        // Get plans fields
        $this->commonPlans	 = Mage::getModel('mobbex/plans')->getCommonPlanFields($id, 'category');
        $this->advancedPlans = Mage::getModel('mobbex/plans')->getAdvancedPlanFields($id, 'category');

		$this->setTemplate('mobbex/plans-filter.phtml');
	}

	public function getTabLabel()
	{
		return $this->__('Mobbex Options');
	}

	public function getTabTitle()
	{
		return $this->__('Edit payment plans for this product');
	}

	public function canShowTab()
	{
		return true;
	}

	public function isHidden()
	{
		return false;
	}
}

