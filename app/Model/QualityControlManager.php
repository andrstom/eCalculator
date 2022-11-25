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
    public $stCXCL13_avg;
    public $stD;
    public $stE;
    public $cal_1;
    public $cal_2;
    public $cal_3;
    public $cal_4;
    public $cal_5;
    public $cal_6;
    public $cal_7;
    public $cal_8;
    public $ratio;
    public $classification;

    /** @var App\Model\CalculatorElisaManager */
    private $calculatorElisaManager;
    
    /** @var Nette\Database\Context */
    private $database;
    
    /** @var \Nette\Security\User */
    private $user;
    
    public function __construct($param = null, Nette\Database\Context $database) {
        $this->database = $database;
        // quality control
        $calculatorElisa = new CalculatorElisaManager($this->database);
        $this->param = $calculatorElisa->getParam($param);
        $this->abs = $this->param['Abs'];
        $this->results = $calculatorElisa->getResult($param);
    }
    
    /**
     * CAL 1 / Blank
     * @return bool
     */
    public function getCal1() {
        // Blank < Blank_min
        $this->cal_1 = ($this->abs[1] < $this->param['blank_max'] ? true : false);
        return $this->cal_1;
    }
    
    /**
     * CAL 2
     * @return float
     */
    public function getCal2() {
        $this->cal_2 = $this->abs[13] - $this->abs[1];
        return $this->cal_2;
    }
    /**
     * CAL 3
     * @return float
     */
    public function getCal3() {
        $this->cal_3 = $this->abs[25] - $this->abs[1];
        return $this->cal_3;
    }
    
    /**
     * CAL 4
     * @return float
     */
    public function getCal4() {
        $this->cal_4 = ($this->abs[37] - $this->abs[1]);
        return $this->cal_4;
    }
    /**
     * CAL_5
     * @return float
     */
    public function getCal5() {
        $this->cal_5 = ($this->abs[49] - $this->abs[1]);
        return $this->cal_5;
    }
    
    /**
     * CAL_6
     * @return float
     */
    public function getCal6() {
        $this->cal_6 = ($this->abs[61] - $this->abs[1]);
        return $this->cal_6;
    }
    /**
     * CAL_7
     * @return float
     */
    public function getCal7() {
        $this->cal_7 = ($this->abs[73] - $this->abs[1]);
        return $this->cal_7;
    }
    /**
     * CAL_8
     * @return float
     */
    public function getCal8() {
        $this->cal_8 = ($this->abs[85] - $this->abs[1]);
        return $this->cal_8;
    }
    
    /**
     * Standard D average
     * @return float
     */
    public function getStD() {
        $this->stD_avg = ($this->getCal2() + $this->getCal3()) / 2;
        return $this->stD_avg;
    }
    
    /**
     * Standard CXCL13 average
     * @return float
     */
    public function getStCXCL13() {
        $this->stCXCL13_avg = ($this->abs[13] + $this->abs[25]) / 2;
        return $this->stCXCL13_avg;
    }
    
    /**
     * Cutoff
     * 
     * @return float
     */
    public function getCutoff() {
        // cuttoff
        if ($this->param['dilution'] == 81) {
            $this->cutoff = $this->getStD() * $this->param['kf_synovia'];
        } elseif ($this->param['dilution'] == 2) {
            $this->cutoff = $this->getStD() * $this->param['kf_csf'];
        } else {
            $this->cutoff = $this->getStD() * $this->param['kf_serum'];
        }
        return $this->cutoff;
    }
    
    /**
     * Standard A (cal5) validation
     * @return bool
     */
    public function qcStA() {
        // StA < cutoff * 0.9
        $this->stA = ($this->getCal5() < (($this->getStD() * $this->param['kf_serum']) * 0.9) ? true : false);
        return $this->stA;
    }
    
    /**
     * Standard A (cal5) validation (mlU/ml only)
     * @return boolean
     */
    public function qcStAmlu() {
        // StA < 120
        $this->stA_mlu = ($this->results[49] < 120 ? true : false);
        return $this->stA_mlu;
    }
    
    /**
     * Standard E (cal4) validation
     * @return bool
     */
    public function qcStE() {
        // StE > cutoff * 1.1
        $this->stE = ($this->getCal4() > (($this->getStD() * $this->param['kf_serum']) * 1.1) ? true : false);
        return $this->stE;
    }
    
    /**
     * Standard D average validation
     * @return boolean
     */
    public function qcStD() {
        // StD > 0.500
        $this->stD = ($this->getStD() > 0.500 ? true : false);
        return $this->stD;
    }
    
    /**
     * Standard CXCL13 average validation
     * @return boolean
     */
    public function qcStCXCL13() {
        // StCXCL13 > 0.500
        $this->stCXCL13 = ($this->getStCXCL13() > 0.500 ? true : false);
        return $this->stCXCL13;
    }
    
    /**
     * Ratio OD validation
     * @return boolean
     */
    public function qcRatio() {
        // RatioOD min < StE / StD < RatioOD max
        $this->ratio = ($this->getCal4() / $this->getStD() > $this->param['ratio_min'] ? ($this->getCal4() / $this->getStD() < $this->param['ratio_max'] ? true : false) : false);
        return $this->ratio;
    }
    
    /**
    * @return array
    */
    public function getQCreport() {
        // set array with qc results
        $this->qualityControl = array(
            'qcBlank' => $this->getBlank(),
            'qcStA' => $this->qcStA(),
            'qcStAmlu' => $this->qcStAmlu(),
            'qcStE' => $this->qcStE(),
            'qcStD' => $this->qcStD(),
            'qcStCXCL13' => $this->qcCXCL13(),
            'qcRatio' => $this->qcRatio());
        return $this->qualityControl;
    }
    
    public function getClassification($param = null) {
        /*dump($param);
        exit;*/
        if($param['dilution'] == '101') {
            if ($param['unit'] == 1) {
                $this->classification = ($param['serum_ip_min'] != 0 && $param['serum_ip_min'] != 0 ? "(Negative > " . $param['serum_ip_min'] . " > Greyzone > " . $param['serum_ip_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 2) {
                $this->classification = ($param['serum_au_min'] != 0 && $param['serum_au_min'] != 0 ? "(Negative > " . $param['serum_au_min'] . " > Greyzone > " . $param['serum_au_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 3) {
                $this->classification = ($param['serum_vieu_min'] != 0 && $param['serum_vieu_min'] != 0 ? "(Negative > " . $param['serum_vieu_min'] . " > Greyzone > " . $param['serum_vieu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 4) {
                $this->classification = ($param['serum_mlu_min'] != 0 && $param['serum_mlu_max'] != 0 ? "(Negative > " . $param['serum_mlu_min'] . " > Greyzone > " . $param['serum_mlu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 5) {
                $this->classification = ($param['serum_iu_min'] != 0 && $param['serum_iu_min'] != 0 ? "(Negative > " . $param['serum_iu_min'] . " > Greyzone > " . $param['serum_iu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = "";
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
            } elseif ($param['unit'] == 5) {
                $this->classification = ($param['csf_iu_min'] != 0 || $param['csf_iu_max'] != 0 ? "(Negative > " . $param['csf_iu_min'] . " > Greyzone > " . $param['csf_iu_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = ($param['csf_pg_min'] != 0 || $param['csf_pg_max'] != 0 ? "(Negative > " . $param['csf_pg_min'] . " > Greyzone > " . $param['csf_pg_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            }
        } elseif ($param['dilution'] == '81') {
            if ($param['unit'] == 1) {
                $this->classification = ($param['synovia_ip_min'] != 0 || $param['synovia_ip_min'] != 0 ? "(Negative > " . $param['synovia_ip_min'] . " > Greyzone > " . $param['synovia_ip_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 2) {
                $this->classification = ($param['synovia_au_min'] != 0 || $param['synovia_au_min'] != 0 ? "(Negative > " . $param['synovia_au_min'] . " > Greyzone > " . $param['synovia_au_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = "";
            }
        } elseif ($param['assay'] == '20') { // show interpratation if assay = ASFUG
            if ($param['unit'] == 1) { // IP
                $this->classification = ($param['serum_ip_min'] != 0 && $param['serum_ip_max'] != 0 ? "(Negative > " . $param['serum_ip_min'] . " > Greyzone > " . $param['serum_ip_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } elseif ($param['unit'] == 2) { // AU/ml
                $this->classification = ($param['serum_au_min'] != 0 && $param['serum_au_max'] != 0 ? "(Negative > " . $param['serum_au_min'] . " > Greyzone > " . $param['serum_au_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = "";
            }
        } elseif ($param['dilution'] != '101' || $param['dilution'] != '2' || $param['dilution'] != '81' || $param['dilution'] != '505') {
            if ($param['unit'] == 6) { //pg/ml
                $this->classification = ($param['csf_pg_min'] != 0 || $param['csf_pg_min'] != 0 ? "(Negative > " . $param['csf_pg_min'] . " > Greyzone > " . $param['csf_pg_max'] . " > Positive)" : "(Nevyžadováno / Unclaimed)");
            } else {
                $this->classification = "";
            }
        }  else {
            $this->classification = "";
        }
        return $this->classification;
    }
}
