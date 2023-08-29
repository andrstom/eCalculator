<?php
declare(strict_types=1);
namespace App\Model;

ini_set('memory_limit','50M');

use Nette;
use Nette\Security\User;
use Nette\SmartObject;
use App\Model\CalculatorMonoManager;
/**
 * Description of PdfManager
 *
 * Setting the appearance of the PDF report.
 */
class PdfManager
{
    /** @string */
    private $reportHeader;
    /** @string */
    private $reportContent;
    /** @string */
    private $reportFooter;
    /** @var Nette\Database\Context */
    private $database;
    /** @var \Nette\Security\User */
    private $user;
    /** @var App\Model\CalculatorMonoManager */
    private $calculatorMonoManager;
    
    public function __construct(Nette\Database\Context $database, User $user, CalculatorMonoManager $calculatorMonoManager)
    {
            $this->user = $user->getIdentity();
            $this->database = $database;
            $this->calculatorMonoManager = $calculatorMonoManager;
    }
    
    /**
     * Define PDF header
     * @return string
     */
    public function getHeader(): string
    {
        $this->reportHeader = '<div class="header">'
                . '<div class="left"><img src="./images/vidia_logo.jpg" height="30px"></div>'
                . '<div class="right">' . date("j.m.Y H:i:s", time()) . '</div>'
                . '</div>';
        return $this->reportHeader;
    }
    
    /**
     * Define PDF footer
     * @return string
     */
    public function getFooter(): string
    {
        $detail = (!empty($this->user->print_detail) && $this->user->print_detail == "ANO" ? $this->user->company_name . ', ' . $this->user->address . ', ' . $this->user->ico : 'Vidia spol s.r.o, Nad Safinou II, Vestec');
        $this->reportFooter = '<div class="footer">'
                . '<div class="left">' . $detail . '</div>'
                . '<div class="right">Page {PAGENO} z {nb}</div>'
                . '</div>';
        return $this->reportFooter;
    }
    
