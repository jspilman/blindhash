<?php

class BlindHash_SecurePassword_Model_Downgrade extends Mage_Core_Model_Abstract
{

    protected $resource;
    protected $read;
    protected $write;
    protected $table;

    public function _construct()
    {
        $this->resource = Mage::getSingleton('core/resource');
        $this->read = $this->resource->getConnection('core_read');
        $this->write = $this->resource->getConnection('core_write');
        $this->table = $this->resource->getTableName('customer_entity_text');
    }

    /**
     * Downgrade all customers passwords from blind hash to 
     * old md5 hash back again
     * 
     * @return int
     */
    public function DowngradeAllCustomerPasswords()
    {
        $attribute = Mage::getModel('eav/config')->getAttribute('customer', 'password_hash');
        $blindhashPrefix = BlindHash_SecurePassword_Model_Encryption::PREFIX . BlindHash_SecurePassword_Model_Encryption::DELIMITER;
        $query = "SELECT entity_id,value AS hash FROM {$this->table} WHERE attribute_id = {$attribute->getId()} AND value like '$blindhashPrefix%' ";
        $passwordList = $this->read->fetchAll($query);
        $count = 0;

        if (!$passwordList)
            return;

        foreach ($passwordList as $password) {
            $customerId = $password['entity_id'];
            $hash = $password['hash'];
            if ($this->_downgradeToOldHash($hash, $customerId)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Downgrade password to old md5 for given customer
     * 
     * @param string $hash 
     * @param int $customerId
     * @return boolean
     */
    protected function _downgradeToOldHash($hash, $customerId)
    {
        $hashArr = explode(BlindHash_SecurePassword_Model_Encryption::DELIMITER, $hash);
        if ((count($hashArr) < 5)) {
            return;
        }
        $OldHash = end($hashArr);
        return $this->write->update($this->table, array('value' => $OldHash), array('entity_id = ?' => $customerId));
    }
}
