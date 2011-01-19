<?php
require_once ('Zend/Translate/Adapter.php');
/** 
 * @author pshipley
 * 
 * 
 */
class Shipley_Translate_Adapter_Realtime extends Zend_Translate_Adapter
{
	// Internal vars
	private $staticTransltor = false;
	private $realtimeTranslator = false;
	private $doRealtimeTranslation = true;
	
    /**
     * If a static adapter name and path to source file are supplied,
     * initiate appropriate Zend Translate object into $this->staticTranslator
     * @param array $options
     */
    function __construct ($options=array())
    {
        if(isset($options['staticAdapter']) && isset($options['content'])){
    		$options['adapter'] = $options['staticAdapter'];
    		$locale = isset($options['locale']) ? $options['locale'] : 'en';
    		$this->staticTransltor = new Zend_Translate($options);
    	}
    }
    
    /**
     * 
     * @param   mixed              $data
     * @param   string|Zend_Locale $locale
     * @param   array              $options (optional)
     * @return  array
     * @see Zend_Translate_Adapter::_loadTranslationData()
     */
    protected function _loadTranslationData ($data, $locale, 
    											array $options = array())
    {
    	$this->staticTransltor->loadTranslationData($data,$locale,$options);
    }
    
    private function initRealtimeAdapter()
    {
    	$realtimeAdapterName = $this->getOptions('realtimeTranslator');
    	$saveTranslationResults = $this->getOptions('saveTranslationResults');
    	if($saveTranslationResults && isStaticAdapterUpdateable($realtimeAdapterName)){
    		$saveTranslationResults = true;
    	} else {
    		$saveTranslationResults = false;
    	}
    	if(is_string($realtimeAdapterName)){
    		$this->realtimeTranslator = new Zend_Translate(
	    		array(
	    			'adapter' => $realtimeAdapterName,
	    			'saveTranslationResults' => $saveTranslationResults
	    		)
	    	);
    	}
    	
    }
    
    public function isStaticAdapterUpdateable($adapterName)
    {
    	$updateableAdapters = array(
    		'csv', 'ini', 'tbx', 'tmx', 'qt', 'xliff', 'xmltm'
    	);
    	
    	if(in_array(strtolower($adapterName), $updateableAdapters)){
    		return true;
    	}
    	
    	return false;
    }
    
    private function addToStaticTranslationSource($message,$translatedValue, $locale)
    {
    	return true;
    }
    
	/**
     * Translates the given string
     * returns the translation
     *
     * @see Zend_Locale
     * @param  string|array       $messageId Translation string, or Array for plural translations
     * @param  string|Zend_Locale $locale    (optional) Locale/Language to use, identical with
     *                                       locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public function translate($messageId, $locale = null)
    {
    	if(empty($messageId)){
    		return '';
    	}
        if ($locale === null) {
            $locale = $this->_options['locale'];
        }

        $plural = null;
        if (is_array($messageId)) {
            if (count($messageId) > 2) {
                $number = array_pop($messageId);
                if (!is_numeric($number)) {
                    $plocale = $number;
                    $number  = array_pop($messageId);
                } else {
                    $plocale = 'en';
                }

                $plural    = $messageId;
                $messageId = $messageId[0];
            } else {
                $messageId = $messageId[0];
            }
        }

        if (!Zend_Locale::isLocale($locale, true, false)) {
            if (!Zend_Locale::isLocale($locale, false, false)) {
                // language does not exist, return original string
                $this->_log($messageId, $locale);
                // use rerouting when enabled
                if (!empty($this->_options['route'])) {
                    if (array_key_exists($locale, $this->_options['route']) &&
                        !array_key_exists($locale, $this->_routed)) {
                        $this->_routed[$locale] = true;
                        return $this->translate($messageId, $this->_options['route'][$locale]);
                    }
                }

                $this->_routed = array();
                if ($plural === null) {
                    return $messageId;
                }

                $rule = Zend_Translate_Plural::getPlural($number, $plocale);
                if (!isset($plural[$rule])) {
                    $rule = 0;
                }

                return $plural[$rule];
            }

            $locale = new Zend_Locale($locale);
        }

        $locale = (string) $locale;
        if ((is_string($messageId) || is_int($messageId)) && isset($this->_translate[$locale][$messageId])) {
            // return original translation
            if ($plural === null) {
                $this->_routed = array();
                return $this->_translate[$locale][$messageId];
            }

            $rule = Zend_Translate_Plural::getPlural($number, $locale);
            if (isset($this->_translate[$locale][$plural[0]][$rule])) {
                $this->_routed = array();
                return $this->_translate[$locale][$plural[0]][$rule];
            }
        } else if (strlen($locale) != 2) {
            // faster than creating a new locale and separate the leading part
            $locale = substr($locale, 0, -strlen(strrchr($locale, '_')));

            if ((is_string($messageId) || is_int($messageId)) && isset($this->_translate[$locale][$messageId])) {
                // return regionless translation (en_US -> en)
                if ($plural === null) {
                    $this->_routed = array();
                    return $this->_translate[$locale][$messageId];
                }

                $rule = Zend_Translate_Plural::getPlural($number, $locale);
                if (isset($this->_translate[$locale][$plural[0]][$rule])) {
                    $this->_routed = array();
                    return $this->_translate[$locale][$plural[0]][$rule];
                }
            }
        } else if (is_string($messageId) && !isset($this->_translate[$locale][$messageId]) && $this->getOptions('doRealtimeTranslation')) {
        	$translatedValue = $this->realtimeTranslator->callApi($messageId,$locale);
        	if(!$translatedValue){
        		return $messageId;
        	}
        	$this->addToStaticTranslationSource($messageId,$translatedValue,$locale);
        	return $translatedValue;
        }

        $this->_log($messageId, $locale);
        // use rerouting when enabled
        if (!empty($this->_options['route'])) {
            if (array_key_exists($locale, $this->_options['route']) &&
                !array_key_exists($locale, $this->_routed)) {
                $this->_routed[$locale] = true;
                return $this->translate($messageId, $this->_options['route'][$locale]);
            }
        }

        $this->_routed = array();
        if ($plural === null) {
            return $messageId;
        }

        $rule = Zend_Translate_Plural::getPlural($number, $plocale);
        if (!isset($plural[$rule])) {
            $rule = 0;
        }

        return $plural[$rule];
    }
    
	/**
     * 
     * @return  string
     * @see Zend_Translate_Adapter::toString()
     */
    public function toString ()
    {
    	return "Shipley_Translate_Adapter_Realtime";
    }
}
?>