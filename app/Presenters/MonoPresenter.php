<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Security\Identity;
use App\Model\DbHandler;
use App\Model\CalculatorMonoManager;
use App\Model\VisitorManager;


class MonoPresenter extends BasePresenter {
    
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
    
    public function renderDefault() {
        $dilutions = ['101' => '101x (serum)', '2' => '2x (CSF)', '81' => '81x (synovia)', '505' => '505x (A. fumigatus IgG)', 'x' => 'Jiné / Other'];
        $this->template->dilutions = $dilutions;
    }
    
    public function createComponentMonoForm() {
        
        // load user info
        $user = $this->getUser();
        // load active units
        $units = $this->dbHandler->getUnitsMono()->where('active', 'ANO')->fetchPairs('id', 'unit_name');
        // dilutions for sample
        $dilutions = ['101' => '101x (serum)', '2' => '2x (CSF)', '81' => '81x (synovia)', '505' => '505x (A. fumigatus IgG)', 'x' => 'Jiné / Other'];
        
        $form = new Form;
        // set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        // set dafault number of samples
        $copies = 1;
        ///set maximum samples
        $maxCopies = 24;
        $regNumb = "^-?(0|[1-9][0-9]*)(\.[0-9]+|\,[0-9]+)?$";
        
        if (!$user->isLoggedIn()) {
                // load active assays
                $this->userassay = $this->dbHandler->getAssaysMono()->where('active', 'ANO')->fetchPairs('id', 'assay_short');
                asort($this->userassay);
            } else {
                // load user assays
                $assays = $this->dbHandler->getUsersAssaysMono()->where('users_id', $user->id)->fetchAll();
                if (!empty($assays)) {
                    foreach ($assays as $assay) {
                        $this->userassay[$assay->assays_id] = $assay->assays->assay_short;
                    }
                    asort($this->userassay);
                } 
            }
        
        $multiplier = $form->addMultiplier('formValues', function (Container $container, Form $form) {
            
            $container->addSelect('assay', '* Vyberte soupravu / Select assay:', $this->userassay)
                ->setRequired('Vyberte soupravu / Select assay')
                ->setPrompt('Select');
            
            $dilutions = ['101' => '101x (serum)', '2' => '2x (CSF)', '81' => '81x (synovia)', '505' => '505x (A. fumigatus IgG)', 'x' => 'Jiné / Other'];
            $container->addSelect('dilution', '* Ředění / Dilution', $dilutions)
                ->setRequired('Vyberte ředění / Select dilution')
                ->setPrompt('Select');
            
            $container->addText('sample_id', 'Sample ID: ')
                ->setRequired('Vyplňte ID vzorku / Fill in the sample ID')
                ->setHtmlAttribute('placeholder', 'Sample ID');
            
            $container->addText('batch', 'LOT:')
                ->setRequired('Vyplňte šarži soupravy / Fill in the LOT')
                ->setHtmlAttribute('placeholder', 'LOT');
            
            $container->addText('blank_max', 'Blank maximum:')
                ->setRequired('Vyplňte OD DB maximum / Fill in the OD DB maximum')
                ->setHtmlAttribute('placeholder', 'OD DB maximum');
            
            $container->addText('cal_min', 'CAL minimum:')
                ->setRequired('Vyplňte OD CAL minimum / Fill in the OD CAL minimum')
                ->setHtmlAttribute('placeholder', 'OD CAL minimum');
            
            $container->addText('kf', 'Corr. factor:')
                ->setRequired('Vyplňte Korekční factor / Fill in the Correction factor')
                ->setHtmlAttribute('placeholder', 'Corr. factor');
            
            $container->addText('std_bmax', 'CAL B/Bmax:')
                ->setRequired('Vyplňte CAL B/Bmax / Fill in the CAL B/Bmax')
                ->setHtmlAttribute('placeholder', 'CAL B/Bmax');
            
            $container->addText('a1', 'A1:')
                ->setRequired('Vyplňte parametr A1 / Fill in the parameter A1')
                ->setHtmlAttribute('placeholder', 'A1');
            
            $container->addText('a2', 'A2:')
                ->setRequired('Vyplňte parametr A2 / Fill in the parameter A2')
                ->setHtmlAttribute('placeholder', 'A2');
            
            $container->addText('c', 'C:')
                ->setRequired('Vyplňte parametr C / Fill in the parameter C')
                ->setHtmlAttribute('placeholder', 'C');
            
            $container->addText('c_min', 'Cmin:')
                ->setRequired('Vyplňte parametr Cmin / Fill in the parameter Cmin')
                ->setHtmlAttribute('placeholder', 'Cmin');
            
            $container->addText('c_max', 'Cmax:')
                ->setRequired('Vyplňte parametr Cmax / Fill in the parameter Cmax')
                ->setHtmlAttribute('placeholder', 'Cmax');
            
            $container->addText('blank_od', 'BLANK:')
                ->setRequired('Vyplňte BLANK OD / Fill in the BLANK OD')
                ->setHtmlAttribute('placeholder', 'BLANK')
                ->setHtmlAttribute('class', 'well');
            
            $container->addText('sample_od', 'SAMPLE:')
                ->setRequired('Vyplňte Sample OD / Fill in the Sample OD')
                ->setHtmlAttribute('placeholder', 'SAMPLE')
                ->setHtmlAttribute('class', 'well');
            
            $container->addText('cal_od', 'CAL:')
                ->setRequired('Vyplňte CAL OD / Fill in the CAL OD')
                ->setHtmlAttribute('placeholder', 'CAL')
                ->setHtmlAttribute('class', 'well');
            
            /*
            $container->addText('serumIgAu', 'Koncentrace Ig v séru / Serum Ig concentration (AU/ml):')
                ->setHtmlAttribute('id', 'serumIgAu')
                //->setDefaultValue(2059.87)
                ->addRule(Form::PATTERN, 'Musí být číslo / Must be a number', $regNumb);
            */
        }, $copies, $maxCopies);

        $multiplier->addCreateButton('Přidat / Add')
                ->setValidationScope([]);
        $multiplier->addCreateButton('Přidat 5x / Add 5x', 5)
                ->setValidationScope([]);
        $multiplier->addRemoveButton('Smazat / Delete')
                ->addClass('btn btn-danger');

        $form->addProtection('Vypršel časový limit, odešlete formulář znovu / Timeout expired, send form again.');
        //$form->getElementPrototype()->target = '_blank';

        $form->addSubmit('sendMonoPdf', 'Spočítat / Calculate (PDF)')
                ->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('sendMonoXls', 'Spočítat / Calculate (Excel)')
                ->setHtmlAttribute('class', 'btn btn-danger');
        $form->addSubmit('sendMonoText', 'Spočítat / Calculate (Text)')
                ->setHtmlAttribute('class', 'btn btn-warning');
        // call method on success
        $form->onSuccess[] = [$this, 'monoCalcFormSuccesed'];
        return $form;
    }
    
    /**
     * Execute form
     * @param form
     */
    public function monoCalcFormSuccesed($form): void
    {
        // get values from form
        $values = $form->getHttpData();
        dump($values);
        exit;
        // calculation of results
        //$results = $this->calculatorSyntesaManager->getResult($values);
        
        // write visitor to log
        //$this->visitorManager->addVisitor($values);
        
        // compilation of results
        /*if (isset($values['sendSyntesaPdf'])) {
            
            // Export to PDF
            $this->pdfManager->pdfReport($results);
            
        } elseif (isset($values['sendSyntesaXls'])) {
            // export to XLSX
        } else {
            // export to TXT
            
        }*/
        
    }
}
