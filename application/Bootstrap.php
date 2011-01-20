<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initRegistry()
	{
		$this->setContainer(Zend_Registry::getInstance());
	}
	
	protected function _initLog()
	{
		$options = $this->getOptions();
		if($options['logging']['enabled']){
			if($options['logging']['writer'] == 'Zend_Log_Writer_Firebug'){
				$writer = new Zend_Log_Writer_Firebug();
				$logger = new Zend_Log($writer);
				Zend_Registry::set('logger', $logger);
				return $logger;
			}
		}
		return false;
	}
}

