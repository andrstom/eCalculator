<?php
declare(strict_types=1);
namespace App\Model;

ini_set('memory_limit','50M');

use Nette;
use Nette\Security\User;
use Nette\SmartObject;
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
    
    public function __construct(Nette\Database\Context $database, User $user)
    {
            $this->user = $user->getIdentity();
            $this->database = $database;
    }
    
    /**
     * Define PDF header
     * @return string
     */
    public function getHeader() {
        
        $this->reportHeader = '<div id="header">
            <div id="left"><img src="./images/vidia_logo.jpg" height="30px"></div>
            <div id="right">' . date("j.m.Y H:i:s", time()) . '</div>
        </div>';
    
        return $this->reportHeader;
        
    }
    
    /**
     * Define PDF footer
     * @return string
     */
    public function getFooter() {

        $detail = (!empty($this->user->print_detail) && $this->user->print_detail == "ANO" ? $this->user->company_name . ', ' . $this->user->address . ', ' . $this->user->ico : 'Vidia spol s.r.o, Nad Safinou II, Vestec');
        
        $this->reportFooter = '<div id="footer"><div id="left">' . $detail . '</div><div id="right">Strana/Page {PAGENO} z {nb}</div></div>';
        
        return $this->reportFooter;
        
    }
    
    /**
     * Define PDF content (ELISA)
     * @return string
     */
    public function getElisaContent($value)
    {
        
        /** App\Model\CalculatorElisaManager */
        $calculator = new CalculatorElisaManager($this->database);
        $param = $calculator->getParam($value);
        $result = $calculator->getResult($value);
        $interpret = $calculator->getInterpretation($value);

        /** App\Model\QualityControlManager */
        $qc = new QualityControlManager($value, $this->database);
        
        /** report content */
        $this->reportContent = '
        <div id="content">
            <div id="title">Protokol o měření / Assay protocol<br>' . $this->database->table('calc_assays')->get($param['assay'])->assay_name . '</div>
            <br>
            <label>Šarže/Lot: </label> ' . $param['batch'] . '<br>
            <label>Expirace/Exp: </label> ' . $param['expiry'] . '
            <br><br>
            <div id="parameters">
                <div id="left">
                    <div id="border-radius">
                        <table id="parameter">
                            <thead>
                                <tr><th colspan="2">Parametry / Parameters</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>St D B/B<sub>max</sub>: </td><td>' . str_replace(".", ",", $param['std_bmax']) . '</td></tr>
                                <tr><td>A1: </td><td>' . str_replace(".", ",", $param['a1']) . '</td></tr>
                                <tr><td>A2: </td><td>' . str_replace(".", ",", $param['a2']) . '</td></tr>
                                <tr><td>C: </td><td>' . str_replace(".", ",", $param['c']) . '</td></tr>
                                <tr><td>Korekční faktor / Correction factor <sub>serum</sub>: </td><td>' . str_replace(".", ",", $param['kf_serum']) . '</td></tr>
                                <tr><td>Korekční faktor / Correction factor <sub>CSF</sub>: </td><td>' . str_replace(".", ",", $param['kf_csf']) . '</td></tr>
                                <tr><td>Korekční faktor / Correction factor <sub>synovia</sub>: </td><td>' . str_replace(".", ",", $param['kf_synovia']) . '</td></tr>
                                <tr><td>Poměr / Ratio OD (ST E / ST D): </td><td>' . str_replace(".", ",", $param['ratio_min']) . ' - ' . str_replace(".", ",", $param['ratio_max']) . '</td></tr>
                                <tr><td>Ředení vzorku / Dilution: </td><td>' . str_replace(".", ",", $param['dilution']) . 'x</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="right">
                    <div id="border-radius">
                        <table id="parameter">
                            <thead>
                                <tr><th colspan="3">Validační kriteria / Validation criteria</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Blank < 0,150: </td><td>' . str_replace(".", ",", $param['Abs'][1]) . ' < 0,150</td><td>' . ($qc->getBlank() ? '<span id="valid">Valid</span>' : '<span id="invalid">Invalid</span>'). '</td></tr>
                                <tr><td>ST A/NC < 0,9 x CUT OFF: </td><td>' . str_replace(".", ",", $qc->getStA()) . ' < ' . number_format(($qc->getStD() * $param['kf_serum']) * 0.9, 3, ",", "") . '</td><td>' . ($qc->qcStA() ? '<span id="valid">Valid</span>' : '<span id="invalid">Invalid</span>') . '</td></tr>
                                ' . 
                                /** if unit = 4 (mlU/ml) */ 
                                ($param['unit'] == 4 ? '<tr><td>ST A/NC < 120 (mlU/ml): </td><td>' . str_replace(".", ",", $result[49]) . ' < 120</td><td>' . ($qc->qcStAmlu() ? '<span id="valid">Valid</span>' : '<span id="invalid">Invalid</span>') . '</td></tr>' : '') . '
                                <tr><td>ST E/PC > 1,1 x CUT OFF: </td><td>' . str_replace(".", ",", $qc->getStE()) . ' > ' . number_format(($qc->getStD() * $param['kf_serum']) * 1.1, 3, ",", "") . '</td><td>' . ($qc->qcStE() ? '<span id="valid">Valid</span>' : '<span id="invalid">Invalid</span>') . '</td></tr>
                                <tr><td>ST D/CAL > 0,500: </td><td>' . str_replace(".", ",", $qc->getStD()) . '</td><td>' . ($qc->qcStD() ? '<span id="valid">Valid</span>' : '<span id="invalid">Invalid</span>') . '</td></tr>
                                <tr><td>Poměr / Ratio OD ST E / ST D: </td><td>' . number_format(($param['Abs'][37] - $param['Abs'][1]) / $qc->getStD(), 3, ",", "") . '</td><td>' . ($qc->qcRatio() ? '<span id="valid">Valid</span>' : '<span id="invalid">Invalid</span>') . '</td></tr>
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
            <div id="results">
                Výsledky / Results (' . ($param['dilution'] == '101' ? 'Serum' : ($param['dilution'] == '2' ? 'CSF' : 'Synovia')) . '; ' . $this->database->table('calc_units')->get($param['unit'])->unit_name . ')
                <div id="border-radius">
                    <table id="assay-result">
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
                                        . '<td id="assay-result">'
                                        . '<span id="sample-id">' . $param['sampleId'][$j] . '</span><br>'
                                        . '<span id="absorbance-value">' . str_replace(".", ",", $param['Abs'][$j]) . '</span><br>'
                                        . '<span id="result-value">' . str_replace(".", ",", $result[$j]) . '</span><br>'
                                        . '<span id="interpretation">' . $interpret[$j] . '</span>'
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
            
            <div id="comments">
                
            </div>
            <div id="left">Provedl / Performed by: ................................</div>
            <div id="right">Ověřil / Verified by: ................................</div> 
        </div>';
        
        return $this->reportContent;
    }
    
    /**
     * Define PDF content (SYNTESA)
     * @return string
     */
    public function getSyntesaContent($value)
    {
        //dump($value);
        //exit;
        /** report content */
        $this->reportContent = ''
            . '<div id="content">
            <br><br>
            <div id="title">Výsledky intrathekální syntézy protilátek v CNS</div>
            <p>Results of intrathecal synthesis of antibodies in CNS</p>
            <br>
            <p class="warning">
            Upozornění: výsledná interpretace musí být vyhodnocena dle výsledků ELISA testu a dle interpretační tabulky!<br>
            Warning: the final interpretation must be evaluated according to the ELISA test results and table of interpretation!
            </p>
            <br>
            <div id="border-radius">
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
            <div id="left">Provedl / Performed by: ................................</div>
            <div id="right">Ověřil / Verified by: ................................</div> ';
        
        return $this->reportContent;
    }
    
    /**
     * PDF report via mPDF library
     * @param mixed $value
     */
    public function pdfReport($value)
    {
        //dump($value);
        //exit;
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
}
