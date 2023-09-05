<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Application\Attributes\Persistent;
use Nette\Forms\Container;
use Nette\Application\UI\Multiplier;
use Nette\Security\Identity;
use App\Model\DbHandler;
use App\Model\PdfManager;
use App\Model\CalculatorMonoManager;
use App\Model\VisitorManager;


class MonoPresenter extends BasePresenter
{
    
    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;
    
    /**
     * @var \App\Model\CalculatorMonoManager
     * @inject
     */
    public $calculatorMonoManager;
    
    /**
     * @var \App\Model\PdfManager
     * @inject
     */
    public $pdfManager;
    
    /**
     * @var \App\Model\TextManager
     * @inject
     */
    public $textManager;
    
    /**
     * @var \App\Model\SpreadsheetManager
     * @inject
     */
    public $spreadsheetManager;
    
    /**
     * @var \App\Model\VisitorManager
     * @inject
     */
    public $visitorManager;

    public $userassay;
    public $editTest;

    public function renderDefault() {
        $this->template->assayIterator = 0;
        $this->template->dilutions = $this->dbHandler->getDilutions();
        $this->template->results = $this->dbHandler->getResultsBySession($this->getSession()->getId());
        $this->template->calculatorMonoManager = $this->calculatorMonoManager;
        $this->template->protocolId = $this->getSession()->getId();
    }

    /**
     * @param int $testId
     */
    public function renderEdit(int $testId) {
        $editTest = $this->dbHandler->getResultsBySession($this->getSession()->getId())->get($testId);
        $this->template->editTest = $editTest;
        if (!$editTest) {
            $this->error('Tento test nebyl nalezen! / This test is not exist!');
        }
    }

    public function renderMonoProtocols() {
        $this->template->monoProtocols = $this->dbHandler->getMonoProtocols()->group('protocol_id');
    }

    /**
     * @param string $protocolId
     */
    public function renderViewProtocol(string $protocolId) {
        $protocol = $this->dbHandler->getResultsBySession($protocolId);
        $this->template->protocol = $protocol;
        $this->template->protocolId = $protocolId;
        $this->template->calculatorMonoManager = $this->calculatorMonoManager;
        if (!$protocol) {
            $this->error('Protokol neexistuje / This protocol is not exist!');
        }
    }
    
