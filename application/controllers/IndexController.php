<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
    	//include_once('Shipley/Translate/Adapter/Realtime/Google.php');
        //$this->view->tl = new Shipley_Translate_Adapter_Realtime_Google();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $logger = Zend_Registry::get('logger');
        $this->view->tl = new Zend_Translate(
        	array(
        		'adapter' => 'Shipley_Translate_Adapter_Realtime',
        		'realtimeTranslator' => 'Shipley_Translate_Adapter_Realtime_Google',
        		'debugMode' => true,
        		'logger' => $logger,
        		'locale' => 'en'
        	)
        );
    	
        $this->view->fromText = $this->getRequest()->getParam('fromText','');
        $this->view->langCode = $this->getRequest()->getParam('langCode','fr');
        $this->view->langList = array(
        	'af' 	=> 'Afrikaans',
        	'sq' 	=> 'Albanian',
        	'ar' 	=> 'Arabic',
        	'be' 	=> 'Belarusian',
        	'bg' 	=> 'Bulgarian',
        	'ca' 	=> 'Catalan',
        	'zh-CN' => 'Chinese Simplified',
        	'zh-TW' => 'Chinese Traditional',
        	'hr'	=> 'Croatian',
        	'cs'	=> 'Czech',
        	'da'	=> 'Danish',
        	'nl'	=> 'Dutch',
        	'en'	=> 'English',
        	'et'	=> 'Estonian',
        	'tl'	=> 'Filipino',
        	'fi'	=> 'Finnish',
        	'fr'	=> 'French',
        	'gl'	=> 'Galician',
        	'de'	=> 'German',
        	'el'	=> 'Greek',
        	'ht'	=> 'Haitian Creole',
        	'iw'	=> 'Hebrew',
        	'hi'	=> 'Hindi',
        	'hu'	=> 'Hungarian',
        	'is'	=> 'Icelandic',
        	'id'	=> 'Indonesian',
        	'ga'	=> 'Irish',
        	'it'	=> 'Italian',
        	'ja'	=> 'Japanese',
        	'lv'	=> 'Latvian',
        	'lt'	=> 'Lithuanian',
        	'mk'	=> 'Macedonian',
        	'ms'	=> 'Malay',
        	'mt'	=> 'Maltese',
        	'no'	=> 'Norwegian',
        	'fa'	=> 'Persian',
        	'pl'	=> 'Polish',
        	'pt'	=> 'Portuguese',
        	'ro'	=> 'Romanian',
        	'ru'	=> 'Russian',
        	'sr'	=> 'Serbian',
        	'sk'	=> 'Slovak',
        	'sl'	=> 'Slovenian',
        	'es'	=> 'Spanish',
        	'sw'	=> 'Swahili',
        	'sv'	=> 'Swedish',
        	'th'	=> 'Thai',
        	'tr'	=> 'Turkish',
        	'uk'	=> 'Ukrainian',
        	'vi'	=> 'Vietnamese',
        	'cy'	=> 'Welsh',
        	'yi'	=> 'Yiddish'
        );
        $this->getResponse()->setHeader('Content-type', 'text/html; charset=utf-8',true);
    }


}

