<?php

require_once PHPTAL_DIR.'PHPTAL/TranslationService.php';

class DummyTranslator implements PHPTAL_TranslationService
{
    public $vars = array();
    public $translations = array();
    
    public function setLanguage(){
    }
    
    public function setEncoding($enc) {}
    
    public function useDomain($domain){
    }
    
    public function setVar($key, $value){ 
        $this->vars[$key] = $value; 
    }

    public function setTranslation($key, $translation){
        $this->translations[$key] = $translation;
    }
    
    public function translate($key, $escape = true){ 
        if (array_key_exists($key, $this->translations)){
            $v = $this->translations[$key];
        }
        else {
            $v = $key;
        }
        
        if ($escape) $v = htmlspecialchars($v);
        
        while (preg_match('/\$\{(.*?)\}/sm', $v, $m)){
            list($src, $var) = $m;
            $v = str_replace($src, $this->vars[$var], $v);
        }
        
        
        return $v; 
    }
}
?>
