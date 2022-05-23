<?php
declare(strict_types=1);
namespace App\Model;

use Nette;
use Nette\SmartObject;

/**
 * TextManager load data from text file or export data into txt file
 */
class TextManager
{
    
    /** @array */
    private $txtData;

    /** @string */
    private $txtReport;
    
    /** @array */
    private $data;
    
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }
    
    
    /**
     * readText load data from text file
     * 
     * @param string (tmpFileName)
     * @param string (delimiter -> "\t", "|", " ", ...)
     * @param string (dataRange -> row:col:line_skipper -> "118:2:6")
     * @return array
     */
    public function readText($tmpFileName = null, $delimiter, $dataRange)
    {
        /**
         * data range (firstRow:firstCol position)
         * @array
         */
        $textRange = explode(":", $dataRange);
        
        /**
         * set first and last row
         * @int
         */
        $firstRow = $textRange[0] - 1;
        $lastRow = $firstRow + 200;
        
        /**
         * set first and last column (total 12)
         * @int
         */
        $firstCol = $textRange[1] - 1;
        $lastCol = $firstCol + 11;
        
        /**
         * set line skipper (count of free lines between lines with optical density)
         */
        $line_skipper = $textRange[2];
        
        /**
         * set the delimiters and its true equivalent
         * @array
         */
        $posibleDelimiters = array("space" => " ", "tab" => "\t", "pipe" => "|", "comma" => ",", "semicolon" => ";");

        /**
         * change delimiter name to equivalent
         * @string
         */
        if (array_key_exists($delimiter, $posibleDelimiters)) {
            
            $dlmtr = $posibleDelimiters[$delimiter];
            
        }

        /**
         * open file and associate rows
         * @array
         */
        $file = file($tmpFileName);

        foreach ($file as $lines) {
        
            $line[] = explode($dlmtr, $lines);
        
        }
        
        /**
         * associate NON-EMPTY values from file into array (by specific range, resp delimiters)
         * @array
         */
        $m = 1;
        
        for ($i = $firstRow; $i <= $lastRow; $i++) {
        
            for ($j = $firstCol; $j <= $lastCol; $j++) {
                
                if (empty($line[$i][$j])) {
                    
                    $line[$i][$j] = "0.0000";
                    
                }
                
                if (!empty($line[$i][$j])){
                    
                    if ($m <= 96) {
                        
                        $line[$i][$j] = str_replace("*****", "0.0000", $line[$i][$j]);
                        $this->txtData[$m] = trim(str_replace(",", ".", $line[$i][$j]));
                        $m++;
                        
                    }
                }
            
            }
            
            $i += $line_skipper;
            
        }

        return $this->txtData;
    }
    
    
    public function textReport($values)
    {
        /** App\Model\Calculator */
        $calculator = new CalculatorElisaManager($this->database);
        $param = $calculator->getParam($values);
        $result = $calculator->getResult($values);
        
        /** App\Model\QualityControlManager */
        $qc = new QualityControlManager($values, $this->database);
        
        /** open file */
        $tmpFilename = $this->database->table('calc_assays')->get($param['assay'])->assay_short . '_' . date('ymd_His', time()) . '.txt';
        $txtReport = fopen($tmpFilename, "w");

        /** set file content e.g. parameters, sample ID, reader values and results */
        $this->txtReport = "Protokol o měření / Assay protocol\n\r\n\r" . PHP_EOL
                . "Název metody / Assay name: " . $this->database->table('calc_assays')->get($param['assay'])->assay_name . "\n\r" . PHP_EOL
                . "Šarže / Lot: " . $param['batch'] . "\n\r" . PHP_EOL
                . "Expirace / Expiration: " . $param['expiry'] . "\n\r\n\r" . PHP_EOL

                . "Parametry / Parameters:\n\r" . PHP_EOL
                . "StD B/Bmax:\t" . str_replace(".", ",", $param['std_bmax']) . "\n\r" . PHP_EOL
                . "A1:\t" . str_replace(".", ",", $param['a1']) . "\n\r" . PHP_EOL
                . "A2:\t" . str_replace(".", ",", $param['a2']) . "\n\r" . PHP_EOL
                . "C:\t" . str_replace(".", ",", $param['c']) . "\n\r" . PHP_EOL
                . "Cmin:\t" . str_replace(".", ",", $param['c_min']) . "\n\r" . PHP_EOL
                . "Cmax:\t" . str_replace(".", ",", $param['c_max']) . "\n\r" . PHP_EOL
                . "Korekční faktor / Correction factor (serum):\t" . str_replace(".", ",", $param['kf_serum']) . "\n\r" . PHP_EOL
                . "Korekční faktor / Correction factor (CSF):\t" . str_replace(".", ",", $param['kf_csf']) . "\n\r" . PHP_EOL
                . "Korekční faktor / Correction factor (synovia):\t" . str_replace(".", ",", $param['kf_synovia']) . "\n\r" . PHP_EOL
                . "Poměr / Ratio OD min:\t" . str_replace(".", ",", $param['ratio_min']) . "\n\r" . PHP_EOL
                . "Poměr / Ratio OD max:\t" . str_replace(".", ",", $param['ratio_max']) . "\n\r" . PHP_EOL
                . "Ředění vzorku / Dilution:\t" . $param['dilution'] . "x \n\r\n\r" . PHP_EOL

                . "Standardy / Standards" . "\n\r" . PHP_EOL
                . "Blank:\t" . str_replace(".", ",", $param['Abs'][1]) . "\n\r" . PHP_EOL
                . "St D/CAL průměr (average):\t" . str_replace(".", ",", $qc->getStD()) . "\n\r" . PHP_EOL
                . "St E/PC:\t" . str_replace(".", ",", $qc->getStE()) . "\n\r" . PHP_EOL
                . "St A/NC:\t" . str_replace(".", ",", $qc->getStA()) . "\n\r" . PHP_EOL
                . "Cut off (serum):\t" . str_replace(".", ",", $qc->getStD() * $param['kf_serum']) . "\n\r" . PHP_EOL
                . "Cut off (CSF):\t" . str_replace(".", ",", $qc->getStD() * $param['kf_csf']) . "\n\r" . PHP_EOL
                . "Cut off (synovia):\t" . str_replace(".", ",", $qc->getStD() * $param['kf_synovia']) . "\n\r\n\r" . PHP_EOL;
        
        /**
         * sample ID
         */
        $this->txtReport .= "ID vzorku / Sample ID:\n\r" . PHP_EOL;
        $i = 1;
        for ($row = "A"; $row <= "H"; $row ++) {

            $this->txtReport .= $row . "\t";

            for ($col = 1; $col <= 12; $col ++) {

                if ($param['sampleId'][$i]) {

                    $this->txtReport .= $param['sampleId'][$i] . "\t";
                } else {

                    $this->txtReport .= "XXX\t";
                }
                $i++;
            }
            $this->txtReport .= "\n\r" . PHP_EOL;
        }

        $this->txtReport .= "\n\r\n\r" . PHP_EOL;

        /**
         * optical density (Absorbance)
         */
        $this->txtReport .= "Optická densita / Optical density:\n\r" . PHP_EOL;
        $i = 1;
        for ($row = "A"; $row <= "H"; $row ++) {

            $this->txtReport .= $row . "\t";

            for ($col = 1; $col <= 12; $col ++) {

                if (empty($param['sampleId'][$i])) {
                    
                    $this->txtReport .= "XXX\t";
                    
                } else {
                    
                    $this->txtReport .= number_format((float) $param['Abs'][$i], 4, ',', '') . "\t";
                    
                }
                $i++;
            }
            $this->txtReport .= "\n\r" . PHP_EOL;
        }

        $this->txtReport .= "\n\r\n\r" . PHP_EOL;

        /**
         * results
         */
        $this->txtReport .= "Výsledky / Results  (" . $this->database->table('calc_units')->get($param['unit'])->unit_name . "):\n\r" . PHP_EOL;
        
        $i = 1;
        for ($row = "A"; $row <= "H"; $row ++) {

            $this->txtReport .= $row . "\t";

            for ($col = 1; $col <= 12; $col ++) {

                if (empty($param['sampleId'][$i])) {

                    $this->txtReport .= "XXX\t";

                } else {

                    $this->txtReport .= str_replace(".", ",", $result[$i]) . "\t";

                }
                $i++;
            }
            $this->txtReport .= "\n\r" . PHP_EOL;
        }

        
        /**
         * write content into file and close file
         */
        $this->txtReport = fwrite($txtReport, $this->txtReport);
        $this->txtReport = fclose($txtReport);
        $this->txtReport = header('Content-Type: application/octet-stream');
        $this->txtReport = header('Content-Disposition: attachment; filename=' . basename($tmpFilename));
        $this->txtReport = header('Expires: 0');
        $this->txtReport = header('Cache-Control: must-revalidate');
        $this->txtReport = header('Pragma: public');
        $this->txtReport = header('Content-Length: ' . filesize($tmpFilename));
        $this->txtReport = readfile($tmpFilename);
        
        /**
         * delete temporary file
         */
        unlink($tmpFilename);
        
        return $this->txtReport;
    }
    
    public function exportSyntesaTxt($values)
    {
        //dump($values);
        //exit;
        /** open file */
        $tmpFilename =  'Antibody_index_' . date('ymd_His', time()) . '.txt';
        $txtReport = fopen($tmpFilename, "w");

        /** set file content e.g. parameters, sample ID, reader values and results */
        $this->txtReport = "Výsledky výpočtu intrathekální syntézy protilátek v CNS / Results of calculation of intrathecal synthesis of antibodies in CNS.\n\r\n\r" . PHP_EOL
                . "Upozornění: výsledná interpretace musí být vyhodnocena dle výsledků ELISA testu a dle přiložené tabulky!\n\r" . PHP_EOL
                . "Warning: the final interpretation must be evaluated according to the ELISA test results and the attached table!\n\r\n\r\n\r" . PHP_EOL
                
                // czech row
                . "Vzorek ID\t"
                . "Metoda\t"
                . "Protilátka\t"
                . "Konc. IgX v séru (AU/ml)\t"
                . "Konc. IgX v CSF (AU/ml)\t"
                . "Celková konc. IgX v séru (mg/l)\t"
                . "Celková konc. IgX v CSF (mg/l)\t"
                . "Celková konc. albuminu v séru (mg/l)\t"
                . "Celková konc. albuminu v CSF (mg/l)\t"
                . "Q total albumin\t"
                . "Antibody index\t"
                . "\n\r" . PHP_EOL
                // english row
                . "Sample ID\t"
                . "Assay\t"
                . "Antibody\t"
                . "Serum IgX conc. (AU/ml)\t"
                . "CSF IgX conc. (AU/ml)\t"
                . "Serum total IgX conc. (mg/l)\t"
                . "CSF total IgX conc. (mg/l)\t"
                . "Serum total albumin conc. (AU/ml)\t"
                . "CSF total albumin conc. (AU/ml)\t"
                . "Q total albumin\t"
                . "Antibody index"
                . "\n\r" . PHP_EOL;
                
                // samples
                foreach ($values as $k => $v) {
            
                    $this->txtReport .= "". str_replace(".", ",", $v['sampleId']) . "\t" .
                            str_replace(".", ",", $v['assay']) . "\t" .
                            str_replace(".", ",", $v['antibody']) . "\t" .
                            str_replace(".", ",", $v['serumIgAu']) . "\t" .
                            str_replace(".", ",", $v['csfIgAu']) . "\t" .
                            str_replace(".", ",", $v['serumIgTotal']) . "\t" .
                            str_replace(".", ",", $v['csfIgTotal']) . "\t" .
                            str_replace(".", ",", $v['serumAlbTotal']) . "\t" .
                            str_replace(".", ",", $v['csfAlbTotal']) . "\t" .
                            str_replace(".", ",", $v['qAlbTotal']) . "\t" .
                            str_replace(".", ",", $v['abIndex']) . "\n\r" . PHP_EOL;
                }
                
        
        /**
         * write content into file and close file
         */
        $this->txtReport = fwrite($txtReport, $this->txtReport);
        $this->txtReport = fclose($txtReport);
        $this->txtReport = header('Content-Type: application/octet-stream');
        $this->txtReport = header('Content-Disposition: attachment; filename=' . basename($tmpFilename));
        $this->txtReport = header('Expires: 0');
        $this->txtReport = header('Cache-Control: must-revalidate');
        $this->txtReport = header('Pragma: public');
        $this->txtReport = header('Content-Length: ' . filesize($tmpFilename));
        $this->txtReport = readfile($tmpFilename);
        
        /**
         * delete temporary file
         */
        unlink($tmpFilename);
        
        return $this->txtReport;
    }
}
?>