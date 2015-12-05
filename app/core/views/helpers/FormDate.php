<?php

class Zend_View_Helper_FormDate extends Zend_View_Helper_FormElement
{

	public function formDate($name, $value = null, $attribs = null)
	{
		$translator = $this->getTranslator();
		
		// separate value into day, month and year
		$day = '';
		$month = '';
		$year = '';
		if (is_array($value)) {
			$day = $value['day'];
			$month = $value['month'];
			$year = $value['year'];
		} elseif (strtotime($value)) {
			list ($year, $month, $day) = explode('-', date('Y-m-d', strtotime($value)));
		}
		
		// build select options, form-control = bootstrap
		$dayAttribs = isset($attribs['dayAttribs']) ? $attribs['dayAttribs'] : array(
			'class' => 'form-control'
		);
		$monthAttribs = isset($attribs['monthAttribs']) ? $attribs['monthAttribs'] : array(
			'class' => 'form-control'
		);
		$yearAttribs = isset($attribs['yearAttribs']) ? $attribs['yearAttribs'] : array(
			'class' => 'form-control'
		);
		
		$dayMultiOptions = array(
			'' => ''
		);
		for ($i = 1; $i < 32; $i ++) {
			$index = str_pad($i, 2, '0', STR_PAD_LEFT);
			$dayMultiOptions[$index] = str_pad($i, 2, '0', STR_PAD_LEFT);
		}
		$monthMultiOptions = array(
			'' => ''
		);
		for ($i = 1; $i < 13; $i ++) {
			$index = str_pad($i, 2, '0', STR_PAD_LEFT);
			$monthMultiOptions[$index] = $translator->translate(date('F', mktime(null, null, null, $i, 01)));
		}
		
		$startYear = date('Y');
		if (isset($attribs['max_year'])) {
			$startYear = $attribs['max_year'];
			unset($attribs['max_year']);
		}
		
		$stopYear = $startYear - 100;
		if (isset($attribs['min_year'])) {
			$stopYear = $attribs['min_year'];
			unset($attribs['min_year']);
		}
		
		$yearMultiOptions = array(
			'' => ''
		);
		
		if ($stopYear < $startYear) {
			for ($i = $startYear; $i >= $stopYear; $i --) {
				$yearMultiOptions[$i] = $i;
			}
		} else {
			for ($i = $startYear; $i <= $stopYear; $i ++) {
				$yearMultiOptions[$i] = $i;
			}
		}
		
		// return the 3 selects separated by &nbsp;
		return $this->view->formSelect($name . '[day]', $day, $dayAttribs, $dayMultiOptions) . '&nbsp;' . $this->view->formSelect($name . '[month]', $month, $monthAttribs, $monthMultiOptions) . '&nbsp;' . $this->view->formSelect($name . '[year]', $year, $yearAttribs, $yearMultiOptions);
	}
}