    /**
     * Handle for loading assay data
     * @param string $assay_id
     * @param string $unit_id
     * @param string $dilution_id
     * @param string $sample_id
     * @param string $batch
     */
    public function handleLoadAssayDetails(string $assay_id = null, string $unit_id = null, string $dilution_id = null, string $sample_id = null, string $batch = null)
    {
        if($assay_id) {
            $assayMono = $this->dbHandler->getAssaysMono()->get($assay_id);
            $this->template->assayMono = $assayMono;
            foreach ($assayMono->related('calc_allowed_dilutions') as $allowedDilution) {
                $dilutions[$allowedDilution->dilutions->id] = $allowedDilution->dilutions->sample_type;
            }
            $this->template->dilutions = $dilutions;
            foreach ($assayMono->related('calc_allowed_units') as $allowedUnit) {
                $units[$allowedUnit->units->id] = $allowedUnit->units->unit_name;
            }
            $this->template->units = $units;
            if ($unit_id) {
                $unit = $this->dbHandler->getUnitsMono()->get($unit_id);
                $this->template->unitName = $unit->unit_name;
                if (in_array($unit->unit_name, $units)) {
                    $this['monoForm']['units_id']->setDefaultValue($unit_id);
                    $this->template->unitName = $unit->unit_name;
                }
                $this['monoForm']['dilutions_id']->setDefaultValue($dilution_id);
                $this['monoForm']['sample_id']->setDefaultValue($sample_id);
            }
            $this['monoForm']['assays_id']->setDefaultValue($assay_id);
            $this['monoForm']['dilutions_id']->setItems($dilutions);
            $this['monoForm']['units_id']->setItems($units);
            $this['monoForm']['sample_id']->setDefaultValue($sample_id);
            $this['monoForm']['batch']->setDefaultValue($batch);
            // load assay details
            if ($this->getUser()->isLoggedIn()) {
                $assaydetail = $this->dbHandler->getUsersAssaysMono()->where('assays_id', $assay_id)->where('users_id', $this->getUser()->id)->fetch();
                $userassay = $this->dbHandler->getAssaysMono()->where('id', $assay_id)->fetchPairs('id', 'assay_name');
                $this['monoForm']['batch']->setDefaultValue($assaydetail['batch']);
                $this['monoForm']['kf']->setDefaultValue(str_replace(".", ",", $assaydetail['kf']));
                $this['monoForm']['blank_max']->setDefaultValue(str_replace(".", ",", $assaydetail['blank_max']));
                $this['monoForm']['cal_min']->setDefaultValue(str_replace(".", ",", $assaydetail['cal_min']));
                $this['monoForm']['detection_limit']->setDefaultValue(str_replace(".", ",", $assaydetail['detection_limit']));
                $this['monoForm']['std_bmax']->setDefaultValue(str_replace(".", ",", $assaydetail['std_bmax']));
                $this['monoForm']['a1']->setDefaultValue(str_replace(".", ",", $assaydetail['a1']));
                $this['monoForm']['a2']->setDefaultValue(str_replace(".", ",", $assaydetail['a2']));
                $this['monoForm']['c']->setDefaultValue(str_replace(".", ",", $assaydetail['c']));
                $this['monoForm']['c_min']->setDefaultValue(str_replace(".", ",", $assaydetail['c_min']));
                $this['monoForm']['c_max']->setDefaultValue(str_replace(".", ",", $assaydetail['c_max']));
                $this['monoForm']['serum_ip_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_ip_min']));
                $this['monoForm']['serum_ip_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_ip_max']));
                $this['monoForm']['serum_au_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_au_min']));
                $this['monoForm']['serum_au_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_au_max']));
                $this['monoForm']['serum_mlu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_mlu_min']));
                $this['monoForm']['serum_mlu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_mlu_max']));
                $this['monoForm']['serum_vieu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_vieu_min']));
                $this['monoForm']['serum_vieu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_vieu_max']));
                $this['monoForm']['serum_iu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_iu_min']));
                $this['monoForm']['serum_iu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_iu_max']));
                $this['monoForm']['csf_ip_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_ip_min']));
                $this['monoForm']['csf_ip_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_ip_max']));
                $this['monoForm']['csf_au_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_au_min']));
                $this['monoForm']['csf_au_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_au_max']));
                $this['monoForm']['csf_mlu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_mlu_min']));
                $this['monoForm']['csf_mlu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_mlu_max']));
                $this['monoForm']['csf_vieu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_vieu_min']));
                $this['monoForm']['csf_vieu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_vieu_max']));
                $this['monoForm']['csf_iu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_iu_min']));
                $this['monoForm']['csf_iu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_iu_max']));
                $this['monoForm']['csf_pg_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_pg_min']));
                $this['monoForm']['csf_pg_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_pg_max']));
                $this['monoForm']['synovia_ip_min']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_ip_min']));
                $this['monoForm']['synovia_ip_max']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_ip_max']));
                $this['monoForm']['synovia_au_min']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_au_min']));
                $this['monoForm']['synovia_au_max']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_au_max']));
            } 
            $this->redrawControl('formWrapper');
            $this->redrawControl('monotestSnippet');
        }
    }
    
    /**
     * Monotest form
     * @return Form
     */
    public function createComponentMonoForm() {
        // load user info
        $user = $this->getUser();
        // load active units
        $units = $this->dbHandler->getUnitsMono()->where('active', 'ANO')->fetchPairs('id', 'unit_name');
        // dilutions for sample
        $dilutions = $this->dbHandler->getDilutions()->fetchPairs('id', 'sample_type');
        $form = new Form;
        // set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        //$regNumb = "^-?(0|[1-9][0-9]*)(\.[0-9]+|\,[0-9]+)?$";
        if (!$user->isLoggedIn()) {
                // load active assays
                $this->userassay = $this->dbHandler->getAssaysMono()->where('active', 'ANO')->fetchPairs('id', 'assay_name');
                asort($this->userassay);
        } else {
            // load user assays
            $assays = $this->dbHandler->getUsersAssaysMono()->where('users_id', $user->id)->fetchAll();
            if (!empty($assays)) {
                foreach ($assays as $assay) {
                    $this->userassay[$assay->assays_id] = $assay->assays->assay_name;
                }
                asort($this->userassay);
            } 
        }
        
        // set greyzone inputs (serum, csf, synovial)
        $form->addText('serum_ip_min')->setDefaultValue('0');
        $form->addText('serum_ip_max')->setDefaultValue('0');
        $form->addText('serum_au_min')->setDefaultValue('0');
        $form->addText('serum_au_max')->setDefaultValue('0');
        $form->addText('serum_mlu_min')->setDefaultValue('0');
        $form->addText('serum_mlu_max')->setDefaultValue('0');
        $form->addText('serum_vieu_min')->setDefaultValue('0');
        $form->addText('serum_vieu_max')->setDefaultValue('0');
        $form->addText('serum_iu_min')->setDefaultValue('0');
        $form->addText('serum_iu_max')->setDefaultValue('0');
        $form->addText('csf_ip_min')->setDefaultValue('0');
        $form->addText('csf_ip_max')->setDefaultValue('0');
        $form->addText('csf_au_min')->setDefaultValue('0');
        $form->addText('csf_au_max')->setDefaultValue('0');
        $form->addText('csf_mlu_min')->setDefaultValue('0');
        $form->addText('csf_mlu_max')->setDefaultValue('0');
        $form->addText('csf_vieu_min')->setDefaultValue('0');
        $form->addText('csf_vieu_max')->setDefaultValue('0');
        $form->addText('csf_iu_min')->setDefaultValue('0');
        $form->addText('csf_iu_max')->setDefaultValue('0');
        $form->addText('csf_pg_min')->setDefaultValue('200');
        $form->addText('csf_pg_max')->setDefaultValue('200');
        $form->addText('synovia_ip_min')->setDefaultValue('0');
        $form->addText('synovia_ip_max')->setDefaultValue('0');
        $form->addText('synovia_au_min')->setDefaultValue('0');
        $form->addText('synovia_au_max')->setDefaultValue('0');
        $form->addSelect('assays_id', '* MONO-VIDITEST', $this->userassay)
            ->setRequired('Vybrat soupravu / Select assay')
            ->setPrompt('Vybrat soupravu / Select assay')
            ->addCondition(Form::FILLED)
                ->toggle('showFormHeader', true)
                ->toggle('showFormDetail', true)
                ->toggle('btnGreyZone', true)
                ->toggle('btnAddToList', true)
                ->toggle('btnEdit', true)
                ->endCondition();
        $form->addSelect('dilutions_id', '* Sample dilution', $dilutions)
            ->setRequired('Zvolit Ředění vzorku / Select Sample dilution')
            ->setDefaultValue(1) // 101x as default
            ->addCondition(Form::EQUAL, 6) // 6 = jine/other
                ->toggle('other_dilution', true)
                ->endCondition()
            ->addCondition(Form::NOT_EQUAL, 6)
                ->toggle('other_dilution', false) 
                ->endCondition();
        $form->addText('other_dilution', 'Jiné / Other:')
            ->setHtmlId('other_dilution')
            ->setHtmlAttribute('placeholder', 'Vyplnit ředění / Fill in dilution (e.g. 202)')
            ->addConditionOn($form['dilutions_id'], Form::EQUAL, 6) // 6 = jine/others
                ->setRequired('Vyplnit Jiné ředění / Set Other dilution')
                ->addRule($form::RANGE, 'Ředění musí být celé číslo v rozmezí %d - %d / The dilution value must be an integer in the range between %d - %d', [2, 1000])
                ->endCondition();
        $form->addSelect('units_id', '* Units', $units)
            ->setRequired('Vybrat jednotku / Select unit');
        $form->addText('sample_id', '* Sample ID: ')
            ->setRequired('Vyplnit ID vzorku / Fill in the sample ID')
            ->setHtmlAttribute('placeholder', 'Sample ID');
        $form->addText('batch', '* LOT:')
            ->setRequired('Vyplnit šarži soupravy / Fill in the LOT')
            ->setHtmlAttribute('placeholder', 'LOT');
        $form->addText('blank_max', '* Blank < X:')
            ->setRequired('Vyplnit Blank < X / Fill in the Blank < X')
            ->setHtmlAttribute('placeholder', 'OD Blank maximum');
        $form->addText('cal_min', '* CAL > X:')
            ->setRequired('Vyplnit CAL > X / Fill in the CAL > X')
            ->setHtmlAttribute('placeholder', 'OD CAL minimum');
        $form->addText('kf', 'Korekční faktor / Correction factor:')
                ->setHtmlAttribute('placeholder', 'Corr. factor')
                ->addConditionOn($form['units_id'], Form::EQUAL, '1') // is required for unit IP
                    ->setRequired('Vyplnit Korekční factor / Fill in the Correction factor')
                    ->endCondition()
                ->addCondition($form::EQUAL, '0')
                    ->addRule($form::RANGE, 'Hodnota musí být větší než 0 / The value must be greater then 0', [0])
                    ->setRequired()
                    ->endCondition();
        $form->addText('std_bmax', 'CAL B/Bmax:')
                ->setHtmlAttribute('placeholder', 'CAL B/Bmax')
                ->addConditionOn($form['units_id'], Form::NOT_EQUAL, '1') // is required for unit non-IP
                    ->setRequired('Vyplnit CAL B/Bmax / Fill in the CAL B/Bmax')
                    ->endCondition();
        $form->addText('a1', 'A1:')
                ->setHtmlAttribute('placeholder', 'A1')
                ->addConditionOn($form['units_id'], Form::NOT_EQUAL, '1') // is required for unit non-IP
                    ->setRequired('Vyplnit parametr A1 / Fill in the parameter A1')
                    ->endCondition();
        $form->addText('a2', 'A2:')
                ->setHtmlAttribute('placeholder', 'A2')
                ->addConditionOn($form['units_id'], Form::NOT_EQUAL, '1') // is required for unit non-IP
                    ->setRequired('Vyplnit parametr A2 / Fill in the parameter A2')
                    ->endCondition();
        $form->addText('c', 'C:')
                ->setHtmlAttribute('placeholder', 'C')
                ->addConditionOn($form['units_id'], Form::NOT_EQUAL, '1') // is required for unit non-IP
                    ->setRequired('Vyplnit parametr C / Fill in the parameter C')
                    ->endCondition();
        $form->addText('c_min', 'Cmin:')
                ->setHtmlAttribute('placeholder', 'Cmin')
                ->addConditionOn($form['units_id'], Form::NOT_EQUAL, '1') // is required for unit non-IP
                    ->setRequired('Vyplnit parametr Cmin / Fill in the parameter Cmin')
                    ->endCondition();
        $form->addText('c_max', 'Cmax:')
                ->setHtmlAttribute('placeholder', 'Cmax')
                ->addConditionOn($form['units_id'], Form::NOT_EQUAL, '1') // is required for unit non-IP
                    ->setRequired('Vyplnit parametr Cmax / Fill in the parameter Cmax')
                    ->endCondition();
        $form->addText('detection_limit', 'Detection limit')
            ->setHtmlAttribute('placeholder', 'Detection limit')
            ->addConditionOn($form['assays_id'], Form::EQUAL, '47') // detection_limit is required for MONO-CXCL13 assay
                ->setRequired('Vyplnit Mez stanovitelnosti / Set Detection limit')
                ->endCondition();
        $form->addText('blank_od', '* OD Blank')
            ->setRequired('Vyplnit OD Blank / Fill in the OD Blank')
            ->setHtmlAttribute('placeholder', 'BLANK')
            ->setHtmlAttribute('class', 'well');
        $form->addText('sample_od', '* OD Sample')
            ->setRequired('Vyplnit OD Sample / Fill in the OD Sample')
            ->setHtmlAttribute('placeholder', 'SAMPLE')
            ->setHtmlAttribute('class', 'well');
        $form->addText('cal_od', '* OD CAL')
            ->setRequired('Vyplnit OD CAL / Fill in the OD CAL')
            ->setHtmlAttribute('placeholder', 'CAL')
            ->setHtmlAttribute('class', 'well');
        $form->addHidden('protocol_id', $this->getSession()->getId());
        // Hidden input - antispam validation
        $form->addHidden('antispam', '');
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu / Form timeout expired, send again.');
        $form->addSubmit('addToList', 'Přidat na protokol / Add to protocol')
                ->setHtmlAttribute('class', 'col-lg-12 col-md-12 col-sm-12 col-xs-12 btn btn-monotest')
                ->setHtmlAttribute('id', 'btnAddToList');
        $form->addSubmit('edit', 'Uložit / Save')
                ->setHtmlAttribute('class', 'col-lg-12 col-md-12 col-sm-12 col-xs-12 btn btn-monotest')
                ->setHtmlAttribute('id', 'btnEdit');
        $form->onSuccess[] = [$this, 'monoCalcFormSuccesed'];
        return $form;
    }
    
    /**
     * Execute form
     * @param form
     */
    public function monoCalcFormSuccesed($form) {
        // get values from form
        $values = $form->getHttpData();
        
        // Antispam validation
        if(!empty($values['antispam'])) {
            $this->flashMessage('Upozornění na SPAM / SPAM Alert!', 'type-error');
            $this->redirect('Homepage:default');
        }
        // nastavit, resp. prepsat hodnotu dilution (6 = jine/other)
        if ($values['dilutions_id'] == 6) {
            $values['dilution_factor'] = $values['other_dilution'];
        } else {
            $values['dilution_factor'] = $this->dbHandler->getDilutions()->get($values['dilutions_id'])->dilution_factor;
        }
        // update assay parameters for logged user
        if ($this->getUser()->isLoggedIn()) {
            try {
                $this->calculatorMonoManager->updateAssayMonoParameters($values, $this->getUser());
            } catch (\PDOException $e) {
                $this->presenter->flashMessage('SQL ERROR: Update assay parameters failed!!! (Detail: '. $e->getMessage() . ')', 'error');
                $this->redirect('Homepage:default');
            }
        }
        $calculateResult = $this->calculatorMonoManager->getResult($values); // calculate result
        $interpretation = $this->calculatorMonoManager->getInterpretation($values); // result interpretation
        $is_valid = ($values['blank_max'] > $values['blank_od'] && $values['cal_min'] < $values['cal_od']) ? "valid" : "invalid";
        $unit_short = $this->dbHandler->getUnitsMono()->get($values['units_id'])->unit_short; // get unit_short for result update by selected unit
        if ($this->editTest) {
            try {
                // update result
                $this->editTest->update([
                    'sample_id' => $values['sample_id'],
                    'assays_id' => $values['assays_id'],
                    'dilutions_id' => $values['dilutions_id'],
                    'dilution_factor' => $values['dilution_factor'],
                    'units_id' => $values['units_id'],
                    'batch' => $values['batch'],
                    'blank_max' => $this->calculatorMonoManager->getParam($values['blank_max']),
                    'cal_min' => $this->calculatorMonoManager->getParam($values['cal_min']),
                    'blank_od' => $this->calculatorMonoManager->getParam($values['blank_od']),
                    'cal_od' => $this->calculatorMonoManager->getParam($values['cal_od']),
                    'sample_od' => $this->calculatorMonoManager->getParam($values['sample_od']),
                    'result' => $this->calculatorMonoManager->getParam($calculateResult),
                    'interpretation' => $interpretation,
                    'is_valid' => $is_valid
                ]);
                // update result by selected unit
                if ($unit_short == "IP") {
                    $this->editTest->update([
                        'kf' => $this->calculatorMonoManager->getParam($values['kf'])
                    ]);
                } elseif ($unit_short == "pg") {
                    $this->editTest->update([
                            'detection_limit' => $this->calculatorMonoManager->getParam($values['detection_limit']),
                            'std_bmax' => $this->calculatorMonoManager->getParam($values['std_bmax']),
                            'a1' => $this->calculatorMonoManager->getParam($values['a1']),
                            'a2' => $this->calculatorMonoManager->getParam($values['a2']),
                            'c' => $this->calculatorMonoManager->getParam($values['c']),
                            'c_min' => $this->calculatorMonoManager->getParam($values['c_min']),
                            'c_max' => $this->calculatorMonoManager->getParam($values['c_max'])
                        ]);
                } else {
                    $this->editTest->update([
                        'std_bmax' => $this->calculatorMonoManager->getParam($values['std_bmax']),
                        'a1' => $this->calculatorMonoManager->getParam($values['a1']),
                        'a2' => $this->calculatorMonoManager->getParam($values['a2']),
                        'c' => $this->calculatorMonoManager->getParam($values['c']),
                        'c_min' => $this->calculatorMonoManager->getParam($values['c_min']),
                        'c_max' => $this->calculatorMonoManager->getParam($values['c_max'])
                    ]);
                }
                $this->presenter->flashMessage('Saved.', 'success');
                $this->redirect('Mono:default');
            } catch (\PDOException $e) {
                $this->presenter->flashMessage('SQL ERROR: Update test parameters failed!!! (Detail: '. $e->getMessage() . ')', 'error');
                $this->redirect('Mono:default');
            }
        } else {
            try {
                // insert data to protokol
                $protocol = $this->dbHandler->getResultsBySession($values['protocol_id']);
                $time = time();
                if($protocol) {
                    $protocol->insert([
                        'protocol_id' => $values['protocol_id'],
                        'sample_id' => $values['sample_id'],
                        'assays_id' => $values['assays_id'],
                        'dilutions_id' => $values['dilutions_id'],
                        'dilution_factor' => $values['dilution_factor'],
                        'units_id' => $values['units_id'],
                        'batch' => $values['batch'],
                        'blank_max' => $this->calculatorMonoManager->getParam($values['blank_max']),
                        'cal_min' => $this->calculatorMonoManager->getParam($values['cal_min']),
                        'blank_od' => $this->calculatorMonoManager->getParam($values['blank_od']),
                        'cal_od' => $this->calculatorMonoManager->getParam($values['cal_od']),
                        'sample_od' => $this->calculatorMonoManager->getParam($values['sample_od']),
                        'result' => $this->calculatorMonoManager->getParam($calculateResult),
                        'interpretation' => $interpretation,
                        'is_valid' => $is_valid,
                        'created_at' => $time,
                    ]);
                    $test = $this->dbHandler->getResultsBySession($values['protocol_id'])->where('created_at', $time);
                    $test_count = $this->dbHandler->getResultsBySession($values['protocol_id'])->count();
                    // update result for selected unit
                    if ($unit_short == "IP") {
                        $test->update([
                            'kf' => $this->calculatorMonoManager->getParam($values['kf']),
                            'test_order' => $test_count
                        ]);
                    } elseif ($unit_short == "pg") {
                        $test->update([
                            'test_order' => $test_count,
                            'detection_limit' => $this->calculatorMonoManager->getParam($values['detection_limit']),
                            'std_bmax' => $this->calculatorMonoManager->getParam($values['std_bmax']),
                            'a1' => $this->calculatorMonoManager->getParam($values['a1']),
                            'a2' => $this->calculatorMonoManager->getParam($values['a2']),
                            'c' => $this->calculatorMonoManager->getParam($values['c']),
                            'c_min' => $this->calculatorMonoManager->getParam($values['c_min']),
                            'c_max' => $this->calculatorMonoManager->getParam($values['c_max'])
                        ]);
                    } else {
                        $test->update([
                            'test_order' => $test_count,
                            'std_bmax' => $this->calculatorMonoManager->getParam($values['std_bmax']),
                            'a1' => $this->calculatorMonoManager->getParam($values['a1']),
                            'a2' => $this->calculatorMonoManager->getParam($values['a2']),
                            'c' => $this->calculatorMonoManager->getParam($values['c']),
                            'c_min' => $this->calculatorMonoManager->getParam($values['c_min']),
                            'c_max' => $this->calculatorMonoManager->getParam($values['c_max'])
                    ]);
                    }
                    $this->presenter->flashMessage('Test byl úspěšně přidán na protokol. / The test was successfully added to the protocol.', 'success');
                    $this->redirect('Mono:default');
                } else {
                    $this->presenter->flashMessage('Protokol neexistuje! / Protocol was not found!', 'error');
                    $this->redirect('Mono:default');
                }
            } catch (\PDOException $e) {
                $this->presenter->flashMessage('SQL ERROR: Insert test  to protocol failed!!! (Detail: '. $e->getMessage() . ')', 'error');
                $this->redirect('Mono:default');
            }
        }
        // write visitor to log
        //$this->visitorManager->addVisitor($values);
    }
    
    /**
     * Edit test on result protocol
     * @param int $testId
     * @param int $assaysId
     */
    public function actionEditTest(int $testId, int $assaysId) {
        $editTest = $this->dbHandler->getMonoProtocols()->get($testId);
        if (!$editTest) {
            $this->error('Test neexistuje. / Test is not exist.');
        }
        $this->template->editTest = $editTest;
        $this->editTest = $editTest;
        //dump($editTest->toArray());exit;
        $this['monoForm']->setDefaults($editTest->toArray());
        $this['monoForm']['other_dilution']->setDefaultValue($editTest->dilution_factor);
        
        $unit = $this->dbHandler->getUnitsMono()->get($editTest->units_id);
        $this->template->unitName = $unit->unit_name;
        
        // refresh selectbox for dilutions and units
        $assayMono = $this->dbHandler->getAssaysMono()->get($assaysId);
        $this->template->assayMono = $assayMono;
        foreach ($assayMono->related('calc_allowed_dilutions') as $allowedDilution) {
            $dilutions[$allowedDilution->dilutions->id] = $allowedDilution->dilutions->sample_type;
        }
        $this['monoForm']['dilutions_id']->setItems($dilutions);
        
        foreach ($assayMono->related('calc_allowed_units') as $allowedUnit) {
            $units[$allowedUnit->units->id] = $allowedUnit->units->unit_name;
        }
        $this['monoForm']['units_id']->setItems($units);
        // load assay parametrs for logged user and set as deafault into form
        if ($this->getUser()->isLoggedIn()) {
                $userAssayMono = $this->dbHandler->getUsersAssaysMono()->where('assays_id', $assaysId)->where('users_id', $this->getUser()->id)->fetch();
                $this['monoForm']->setDefaults($userAssayMono->toArray());
        }
    }
    
    /**
     * Delete test from result protocol
     * @param int $testId
     */
    public function actionDeleteTest(int $testId) {
        $delete = $this->dbHandler->getResultsBySession($this->getSession()->getId())->get($testId);
        if (!$delete) {
            $this->error('Nelze smazat, záznam neexistuje nebo byl již smazán!!! / Unable to delete, result does not exist or has already been deleted!');
        } else {
            try {
                $delete->delete();
                // redirect and message
                $this->flashMessage('Removed.');
                $this->redirect('Mono:default');
            } catch (Exception $e) {
                // redirect and message
                $this->flashMessage('Test nelze odstranit. (CHYBA: ' . $e . ')');
                $this->redirect('Mono:default');
            }
        }
    }
    
    /**
     * Delete protocol
     * @param string $protocolId
     */
    public function actionDeleteProtocol(string $protocolId) {
        $delete = $this->dbHandler->getResultsBySession($protocolId);
        if (!$delete) {
            $this->error('Unable to delete, protocol does not exist or has already been deleted!');
        } else {
            try {
                $delete->delete();
                // redirect and message
                $this->flashMessage('Cleaned up.');
                $this->redirect('Mono:default');
            } catch (Exception $e) {
                // redirect and message
                $this->flashMessage('Test is not possible to delete. (CHYBA: ' . $e . ')');
                $this->redirect('Mono:default');
            }
        }
    }
    
    /**
     * PDF protocol
     * @param string $protocolId
     */
    public function actionPrintPdf(string $protocolId) {
        $protocol = $this->dbHandler->getResultsBySession($protocolId)->fetchAll();
        if (!$protocol) {
            $this->error('Protocol is not exist!');
        } else {
            try {
                $this->pdfManager->pdfMonoReport($protocol);
                $this->redirect('Mono:default');
            } catch (Exception $e) {
                // redirect and message
                $this->flashMessage('Error during export to PDF. (CHYBA: ' . $e . ')');
                $this->redirect('Mono:default');
            }
        }
    }
    
    /**
     * Export to excel
     * $param string $protocolId
     */
    public function actionExportExcel(string $protocolId) {
        $protocol = $this->dbHandler->getResultsBySession($protocolId)->fetchAll();
        if (!$protocol) {
            $this->error('Protocol is not exist!');
        } else {
            try {
                $this->spreadsheetManager->exportMonotestXls($protocolId);
                $this->redirect('Mono:default');
            } catch (Exception $e) {
                // redirect and message
                $this->flashMessage('Error during export to EXCEL. (CHYBA: ' . $e . ')');
                $this->redirect('Mono:default');
            }
        }
    }
    
    public function actionChangePosition(int $resultId, int $index, string $protocolId, string $actualRequest)
    {
        $allTests = $this->dbHandler->getResultsBySession($protocolId);
        $testsCount = $allTests->count();
        $testActualOrder = $this->dbHandler->getResultsBySession($protocolId)->get($resultId);
        $test_order = $testActualOrder->test_order;
        $testNewOrder = $this->dbHandler->getResultsBySession($protocolId)->where('test_order', $testActualOrder->test_order + $index)->fetch();
        
        if ($testNewOrder && ($testNewOrder['test_order'] != 0 || $testNewOrder['test_order'] > $testsCount)) {
            try {
                $testActualOrder->update([
                    'test_order' => $test_order + $index,
                ]);
                $testNewOrder->update([
                    'test_order' => $test_order,
                ]);
                $this->restoreRequest($actualRequest);
                $this->redirect('Mono:default');
            } catch (Exception $e) {
                // redirect and message
                $this->flashMessage('Error during changing position. (CHYBA: ' . $e . ')');
                $this->redirect('Mono:default');
            }
            
        }
    }
}
