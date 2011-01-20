<?php
require_once ('Shipley/Translate/Adapter/Realtime.php');
/** 
 * @author pshipley
 * 
 * 
 */
class Shipley_Translate_Adapter_Realtime_Google extends Shipley_Translate_Adapter_Realtime
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
    	
    	if(isset($options['localSourceType'])){
    		if($options['localSourceType'] == 'Tmx'){
    			$localTranslate = new Zend_Translate(
    				array(
    					'adapter' => 'Tmx',
    					'content' => $options['content'],
    					'local'	  => $options['locale']
    				)
    			);
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
    
    public function callApi($messageId,$locale)
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