    /**
     * Define PDF content (ELISA)
     * @return string
     */
    public function getElisaContent($value): string
    {
        /** App\Model\CalculatorElisaManager */
        $calculator = new CalculatorElisaManager($this->database);
        $param = $calculator->getParam($value);
        $result = $calculator->getResult($value);
        $interpret = $calculator->getInterpretation($value);
        $assay_layout = $this->database->table('calc_assays')->get($param['assay'])->layout;

        /** App\Model\QualityControlManager */
        $qc = new QualityControlManager($value, $this->database);
        
        /** report content */
        $this->reportContent = '
        <div class="content">
            <div class="title">Protokol o měření / Assay protocol<br>' . $this->database->table('calc_assays')->get($param['assay'])->assay_name . '</div>
            <br>
            <label>Šarže/Lot: </label> ' . $param['batch'] . '<br>
            <label>Expirace/Exp: </label> ' . $param['expiry'] . '
            <br><br>
            <div class="parameters">
                <div class="left">
                    <div class="border-radius">
                        <table class="parameter">
                            <thead>
                                <tr><th colspan="2">Parametry / Parameters</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>St D B/B<sub>max</sub>: </td><td>' . str_replace(".", ",", $param['std_bmax']) . '</td></tr>
                                <tr><td>A1: </td><td>' . str_replace(".", ",", $param['a1']) . '</td></tr>
                                <tr><td>A2: </td><td>' . str_replace(".", ",", $param['a2']) . '</td></tr>
                                <tr><td>C: </td><td>' . str_replace(".", ",", $param['c']) . '</td></tr>';
                                if ($assay_layout == 1) { // default layout BL, CAL, CAL, PC, NC
                                    $this->reportContent .= '<tr><td>Korekční faktor / Correction factor <sub>serum</sub>: </td><td>' . str_replace(".", ",", $param['kf_serum']) . '</td></tr>
                                    <tr><td>Korekční faktor / Correction factor <sub>CSF</sub>: </td><td>' . str_replace(".", ",", $param['kf_csf']) . '</td></tr>
                                    <tr><td>Korekční faktor / Correction factor <sub>synovia</sub>: </td><td>' . str_replace(".", ",", $param['kf_synovia']) . '</td></tr>
                                    <tr><td>Poměr / Ratio OD (ST E / ST D): </td><td>' . str_replace(".", ",", $param['ratio_min']) . ' - ' . str_replace(".", ",", $param['ratio_max']) . '</td></tr>';
                                } elseif ($assay_layout == 2) { // CXCL13 layout BL, CAL, CAL
                                    $this->reportContent .= '<tr><td>Analytická citlivost / Analytical sensitivity (OD): </td><td>' . str_replace(".", ",", $param['c_min']) . '</td></tr>
                                    <tr><td>Mez detekce (pg/ml) / Detection limit (pg/ml): </td><td>' . str_replace(".", ",", $param['detection_limit']) . '</td></tr>';
                                } else {
                                    $this->reportContent .= '';
                                }
                                $this->reportContent .= '<tr><td>Ředení vzorku / Dilution: </td><td>' . str_replace(".", ",", $param['dilution']) . 'x</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="right">
                    <div class="border-radius">
                        <table class="parameter">
                            <thead>
                                <tr><th colspan="3">Validační kriteria / Validation criteria</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Blank < '. number_format((float)$param['blank_max'], 3, ",", "") .': </td><td>' . number_format((float)$param['Abs'][1], 3, ",", "") . ' < ' . number_format((float)$param['blank_max'], 3, ",", "") . '</td><td>' . ($qc->getCal1() ? '<span class="valid">Valid</span>' : '<span class="invalid">Invalid</span>'). '</td></tr>
                                ' . 
                                /** if unit = 4 (mlU/ml) */ 
                                ($param['unit'] == 4 ? '<tr><td>ST A/NC < 120 (mlU/ml): </td><td>' . str_replace(".", ",", $result[49]) . ' < 120</td><td>' . ($qc->qcStAmlu() ? '<span class="valid">Valid</span>' : '<span class="invalid">Invalid</span>') . '</td></tr>' : '');
                                if ($assay_layout == 1) { // default layout BL, CAL, CAL, PC, NC
                                    $this->reportContent .= '<tr><td>ST A/NC < 0,9 x CUT OFF: </td><td>' . str_replace(".", ",", $qc->getCal5()) . ' < ' . number_format(($qc->getStD() * $param['kf_serum']) * 0.9, 3, ",", "") . '</td><td>' . ($qc->qcStA() ? '<span class="valid">Valid</span>' : '<span class="invalid">Invalid</span>') . '</td></tr>
                                    <tr><td>ST E/PC > 1,1 x CUT OFF: </td><td>' . str_replace(".", ",", $qc->getCal4()) . ' > ' . number_format(($qc->getStD() * $param['kf_serum']) * 1.1, 3, ",", "") . '</td><td>' . ($qc->qcStE() ? '<span class="valid">Valid</span>' : '<span class="invalid">Invalid</span>') . '</td></tr>
                                    <tr><td>ST D/CAL > 0,500: </td><td>' . str_replace(".", ",", $qc->getStD()) . '</td><td>' . ($qc->qcStD() ? '<span class="valid">Valid</span>' : '<span class="invalid">Invalid</span>') . '</td></tr>
                                    <tr><td>Poměr / Ratio OD ST E / ST D: </td><td>' . number_format($qc->getCal4() / $qc->getStD(), 3, ",", "") . '</td><td>' . ($qc->qcRatio() ? '<span class="valid">Valid</span>' : '<span class="invalid">Invalid</span>') . '</td></tr>';
                                } elseif ($assay_layout == 2) { // CXCL13 layout BL, CAL, CAL
                                    $this->reportContent .= '<tr><td>ST CXCL13 > 0,500: </td><td>' . number_format($qc->getStCXCL13(), 3, ",", "") . '</td><td>' . ($qc->qcStCXCL13() ? '<span class="valid">Valid</span>' : '<span class="invalid">Invalid</span>') . '</td></tr>
                                    <tr><td>Analytická citlivost / Analytical sensitivity (OD): </td><td colspan="2">' . str_replace(".", ",", $param['c_min']) . '</td></tr>
                                    <tr><td>Mez detekce / Detection limit (pg/ml): </td><td colspan="2">' . str_replace(".", ",", $param['detection_limit']) . '</td></tr>';
                                } else {
                                    $this->reportContent .= '';
                                }
                                $this->reportContent .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <br>';
        $i = 1;
        $j = 1;
        $r = array("", "A", "B", "C", "D", "E", "F", "G", "H");
        $this->reportContent .= '
            <div class="results">
                Výsledky / Results (' . ($param['dilution'] == '101' ? 'Serum' : ($param['dilution'] == '2' ? 'CSF' : ($param['dilution'] == '81' ? 'Synovia' : ($param['dilution'] == '505' ? 'Serum' : 'Jiné/Other')))) . '; ' . $this->database->table('calc_units')->get($param['unit'])->unit_name . ')
                <div class="border-radius">
                    <table class="assay-result">
                        <thead><tr><th></th><th>1.</th><th>2.</th><th>3.</th><th>4.</th><th>5.</th><th>6.</th><th>7.</th><th>8.</th><th>9.</th><th>10.</th><th>11.</th><th>12.</th></tr></thead>';
                        // print results
                        for ($row = 1; $row <= 8; ++$row) {
                            $this->reportContent .= '<tr><th>' . $r[$i] . '</th>';
                            $i++;
                            for ($col = 1; $col <= 12; ++$col) {
                                // relese data if sampleID is empty 
                                if (empty($param['sampleId'][$j])) {
                                    $param['Abs'][$j] = "";
                                    $result[$j] = "";
                                    $interpret[$j] = "";
                                }
                                // print cell with sampleID, Abs, Result and interpretation values
                                $this->reportContent .= ''
                                        . '<td class="assay-result">'
                                        . '<span class="sample-id">' . $param['sampleId'][$j] . '</span><br>'
                                        . '<span class="absorbance-value">' . str_replace(".", ",", $param['Abs'][$j]) . '</span><br>'
                                        . '<span class="result-value">' . str_replace(".", ",", $result[$j]) . '</span><br>'
                                        . '<span class="interpretation">' . $interpret[$j] . '</span>'
                                        . '</td>';
                                $j++;
                            }
                            $this->reportContent .= '</tr>';
                        }
                    $this->reportContent .= '</table>
                </div>
            </div>
            <br>
            <small><i>Hodnocení / Classification:<br>' . $qc->getClassification($param) . '</i></small>
            <div class="comments"></div>
            <div class="left">Provedl / Performed by: ................................</div>
            <div class="right">Ověřil / Verified by: ................................</div> 
        </div>';
        return $this->reportContent;
    }
    
