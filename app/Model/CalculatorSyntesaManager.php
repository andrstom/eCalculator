<?php
declare(strict_types=1);
namespace App\Model;

use Nette;
use Nette\SmartObject;

/**
 * Description of CalculatorSyntesaManager
 *
 * @author andrs
 */
class CalculatorSyntesaManager {
    
    /** @var form values */
    private $param;
    
    /**
     * Calculation of qIgTotal = csfIgTotal / serumIgTotal;
     */
    private function getQIgTotal($csfIgTotal, $serumIgTotal) {
        
        $result = $csfIgTotal / $serumIgTotal;
        
        return $result;
        
    }
    
    /**
     * Calculation of qIgTotal = csfIgTotal / serumIgTotal;
     */
    private function getQAlbTotal($csfAlbTotal, $serumAlbTotal) {
        
        $result = $csfAlbTotal / $serumAlbTotal;
        
        return $result;
        
    }
    
    /**
     * Calculation of qPathSpec = csfIgAu / serumIgAu;
     */
    private function getQPathSpec($csfIgAu, $serumIgAu) {
        
        $result = $csfIgAu / $serumIgAu;
        
        return $result;
        
    }
    
    /**
     * get all values from form
     * @return array
     */
    public function getResult($value) {
        
        // array iteration and calculating
        foreach ($value['formValues'] as $k => $v) {
            
            // Replace comma to dot
            $this->param[$k] = str_replace(",", ".", $v);
            
            // Calculation of Q Ig total
            $qIgTotal = $this->getQIgTotal($this->param[$k]['csfIgTotal'], $this->param[$k]['serumIgTotal']);
            $this->param[$k]['qIgTotal'] = $qIgTotal;
            
            // Calculation of Q Albumin total
            $qAlbTotal = $this->getQAlbTotal($this->param[$k]['csfAlbTotal'], $this->param[$k]['serumAlbTotal']);
            $this->param[$k]['qAlbTotal'] = $qAlbTotal;
            
            // Calculation of Q path.-spec.
            $qPathSpec = $this->getQPathSpec($this->param[$k]['csfIgAu'], $this->param[$k]['serumIgAu']);
            $this->param[$k]['qPathSpec'] = $qPathSpec;
            
            // Calculation of QlimitIg (depends on antibody value)
            if ($this->param[$k]['antibody'] == 'IgG'){
                // IgG limit
                $qLimitIg = 0.93 * sqrt(pow($qAlbTotal, 2) + 0.000006) - 0.0017;
            } elseif($this->param[$k]['antibody'] == 'IgM') {
                // IgM limit
                $qLimitIg = 0.67 * sqrt(pow($qAlbTotal, 2) + 0.00012) - 0.0071;
            } else {
                // IgA limit
                $qLimitIg = 0.77 * sqrt(pow($qAlbTotal, 2) + 0.000023) - 0.0031;
            }
            $this->param[$k]['qLimitIg'] = $qLimitIg;
            
            // Calculation of Antibody index
            if ($qIgTotal < $qLimitIg) {
                $ai = $qPathSpec / $qIgTotal;
            } else {
                $ai = $qPathSpec / $qLimitIg;
            }
            $this->param[$k]['abIndex'] = $ai;
            
        }
        
        return $this->param;
    }
}
