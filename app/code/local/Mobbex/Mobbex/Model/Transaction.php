<?php

class Mobbex_Mobbex_Model_Transaction extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('mobbex/transaction');
    }

    /**
     * Get custom transaction data
     *
     * @param int $order_id
     * @param string $searched_column
     * @return string
     */
    public function getMobbexTransaction($order_id, $searched_column = 'data')
    {

        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->getColumnValues($searched_column);

        return $collection->getFirstItem();
    }

    /**
     * Saves custom field
     *
     * @param int $order_id
     * @param string $data
     *
     * @return void
     * @throws Exception
     */
    public function saveMobbexTransaction($order_id, $data)
    {
        //Get model
        $transaction = new Mobbex_Mobbex_Model_Transaction();
        try {
            $transaction->setData('order_id', $order_id);
            $transaction->setData('data', $data);
            $transaction->save();
        } catch (\Exception $e) {}
    }
}
