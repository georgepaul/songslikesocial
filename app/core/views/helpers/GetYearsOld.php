<?php

class Zend_View_Helper_GetYearsOld extends Zend_View_Helper_Abstract
{

	public function GetYearsOld($birthday)
	{
		if (($birthday = strtotime($birthday)) === false)
			return false;
		
		for ($i = 0; strtotime("-$i year") > $birthday; ++ $i);
		
		return $i - 1;
	}
}