<?php
declare(strict_types=1);
namespace App\Model;

use Nette;
use Nette\Security\User;

class CalculatorMonoManager {
    
    use Nette\SmartObject;
    
    public $user;
    
    /** @array */
    public $param;
    
    /** @mixed */
    public $reader;

    /** @array */
    public $result;
    
    /** @int */
    public $layout_id;

    /** @string */
    public $interpretation;
    
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }
    
    public function getCountTestsOnProtocol(int $protocol_id): float {
        return $this->database->table('calc_mono_results')->where('protocol_id', $protocol_id)->count();
    }
    
    public function isProtocolId(int $protocol_id): bool {
        $protocolCount = $this->database->table('calc_mono_results')->where('protocol_id', $protocol_id)->count();
        if ($protocolCount == 0)
            return false;
        else 
            return true;
    }
    
    public function isTestInProtocol(int $protocol_id, int $test_id): bool {
        $testInProtocol = $this->database->table('calc_mono_results')->where('protocol_id', $protocol_id)->where('test_id', $test_id);
        if ($testInProtocol == 0)
            return false;
        else 
            return true;
    }
    
    /*
     * Update assay parametres
     * @param type array
     */
    public function updateAssayMonoParameters($values, $user) {
        $assay_params = $this->database->table('calc_users_assays_mono')
                ->where("users_id = ?", $user->id)
                ->where("assays_id = ?", $values['assays_id'])
                ->fetch();
        
        $assay = $this->database->table('calc_assays_mono')->get($values['assays_id']);
        if($assay_params) {
            $assay_params->update([
                'batch' => $this->getParam($values['batch']),
                'blank_max' => $this->getParam($values['blank_max']),
                'cal_min' => $this->getParam($values['cal_min']),
                'serum_ip_min' => $this->getParam($values['serum_ip_min']),
                'serum_ip_max' => $this->getParam($values['serum_ip_max']),
                'serum_au_min' => $this->getParam($values['serum_au_min']),
                'serum_au_max' => $this->getParam($values['serum_au_max']),
                'serum_mlu_min' => $this->getParam($values['serum_mlu_min']),
                'serum_mlu_max' => $this->getParam($values['serum_mlu_max']),
                'serum_vieu_min' => $this->getParam($values['serum_vieu_min']),
                'serum_vieu_max' => $this->getParam($values['serum_vieu_max']),
                'serum_iu_min' => $this->getParam($values['serum_iu_min']),
                'serum_iu_max' => $this->getParam($values['serum_iu_max']),
                'csf_ip_min' => $this->getParam($values['csf_ip_min']),
                'csf_ip_max' => $this->getParam($values['csf_ip_max']),
                'csf_au_min' => $this->getParam($values['csf_au_min']),
                'csf_au_max' => $this->getParam($values['csf_au_max']),
                'csf_mlu_min' => $this->getParam($values['csf_mlu_min']),
                'csf_mlu_max' => $this->getParam($values['csf_mlu_max']),
                'csf_vieu_min' => $this->getParam($values['csf_vieu_min']),
                'csf_vieu_max' => $this->getParam($values['csf_vieu_max']),
                'csf_iu_min' => $this->getParam($values['csf_iu_min']),
                'csf_iu_max' => $this->getParam($values['csf_iu_max']),
                'synovia_ip_min' => $this->getParam($values['synovia_ip_min']),
                'synovia_ip_max' => $this->getParam($values['synovia_ip_max']),
                'synovia_au_min' => $this->getParam($values['synovia_au_min']),
                'synovia_au_max' => $this->getParam($values['synovia_au_max'])
            ]);
            // update semikvantitative parameters
            if ($values['units_id'] == 1) {
                $assay_params->update([
                    'kf' => $this->getParam($values['kf'])
                ]);
            }            
            // update kvantitative parameters
            if ($values['units_id'] != 1) {
                $assay_params->update([
                    'std_bmax' => $this->getParam($values['std_bmax']),
                    'a1' => $this->getParam($values['a1']),
                    'a2' => $this->getParam($values['a2']),
                    'c' => $this->getParam($values['c']),
                    'c_min' => $this->getParam($values['c_min']),
                    'c_max' => $this->getParam($values['c_max'])
                ]);
            }
            // update detection limit for CXCL13 assay
            if ($assay->assay_short == "CXCL13") {
                $assay_params->update([
                    'detection_limit' => $this->getParam($values['detection_limit']),
                    'csf_pg_min' => $this->getParam($values['csf_pg_min']),
                    'csf_pg_max' => $this->getParam($values['csf_pg_max'])
                ]);
            }
        }
    }
    
    /**
     * Replace comma to dot
     * @param string|array
     * @return string|array
     */
    public function getParam($value) {
        $this->param = $value;
        if(!is_array($this->param)) {
            /* replace comma for parametres outside of array */
            $this->param = str_replace(",", ".", $this->param);
        } else {
            /* replace comma for parametres in array */
            foreach ($this->param as $k => $v) {
                $this->param[$k] = str_replace(",", ".", $v);
            }
        } 
        return $this->param;
    }
    
    /**
     * Set special result for specific combination of assay / dilution / unit
     * @param string $result
     * @param string $assay_id
     * @param string $dilution_factor
     * @param string $unit_id
     * @param string $c_max
     * @return float
     */
    public function specialOverResult(string $result, string $assay_id, string $dilution_factor, string $unit_id, string $c_max):float {
        $CmaxRounded = round(($c_max / 101) * $dilution_factor, -1);
        $specialResult = $result;
        if ($result == "nan") {
            $specialResult = $CmaxRounded;
        }
        if ($result <= 0) {
            $specialResult = 0;
        }
        // Borrelia IgM + CSF + AU
        if ($assay_id == 4 && $dilution_factor == 2 && $unit_id == 2 && ($result >= 16 || $result == "nan")) {
            $specialResult = 16;
        }
        // Borrelia IgM + Synovia + AU
        if ($assay_id == 4 && $dilution_factor == 81 && $unit_id == 2  && ($result >= 640 || $result == "nan")) {
            $specialResult = 640;
        }
        // Borrelia IgG + CSF + AU
        if ($assay_id == 5 && $dilution_factor == 2 && $unit_id == 2 && ($result >= 8 || $result == "nan")) {
            $specialResult = 8;
        }
        // Borrelia IgG + Synovia + AU
        if ($assay_id == 5 && $dilution_factor == 81 && $unit_id == 2 && ($result >= 320 || $result == "nan")) {
            $specialResult = 320;
        }
        // TBEV IgG + CSF + AU
        if ($assay_id == 40 && $dilution_factor == 2 && $unit_id == 2  && ($result >= 16 || $result == "nan")) {
            $specialResult = 16;
        }
        // TBEV IgG + Serum + VIEU
        if ($assay_id == 40 && $dilution_factor == 101 && $unit_id == 3  && ($result >= 2200 || $result == "nan")) {
            $specialResult = 2200;
        }
        // TBEV IgG + CSF + VIEU
        if ($assay_id == 40 && $dilution_factor == 2 && $unit_id == 3  && ($result >= 45 || $result == "nan")) {
            $specialResult = 45;
        }
        // ASFU IgG
        if ($assay_id == 3 && ($result >= $c_max || $result == "nan")) {  
            $specialResult = number_format($CmaxRounded / 5, 0, '.', '');
        }
        return (float)$specialResult;
    }
    
    /**
     * calculate result for index (IP)
     * @param array $value
     * @return float
     */
    public function calcIP(array $value):float
    {
        $param = $this->getParam($value);
        $sample = $param['sample_od'] - $param['blank_od'];
        $cutoff = ($param['cal_od'] - $param['blank_od']) * $param['kf'];
        
        if ($sample <= 0) {
            $result = 0;
        } else {
            $result = number_format($sample / $cutoff, 2, ".", " ");
        }
        return (float)$result;
    }
    
    /**
     * calculate result for AU/ml (AU)
     * @param array $value
     * @return float
     */
    public function calcAU(array $value):float
    {
        $param = $this->getParam($value);
        $Blank = $param['blank_od'];
        $BMax = ($param['cal_od'] - $Blank) / $param['std_bmax'];
        $CmaxRounded = round(($param['c_max'] / 101) * $param['dilution_factor'], -1); // round to nearest 10
        $sample = $param['sample_od'] - $Blank;
        // define condition
        $condition1 = $sample + 0.05;
        $condition2 = $sample / $BMax;
        // the calculation is made according to the condition
        if ($sample <= 0) { // division by zero
            $result = 0;
        } elseif ($condition1 > $BMax) {
            $result = number_format($CmaxRounded, 0, '.', '');
        } elseif ($condition2 < $param['c_min']) {
            $result = ((($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min']) / 101) * $param['dilution_factor'];
            if ($param['assays_id'] == 3) { // result for MONO-ASFUG
                $result = $result / 5;
            }
            $result = number_format($result , 2, '.', '');
        } else {
            $result = ((log(($sample / $BMax - $param['a1']) / (-$param['a2'])) * (-$param['c'])) / 101) * $param['dilution_factor'];
            if ($param['assays_id'] == 3) { // result for MONO-ASFUG
                $result = $result / 5;
            }
            $result = number_format($result , 2, '.', '');
        }
        $result = $this->specialOverResult($result, $param['assays_id'], $param['dilution_factor'], $param['units_id'], $param['c_max']);
        return (float)$result;
    }
     
    /**
     * calculate result for mlU/ml (mlU)
     * @param array $value
     * @return float
     */
    public function calcMLU(array $value):float
    {
        $param = $this->getParam($value);
        $Blank = $param['blank_od'];
        $BMax = ($param['cal_od'] - $Blank) / $param['std_bmax'];
        $CmaxRounded = round(($param['c_max'] / 101) * $param['dilution_factor'], -1); // round to nearest 10
        // substract BLANK value from sample
        $sample = $param['sample_od'] - $Blank;
        // define conditions
        $condition1 = $sample + 0.05;
        $condition2 = $sample / $BMax;
        $condition3 = log(($sample / $BMax - $param['a1']) / (-$param['a2'])) * (-$param['c']);
        // the calculation is made according to the condition
        if ($sample <= 0) { // check division by zero
            $result = 0;
        } elseif ($condition1 > $BMax) {
            $result = number_format($CmaxRounded, 0, '.', '');
        } elseif ($condition2 < $param['c_min']) {
            $result = ((($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min']) / 101) * $param['dilution_factor'];
            $result = number_format($result , 2, '.', '');
        } elseif ($condition3 > $param['c_max']) {
            $result = number_format($CmaxRounded, 0, '.', '');
        } else {
            $result = ((log(($sample / $BMax - $param['a1']) / (-$param['a2'])) * (-$param['c'])) / 101) * $param['dilution_factor'];
            $result = number_format($result , 2, '.', '');
        }
        $result = $this->specialOverResult($result, $param['assays_id'], $param['dilution_factor'], $param['units_id'], $param['c_max']);
        return (float)$result;
    }
    
    /**
     * calculate result for VIEU/ml (VIEU)
     * @param array $value
     * @return float
     */
    public function calcVIEU(array $value):float
    {
        $param = $this->getParam($value);
        $Blank = $param['blank_od'];
        $BMax = ($param['cal_od'] - $Blank) / $param['std_bmax'];
        // substract BLANK value from sample
        $sample = $param['sample_od'] - $Blank;
        // define condition
        $condition1 = $sample + 0.05;
        $condition2 = $sample / $BMax;
        if ($sample <= 0) { // check division by zero
            $result = 0;
        } elseif ($condition1 > $BMax) {
            $result = ($param['dilution_factor'] == "2") ? 45 : 2200;
        } elseif ($condition1 < $param['c_min']) {
            $result = ($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min'];
            $result = ((2140.6 - (2085) / (1 + ($result * $result) / 5055.2)) / 101 ) * $param['dilution_factor'];
            $result = number_format($result , 2, '.', '');
        } else {
            $result = log(($sample / $BMax - $param['a1']) / (-$param['a2'])) * (-$param['c']);
            $result = ((2140.6 - (2085) / (1 + ($result * $result) / 5055.2)) / 101) * $param['dilution_factor'];
            //$result = ($result < $param['c_max']) ? $result : (float)$param['c_max'];
            $result = number_format($result , 2, '.', '');
        }
        $result = $this->specialOverResult($result, $param['assays_id'], $param['dilution_factor'], $param['units_id'], $param['c_max']);
        return (float)$result;
    }
    
    /**
     * calculate result for pg/ml (pg)
     * @param array $value
     * @return float
     */
    public function calcPG(array $value):float
    {
        $param = $this->getParam($value);
        $BMax = $param['cal_od'] / $param['std_bmax']; // NO BLANK SUBSTRACTION !!!
        $sample = $param['sample_od'];
        // define condition
        $condition1 = $sample + 0.01;
        $condition2 = $sample / $BMax;
        if ($condition1 > $BMax) {
            $result = $param['c_max'];
            $result = number_format($result , 2, '.', '');
        } elseif ($condition2 < $param['c_min']) {
            $result = (($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min']) * $param['dilution_factor'];
            $result = number_format($result , 2, '.', '');
        } else {
            $result = (log(($sample / $BMax - $param['a1']) / (-$param['a2']))) * (-$param['c']) * $param['dilution_factor'];
            $result = number_format($result , 2, '.', '');
        }
        // result replacement
        if ($result >= $param['c_max']) {
            $result = number_format($param['c_max'], 0, '.', '');
        }
        if ($result == "nan") {
            $result = number_format($param['c_max'], 0, '.', '');
        }
        if ($sample > 2) {
            $result = number_format($param['c_max'], 0, '.', '');
        }
        if (isset($param['detection_limit'])) {
            if ($result < $param['detection_limit']) {
                $result = $param['detection_limit'];
            }
        }
        $result = $this->specialOverResult($result, $param['assays_id'], $param['dilution_factor'], $param['units_id'], $param['c_max']);
        return (float)$result;
    }

    /**
     * get results according to the selected units, default unit = AU/ml = IU/ml
     * @param array $value
     * @return float
     */
    public function getResult(array $value):float
    {
        /** load unit details */
        $unit_short = $this->database->table('calc_units_mono')->get($value['units_id'])->unit_short;
        /** get the result per unit */
        if ($unit_short == "IP") {
            $this->result = $this->calcIP($value);
        } elseif ($unit_short == "VIEU") {
            $this->result = $this->calcVIEU($value);
        } elseif ($unit_short == "mlU") {
            $this->result = $this->calcMLU($value);
        } elseif ($unit_short == "pg") {
            $this->result = $this->calcPG($value);
        } else {
            $this->result = $this->calcAU($value); // AU, IU
        }
        return $this->result;
    }
    
    /**
     * Result interpretation (Negative/Greyzone/Positive)
     * @param array
     * @return string
     */
    public function getInterpretation(array $value): string
    {
        $param = $this->getParam($value);
        $result = $this->getResult($value);
        $unit = $this->database->table('calc_units_mono')->get($param['units_id']);
        /** set range according to the selected dilution */
        if ($param['dilutions_id'] == "1") { // 1 = serum
            $ip_min = $param['serum_ip_min'];
            $ip_max = $param['serum_ip_max'];
            $au_min = $param['serum_au_min'];
            $au_max = $param['serum_au_max'];
            $mlu_min = $param['serum_mlu_min'];
            $mlu_max = $param['serum_mlu_max'];
            $vieu_min = $param['serum_vieu_min'];
            $vieu_max = $param['serum_vieu_max'];
            $iu_min = $param['serum_iu_min'];
            $iu_max = $param['serum_iu_max'];
        } elseif ($param['dilutions_id'] == "2") { // 2 = csf
            $ip_min = $param['csf_ip_min'];
            $ip_max = $param['csf_ip_max'];
            $au_min = $param['csf_au_min'];
            $au_max = $param['csf_au_max'];
            $mlu_min = $param['csf_mlu_min'];
            $mlu_max = $param['csf_mlu_max'];
            $vieu_min = $param['csf_vieu_min'];
            $vieu_max = $param['csf_vieu_max'];
            $iu_min = $param['csf_iu_min'];
            $iu_max = $param['csf_iu_max'];
            $pg_min = $param['csf_pg_min'];
            $pg_max = $param['csf_pg_max'];
        } elseif ($param['dilutions_id'] == "3") { // 3 = sinovia
            $ip_min = $param['synovia_ip_min'];
            $ip_max = $param['synovia_ip_max'];
            $au_min = $param['synovia_au_min'];
            $au_max = $param['synovia_au_max'];
        } elseif ($param['dilutions_id'] == "5") { // 5 = 505x A.fumigatus
            $ip_min = $param['serum_ip_min'];
            $ip_max = $param['serum_ip_max'];
            $au_min = $param['serum_au_min'];
            $au_max = $param['serum_au_max'];
        } else {
            $ip_min = "";
            $ip_max = "";
            $au_min = "";
            $au_max = "";
            $mlu_min = "";
            $mlu_max = "";
            $vieu_min = "";
            $vieu_max = "";
            $iu_min = "";
            $iu_max = "";
            $pg_min = "";
            $pg_max = "";
        }
        
        /** set the interpretation per unit */
        if ($unit->unit_short == "IP") {
            $this->interpretation = (!empty($ip_min) && !empty($ip_min) ? ($result < $ip_min ? "negative" : ($result > $ip_max ? "positive" : "<span id='ipret-greyzone'>Greyzone<span>")) : "");
        } elseif ($unit->unit_short == "AU") {
            $this->interpretation = (!empty($au_min) && !empty($au_min) ? ($result < $au_min ? "negative" : ($result > $au_max ? "positive" : "greyzone")) : "");
        } elseif ($unit->unit_short == "mlU") {
            $this->interpretation = (!empty($mlu_min) && !empty($mlu_min) ? ($result < $mlu_min ? "negative" : ($result > $mlu_max ? "positive" : "greyzone")) : "");
        } elseif ($unit->unit_short == "IU") {
            $this->interpretation = (!empty($iu_min) && !empty($iu_min) ? ($result < $iu_min ? "negative" : ($result > $iu_max ? "positive" : "greyzone")) : "");
        } elseif ($unit->unit_short == "pg") {
            $this->interpretation = (!empty($pg_min) && !empty($pg_min) ? ($result == "< " . $param['detection_limit'] || $result < $pg_min ? "negative" : ($result > $pg_max ? "positive" : "greyzone")) : "");
        } else {
            $this->interpretation = (!empty($vieu_min) && !empty($vieu_min) ? ($result < $vieu_min ? "negative" : ($result > $vieu_max ? "positive" : "greyzone")) : "");
        }
        return $this->interpretation;
    }
    
    /**
     * Verify if is result greather then Cmax
     * @param Nette\Database\Table\ActiveRow $result
     * @return string
     */
    public function isMoreThenCmax(Nette\Database\Table\ActiveRow $result): string {
        // default result
        $isMoreThen = "" . number_format($result->result, 2, ',', '');
        // value for assays (without BBG, BBM, TBEVG)
        if (!in_array($result->assays_id, [4 , 5, 40])) {
            if ($result->units_id != 1 && $result->result >= $result->c_max) {
                $isMoreThen = "> " . $result->result;
            }
        }
        // result for Borrelia IgG
        if ($result->assays_id == 5) {
            // AU/ml
            if ($result->units_id == 2) { 
                // Serum
                if ($result->dilution_factor == "101") {
                    if ($result->result >= $result->c_max) {
                        $isMoreThen = "> " . $result->c_max;
                    }
                }
                // CSF
                if ($result->dilution_factor == "2") {
                    if ($result->result >= 8) {
                        $isMoreThen = "> 8";
                    }
                }
                // Synovial
                if ($result->dilution_factor == "81") {
                    if ($result->result >= 320) {
                        $isMoreThen = "> 320";
                    }
                }
            }
        }
        // result for Borrelia IgM
        if ($result->assays_id == 4) {
            // AU/ml
            if ($result->units_id == 2) {
                // Serum
                if ($result->dilution_factor == "101") {
                    if ($result->result >= $result->c_max) {
                        $isMoreThen = "> " . $result->c_max;
                    }
                }
                // CSF
                if ($result->dilution_factor == "2") {
                    if ($result->result >= 16) {
                        $isMoreThen = "> 16";
                    }
                }
                // Synovial
                if ($result->dilution_factor == "81") {
                    if ($result->result >= 640) {
                        $isMoreThen = "> 640";
                    }
                }
            }
        }
        // result for TBEV IgG
        if ($result->assays_id == 40) {
            // AU/ml
            if ($result->units_id == 2) {
                // Serum
                if ($result->dilution_factor == "101") {
                    if ($result->result >= $result->c_max) {
                        $isMoreThen = "> " . $result->c_max;
                    }
                }
                // CSF
                if ($result->dilution_factor == "2") {
                    if ($result->result >= 16) {
                        $isMoreThen = "> 16";
                    }
                }
            }
            // VIEU/ml
            if ($result->units_id == 3) { 
                // Serum
                if ($result->dilution_factor == "101") {
                    if ($result->result >= 2200) {
                        $isMoreThen = "> 2200";
                    }
                }
                // CSF
                if ($result->dilution_factor == "2") {
                    if ($result->result >= 45) {
                        $isMoreThen = "> 45";
                    }
                }
            }
        }
        // result for VZV IgG
        if ($result->assays_id == 45) {
            // mlU/ml
            if ($result->units_id == 4) {
                // Serum
                if ($result->dilution_factor == "101") {
                    if ($result->result >= $result->c_max) {
                        $isMoreThen = "> " . $result->c_max;
                    }
                }
                // CSF
                if ($result->dilution_factor == "2") {
                    if ($result->result >= 80) {
                        $isMoreThen = "> 80";
                    }
                }
            }
        }
        return $isMoreThen;
    }
    
}