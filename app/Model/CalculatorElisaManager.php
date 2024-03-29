<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\User;

class CalculatorElisaManager {
    
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

    /** @array */
    public $interpretation;
    
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }
    
    public function getReader($value) {
        /** load reader (if exist) or set manual as default */
        $reader = $this->database->table('calc_reader')->get($value);
        if ($reader)
            $this->reader = $reader;
        else 
            $this->reader = "manual"; 
        return $this->reader;
    }
    
    public function getLayoutId($value) {
        // load layout
        $this->layout_id = $this->database->table('calc_assays')->get($value)->layout;
        return $this->layout_id;
    }
    
    public function getFileFormatVerification($reader, $file) {
        $allowedFormats = array(
            'XLS' => ['xls', 'XLS', 'xlsx', 'XLSX'],
            'TXT' => ['txt', 'TXT', 'csv', 'CSV']
        );
        // get file extension
        $fileFormat = pathinfo($file, PATHINFO_EXTENSION);
        if ($reader != 'manual') {
            if ($reader->reader_output == "XLS") {
                if (!in_array($fileFormat, $allowedFormats['XLS'])) {
                    return false;
                } else {
                    return true;
                }
            }
            if($reader->reader_output == "TXT") {
                if (!in_array($fileFormat, $allowedFormats['TXT'])) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
    /*
     * Update assay parametres
     * @param form values
     */
    public function updateAssayParameters($values, $user) {
        $assay_params = $this->database->table('calc_users_assays')
                ->where("users_id = ?", $user->id)
                ->where("assays_id = ?", $values['assay'])
                ->where("units_id = ?", $values['unit'])
                ->fetch();
        
        $assay = $this->database->table('calc_assays')->get($values['assay']);
        if($assay_params) {
            $assay_params->update([
                'batch' => $this->getParam($values['batch']),
                'expiry' => $this->getParam($values['expiry']),
                'blank_max' => $this->getParam($values['blank_max']),
                'std_bmax' => $this->getParam($values['std_bmax']),
                'a1' => $this->getParam($values['a1']),
                'a2' => $this->getParam($values['a2']),
                'c' => $this->getParam($values['c']),
                'c_min' => $this->getParam($values['c_min']),
                'c_max' => $this->getParam($values['c_max']),
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
                'synovia_au_max' => $this->getParam($values['synovia_au_max']),
                'editor' => $user->getIdentity()->getData()['login'],
                'edited_at' => time(),
            ]);
            // update KF and RATIO OD for non-CXCL13 assays
            if ($assay->assay_short != "CXCL13") {
                $assay_params->update([
                    'kf_serum' => $this->getParam($values['kf_serum']),
                    'kf_csf' => $this->getParam($values['kf_csf']),
                    'kf_synovia' => $this->getParam($values['kf_synovia']),
                    'ratio_min' => $this->getParam($values['ratio_min']),
                    'ratio_max' => $this->getParam($values['ratio_max']),
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
     * get all values from form and replace comma to dot
     * @return array
     */
    public function getParam($value) {
        $this->param = $value;
        if(!is_array($this->param)) {
            /* replace comma for parametres outside of array */
            $this->param = str_replace(",", ".", $this->param);
        } else {
            /* replace comma for parametres in array and associate CALs to sampleIds */
            foreach ($this->param as $k => $v) {
                $this->param['sampleId'][1] = $this->param['cal_1'];
                $this->param['sampleId'][13] = $this->param['cal_2'];
                $this->param['sampleId'][25] = $this->param['cal_3'];
                $this->param['sampleId'][37] = $this->param['cal_4'];
                $this->param['sampleId'][49] = $this->param['cal_5'];
                $this->param['sampleId'][61] = $this->param['cal_6'];
                $this->param['sampleId'][73] = $this->param['cal_7'];
                $this->param['sampleId'][85] = $this->param['cal_8'];
                $this->param[$k] = str_replace(",", ".", $v);
            }
            $reader = $this->getReader($this->param['reader']);
            if ($reader != "manual") {
                if ($reader->reader_output == "XLS") {
                    /**
                    *  Read Excel workbook
                    * @param string file tmp_name
                    * @param int sheetnumber (e.g. 1)
                    * @param string dimension (e.g. A1:L8)
                    */
                    $spreadsheet = new SpreadsheetManager($this->database);
                    $excelData = $spreadsheet->readExcel($this->param['file'], $reader->reader_xls_list, $reader->reader_data_range);
                    $this->param['Abs'] = $excelData;
                }
                if ($reader->reader_output == "TXT") {
                    /**
                     * TextManager load data from text file
                     * 
                     * @param string (tmpFileName)
                     * @param string (delimiter -> "\t", "|", " ", ...)
                     * @param string (dataRange -> row:col:line skipper -> "118:2:6")
                     * @return array
                     */
                    $text = new TextManager($this->database);
                    $textReport = $text->readText($this->param['file'], $reader->reader_txt_separator, $reader->reader_data_range);
                    $this->param['Abs'] = $textReport;
                }
            } else {
                foreach ($this->param['Abs'] as $k1 => $v1) {
                    $this->param['Abs'][$k1] = (float) $v1;
                }
            }
        }
        return $this->param;
    }
    
    /**
     * calculate result for index (IP)
     * @return float
     */
    public function calcIP($value) {
        $param = $this->getParam($value);
        
        $kf_csf_allowed = array(1, 2, 6); // platny KFcsf pro metody BBG, BBM, TBEVG
        $kf_synovial_allowed = array(1, 2); // platny KFsynovial pro metody BBG, BBM
        if ($param['dilution'] == "81" && in_array($param['assay'], $kf_synovial_allowed)) {
            $kf = $param['kf_synovia'];
        } elseif ($param['dilution'] == "2" && in_array($param['assay'], $kf_csf_allowed)) {
            $kf = $param['kf_csf'];
        } else {
            $kf = $param['kf_serum'];
        }
        $Blank = $param['Abs'][1];
        $cutoff = ((($param['Abs'][13] - $Blank) + ($param['Abs'][25] - $Blank)) / 2) * $kf;
        
        foreach ($param['Abs'] as $k => $v) {
            $sample = $v - $Blank;
            if (!empty($v)) {
                if ($sample <= 0) {
                    $result[$k] = "< Blank";
                } else {
                    $result[$k] = number_format($sample / $cutoff, 2, ".", " ");
                }
            } else {
                $result[$k] = "";
            }
        }
        return $result;
    }
    
    /**
     * calculate result for AU/ml (AU)
     * @return array
     */
    public function calcAU($value) {
        $param = $this->getParam($value);
        $Blank = $param['Abs'][1];
        $BMax = (((($param['Abs'][13] - $Blank) / $param['std_bmax']) + (($param['Abs'][25] - $Blank) / $param['std_bmax'])) / 2);
        $Cmax = (($param['c_max'] / 101) * $param['dilution']);
        if ($Cmax < 10) {
            $CmaxRounded = $Cmax; // without round to nearest 10
        } else {
            $CmaxRounded = round($Cmax, -1); // round to nearest 10
        }
        foreach ($param['Abs'] as $k => $v) {
            // substract BLANK value from sample
            $sample = $v - $Blank;
            // define condition
            $condition1 = $sample + 0.05;
            $condition2 = ($sample / $BMax) - $param['a1'];
            $condition3 = $sample / $BMax;
            // the calculation is made according to the condition
            if ($sample <= 0) { // division by zero
                $result[$k] = "< Blank";
            } elseif ($condition1 > $BMax) {
                $result[$k] = "> " . number_format($CmaxRounded, 0, '.', '');
            } elseif ($condition2 > 0) {
                $result[$k] = "> " . number_format($CmaxRounded, 0, '.', '');
            } elseif ($condition3 < $param['c_min']) {
                $result[$k] = ((($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min']) / 101) * $param['dilution'];
                if ($param['assay'] == '20') { // result for ASFUG
                    $result[$k] = $result[$k] / 5;
                }
                $result[$k] = number_format($result[$k] , 2, '.', '');
            } else {
                $result[$k] = ((log(($sample / $BMax - $param['a1']) / (-$param['a2'])) * (-$param['c'])) / 101) * $param['dilution'];
                if ($param['assay'] == '20') { // result for ASFUG
                    $result[$k] = $result[$k] / 5;
                }
                $result[$k] = number_format($result[$k] , 2, '.', '');
            }
            if ($result[$k] == "nan") {
                if ($param['assay'] == '20') { // result for ASFUG 
                    $result[$k] = "> " . number_format($CmaxRounded / 5, 0, '.', '');
                }
                $result[$k] = "> " . number_format($CmaxRounded, 0, '.', '');
            }
        }
        return $result;
    }
     
    /**
     * calculate result for mlU/ml (mlU)
     * @return array
     */
    public function calcMLU($value) {
        $param = $this->getParam($value);
        $Blank = $param['Abs'][1];
        $BMax = (((($param['Abs'][13] - $Blank) / $param['std_bmax']) + (($param['Abs'][25] - $Blank) / $param['std_bmax'])) / 2);
        $Cmax = (($param['c_max'] / 101) * $param['dilution']);
        if ($Cmax < 10) {
            $CmaxRounded = $Cmax; // without round to nearest 10
        } else {
            $CmaxRounded = round($Cmax, -1); // round to nearest 10
        }
        foreach ($param['Abs'] as $k => $v) {
            // substract BLANK value from sample
            $sample = $v - $Blank;
            // define conditions
            $condition1 = $sample + 0.05;
            $condition2 = ($sample / $BMax) - $param['a1'];
            $condition3 = $sample / $BMax;
            $condition4 = log(($sample / $BMax - $param['a1']) / (-$param['a2'])) * (-$param['c']);
            // the calculation is made according to the condition
            if ($sample <= 0) { // check division by zero
                $result[$k] = "< Blank";
            } elseif ($condition1 > $BMax) {
                $result[$k] = "> " . number_format($CmaxRounded, 0, '.', '');
            } elseif ($condition2 > 0) {
                $result[$k] = "> " . number_format($CmaxRounded, 0, '.', '');
            } elseif ($condition3 < $param['c_min']) {
                $result[$k] = ((($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min']) / 101) * $param['dilution'];
                $result[$k] = number_format($result[$k] , 2, '.', '');
            } elseif ($condition4 > $param['c_max']) {
                $result[$k] = "> " . number_format($CmaxRounded, 0, '.', '');
            } else {
                $result[$k] = ((log(($sample / $BMax - $param['a1'])/(-$param['a2'])) * (-$param['c'])) / 101) * $param['dilution'];
                $result[$k] = number_format($result[$k] , 2, '.', '');
            }
            if ($result[$k] == "nan") {
                $result[$k] = "> " . number_format($CmaxRounded, 0, '.', '');
            }
        }
        return $result;
    }
    
    /**
     * calculate result for VIEU/ml (VIEU)
     * @return array
     */
    public function calcVIEU($value) {
        $param = $this->getParam($value);
        $Blank = $param['Abs'][1];
        $BMax = (((($param['Abs'][13] - $Blank) / $param['std_bmax']) + (($param['Abs'][25] - $Blank) / $param['std_bmax'])) / 2);
        foreach ($param['Abs'] as $k => $v) {
            // substract BLANK value from sample
            $sample = $v - $Blank;
            // define condition
            $condition1 = $sample + 0.05;
            $condition2 = ($sample / $BMax) - $param['a1'];
            $condition3 = $sample / $BMax;
            if ($sample <= 0) { // check division by zero
                $result[$k] = "< Blank";
            } elseif ($condition1 > $BMax) {
                $result[$k] = $param['dilution'] == "2" ? "> 45" : "> 2200";
            } elseif ($condition2 > 0) {
                $result[$k] = $param['dilution'] == "2" ? "> 45" : "> 2200";
            } elseif ($condition3 < $param['c_min']) {
                $result[$k] = ($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min'];
                $result[$k] = ((2140.6 - (2085) / (1 + ($result[$k] * $result[$k]) / 5055.2)) / 101 ) * $param['dilution'];
                $result[$k] = number_format($result[$k] , 2, '.', '');
            } else {
                $result[$k] = log(($sample / $BMax - $param['a1']) / (-$param['a2'])) * (-$param['c']);
                $result[$k] = ((2140.6 - (2085) / (1 + ($result[$k] * $result[$k]) / 5055.2)) / 101) * $param['dilution'];
                $result[$k] = number_format($result[$k] , 2, '.', '');
            }
            if ($result[$k] == "nan") {
                $result[$k] = ($param['dilution'] == "2" ? "> 45" : "> 2200");
            }
        }
        return $result;
    }
    
    /**
     * calculate result for pg/ml (pg)
     * @return array
     */
    public function calcPG($value) {
        $param = $this->getParam($value);
        $BMax = (((($param['Abs'][13]) / $param['std_bmax']) + (($param['Abs'][25]) / $param['std_bmax'])) / 2);
        foreach ($param['Abs'] as $k => $v) {
            $sample = $v;
            // define condition
            $condition1 = $sample + 0.01;
            $condition2 = ($sample / $BMax) - $param['a1'];
            $condition3 = $sample / $BMax;
            if ($condition1 > $BMax) {
                $result[$k] = $param['c_max'] * $param['dilution'];
                $result[$k] = number_format($result[$k] , 2, '.', '');
            } elseif ($condition2 > 0) {
                $result[$k] = $param['c_max'] * $param['dilution'];
                $result[$k] = number_format($result[$k] , 2, '.', '');
            } elseif ($condition3 < $param['c_min']) {
                $result[$k] = (($sample / $BMax) * (log(($param['c_min'] - $param['a1']) / (-$param['a2']))) * (-$param['c']) / $param['c_min']) * $param['dilution'];
                $result[$k] = number_format((float)$result[$k] , 2, '.', '');
            } else {
                $result[$k] = (log(($sample / $BMax - $param['a1']) / (-$param['a2']))) * (-$param['c']) * $param['dilution'];
                $result[$k] = number_format((float)$result[$k] , 2, '.', '');
            }
            // result replacement
            if ($result[$k] == "nan") {
                $result[$k] = "> " . number_format((float)$param['c_max'], 0, '.', '');
            }
            if ($sample > 2) {
                $result[$k] = "OVER!";
            }
            if ($result[$k] < $param['detection_limit']) {
                $result[$k] = "< " . $param['detection_limit'];
            }
            
            if (($param['dilution'] == 2) && ($result[$k] >= (float)$param['c_max'])) {
                $result[$k] = "> " . number_format((float)$param['c_max'], 0, '.', '');
            }
            if($result[$k] >= (float)$param['c_max'] * 50) {
                $result[$k] = "> " . number_format((float)$param['c_max'] * 50, 0, '.', '');
            }
        }
        return $result;
    }
    
    /**
     * get results according to the selected units
     * default unit = AU/ml = IU/ml = pg/ml
     * @return array
     */
    public function getResult($value) {
        /** load unit details */
        $unit_short = $this->database->table('calc_units')->get($value['unit'])->unit_short;
        /** get the result per unit */
        if ($unit_short == "IP") {
            $this->result = $this->calcIP($value);
        } elseif ($unit_short == "VIEU") {
            $this->result = $this->calcVIEU($value);
        } elseif ($unit_short == "mlU") {
            $this->result = $this->calcMLU($value);
        } elseif ($unit_short == "pg") {
            $this->result = $this->calcPG($value); // pg/ml
        } else {
            $this->result = $this->calcAU($value); // AU, IU
        }
        return $this->result;
    }
    
    /**
     * Result interpretation (Negative/Greyzone/Positive)
     * 
     * @param mixed
     * @return array
     */
    public function getInterpretation($value)
    {
        $param = $this->getParam($value);
        $result = $this->getResult($value);
        $unit = $this->database->table('calc_units')->get($param['unit']);
        
        /** set range according to the selected dilution */
        if ($param['dilution'] == "101") {
            
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
        } elseif ($param['dilution'] == "2") {
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
        } elseif ($param['dilution'] == "81") {
            $ip_min = $param['synovia_ip_min'];
            $ip_max = $param['synovia_ip_max'];
            $au_min = $param['synovia_au_min'];
            $au_max = $param['synovia_au_max'];
        } elseif ($param['dilution'] == "505") {
            $ip_min = $param['serum_ip_min'];
            $ip_max = $param['serum_ip_max'];
            $au_min = $param['serum_au_min'];
            $au_max = $param['serum_au_max'];
        } else {
            $pg_min = $param['csf_pg_min'];
            $pg_max = $param['csf_pg_max'];
        }
        
        /** set the interpretation per unit */
        foreach ($result as $k =>$v) {
            
            if ($unit->unit_short == "IP") {
                $this->interpretation[$k] = (!empty($ip_min) && !empty($ip_min) ? ($v < $ip_min ? "<span id='ipret-negative'>Negative</span>" : ($v > $ip_max ? "<span id='ipret-positive'>Positive</span>" : "<span id='ipret-greyzone'>Greyzone<span>")) : "");
            } elseif ($unit->unit_short == "AU") {
                $this->interpretation[$k] = (!empty($au_min) && !empty($au_min) ? ($v < $au_min ? "<span id='ipret-negative'>Negative</span>" : ($v > $au_max ? "<span id='ipret-positive'>Positive</span>" : "<span id='ipret-greyzone'>Greyzone</span>")) : "");
            } elseif ($unit->unit_short == "mlU") {
                $this->interpretation[$k] = (!empty($mlu_min) && !empty($mlu_min) ? ($v < $mlu_min ? "<span id='ipret-negative'>Negative</span>" : ($v > $mlu_max ? "<span id='ipret-positive'>Positive</span>" : "<span id='ipret-greyzone'>Greyzone</span>")) : "");
            } elseif ($unit->unit_short == "IU") {
                $this->interpretation[$k] = (!empty($iu_min) && !empty($iu_min) ? ($v < $iu_min ? "<span id='ipret-negative'>Negative</span>" : ($v > $iu_max ? "<span id='ipret-positive'>Positive</span>" : "<span id='ipret-greyzone'>Greyzone</span>")) : "");
            } elseif ($unit->unit_short == "pg") {
                $this->interpretation[$k] = (!empty($pg_min) && !empty($pg_min) ? ($v == "< " . $param['detection_limit'] || $v < $pg_min ? "<span id='ipret-negative'>Negative</span>" : ($v > $pg_max ? "<span id='ipret-positive'>Positive</span>" : "<span id='ipret-greyzone'>Greyzone</span>")) : "");
            } else {
                $this->interpretation[$k] = (!empty($vieu_min) && !empty($vieu_min) ? ($v < $vieu_min ? "<span id='ipret-negative'>Negative</span>" : ($v > $vieu_max ? "<span id='ipret-positive'>Positive</span>" : "<span id='ipret-greyzone'>Greyzone</span>")) : "");
            }
            
            if ($v == "< Blank" || $v == "OVER!") {
                $this->interpretation[$k] = "<span id='ipret-positive'></span>";
            }
        }
        return $this->interpretation;
    }
    
}