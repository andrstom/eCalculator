<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Nette\SmartObject;
use App\Model\CalculatorMonoManager;

/**
 * Description of SpreadsheetManager
 *
 * @author andrs
 */
class SpreadsheetManager
{
    // @array
    private $readData;
    
    /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet*/
    private $spreadsheet;

    /** @var Nette\Database\Context */
    private $database;
    
    /** @var App\Model\CalculatorMonoManager */
    private $calculatorMonoManager;
    
    public function __construct(Nette\Database\Context $database, CalculatorMonoManager $calculatorMonoManager) {
        $this->database = $database;
        $this->calculatorMonoManager = $calculatorMonoManager;
    }
    
    /**
     * Read excel sheet
     * 
     * @param file (tmpFileName)
     * @param int (sheetNumber -> 0, 1, ...)
     * @param string (dataArea -> "A1:L8")
     * @return array
     */
    public function readExcel($tmpFileName = null, $sheetNumber = null, $dataArea = null) {
        // read data from excel file
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFileName)
                ->getSheet($sheetNumber - 1)                            // set sheet number (substract -1)
                ->rangeToArray($dataArea, 0, true, true, true);         // set area for reading
        
        /** Associate NON-EMPTY data from worksheet into array and replace comma to dot */
        $i = 1;
        foreach ($spreadsheet as $line) {
            foreach ($line as $k => $v) {
                if (!empty($v) || $v == 0)
                    $this->readData[$i] = str_replace(",", ".", $v);
                $i++;
            }
        }
        return $this->readData; 
    }
    
    /**
     * Syntesa results xls export
     * @param array
     * @return xls datasheet
     */
    public function exportSyntesaXls($values) {
        
        // Create new Spreadsheet object
        $this->spreadsheet = new Spreadsheet();
        
        // Sheet details
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->spreadsheet->getActiveSheet()->setTitle('Antibody index - Results');
        
        // Set table title
        $this->spreadsheet->getActiveSheet()->setCellValue('A1', 'Výsledky intrathekální syntézy protilátek v CNS');
        $this->spreadsheet->getActiveSheet()->setCellValue('A2', 'Results of intrathecal synthesis of antibodies in CNS');
        $this->spreadsheet->getActiveSheet()->setCellValue('A5', 'Upozornění: výsledná interpretace musí být vyhodnocena dle výsledků ELISA testu a dle přiložené tabulky (ZDE)!');
        $this->spreadsheet->getActiveSheet()->setCellValue('A6', 'Warning: the final interpretation must be evaluated according to the ELISA test results and the attached table (HERE)');

        // Set table header
        // czech
        $this->spreadsheet->getActiveSheet()->setCellValue('A8', 'Vzorek ID');
        $this->spreadsheet->getActiveSheet()->setCellValue('B8', 'Metoda');
        $this->spreadsheet->getActiveSheet()->setCellValue('C8', 'Protilátka');
        $this->spreadsheet->getActiveSheet()->setCellValue('D8', 'Konc. Ig X v séru (AU/ml)');
        $this->spreadsheet->getActiveSheet()->setCellValue('E8', 'Konc. Ig X v CSF (AU/ml)');
        $this->spreadsheet->getActiveSheet()->setCellValue('F8', 'Celková konc. IgX v séru (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('G8', 'Celková konc. IgX v CSF (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('H8', 'Celková konc. albuminu v séru (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('I8', 'Celková konc. albuminu v CSF (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('J8', 'Q total albumin');
        $this->spreadsheet->getActiveSheet()->setCellValue('K8', 'Antibody index');
        // english
        $this->spreadsheet->getActiveSheet()->setCellValue('A9', 'Sample ID');
        $this->spreadsheet->getActiveSheet()->setCellValue('B9', 'Assay');
        $this->spreadsheet->getActiveSheet()->setCellValue('C9', 'Antibody');
        $this->spreadsheet->getActiveSheet()->setCellValue('D9', 'Serum Ig X conc. (AU/ml)');
        $this->spreadsheet->getActiveSheet()->setCellValue('E9', 'CSF Ig X conc. (AU/ml)');
        $this->spreadsheet->getActiveSheet()->setCellValue('F9', 'Serum total IgX conc. (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('G9', 'CSF total Ig X conc. (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('H9', 'Serum total albumin conc. (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('I9', 'CCSF total albumin conc. (mg/l)');
        $this->spreadsheet->getActiveSheet()->setCellValue('J9', 'Q total albumin');
        $this->spreadsheet->getActiveSheet()->setCellValue('K9', 'Antibody index');
        
        // Add data from  FORM
        $i = 10; // start row
        foreach ($values as $k => $v) {
            $this->spreadsheet->getActiveSheet()->setCellValue('A' . $i, $v['sampleId']);
            $this->spreadsheet->getActiveSheet()->setCellValue('B' . $i, $v['assay']);
            $this->spreadsheet->getActiveSheet()->setCellValue('C' . $i, $v['antibody']);
            $this->spreadsheet->getActiveSheet()->setCellValue('D' . $i, $v['serumIgAu']);
            $this->spreadsheet->getActiveSheet()->setCellValue('E' . $i, $v['csfIgAu']);
            $this->spreadsheet->getActiveSheet()->setCellValue('F' . $i, $v['serumIgTotal']);
            $this->spreadsheet->getActiveSheet()->setCellValue('G' . $i, $v['csfIgTotal']);
            $this->spreadsheet->getActiveSheet()->setCellValue('H' . $i, $v['serumAlbTotal']);
            $this->spreadsheet->getActiveSheet()->setCellValue('I' . $i, $v['csfAlbTotal']);
            $this->spreadsheet->getActiveSheet()->setCellValue('J' . $i, round($v['qAlbTotal'], 4));
            $this->spreadsheet->getActiveSheet()->setCellValue('K' . $i, round($v['abIndex'], 2));
            $i++;
        }
        
        // set cell settings
        $this->spreadsheet->getActiveSheet()->getStyle('A8:K9')->getAlignment()->setWrapText(true);
        
        $this->spreadsheet->getActiveSheet()->getCell('A5')->getHyperlink()->setUrl('https://www.vidia.cz/eCalculator/images/IS_interpretation_table.pdf');
        $this->spreadsheet->getActiveSheet()->getCell('A6')->getHyperlink()->setUrl('https://www.vidia.cz/eCalculator/images/IS_interpretation_table.pdf');
        
        // MERGE cells
        $cellToMerge = array('A1:K1', 'A2:K2', 'A3:K3', 'A4:K4', 'A5:K5', 'A6:K6');
        foreach ($cellToMerge as $cells) {
            $this->spreadsheet->getActiveSheet()->mergeCells($cells);
        }
        
        // THIN borders
        $styleBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                ],
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                ],
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                ],
            ],
        ];

        $thinBorder = array('A10:'. $this->spreadsheet->getActiveSheet()->getHighestColumn() . $this->spreadsheet->getActiveSheet()->getHighestRow());
        foreach ($thinBorder as $cells) {
            $this->spreadsheet->getActiveSheet()->getStyle($cells)->applyFromArray($styleBorder);
        }
        
        // set background color
        $this->spreadsheet->getActiveSheet()->getStyle('A8:K9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F581FF');
        $this->spreadsheet->getActiveSheet()->getStyle('J10:K' . $this->spreadsheet->getActiveSheet()->getHighestRow())
                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FAC0FF');
        
        // set column dimension
        foreach(range('A','K') as $column) {
             $this->spreadsheet->getActiveSheet()->getColumnDimension($column)->setWidth(12);
        }
        
        // set alignment
        $this->spreadsheet->getActiveSheet()->getStyle('A1:K9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $this->spreadsheet->getActiveSheet()->getStyle('A1:K9')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
        // set font size
        $this->spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setSize(20)->setBold(true);
        $this->spreadsheet->getActiveSheet()->getStyle('A2')->getFont()->setSize(15)->setBold(true);
        $this->spreadsheet->getActiveSheet()->getStyle('A3')->getFont()->setSize(12)->setItalic(true)->setBold(true);
        $this->spreadsheet->getActiveSheet()->getStyle('A5')->getFont()->setSize(12)->setItalic(true)->setColor( new \PhpOffice\PhpSpreadsheet\Style\Color( \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE ) );
        $this->spreadsheet->getActiveSheet()->getStyle('A6')->getFont()->setSize(10)->setItalic(true)->setColor( new \PhpOffice\PhpSpreadsheet\Style\Color( \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE ) );
        $this->spreadsheet->getActiveSheet()->getStyle('A8:K8')->getFont()->setSize(10)->setBold(true);
        $this->spreadsheet->getActiveSheet()->getStyle('A9:K9')->getFont()->setSize(8)->setItalic(true);
        $this->spreadsheet->getActiveSheet()->getStyle('J10:K' . $this->spreadsheet->getActiveSheet()->getHighestRow())->getFont()->setSize(13)->setBold(true);
        
        // set header
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&H Vidia spol. s r.o.');

        // set footer
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&R&H&P / &N');
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&HVytořeno / Created at ' . date("H:i:s d.m.Y",time()) . '&R&H &P of &N');

        
        // print settings
        $this->spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1)->setFitToHeight(1);
        
        // add Logo
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Vidia_logo');
        $drawing->setDescription('Vidia');
        $drawing->setPath('./images/vidia_logo.jpg'); // put your path and image here
        $drawing->setCoordinates('J1');
        $drawing->setWidth(120);
        $drawing->setWorksheet($this->spreadsheet->getActiveSheet());
        

        // Redirect output to a client’s web browser (Xls)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename="Antibody Index - ' . date("Ymd_His",time()) . '.xls"');
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xls');
        $writer->save('php://output');
    }
    
    /**
     * ELISA results xls export
     * @param array
     * @return xls datasheet
     */
    public function exportElisaXls($values) {
        
        /** App\Model\Calculator */
        $calculator = new CalculatorElisaManager($this->database);
        $param = $calculator->getParam($values);
        $result = $calculator->getResult($values);
        $assay = $this->database->table('calc_assays')->get($param['assay']);
        /** App\Model\QualityControlManager */
        $qc = new QualityControlManager($values, $this->database);
        
        // Create new Spreadsheet object
        $this->spreadsheet = new Spreadsheet();

        $this->spreadsheet->setActiveSheetIndex(0);
        $this->spreadsheet->getActiveSheet()->setTitle('Results_' . $assay->assay_short);

        /** assay info cells */
        $this->spreadsheet->getActiveSheet()->setCellValue('A1', 'Protokol o měření / Assay protocol');
        $this->spreadsheet->getActiveSheet()->setCellValue('A2', $assay->assay_name);
        $this->spreadsheet->getActiveSheet()->setCellValue('A3', 'Šarže / Lot');
        $this->spreadsheet->getActiveSheet()->setCellValue('C3', $param["batch"]);
        $this->spreadsheet->getActiveSheet()->setCellValue('A4', 'Expirace / Expiry');
        $this->spreadsheet->getActiveSheet()->setCellValue('C4', $param["expiry"]);

        /** parameters cells */
        $this->spreadsheet->getActiveSheet()->setCellValue('A6', 'Parametry / Parameters');
        $this->spreadsheet->getActiveSheet()->setCellValue('A7', 'St D B/Bmax');
        $this->spreadsheet->getActiveSheet()->setCellValue('E7', $param["std_bmax"]);
        $this->spreadsheet->getActiveSheet()->setCellValue('A8', 'A1');
        $this->spreadsheet->getActiveSheet()->setCellValue('E8', $param["a1"]);
        $this->spreadsheet->getActiveSheet()->setCellValue('A9', 'A2');
        $this->spreadsheet->getActiveSheet()->setCellValue('E9', $param["a2"]);
        $this->spreadsheet->getActiveSheet()->setCellValue('A10', 'C');
        $this->spreadsheet->getActiveSheet()->setCellValue('E10', $param["c"]);
        $this->spreadsheet->getActiveSheet()->setCellValue('A11', 'Cmin');
        $this->spreadsheet->getActiveSheet()->setCellValue('E11', $param["c_min"]);
        $this->spreadsheet->getActiveSheet()->setCellValue('A12', 'Cmax');
        $this->spreadsheet->getActiveSheet()->setCellValue('E12', $param["c_max"]);
        if ($assay->layout == 1) {
            $this->spreadsheet->getActiveSheet()->setCellValue('A14', 'Korekční faktor / Correction factor (serum)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E14', (isset($param['kf_serum']) ? $param['kf_serum'] : "N/A"));
            $this->spreadsheet->getActiveSheet()->setCellValue('A15', 'Korekční faktor / Correction factor (CSF)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E15', (isset($param['kf_csf']) ? $param['kf_csf'] : "N/A"));
            $this->spreadsheet->getActiveSheet()->setCellValue('A16', 'Korekční faktor / Correction factor (synovia)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E16', (isset($param['kf_synovia']) ? $param['kf_synovia'] : "N/A"));
            $this->spreadsheet->getActiveSheet()->setCellValue('A17', 'Poměr / Ratio OD (St E / St D) min-max');
            $this->spreadsheet->getActiveSheet()->setCellValue('E17', (isset($param['ratio_min']) ? $param['ratio_min'] : "N/A"));
            $this->spreadsheet->getActiveSheet()->setCellValue('F17', (isset($param['ratio_max']) ? $param['ratio_max'] : "N/A"));
        } elseif ($assay->layout == 2) {
            $this->spreadsheet->getActiveSheet()->setCellValue('A14', 'Analytická citlivost / Analytical sensitivity (OD)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E14', $param["c_min"]);
            $this->spreadsheet->getActiveSheet()->setCellValue('A15', 'Mez stanovitelnosti / Detection limit (pg/ml)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E15', $param["detection_limit"]);
        } else { // pripraveno pro dalsi layout
            $this->spreadsheet->getActiveSheet()->setCellValue('A14', '');
            $this->spreadsheet->getActiveSheet()->setCellValue('E14', '');
        }
        $this->spreadsheet->getActiveSheet()->setCellValue('A13', 'Ředění / Dilution');
        $this->spreadsheet->getActiveSheet()->setCellValue('E13', $param["dilution"]);
        
        /** standards */
        $this->spreadsheet->getActiveSheet()->setCellValue('A19', 'Standardy / Standards');
        $this->spreadsheet->getActiveSheet()->setCellValue('A20', 'Blank');
        $this->spreadsheet->getActiveSheet()->setCellValue('E20', $param['Abs'][1]);
        $this->spreadsheet->getActiveSheet()->setCellValue('F20', 'max. < ' . $param['blank_max']);
        if($assay->layout == 1) {
            $this->spreadsheet->getActiveSheet()->setCellValue('A21', 'St A / NC');
            $this->spreadsheet->getActiveSheet()->setCellValue('E21', $qc->getCal5());
            $this->spreadsheet->getActiveSheet()->setCellValue('A22', 'St E / PC');
            $this->spreadsheet->getActiveSheet()->setCellValue('E22', $qc->getCal4());
            $this->spreadsheet->getActiveSheet()->setCellValue('A23', 'St D / CAL');
            $this->spreadsheet->getActiveSheet()->setCellValue('E23', $qc->getStD());
            $this->spreadsheet->getActiveSheet()->setCellValue('A24', 'CUTOFF (serum)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E24', (isset($param['kf_serum']) ? $qc->getStD() * $param['kf_serum'] : "N/A"));
            $this->spreadsheet->getActiveSheet()->setCellValue('A25', 'CUTOFF (csf)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E25', (isset($param['kf_csf']) ? $qc->getStD() * $param['kf_csf'] : "N/A"));
            $this->spreadsheet->getActiveSheet()->setCellValue('A26', 'CUTOFF (synovia)');
            $this->spreadsheet->getActiveSheet()->setCellValue('E26', (isset($param['kf_synovia']) ? $qc->getStD() * $param['kf_synovia'] : "N/A"));
        } elseif($assay->layout == 2) {
            $this->spreadsheet->getActiveSheet()->setCellValue('A21', 'St CXCL13 average');
            $this->spreadsheet->getActiveSheet()->setCellValue('E21', $qc->getStCXCL13());
        } else { // pripraveno pro dalsi layout
            $this->spreadsheet->getActiveSheet()->setCellValue('A21', '');
            $this->spreadsheet->getActiveSheet()->setCellValue('E21', '');
        }
        
        /** table of sample ID */
        $this->spreadsheet->getActiveSheet()->setCellValue('A28', 'Vzorkek ID / Sample ID');
        
        $i = 29;    // sample ID start row
        $j = 1;
        for ($l = 1; $l <= 12; $l++) {
            for ($k = "A"; $k <= "L"; $k++) {
                if ($j == 97) break;
                $this->spreadsheet->getActiveSheet()->setCellValue($k . $i, $param['sampleId'][$j]);
                $j++;
            }
            $i++;
        }
        
        /** table of opical density */
        $this->spreadsheet->getActiveSheet()->setCellValue('A38', 'Optická denzita / Optical density');
        $i = 39;    // opical density start row
        $j = 1;
        for ($l = 1; $l <= 12; $l++) {
            for ($k = "A"; $k <= "L"; $k++) {
                if ($j == 97) break;
                
                $this->spreadsheet->getActiveSheet()->setCellValue($k . $i, $param['Abs'][$j]);
                $j++;
            }
            $i++;
        }
        
        /** table of results */
        $this->spreadsheet->getActiveSheet()->setCellValue('A48', "Výsledky / Results (" . $this->database->table('calc_units')->get($param['unit'])->unit_name . ")");
        $i = 49;    // results start row
        $j = 1;
        for ($l = 1; $l <= 12; $l++) {
            for ($k = "A"; $k <= "L"; $k++) {
                if ($j == 97) break;
                $this->spreadsheet->getActiveSheet()->setCellValue($k . $i, $result[$j]);
                $j++;
            }
            $i++;
        }
            
        
        /**
         * Cell style
         */
        
        /** set column width */
        foreach(range('A','L') as $columnID) {
            $this->spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setWidth(7.5);
        }
        
        /** set font size */
        $this->spreadsheet->getActiveSheet()->getStyle('A1:L85')->getFont()->setSize(9);
        
        /** THIN borders */
        $styleThinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        
        /** cells with thin borders */
        $thinBorder = array('A7:F17', 'A20:F26', 'A29:L36', 'A39:L46', 'A49:L56');
        foreach ($thinBorder as $cells) {
            $this->spreadsheet->getActiveSheet()->getStyle($cells)->applyFromArray($styleThinBorder);
        }
        
        /** MEDIUM borders */
        $styleMediumBorder = [
            'borders' => [
                'allBorders' => [
                    'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM
                ]
            ]
        ];
        
        /** cells with medium borders */
        $mediumBorder = array('A6:F6', 'A19:F19', 'A28:L28', 'A38:L38', 'A48:L48');
        
        /** apply style */
        foreach ($mediumBorder as $cells) {
            $this->spreadsheet->getActiveSheet()->getStyle($cells)->applyFromArray($styleMediumBorder);
        }
        
        /** MERGE cells */
        $cellToMerge = array('A1:L1', 'A2:L2', 'A3:B3', 'A4:B4', 'A6:F6', 'A7:D7', 'A8:D8', 'A9:D9', 'A10:D10', 'A11:D11', 'A12:D12', 'A13:D13', 'A14:D14', 'A15:D15', 'A16:D16',
            'A17:D17', 'A19:F19', 'A20:D20', 'A21:D21', 'A22:D22', 'A23:D23', 'A24:D24', 'A25:D25', 'A26:D26', 'A28:L28', 'A38:L38', 'A48:L48');
        
        /** apply style */
        foreach ($cellToMerge as $cells) {
            $this->spreadsheet->getActiveSheet()->mergeCells($cells);
        }
        
        /** HEADERS (font, align, background) */
        $reportHeader = [
            'font'  => [
                'bold'  => true,
                'size'  => 13
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => [
                    'rgb' => 'E589B7'
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]
        ];
        
        $tableHeader = [
            'font'  => [
                'bold'  => true,
                'size'  => 11
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => [
                    'rgb' => 'E589B7'
                ]
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        
        /** apply style */
        $this->spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($reportHeader);
        $this->spreadsheet->getActiveSheet()->getStyle('A2')->applyFromArray($reportHeader);
        $this->spreadsheet->getActiveSheet()->getStyle('A19')->applyFromArray($tableHeader);
        $this->spreadsheet->getActiveSheet()->getStyle('A28')->applyFromArray($tableHeader);
        $this->spreadsheet->getActiveSheet()->getStyle('A38')->applyFromArray($tableHeader);
        $this->spreadsheet->getActiveSheet()->getStyle('A48')->applyFromArray($tableHeader);
        $this->spreadsheet->getActiveSheet()->getStyle('A6:F6')->applyFromArray($tableHeader);
        
        // set header
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&H Vidia spol. s r.o.');

        // set footer
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&R&H&P / &N');
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&HVytořeno / Created at ' . date("H:i:s d.m.Y",time()) . '&R&H &P of &N');

        
        // add Logo
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Vidia_logo');
        $drawing->setDescription('Vidia');
        $drawing->setPath('./images/vidia_logo.jpg'); // put your path and image here
        $drawing->setCoordinates('I6');
        $drawing->setHeight(109);
        $drawing->setWorksheet($this->spreadsheet->getActiveSheet());
        
        // Redirect output to a client’s web browser (Xls)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename="' . $this->database->table('calc_assays')->get($param['assay'])->assay_short . '_' . date("Ymd_His",time()) . '.xls"');
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xls');
        $writer->save('php://output');
    }
    
    /**
     * MONOTEST export
     * @param string
     * @return xls datasheet
     */
    public function exportMonotestXls(string $protocolId)
    {
        $protocols = $this->database->table('calc_mono_results')->where('protocol_id', $protocolId)->fetchAll();
        // Create new Spreadsheet object
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->spreadsheet->getActiveSheet()->setTitle('MONO-VIDITEST');
        /** assay info cells */
        $this->spreadsheet->getActiveSheet()->setCellValue('A1', 'Sample ID');
        $this->spreadsheet->getActiveSheet()->setCellValue('B1', 'MONO-VIDITEST');
        $this->spreadsheet->getActiveSheet()->setCellValue('C1', 'LOT');
        $this->spreadsheet->getActiveSheet()->setCellValue('D1', 'Dilution');
        $this->spreadsheet->getActiveSheet()->setCellValue('E1', 'Unit');
        $this->spreadsheet->getActiveSheet()->setCellValue('F1', 'Blank > X');
        $this->spreadsheet->getActiveSheet()->setCellValue('G1', 'CAL < X');
        $this->spreadsheet->getActiveSheet()->setCellValue('H1', 'Correction factor');
        $this->spreadsheet->getActiveSheet()->setCellValue('I1', 'Detection limit');
        $this->spreadsheet->getActiveSheet()->setCellValue('J1', 'CAL B/Bmax');
        $this->spreadsheet->getActiveSheet()->setCellValue('K1', 'A1');
        $this->spreadsheet->getActiveSheet()->setCellValue('L1', 'A2');
        $this->spreadsheet->getActiveSheet()->setCellValue('M1', 'C');
        $this->spreadsheet->getActiveSheet()->setCellValue('N1', 'Cmin');
        $this->spreadsheet->getActiveSheet()->setCellValue('O1', 'Cmax');
        $this->spreadsheet->getActiveSheet()->setCellValue('P1', 'OD Blank');
        $this->spreadsheet->getActiveSheet()->setCellValue('Q1', 'OD Sample');
        $this->spreadsheet->getActiveSheet()->setCellValue('R1', 'OD CAL');
        $this->spreadsheet->getActiveSheet()->setCellValue('S1', 'Result');
        $this->spreadsheet->getActiveSheet()->setCellValue('T1', 'Interpretation');
        $this->spreadsheet->getActiveSheet()->setCellValue('U1', 'Validation');
        
        $i=2;
        foreach ($protocols as $result) {
            $verified_result = $this->calculatorMonoManager->isMoreThenCmax($result);
            $kf = $result->units_id == "1" ? $result->kf : "";
            $std_bmax = $result->units_id != "1" ? $result->std_bmax : "";
            $a1 = $result->units_id != "1" ? $result->a1 : "";
            $a2 = $result->units_id != "1" ? $result->a2 : "";
            $c = $result->units_id != "1" ? $result->c : "";
            $c_min = $result->units_id != "1" ? $result->c_min : "";
            $c_max = $result->units_id != "1" ? $result->c_max : "";
            $detection_limit = $result->units_id != "1" ? $result->detection_limit : "";
            $this->spreadsheet->getActiveSheet()->setCellValue('A'.$i, $result->sample_id);
            $this->spreadsheet->getActiveSheet()->setCellValue('B'.$i, $result->ref('calc_assays_mono', 'assays_id')->assay_name);
            $this->spreadsheet->getActiveSheet()->setCellValue('C'.$i, $result->batch);
            $this->spreadsheet->getActiveSheet()->setCellValue('D'.$i, $result->dilution_factor);
            $this->spreadsheet->getActiveSheet()->setCellValue('E'.$i, $result->ref('calc_units_mono', 'units_id')->unit_name);
            $this->spreadsheet->getActiveSheet()->setCellValue('F'.$i, $result->blank_max);
            $this->spreadsheet->getActiveSheet()->setCellValue('G'.$i, $result->cal_min);
            $this->spreadsheet->getActiveSheet()->setCellValue('H'.$i, $kf);
            $this->spreadsheet->getActiveSheet()->setCellValue('I'.$i, $detection_limit);
            $this->spreadsheet->getActiveSheet()->setCellValue('J'.$i, $std_bmax);
            $this->spreadsheet->getActiveSheet()->setCellValue('K'.$i, $a1);
            $this->spreadsheet->getActiveSheet()->setCellValue('L'.$i, $a2);
            $this->spreadsheet->getActiveSheet()->setCellValue('M'.$i, $c);
            $this->spreadsheet->getActiveSheet()->setCellValue('N'.$i, $c_min);
            $this->spreadsheet->getActiveSheet()->setCellValue('O'.$i, $c_max);
            $this->spreadsheet->getActiveSheet()->setCellValue('P'.$i, $result->blank_od);
            $this->spreadsheet->getActiveSheet()->setCellValue('Q'.$i, $result->sample_od);
            $this->spreadsheet->getActiveSheet()->setCellValue('R'.$i, $result->cal_od);
            $this->spreadsheet->getActiveSheet()->setCellValue('S'.$i, $verified_result);
            $this->spreadsheet->getActiveSheet()->setCellValue('T'.$i, $result->interpretation);
            $this->spreadsheet->getActiveSheet()->setCellValue('U'.$i, $result->is_valid);
            $i++;
        }
        /** set font size */
        $this->spreadsheet->getActiveSheet()->getStyle('A1:U1')->getFont()->setSize(10);
        /** set font size */
        $this->spreadsheet->getActiveSheet()->getStyle('A2:U200')->getFont()->setSize(10);
        // set header
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&H Vidia spol. s r.o.');
        // set footer
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&R&H&P / &N');
        $this->spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&HCreated at ' . date("H:i:s d.m.Y",time()) . '&R&H &P of &N');
        // Redirect output to a client’s web browser (Xls)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename="MONO-VIDITEST_' . date("Ymd_His",time()) . '.xls"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xls');
        $writer->save('php://output');
    }
}