    /**
     * Define PDF content (SYNTESA)
     * @return string
     */
    public function getSyntesaContent($value): string
    {
        /** report content */
        $this->reportContent = ''
            . '<div class="content">
            <br><br>
            <div class="title">Výsledky intrathekální syntézy protilátek v CNS</div>
            <p>Results of intrathecal synthesis of antibodies in CNS</p>
            <br>
            <p class="warning">
            Upozornění: výsledná interpretace musí být vyhodnocena dle výsledků ELISA testu a dle interpretační tabulky!<br>
            Warning: the final interpretation must be evaluated according to the ELISA test results and table of interpretation!
            </p>
            <br>
            <div class="border-radius">
                <table>
                    <thead>
                        <tr>
                            <th style="border-right: 1px solid #7e4a6a">Pacient ID</th>
                            <th style="border-right: 1px solid #7e4a6a">Metoda</th>
                            <th style="border-right: 1px solid #7e4a6a">Protilátka</th>
                            <th style="border-right: 1px solid #7e4a6a">Materiál</th>
                            <th style="border-right: 1px solid #7e4a6a">Konc. Ig<i>X</i> (AU/ml)</th>
                            <th style="border-right: 1px solid #7e4a6a">Celková konc. Ig<i>X</i>(mg/l)</th>
                            <th style="border-right: 1px solid #7e4a6a">Celková konc. albuminu (mg/l)</th>
                            <th style="border-right: 1px solid #7e4a6a">Q<sub>total albumin</sub></th>
                            <th>Antibody Index</th>
                        </tr>
                        <tr>
                            <td><i>Patient ID</i></td>
                            <td><i>Assay</i></td>
                            <td><i>Antibody</i></td>
                            <td><i>Sample</i></td>
                            <td><i>Conc. Ig<i>X</i> (AU/ml)</i></td>
                            <td><i>Total conc. Ig<i>X</i>(mg/l)</i></td>
                            <td><i>Total conc. albumin (mg/l)</i></td>
                            <td><i>Q<sub>total albumin</sub></i></td>
                            <td><i>Antibody Index</i></td>
                        </tr>
                    </thead>
                    <tbody>';
                    foreach ($value as $k => $v) {
                        $this->reportContent .= '<tr>'
                                . '<td rowspan="2"><b>' . $v['sampleId'] . '</b></td>'
                                . '<td rowspan="2">' . $v['assay'] . '</td>'
                                . '<td rowspan="2">' . $v['antibody'] . '</td>'
                                . '<td><b>Serum</b></td>'
                                . '<td>' . str_replace(".", ",", $v['serumIgAu']) . '</td>'
                                . '<td>' . str_replace(".", ",", $v['serumIgTotal']) . '</td>'
                                . '<td>' . str_replace(".", ",", $v['serumAlbTotal']) . '</td>'
                                . '<td rowspan="2"><b>' . str_replace(".", ",", round($v['qAlbTotal'], 4)) . '</b></td>'
                                . '<td rowspan="2"><b>' . str_replace(".", ",", round($v['abIndex'], 2)) . '</b></td>'
                            . '</tr>'
                            . '<tr>'
                                . '<td><b>CSF</b></td>'
                                . '<td>' . str_replace(".", ",", $v['csfIgAu']) . '</td>'
                                . '<td>' . str_replace(".", ",", $v['csfIgTotal']) . '</td>'
                                . '<td>' . str_replace(".", ",", $v['csfAlbTotal']) . '</td>'
                            . '</tr>';
                    }
                    $this->reportContent .= '
                    </tbody>
                </table>
            </div>
            <br><br><br>
            <div class="left">Provedl / Performed by: ................................</div>
            <div class="right">Ověřil / Verified by: ................................</div> ';
        return $this->reportContent;
    }
    
