<?php
declare(strict_types=1);
namespace App\Model;

use Nette;
use App\Model\CalculatorElisaManager;
use Nette\SmartObject;

/**
 * Quality control manager for result validation
 * 
 * @param array (abs)
 * @param array (results)
 * $param array (param)
 * 
 * return array
 */
class QualityControlManager
{
    public $abs;
    public $results;
    public $param;
    public $cuttoff;
    public $blank;
    public $stA;
    public $stA_mlu;
    public $stD_avg;
    public $stD;
    public $stE;
    public $ratio;
    public $classification;

    /** @var App\Model\CalculatorElisaManager */
    private $calculatorElisaManager;
    
    /** @var Nette\Database\Context */
    private $database;
    
    /** @var \Nette\Security\User */
    private $user;
    
    public function __construct($param = null, Nette\Database\Context $database)
    {
        $this->database = $database;

        // quality control
        $calculatorElisa = new CalculatorElisaManager($this->database);
        $this->param = $calculatorElisa->getParam($param);
        $this->abs = $this->param['Abs'];
        $this->results = $calculatorElisa->getResult($param);

    }
    
    /**
     * Blank
     * 
     * @return float
     */
    public function getBlank()
    {
        // Blank < 0.150
        $this->blank = ($this->abs[1] < 0.150 ? true : false);
        
        return $this->blank;
    }
    
    /**
     * Standard E
     * 
     * @return float
     */
    public function getStA()
    {
        // StD average
        $this->stA = ($this->abs[49] - $this->abs[1]);
        
        return $this->stA;
    }
    
    /**
     * Standard D (average)
     * 
     * @return float
     */
    public function getStD()
    {
        // StD average
        $this->stD_avg = ((($this->abs[13] - $this->abs[1]) + ($this->abs[25] - $this->abs[1])) / 2);
        
        return $this->stD_avg;
    }
    
    /**
     * Standard E
     * 
     * @return float
     */
    public function getStE()
    {
        // StD average
        $this->stE = ($this->abs[37] - $this->abs[1]);
        
        return $this->stE;
    }
    /**
     * Cutoff
     * 
     * @return float
     */
    public function getCutoff()
    {
        // cuttoff
        if ($this->param['dilution'] == 101) {
            
            $this->cutoff = $this->getStD() * $this->param['kf_serum'];
            
        } elseif ($this->param['dilution'] == 2) {
            
            $this->cutoff = $this->getStD() * $this->param['kf_csf'];
            
        } else {
            
            $this->cutoff = $this->getStD() * $this->param['kf_synovia'];
            
        }
        
        return $this->cutoff;
    }
    
    
    /**
     * Standard A validation
     * 
     * @return boolean
     */
    public function qcStA()
    {
        // StA < cutoff * 0.9
        $this->stA = (($this->abs[49] - $this->abs[1]) < (($this->getStD() * $this->param['kf_serum']) * 0.9) ? true : false);
        
        //dump($this->stA);
        //exit;
        
        return $this->stA;
    }
    
    /**
     * Standard A validation (mlU/ml only)
     * 
     * @return boolean
     */
    public function qcStAmlu()
    {
        // StA < 120
        $this->stA_mlu = ($this->results[49] < 120 ? true : false);
        
        return $this->stA_mlu;
    }
    
    /**
     * Standard E validation
     * 
     * @return boolean
     */
    public function qcStE()
    {
        // StE > cutoff * 1.1
        $this->stE = (($this->abs[37] - $this->abs[1]) > (($this->getStD() * $this->param['kf_serum']) * 1.1) ? true : false);
        
        return $this->stE;
    }
    
    /**
     * Standard D validation
     * 
     * @return boolean
     */
    public function qcStD()
    {
        // StD > 0.500
        $this->stD = ($this->getStD() > 0.500 ? true : false);
        
        return $this->stD;
    }
    
    
    /**
     * Ratio OD validation
     * 
     * @return boolean
     */
    public function qcRatio()
    {
        // RatioOD min < StE / StD < RatioOD max
        $this->ratio = (($this->abs[37] - $this->abs[1]) / $this->getStD() > $this->param['ratio_min'] ? (($this->abs[37] - $this->abs[1]) / $this->getStD() < $this->param['ratio_max'] ? true : false) : false);
        
        return $this->ratio;
    }
    
    
    /**
    * @return array
    */
    public function getQCreport()
    {
        
        // set array with qc results
        $this->qualityControl = array(
            'qcBlank' => $this->getBlank(),
            'qcStA' => $this->qcStA(),
            'qcStAmlu' => $this->qcStAmlu(),
            'qcStE' => $this->qcStE(),
            'qcStD' => $this->qcStD(),
            'qcRatio' => $this->qcRatio());
        
        return $this->qualityControl;
    }
    
