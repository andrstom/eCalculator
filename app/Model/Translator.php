<?php
namespace App\Model;


class Translator implements \Nette\Localization\ITranslator
{

    /**
     * Translates the given string.
     * @param string message
     * @param int plural count
     * @return string
     */
    public function translate($message, $count = null)
    {
        $labels = array('* Vyberte metodu:' => '* Select assay:');
        
        if (in_array($message, $labels))
            return $labels[$message];
        else 
            return $message;
            
    }
}