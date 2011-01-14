<?php
require_once ('Zend/Translate/Adapter.php');
/** 
 * @author pshipley
 * 
 * 
 */
class Shipley_Translate_Adapter_Realtime_Google extends Zend_Translate_Adapter
{
    // Internal variables
    private $_apiToken = '';
    private $_apiUrl = 'https://www.googleapis.com/language/translate/v2';
    private $_fromLang = 'en';
    private $_toLang = false;
	
	function __construct ($options=array())
    {
    	// Set $this->_apiToken if supplied, else retreive from tokens.ini
    	if(isset($options['apiToken'])){
    		$this->setApiToken($options['apiToken']);
    	} else {
    		$tokensConfig = Zend_Registry::get('tokensConfig');
    		$this->setApiToken($tokensConfig->token->translate->google);
    	}
    	
    	// Set default options for adapter
    	$this->_options['realtimeTranslation'] = true;
    	
    	// Override default options with any supplied
    	if(count($options) > 0){
    		foreach($options as $key => $value){
    			$this->_options[$key] = $value;
    		}
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
    
    }
    
    private function _callApi($messageId,$locale)
    {
    	$client = new Zend_Http_Client($this->_apiUrl);
    	$client->setParameterGet('key',$this->getApiKey());
    	$client->setParameterGet('q',$messageId);
    	$client->setParameterGet('source',$this->getFromLang());
    	$client->setParameterGet('target',$locale);
    	
    	
    	$response = $client->request('GET');
    	if($response->getStatus() == 200){
	    	$results = json_decode($response->getBody(),true);
	    	if(is_array($results)){
	    		return isset($results['data']['translations'][0]['translatedText']) ? $results['data']['translations'][0]['translatedText'] : false;
	    	}
    	} else {
    		return $response->getBody();
    	}
    	
    	return false;
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
        } else if (is_string($messageId) && !isset($this->_translate[$locale][$messageId]) && $this->getOptions('realtimeTranslation')) {
        	$translatedValue = $this->_callApi($messageId,$locale);
        	if(!$translatedValue){
        		return $messageId;
        	}
        	//$this->addNewTranslation($messageId,$translatedValue,$locale);
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
    	return "Shipley_Translate_Adapter_Realtime_Google";
    }
	/**
     * @return the $_apiKey
     */
    public function getApiKey()
    {
        return $this->_apiToken;
    }

	/**
     * @param field_type $_apiKey
     */
    public function setApiToken($_apiKey)
    {
        $this->_apiToken = $_apiKey;
    }

	/**
     * @return the $_fromLang
     */
    public function getFromLang()
    {
        return $this->_fromLang;
    }

	/**
     * @param field_type $_fromLang
     */
    public function setFromLang($_fromLang)
    {
        $this->_fromLang = $_fromLang;
    }

	/**
     * @return the $_toLang
     */
    public function getToLang()
    {
        return $this->_toLang;
    }

	/**
     * @param field_type $_toLang
     */
    public function setToLang($_toLang)
    {
        $this->_toLang = $_toLang;
    }
    

}