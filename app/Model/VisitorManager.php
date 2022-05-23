<?php
declare(strict_types=1);

namespace App\Model;

/**
 * Description of VisitorManager
 *
 * @author andrs
 */
class VisitorManager {
    
    private $addVisitor;

    public function addVisitor($values) {
        
        // set visitor IP
        $visitorIP = $_SERVER['REMOTE_ADDR'];
        
        // set used method and output
        if (isset($values['sendSyntesaPdf']))
            $calcMethod = "Syntesa \t PDF";
        
        if (isset($values['sendSyntesaXls']))
            $calcMethod = "Syntesa \t XLSX";
        
        if (isset($values['sendSyntesaText']))
            $calcMethod = "Syntesa \t TXT";
        
        if (isset($values['sendElisaPdf']))
            $calcMethod = "ELISA \t PDF";
        
        if (isset($values['sendElisaXls']))
            $calcMethod = "ELISA \t XLSX";
        
        if (isset($values['sendElisaText']))
            $calcMethod = "ELISA \t TXT";
        
        // open file
        $tmpFilename =  './images/pocitadlo_navstev.txt';
        $txtReport = fopen($tmpFilename, "a+");

        // set file content
        $this->addVisitor = date("Ymd_his", time()) . "\t" . $visitorIP . "\t" . $calcMethod . "\t" . "\n\r" . PHP_EOL;

        // write and close file
        $this->addVisitor = fwrite($txtReport, $this->addVisitor);
        $this->addVisitor = fclose($txtReport);
        
        return $this->addVisitor;
    }
}
