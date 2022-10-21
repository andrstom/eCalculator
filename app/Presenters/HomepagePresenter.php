<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use App\Model\DbHandler;
use App\Model\CalculatorElisaManager;
use App\Model\PdfManager;
use App\Model\TextManager;
use App\Model\SpreadsheetManager;
use App\Model\VisitorManager;


class HomepagePresenter extends BasePresenter {
    
    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;
    
    /**
     * @var \App\Model\CalculatorElisaManager
     * @inject
     */
    public $calculatorElisaManager;
    
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
    
    public function renderDefault() {
        $dilutions = ['101' => '101x (serum)', '2' => '2x (CSF)', '81' => '81x (synovia)', '505' => '505x (A. fumigatus IgG)', 'x' => 'Jiné / Other'];
        $this->template->dilutions = $dilutions;
        if ($this->getUser()->isLoggedIn()) {
            //$this->template->user_reader = $this->dbHandler->getReaders()->get($this->getUser()->getIdentity()->reader_id);
            //$this->template->user_readers = $this->dbHandler->getUsersReaders()->where('user_id', $this->getUser()->getIdentity()->id);
        }
    }
    
    /**
     * Load assay details into form
     * @param number
     */
    public function handleLoadAssayDetails($value) {
        if ($value) {
            // load assay details
            if ($this->getUser()->isLoggedIn()) {
                
                $assaydetail = $this->dbHandler->getUsersAssays()->where('assays_id', $value)->where('users_id', $this->getUser()->id)->fetch();
                
                $this['calculatorForm']['batch']->setDefaultValue($assaydetail['batch']);
                $this['calculatorForm']['expiry']->setDefaultValue($assaydetail['expiry']);
                $this['calculatorForm']['kf_serum']->setDefaultValue(str_replace(".", ",", $assaydetail['kf_serum']));
                $this['calculatorForm']['kf_csf']->setDefaultValue(str_replace(".", ",", $assaydetail['kf_csf']));
                $this['calculatorForm']['kf_synovia']->setDefaultValue(str_replace(".", ",", $assaydetail['kf_synovia']));
                $this['calculatorForm']['blank_max']->setDefaultValue(str_replace(".", ",", $assaydetail['blank_max']));
                $this['calculatorForm']['detection_limit']->setDefaultValue(str_replace(".", ",", $assaydetail['detection_limit']));
                $this['calculatorForm']['std_bmax']->setDefaultValue(str_replace(".", ",", $assaydetail['std_bmax']));
                $this['calculatorForm']['a1']->setDefaultValue(str_replace(".", ",", $assaydetail['a1']));
                $this['calculatorForm']['a2']->setDefaultValue(str_replace(".", ",", $assaydetail['a2']));
                $this['calculatorForm']['c']->setDefaultValue(str_replace(".", ",", $assaydetail['c']));
                $this['calculatorForm']['c_min']->setDefaultValue(str_replace(".", ",", $assaydetail['c_min']));
                $this['calculatorForm']['c_max']->setDefaultValue(str_replace(".", ",", $assaydetail['c_max']));
                $this['calculatorForm']['ratio_min']->setDefaultValue(str_replace(".", ",", $assaydetail['ratio_min']));
                $this['calculatorForm']['ratio_max']->setDefaultValue(str_replace(".", ",", $assaydetail['ratio_max']));
                
                $this['calculatorForm']['serum_ip_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_ip_min']));
                $this['calculatorForm']['serum_ip_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_ip_max']));
                $this['calculatorForm']['serum_au_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_au_min']));
                $this['calculatorForm']['serum_au_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_au_max']));
                $this['calculatorForm']['serum_mlu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_mlu_min']));
                $this['calculatorForm']['serum_mlu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_mlu_max']));
                $this['calculatorForm']['serum_vieu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_vieu_min']));
                $this['calculatorForm']['serum_vieu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_vieu_max']));
                $this['calculatorForm']['serum_iu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_iu_min']));
                $this['calculatorForm']['serum_iu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['serum_iu_max']));
                
                $this['calculatorForm']['csf_ip_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_ip_min']));
                $this['calculatorForm']['csf_ip_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_ip_max']));
                $this['calculatorForm']['csf_au_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_au_min']));
                $this['calculatorForm']['csf_au_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_au_max']));
                $this['calculatorForm']['csf_mlu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_mlu_min']));
                $this['calculatorForm']['csf_mlu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_mlu_max']));
                $this['calculatorForm']['csf_vieu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_vieu_min']));
                $this['calculatorForm']['csf_vieu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_vieu_max']));
                $this['calculatorForm']['csf_iu_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_iu_min']));
                $this['calculatorForm']['csf_iu_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_iu_max']));
                $this['calculatorForm']['csf_pg_min']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_pg_min']));
                $this['calculatorForm']['csf_pg_max']->setDefaultValue(str_replace(".", ",", $assaydetail['csf_pg_max']));
                
                $this['calculatorForm']['synovia_ip_min']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_ip_min']));
                $this['calculatorForm']['synovia_ip_max']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_ip_max']));
                $this['calculatorForm']['synovia_au_min']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_au_min']));
                $this['calculatorForm']['synovia_au_max']->setDefaultValue(str_replace(".", ",", $assaydetail['synovia_au_max']));
                $this['calculatorForm']['unit']->setDefaultValue($assaydetail['units_id']);
            }
            $assay = $this->dbHandler->getAssays()->get($value);
            $this->template->assay = $assay;
            $layout_id = $assay->layout;
            $layout_detail = $this->dbHandler->getLayouts()->get($layout_id);
            $this['calculatorForm']['cal_1']->setDefaultValue($layout_detail->cal_1);
            $this['calculatorForm']['cal_2']->setDefaultValue($layout_detail->cal_2);
            $this['calculatorForm']['cal_3']->setDefaultValue($layout_detail->cal_3);
            $this['calculatorForm']['cal_4']->setDefaultValue($layout_detail->cal_4);
            $this['calculatorForm']['cal_5']->setDefaultValue($layout_detail->cal_5);
            $this['calculatorForm']['cal_6']->setDefaultValue($layout_detail->cal_6);
            $this['calculatorForm']['cal_7']->setDefaultValue($layout_detail->cal_7);
            $this['calculatorForm']['cal_8']->setDefaultValue($layout_detail->cal_8);
        }
        $this->redrawControl('wrapper');
        $this->redrawControl('paramSnippet');
    }
    
    /**
     * @return Nette\Application\UI\Form
     */
    public function createComponentCalculatorForm() {
        // load user info
        $user = $this->getUser();
        // load active units
        $units = $this->dbHandler->getUnits()->where('active', 'ANO')->fetchPairs('id', 'unit_name');
        // set default reader
        $reader = array('manual' => 'MANUAL');
        
        $dilutions = ['101' => '101x (serum)', '2' => '2x (CSF)', '81' => '81x (synovia)', '505' => '505x (A. fumigatus IgG)', 'x' => 'Jiné / Other'];;
        $this->template->dilutions = $dilutions;
        
        // create form
        $form = new Form;
        // set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        
        // load data for non-logged/logged user
        if (!$user->isLoggedIn()) {
            // load active assays
            $userassay = $this->dbHandler->getAssays()->where('active', 'ANO')->fetchPairs('id', 'assay_name');
            // select assay
            $form->addSelect('assay', '* Vyberte soupravu / Select assay:', $userassay)
                ->setRequired('Vyberte soupravu / Select assay')
                ->setPrompt('Vybrat / Select');
        } else {
            // load user readers
            $user_readers = $this->dbHandler->getUsersReaders()->where('user_id', $user->id)->fetchAll();
            // add user readers into array "reader"
            if (!empty($user_readers)) {
                foreach ($user_readers as $user_reader) {
                    $reader[$user_reader->reader_id] = $user_reader->reader->reader_name;
                }
            }
            // load user assays
            $assays = $this->dbHandler->getUsersAssays()->where('users_id', $user->id)->fetchAll();
            if (!empty($assays)) {
                foreach ($assays as $assay) {
                    $userassay[$assay->assays_id] = $assay->assays->assay_name;
                }
                asort($userassay);
                $form->addSelect('assay', '* Vyberte soupravu  / Select assay:', $userassay)
                    ->setRequired('Vyberte soupravu  / Select assay')
                    ->setPrompt('Vybrat  / Select ...');
            } else {
                $form->addSelect('assay', '* Vyberte metodu  / Select assay:')
                    ->setRequired('Vyberte metodu  / Select assay')
                    ->setPrompt('Uživatel nemá dostupnou žádnou metodu !!!');
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
        
        $form->addText('cal_1')->setDefaultValue('BLANK')
                ->setHtmlAttribute('tabindex', 1)
                ->setHtmlAttribute('size', 3);
        $form->addText('cal_2')->setDefaultValue('ST D/CAL')
                ->setHtmlAttribute('tabindex', 2)
                ->setHtmlAttribute('size', 3);
        $form->addText('cal_3')->setDefaultValue('ST D/CAL')
                ->setHtmlAttribute('tabindex', 3)
                ->setHtmlAttribute('size', 3);
        $form->addText('cal_4')->setDefaultValue('ST E/PC')
                ->setHtmlAttribute('tabindex', 4)
                ->setHtmlAttribute('size', 3);
        $form->addText('cal_5')->setDefaultValue('ST A/NC')
                ->setHtmlAttribute('tabindex', 5)
                ->setHtmlAttribute('size', 3);
        $form->addText('cal_6')->setDefaultValue('')
                ->setHtmlAttribute('tabindex', 6)
                ->setHtmlAttribute('size', 3);
        $form->addText('cal_7')->setDefaultValue('')
                ->setHtmlAttribute('tabindex', 7)
                ->setHtmlAttribute('size', 3);
        $form->addText('cal_8')->setDefaultValue('')
                ->setHtmlAttribute('tabindex', 8)
                ->setHtmlAttribute('size', 3);
        $form->addText('batch', '* Šarže / LOT :')
                ->setRequired('Vyplňtě Šarže / LOT');
        $form->addText('expiry', '* Expirace / Expiration:')
                ->setRequired('Vyplňtě Expirace / Expiration');
        $form->addText('kf_serum', '* Serum:')
                ->addConditionOn($form['assay'], Form::NOT_EQUAL, '19') // kf_serum is required for non-CXCL13 assays
                ->setRequired('Vyplňtě Korekční faktor / Set Correction factor (serum)')
                ->endCondition();
        $form->addRadioList('dilution', '* Ředění vzorku / Dilution:', ['101' => '101x (serum)', '2' => '2x (CSF)', '81' => '81x (synovia)', '505' => '505x (A. fumigatus IgG)', 'x' => 'Jiné / Other'])
                ->setDefaultValue('101')
                ->addCondition(Form::EQUAL, 'x')
                    ->toggle('other_dilution', true) 
                    ->endCondition()
                ->addCondition(Form::NOT_EQUAL, 'x')
                    ->toggle('other_dilution', false) 
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::PATTERN, '1|2') // BBM, BBG
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Nepovolený typ materiálu (povolené: serum, CSF, synovia, Jiné) / Incorrect sample (allowed: serum, CSF, synovia, Other).', '101|2|81|x') // allowed sample: serum, CSF, synovia, Other
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::PATTERN, '3|4|5|6|7|8') // CMVG, HHV6G, HSVG, TBEVG, VCAG, VZVG
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Nepovolený typ materiálu (povolené: serum, CSF, Jiné) / Incorrect sample (allowed: serum, CSF, Other).', '101|2|x') // allowed dilution: serum, CSF, other
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::PATTERN, '9|10|11|12|13|14|15|16|17|18|20|21|22') // JCV, SARSS1A, SARSS1M, SARSS1G, SARSNPA, SARSNPM, SARSNPA, SARSS1A, SARSRBDG, PSAE, EBNAG, ASFUG, ASFUM, ASFUA
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Nepovolený typ materiálu (povolené: serum, Jiné) / Incorrect sample (allowed: serum, Other).', '101|x') // allowed dilution: serum, other
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::PATTERN, '19') // CXCL13
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Nepovolený typ materiálu (povolené: CSF, Jiné) / Incorrect sample (allowed: CSF, Other).', '2|x') // allowed dilution: CSF, other
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::PATTERN, '20') //  ASFUG
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Nepovolený typ materiálu (povolené: 505x, Jiné) / Incorrect sample (allowed: 505x, Other).', '505|x') // allowed dilution: 505x, other
                    ->endCondition();
                
        $form->addInteger('other_dilution', 'Jiné/Other:')
                ->setHtmlId('other_dilution')
                ->setHtmlAttribute('class', 'col-lg-2')
                ->addRule($form::RANGE, 'Hodnota musí být v rozsahu mezi: / Allowed range is between: (%d - %d)', [2, 1000])
                ->addConditionOn($form['dilution'], Form::EQUAL, 'x')
                    ->setRequired('Vyplnit Jiné ředění / Set Other dilution')
                    ->endCondition();
        $form->addText('kf_csf', 'CSF:')
                ->addConditionOn($form['assay'], Form::NOT_EQUAL, '19') // kf_csf is required for non-CXCL13 assays and dilution "CSF"
                    ->addConditionOn($form['dilution'], Form::EQUAL, '2')
                        ->setRequired('Vyplňtě Korekční faktor / Set Correction factor (CSF)')
                    ->endCondition()
                ->endCondition();
        $form->addText('kf_synovia', 'Synovia:')
                ->addConditionOn($form['assay'], Form::NOT_EQUAL, '19') // kf_synovia is required for non-CXCL13 assays and dilution "Synovia"
                    ->addConditionOn($form['dilution'], Form::EQUAL, '81')
                        ->setRequired('Vyplňtě Korekční faktor / Set Correction factor (Synovia)')
                    ->endCondition()
                ->endCondition();
        $form->addText('blank_max', 'BLANK maximum:')
                ->setRequired('Vyplňtě BLANK maximum / Set Blank maximum');
        $form->addText('std_bmax', '* ST D B/Bmax (CAL B/Bmax):')
                ->setRequired('Vyplňtě / Set STD B/Bmax');
        $form->addText('a1', '* A1:')
                ->setRequired('Vyplňtě A1 / Set A1');
        $form->addText('a2', '* A2:')
                ->setRequired('Vyplňtě A2 / Set A2');
        $form->addText('c', '* C:')
                ->setRequired('Vyplňtě C / Set C');
        $form->addText('c_min', '* C (min):')
                ->setRequired('Vyplňtě C (min) / Set C (min)');
        $form->addText('c_max', '* C (max):')
                ->setRequired('Vyplňtě C (max) / Set C (max)');
        $form->addText('detection_limit', '')
                ->addConditionOn($form['assay'], Form::EQUAL, '19') // detection_limit is required for CXCL13 assay
                ->setRequired('Vyplňtě Mez stanovitelnosti / Set Detection limit')
                ->endCondition();
        $form->addText('ratio_min', 'min:')
                ->addConditionOn($form['assay'], Form::NOT_EQUAL, '19') // ratio_min is required for non-CXCL13 assays
                ->setRequired('Vyplňtě Poměr OD (min) / Set Ratio OD (min)')
                ->endCondition();
        $form->addText('ratio_max', 'max:')
                ->addConditionOn($form['assay'], Form::NOT_EQUAL, '19') // ratio_max is required for non-CXCL13 assays
                ->setRequired('Vyplňtě Poměr OD (max) / Set Ratio OD (max)')
                ->endCondition();
        // select source (reader type) of measured data
        $form->addSelect('reader', '', $reader)
                ->setRequired('Vyberte zdroj naměřených hodnot / Select source of measured data')
                ->setPrompt('Vyberte zdroj naměřených hodnot / Select source of measured data')
                ->addCondition($form::EQUAL, 'manual')
                    ->toggle('source-container-manual')
                    ->endCondition()
                ->addCondition($form::NOT_EQUAL, 'manual')
                    ->toggle('source-container-reader')
                    ->endCondition();

        // set the default reader for the logged-in user (if the user has more readers then choose the first one [0] = manual)
        $user->isLoggedIn() && !empty($reader[1]) ? $form['reader']->setDefaultValue(array_keys($reader)[1]) : $form['reader']->setDefaultValue('manual');
        
        $form->addSelect('unit', '* Jednotka / Unit:', $units)
                ->setDefaultValue(1)
                ->addConditionOn($form['assay'], Form::PATTERN, '1|2|3|4|5|7|9|15|18|20|21|22|23') // BORRG, BORRM, CMVG, HHV6G, HSVG, VCAG, JCVG, SARSNPG, EBNAG, 
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Jednotku nezle použít pro zvolenou metodu (povolené jednotky Index, AU/ml) / Selected unit can not be used for selected assay (allowed units Index, AU/ml).', '[1]|[2]') // IP, AU
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::EQUAL, '6') // TBEVG
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Jednotku nezle použít pro zvolenou metodu (povolené jednotky Index, AU/ml, VIEU/ml) / Selected unit can not be used for selected assay (allowed units Index, AU/ml, VIEU/ml).', '[1]|[2]|[3]') // Povolené metody IP, AU, VIEU
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::EQUAL, '8') // VZVG
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Jednotku nezle použít pro zvolenou metodu (povolené jednotky Index, mlU/ml) / Selected unit can not be used for selected assay (allowed units Index, mlU/ml).', '[1]|[4]') // Povolené jednotky IP, mlU/ml
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::PATTERN, '10|11|12|16') // SARSSA, SARSSM, SARSSG, SARSRBDG
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Jednotku nezle použít pro zvolenou metodu (povolené jednotky Index, IU/ml) / Selected unit can not be used for selected assay (allowed units Index, IU/ml).', '[1]|[5]') // IP, IU
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::PATTERN, '13|14') // SARSNPA, SARSNPM
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Jednotku nezle použít pro zvolenou metodu (povolené jednotky Index) / Selected unit can not be used for selected assay (allowed units Index).', '[1]') // IP
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::EQUAL, '17') // Pseudomonas aeruginosa IgG
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Jednotku nezle použít pro zvolenou metodu (povolená jednotka AU/ml) / Selected unit can not be used for selected assay (allowed unit AU/ml).', '[2]') // AU
                    ->endCondition()
                ->addConditionOn($form['assay'], Form::EQUAL, '19') // CXCL13
                    ->setRequired()
                    ->addRule(Form::PATTERN, 'Jednotku nezle použít pro zvolenou metodu (povolená jednotka pg/ml) / Selected unit can not be used for selected assay (allowed unit pg/ml).', '[6]') // pg
                    ->endCondition();

        $form->addUpload('file','* Vybrat soubor / Select file:')
                ->setHtmlAttribute('class', 'form-control')
                ->addConditionOn($form['reader'], Form::NOT_EQUAL, 'manual')
                    ->setRequired('Vyberte soubor z fotometru / Select reader file.');

        // Hidden input - antispam validation
        $form->addHidden('antispam', '');
        
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu / Timeout expired, send form again.');
        $form->getElementPrototype()->target = '_blank';

        $form->addSubmit('sendElisaPdf', 'Uložit jako PDF (tisk) / Save as PDF (print)')
                ->setHtmlAttribute('class', 'btn-primary');
        $form->addSubmit('sendElisaXls', 'Uložit jako EXCEL / Save as EXCEL')
                ->setHtmlAttribute('class', 'btn-info');
        $form->addSubmit('sendElisaText', 'Uložit jako TEXT / Save as TEXT ')
                ->setHtmlAttribute('class', 'btn-warning');

        // call method calculatorFormSucceeded() on success
        $form->onSuccess[] = [$this, 'calculatorFormSucceeded'];
        return $form;
    }
    
    /**
     * Execute form
     * @param form
     */
    public function calculatorFormSucceeded($form) {
        // get values from form
        $values = $form->getHttpData();
        
        // nastavit, resp. prepsat hodnotu dilution v zavislosti na vybranem radio
        if ($values['dilution'] == 'x') {
            $values['dilution'] = $values['other_dilution'];
        }

        // Antispam validation
        if(!empty($values['antispam'])) {
            $this->flashMessage('Upozornění na SPAM / SPAM Alert!', 'type-error');
            $this->redirect('Homepage:default');
        }
        // set default values into form
        $this['calculatorForm']->setDefaults($values);
        $this->template->sendAbs = $values['Abs'];
        $this->template->sendSampleId = $values['sampleId'];
        
        // load reader from db or set manual as default
        $reader = $this->calculatorElisaManager->getReader($values['reader']);
        
        // verification of reader and file compatibility
        if ($reader != 'manual') {
            $validFileFormat = $this->calculatorElisaManager->getFileFormatVerification($reader, $values['file']->name);
            if (!$validFileFormat) {
                $this->flashMessage('Neplatný formát souboru. Zkontrolujte zvolený fotometr a formát nahraného souboru (povolené formáty .txt, .xls, .xlsx) / Invalid file format. Check the selected photometer and the format of the recorded file (allowed formats .txt, .xls, .xlsx).', 'type-error');
                $this->redirect('Homepage:default');
            }
        }
        /*
         * Update assay parameters in db if user is logged
         * @param form values
         * @param user
         */
        if($this->getUser()->isLoggedIn()) {
            try {
                $this->calculatorElisaManager->updateAssayParameters($values, $this->getUser());
            } catch (\PDOException $e) {
                $this->presenter->flashMessage('SQL ERROR: Update assay parameters failed!!! (Detail: '. $e->getMessage() . ')');
                $this->redirect('Homepage:default');
            }
        }
        
        /** 
         * reader range and separator verification (96 only)
         */
        if (count($this->calculatorElisaManager->getParam($values)['Abs']) != 96) {
            $this->presenter->flashMessage('Fotometr má chybně nastavenou "oblast naměřených dat" nebo "textový separátor"! Kontaktujte servis@vidia.cz / The photometer has incorrectly set the "measured data area" or "text separator"! Please, contact servis@vidia.cz', 'error');
            $this->redirect('Homepage:default');
        }
        
        // write visitor to log
        $this->visitorManager->addVisitor($values);
            
        // create db backup (one per week)
        if (!file_exists('../db_backup/week_' . date('W') . '.sql.gz')) {
            // localhost
            //$dumper = new \MySQLDump(new \mysqli('127.0.0.1', 'root', '', 'vidia_cz2'));
            // ignum hosting
            $dumper = new \MySQLDump(new \mysqli('db020.webglobe.com', 'vidia_cz2', 'e019189a', 'vidia_cz2'));
            $dumper->save('../db_backup/week_' . date('W') . '.sql.gz');
        }
        
        /**
         * Return final report file (PDF, EXCEL or TEXT)
         */
        if (isset($values['sendElisaPdf'])) {
            // PDF report
            try {
                $this->pdfManager->pdfReport($values);
            } catch (\Exception $e) {
                $this->presenter->flashMessage('ERROR: PdfManager::pdfReport(), ' . $e->getMessage() . ')', 'error');
            }
        } elseif (isset($values['sendElisaXls'])) {
            // Excel report
            try {
                $this->spreadsheetManager->exportElisaXls($values);
            } catch (\Exception $e) {
                $this->presenter->flashMessage('ERROR: SpreadsheetManager::exportElisaXLS(), ' . $e->getMessage() . ')', 'error');
            }
        } else {
            // TXT report
            try {
                $this->textManager->textReport($values);
            } catch (\Exception $e) {
                $this->presenter->flashMessage('ERROR: TextManager::textReport(), ' . $e->getMessage() . ')', 'error');
            }
        }
    }
}