    /**
     * Define PDF content (Monotest)
     * @return string
     */
    public function getMonotestReport(array $protocols): string
    {
        /** report content */
        $this->reportContent = '
        <div class="content">
            <div class="title">MONO-VIDITEST&trade; Results protocol</div>
            <hr>';
            $testIterator = 1;
            $testPaginator = 0;
            foreach ($protocols as $protocol => $result) {
                $assay = $result->ref('calc_assays_mono','assays_id');
                $unit = $result->ref('calc_units_mono','units_id');
                $dilution = $result->ref('calc_dilutions','dilutions_id');
                $blank_validation = $result['blank_max'] < $result['blank_od'] ? "invalid" : "";
                $cal_validation = $result['cal_min'] > $result['cal_od'] ? "invalid" : "";
                $verified_result = $this->calculatorMonoManager->isMoreThenCmax($result);
                $this->reportContent .='
                <div class="result-wrapper">
                    <div class="result-number">' . $testIterator . '</div>
                    <div class="result">
                        <div class="alert"><img src="./images/alert-128-' . $result['is_valid'] . '.png" width="50" height="25" /></div>
                        <table class="result-info">
                            <thead>
                                <tr>
                                    <th class="w-15">Sample ID</th>
                                    <th class="w-30">MONO-VIDITEST&trade;</th>
                                    <th class="w-10">LOT</th>
                                    <th class="w-10">Dilution</th>
                                    <th class="w-10">OD Blank</th>
                                    <th class="w-10">OD Sample</th>
                                    <th class="w-10">OD CAL</th>
                                    <th class="w-15">Result (unit)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>'
                                    . '<td class="w-15 bold">' . $result['sample_id'] . '</td>'
                                    . '<td class="w-30">' . $assay->assay_name . '</td>'
                                    . '<td class="w-10">' . $result['batch'] . '</td>'
                                    . '<td class="w-10">' . $dilution->sample_type .'</td>'
                                    . '<td class="w-10 ' . $blank_validation . '">' . number_format($result['blank_od'], 3, ',', '') . '</td>'
                                    . '<td class="w-10">' . number_format($result['sample_od'], 3, ',', '') . '</td>'
                                    . '<td class="w-10 ' . $cal_validation . '">' . number_format($result['cal_od'], 3, ',', '') . '</td>'
                                    . '<td class="w-15 bold">' . str_replace(".", ",", $verified_result) . ' ('. $unit->unit_name .')<div class="interpret-' . $result['interpretation'] . '">' . $result['interpretation'] . '</div></td>'
                                . '</tr>
                            </tbody>
                        </table>
                        <div class="runtime-variables">Validation criteria and runtime variables:</div>
                        <table class="result-detail">
                            <thead>
                                <tr>
                                    <th class="w-10">OD Blank < X</th>
                                    <th class="w-10">OD CAL > X</th>';
                                    if ($unit->unit_short == 'IP') {
                                        $this->reportContent .= '<th class="w-10">Corr. factor</th>';
                                    } elseif ($unit->unit_short == 'pg') {
                                        $this->reportContent .= '<th class="w-10">Detection limit</th>
                                        <th class="w-10">CAL B/Bmax</th>
                                        <th class="w-10">A1</th>
                                        <th class="w-10">A2</th>
                                        <th class="w-10">C</th>
                                        <th class="w-10">Cmin</th>
                                        <th class="w-10">Cmax</th>';
                                    } else {
                                        $this->reportContent .= '<th class="w-10">CAL B/Bmax</th>
                                        <th class="w-10">A1</th>
                                        <th class="w-10">A2</th>
                                        <th class="w-10">C</th>
                                        <th class="w-10">Cmin</th>
                                        <th class="w-10">Cmax</th>';
                                    }
                                $this->reportContent .= '</tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="w-10 ' . $blank_validation . '">' . number_format($result['blank_max'], 3, ',', '') . ' ' . $blank_validation . '</td>
                                    <td class="w-10 ' . $cal_validation . '">' . number_format($result['cal_min'], 3, ',', '') . ' ' . $cal_validation . '</td>';
                                    if ($unit->unit_short == 'IP') {
                                        $this->reportContent .= '<td>' . number_format($result->kf, 2, ',', '') . '</td>';
                                    } elseif ($unit->unit_short == 'pg') {
                                        $this->reportContent .= '<td>' . $result['detection_limit'] . '</td>
                                        <td>' . number_format($result['std_bmax'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['a1'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['a2'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['c'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['c_min'], 2, ',', '') . '</td>
                                        <td>' . number_format($result['c_max'], 0, ',', '') . '</td>';
                                    } else {
                                        $this->reportContent .= '<td>' . number_format($result['std_bmax'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['a1'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['a2'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['c'], 4, ',', '') . '</td>
                                        <td>' . number_format($result['c_min'], 2, ',', '') . '</td>
                                        <td>' . number_format($result['c_max'], 0, ',', '') . '</td>';
                                    }
                                $this->reportContent .= '</tr>
                            </tbody>
                        </table>
                    </div>
                    <p>Comment: </p>
                </div>';
                $testIterator++;
                $testPaginator++;
                if ($testPaginator == 6) {
                    $this->reportContent .= '<pagebreak>';
                    $testPaginator = 0;
                }
            }
            $this->reportContent .= '<br><br>
            <div class="left">Performed by: ................................</div>
            <div class="right">Verified by: ................................</div>
        </div>';
        return $this->reportContent;
    }
    /**
     * PDF report via mPDF library
     * @param mixed $value
     */
    public function pdfReport($value)
    {
        /** page settings */
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5
        ]);
        $mpdf->SetDisplayMode('fullpage');
        /** set header and footer */
        $mpdf->SetHTMLHeader($this->getHeader());
        $mpdf->SetHTMLFooter($this->getFooter());
        /*
         * Set final content 
         */
        if (isset($value['sendElisaPdf'])) {
            /** load a stylesheet */
            $stylesheet = file_get_contents('./css/printElisa.css');
            $mpdf->WriteHTML($stylesheet, 1);       // The parameter 1 tells that this is css/style only and no body/html/text
            /** create final PDF content and file name */
            $mpdf->WriteHTML($this->getElisaContent($value), 2);
            $mpdf->Output($this->database->table('calc_assays')->get($value['assay'])->assay_short . '_'. date("ymd_His", time()) .'.pdf','I');
        } else {
            /** load a stylesheet */
            $stylesheet = file_get_contents('./css/printSyntesa.css');
            $mpdf->WriteHTML($stylesheet, 1);       // The parameter 1 tells that this is css/style only and no body/html/text
            /** create final PDF content and file name */
            $mpdf->WriteHTML($this->getSyntesaContent($value), 2);
            $mpdf->Output('Report_'. date("Ymd_His", time()) .'.pdf','I');
        }
    }
    
    /**
     * MONOTEST PDF report via mPDF library
     * @param mixed $value
     */
    public function pdfMonoReport($value)
    {
        /** page settings */
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5
        ]);
        $mpdf->SetDisplayMode('fullpage');
        /** set header and footer */
        $mpdf->SetHTMLHeader($this->getHeader());
        $mpdf->SetHTMLFooter($this->getFooter());
        /** load a stylesheet */
        $stylesheet = file_get_contents('./css/printMono.css');
        $mpdf->WriteHTML($stylesheet, 1);       // The parameter 1 tells that this is css/style only and no body/html/text
        /** create final PDF content and file name */
        $mpdf->WriteHTML($this->getMonotestReport($value), 2);
        $mpdf->Output('MONO-VIDITEST Calculator_'. date("ymd_His", time()) .'.pdf','I');
    }
}
