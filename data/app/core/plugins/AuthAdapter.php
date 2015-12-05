<?php

class Application_Plugin_AuthAdapter extends Zend_Auth_Adapter_DbTable
{

	public function __construct($zendDb = null, $tableName = null, $identityColumn = null, $credentialColumn = null)
	{
		// Get the default db adapter
		if ($zendDb == null) {
			$zendDb = Zend_Db_Table::getDefaultAdapter();
		}
		
		// Set default values
		$tableName = $tableName ? $tableName : 'accounts';
		$identityColumn = $identityColumn ? $identityColumn : 'email';
		$credentialColumn = $credentialColumn ? $credentialColumn : 'password';
		
		parent::__construct($zendDb, $tableName, $identityColumn, $credentialColumn);
	}

	protected function _authenticateCreateSelect()
	{
		// get select
		$dbSelect = clone $this->getDbSelect();
		$dbSelect->from($this->_tableName)->where($this->_zendDb->quoteIdentifier($this->_identityColumn, true) . ' = ?', $this->_identity);
		
		return $dbSelect;
	}

	protected function _authenticateValidateResult($resultIdentity)
	{
		$hash = new Application_Plugin_Phpass();
		$check = false;
		
		// auto-login
		if ($this->_credentialTreatment == 'autologin') {
			$check = true;
		}
		
		// again, try with md5
		if (is_string($this->_credential) && md5($this->_credential) == $resultIdentity['password']) {
			$check = true;
		}
		
		// Check that hash value is correct
		if (is_string($this->_credential) && $hash->CheckPassword($this->_credential, $resultIdentity['password'])) {
			$check = true;
		}
		
		if (! $check) {
			$this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
			$this->_authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
			return $this->_authenticateCreateAuthResult();
		}
		
		$this->_resultRow = $resultIdentity;
		
		$this->_authenticateResultInfo['code'] = Zend_Auth_Result::SUCCESS;
		$this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
		return $this->_authenticateCreateAuthResult();
	}

	public function getResultRowObject($returnColumns = null, $omitColumns = null)
	{
		if ($returnColumns || $omitColumns) {
			return parent::getResultRowObject($returnColumns, $omitColumns);
		} else {
			$omitColumns = array(
				'password'
			);
			return parent::getResultRowObject($returnColumns, $omitColumns);
		}
	}
}
