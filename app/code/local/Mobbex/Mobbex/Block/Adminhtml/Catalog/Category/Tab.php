<?php
class Mobbex_Mobbex_Block_Adminhtml_Catalog_Category_Tab extends Mage_Adminhtml_Block_Template
{
	/** Common plans fields data. */
	public $commonPlans = [];

	/** Advanced plans fields data. */
	public $advancedPlans = [];

    public function _construct()
	{
		$id = Mage::registry('current_category') ? Mage::registry('current_category')->getId() : false;

		if (empty($id))
			return;

		// Get plans fields
		$this->commonPlans	 = Mage::getModel('mobbex/plans')->getCommonPlanFields($id, 'category');
		$this->advancedPlans = Mage::getModel('mobbex/plans')->getAdvancedPlanFields($id, 'category');
        $this->setTemplate('mobbex/plans-filter.phtml');
	}
}

