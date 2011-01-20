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
	private $debugMode = Zend_Log::WARN; // Set to false you want to disable.
	private $debugLog = false;
	
    /**
     * If a static adapter name and path to source file are supplied,
     * initiate appropriate Zend Translate object into $this->staticTranslator
     * @param array $options
     */
    function __construct ($options=array())
    {
    	if($options['debugMode'] == true && isset($options['logger'])){
    		$this->debugMode = true;
    		$this->initDebugMode($options['logger']);
    	}
    	
    	$this->debugOutput('Constructing Shipley_Translate_Adapter_Realtime with static adapter: '.
    					   (isset($options['staticAdapter']) ? $options['staticAdapter'] : 'none').
    					   ', realtime adapter: '.$options['realtimeTranslator'].', locale: '.$options['locale'],Zend_Log::INFO);
        
    	if(isset($options['staticAdapter']) && isset($options['content'])){
    		$options['adapter'] = $options['staticAdapter'];
    		$locale = isset($options['locale']) ? $options['locale'] : 'en';
    		$this->debugOutput('Initiating Static Translator with adapter: '.$options['adapter'].' and content: '.$options['content'],Zend_Log::INFO);
    		$this->staticTransltor = new Zend_Translate($options);
    	}
    	
    	if(isset($options['doRealtimeTranslation']) && $options['doRealtimeTranslation'] != $this->doRealtimeTranslation){
    		$this->doRealtimeTranslation = $options['doRealtimeTranslation'];
    	}
    	
    	foreach($options as $key => $value){
    		$this->_options[$key] = $value;
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
    	$this->debugOutput('Loading static translator translation data. Data: '.$data.', locale: '.$locale.', options: '.print_r($options,true),Zend_Log::INFO);
    	$this->staticTransltor->loadTranslationData($data,$locale,$options);
    }
    
    private function initRealtimeAdapter()
    {
    	
    	$realtimeAdapterName = $this->getOptions('realtimeTranslator');
    	$saveTranslationResults = $this->getOptions('saveTranslationResults');
    	$this->debugOutput('Initiating realtime translator with adapter '.$realtimeAdapterName,Zend_Log::INFO);
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
    	$this->debugOutput('Adding new translation to static translation source.'.
    					   ' Message Id: \''.$message.'\', translated value: \''.$translatedValue.'\', locale: \''.$locale.'\'',Zend_Log::INFO);
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
        } else if (is_string($messageId) && !isset($this->_translate[$locale][$messageId]) && $this->doRealtimeTranslation) {
        	$this->debugOutput('Message not found in static translation source for: \''.$messageId.'\', will try realtime translation.',Zend_Log::WARN);
        	$this->initRealtimeAdapter();
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
    
    private function initDebugMode($logger)
    {	
    	if($logger instanceof Zend_Log){
    		$this->debugLog = $logger;
    	}
    }
    
    private function debugOutput($message,$priority=Zend_Log::WARN)
    {
    	if($this->debugMode && $priority >= $this->debugMode){
    		$this->debugLog->log($message,$priority);
    	}
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