    public function getClassification($param = null)
    {
        if($param['dilution'] == '101') {
            
            if ($param['unit'] == 1) {
                $this->classification = ($param['serum_ip_min'] != 0 && $param['serum_ip_min'] != 0 ? "(Negative > " . $param['serum_ip_min'] . " > Greyzone > " . $param['serum_ip_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 2) {
                $this->classification = ($param['serum_au_min'] != 0 && $param['serum_au_min'] != 0 ? "(Negative > " . $param['serum_au_min'] . " > Greyzone > " . $param['serum_au_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 3) {
                $this->classification = ($param['serum_vieu_min'] != 0 && $param['serum_vieu_min'] != 0 ? "(Negative > " . $param['serum_vieu_min'] . " > Greyzone > " . $param['serum_vieu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 4) {
                $this->classification = ($param['serum_mlu_min'] != 0 && $param['serum_mlu_max'] != 0 ? "(Negative > " . $param['serum_mlu_min'] . " > Greyzone > " . $param['serum_mlu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = ($param['serum_iu_min'] != 0 && $param['serum_iu_min'] != 0 ? "(Negative > " . $param['serum_iu_min'] . " > Greyzone > " . $param['serum_iu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            }
        } elseif ($param['dilution'] == '2') {
            if ($param['unit'] == 1) {
                $this->classification = ($param['csf_ip_min'] != 0 || $param['csf_ip_min'] != 0 ? "(Negative > " . $param['csf_ip_min'] . " > Greyzone > " . $param['csf_ip_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 2) {
                $this->classification = ($param['csf_au_min'] != 0 || $param['csf_au_min'] != 0 ? "(Negative > " . $param['csf_au_min'] . " > Greyzone > " . $param['csf_au_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 3) {
                $this->classification = ($param['csf_vieu_min'] != 0 || $param['csf_vieu_min'] != 0 ? "(Negative > " . $param['csf_vieu_min'] . " > Greyzone > " . $param['csf_vieu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 4) {
                $this->classification = ($param['csf_mlu_min'] != 0 || $param['csf_mlu_max'] != 0 ? "(Negative > " . $param['csf_mlu_min'] . " > Greyzone > " . $param['csf_mlu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = ($param['csf_iu_min'] != 0 || $param['csf_iu_max'] != 0 ? "(Negative > " . $param['csf_iu_min'] . " > Greyzone > " . $param['csf_iu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            }
        } else {
            if ($param['unit'] == 1) {
                $this->classification = ($param['synovia_ip_min'] != 0 || $param['synovia_ip_min'] != 0 ? "(Negative > " . $param['synovia_ip_min'] . " > Greyzone > " . $param['synovia_ip_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 2) {
                $this->classification = ($param['synovia_au_min'] != 0 || $param['synovia_au_min'] != 0 ? "(Negative > " . $param['synovia_au_min'] . " > Greyzone > " . $param['synovia_au_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 3) {
                $this->classification = ($param['synovia_vieu_min'] != 0 || $param['synovia_vieu_min'] != 0 ? "(Negative > " . $param['synovia_vieu_min'] . " > Greyzone > " . $param['synovia_vieu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 4) {
                $this->classification = ($param['synovia_mlu_min'] != 0 || $param['synovia_mlu_max'] != 0 ? "(Negative > " . $param['synovia_mlu_min'] . " > Greyzone > " . $param['synovia_mlu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = ($param['synovia_iu_min'] != 0 || $param['synovia_iu_max'] != 0 ? "(Negative > " . $param['synovia_iu_min'] . " > Greyzone > " . $param['synovia_iu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            }
        }
        return $this->classification;
    }
}
