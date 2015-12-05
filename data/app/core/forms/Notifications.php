<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Notifications extends Application_Form_Main
{

	/**
	 *
	 * Change notifications form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Notifications.phtml'))));


		// fields

		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$notifications_meta = json_decode($ProfilesMeta->getMetaValue('bulk_notifications'), true);
			
		$n1 = new Zend_Form_Element_Checkbox('notification_email_1');
		$n1
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_1']) && $notifications_meta['notification_email_1'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone posts a new comment'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n2 = new Zend_Form_Element_Checkbox('notification_email_2');
		$n2
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_2']) && $notifications_meta['notification_email_2'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone likes your post'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n3 = new Zend_Form_Element_Checkbox('notification_email_3');
		$n3
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_3']) && $notifications_meta['notification_email_3'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone follows you'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n4 = new Zend_Form_Element_Checkbox('notification_email_4');
		$n4
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_4']) && $notifications_meta['notification_email_4'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email on new friends'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n6 = new Zend_Form_Element_Checkbox('notification_email_6');
		$n6
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_6']) && $notifications_meta['notification_email_6'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when you lose a follower'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n7 = new Zend_Form_Element_Checkbox('notification_email_7');
		$n7
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_7']) && $notifications_meta['notification_email_7'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone posts on your wall'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n8 = new Zend_Form_Element_Checkbox('notification_email_8');
		$n8
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_8']) && $notifications_meta['notification_email_8'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone sends you a private message'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Update'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($n1, $n2, $n3, $n4, $n6, $n7, $n8, $submit));

		$this->postInit();
	}

}

