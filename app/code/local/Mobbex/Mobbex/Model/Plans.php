<?php
class Mobbex_Mobbex_Model_Plans {

    /** @var Mobbex_Mobbex_Helper_Data */
    public $helper;

    /**
     * All 'ahora' plan keys.
     */
    const PLANS = ['ahora_3', 'ahora_6', 'ahora_12', 'ahora_18'];


    /** @var Mobbex_Mobbex_Model_Customfield */
    public $fields;

    public function __construct()
    {
        // Init class properties
        $this->helper = Mage::helper('mobbex/data');
        $this->fields = Mage::getModel('mobbex/customfield');
    }

    /**
     * Get advanced plans fields data for
     * use in product/category config.
     *
     * @param string $id ID of catalog object.
     * @param string $catalogType Type of catalog object.
     *
     * @return array
     */
    public function getCommonPlanFields($id, $catalogType = 'product')
    {
        $result = [];

        // Get sources list from API and current saved configuration from db
        $sources 	  = $this->helper->getSources();
        $checkedPlans = $this->fields->getCustomField($id, $catalogType, 'common_plans') ?: [];

        // Create common plan fields
        foreach ($sources as $source) {
            $plans = !empty($source['installments']['list']) ? $source['installments']['list'] : [];

            foreach ($plans as $plan) {
                $planId	= "common_plan_{$plan['reference']}";

                // Create field data
                $result[$planId] = [
                    'label' => $plan['description'] ?: $plan['name'],
                    'value' => (!in_array($plan['reference'], $checkedPlans) && $this->fields->getCustomField($id, $catalogType, $plan['reference']) !== 'yes'),
                ];
            }
        }

        return $result;
    }

    /**
     * Get advanced plans fields data for
     * use in product/category config.
     *
     * @param string $id ID of catalog object.
     * @param string $catalogType Type of catalog object.
     *
     * @return array
     */
    public function getAdvancedPlanFields($id, $catalogType = 'product')
    {
        $result = [];

        // Get sources list from API and current saved configuration from db
        $sources 	  = $this->helper->getSourcesAdvanced();
        $checkedPlans = $this->fields->getCustomField($id, $catalogType, 'advanced_plans') ?: [];
        $checkedPlans = explode($checkedPlans, ',');

        // Create advanced plan fields
        foreach ($sources as $source) {
            $plans      = !empty($source['installments']) ? $source['installments'] : [];
            $reference  = $source['source']['reference'];
            $sourceName = $source['source']['name'];

            foreach ($plans as $plan) {
                $planId	= "advanced_plan_{$plan['uid']}";

                // Create field data
                $result[$reference][$sourceName][$planId] = [
                    'label' => $plan['description'] ?: $plan['name'],
                    'value' => (is_array($checkedPlans) && in_array($plan['uid'], $checkedPlans)),
                ];
            }
        }

        return $result;
    }

    /**
     * Save plan filter fields of product/category.
     *
     * @param mixed $id
     * @param string $catalogType
     * @throws Exception
     */
    public function savePlanFields($id, $catalogType = 'product')
    {
        $common_plans = $advanced_plans = [];
        // Remove values saved with previus method
        foreach (self::PLANS as $plan) {
            $planId = $this->fields->getCustomField($id, $catalogType, $plan, 'customfield_id');

            if ($planId) {
                $this->fields->load($planId);
                $this->fields->delete();
            }
        }

        // Get posted values
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'common_plan_') !== false && $value === 'no') {
                $uid = explode('common_plan_', $key)[1];
                $common_plans[] = $uid;
            } else if (strpos($key, 'advanced_plan_') !== false && $value === 'on'){
                $uid = explode('advanced_plan_', $key)[1];
                $advanced_plans[] = $uid;
            }
        }

        $common_plans = implode(",", $common_plans);
        $advanced_plans = implode(",", $advanced_plans);

        // Save data
        $this->fields->saveCustomField($id, $catalogType, 'common_plans', $common_plans);
        $this->fields->saveCustomField($id, $catalogType, 'advanced_plans', $advanced_plans);

        return true;
    }
}
