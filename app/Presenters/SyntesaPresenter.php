<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Security\Identity;
use App\Model\DbHandler;
use App\Model\PdfManager;
use App\Model\TextManager;
use App\Model\CalculatorSyntesaManager;
use App\Model\SpreadsheetManager;
use App\Model\VisitorManager;


class SyntesaPresenter extends BasePresenter
{
    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;
    
    /**
     * @var \App\Model\PdfManager
     * @inject
     */
    public $pdfManager;
    
    /**
     * @var \App\Model\SpreadsheetManager
     * @inject
     */
    public $spreadsheetManager;
    
    /**
     * @var \App\Model\TextManager
     * @inject
     */
    public $textManager;
    
    /**
     * @var \App\Model\CalculatorSyntesaManager
     * @inject
     */
    public $calculatorSyntesaManager;
    
    /**
     * @var \App\Model\VisitorManager
     * @inject
     */
    public $visitorManager;
    
    /**
     * @var Nette\Database\Context
     */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }
    
    
    public function renderDefault() {
    
        $this->template->assays = $this->dbHandler->getAssays();
        
    }
    
    public function createComponentMultiplierForm() 
    {

            $form = new Form;

            // set Bootstrap 3 layout
            $this->makeStyleBootstrap3($form);

            // set dafault number of samples
            $copies = 1;

            ///set maximum samples
            $maxCopies = 100;
            
            $multiplier = $form->addMultiplier('formValues', function (Container $container, Form $form) {

                $regNumb = "^-?(0|[1-9][0-9]*)(\.[0-9]+|\,[0-9]+)?$";

                $assays = array("Borrelia" => "Borrelia", "TBEV" => "TBEV", "VZV" => "VZV", "CMV" => "CMV", "HSV" => "HSV", "EBV VCA" => "EBV VCA", "HHV6" => "HHV-6");

                $antibody = array("IgG" => "IgG", "IgM" => "IgM", "IgA" => "IgA");

                $container->addSelect('assay', 'Metoda / Assay:', $assays)
                    //->setDefaultValue("Borrelia")
                    ->setPrompt("Vybrat metodu / Select assay")
                    ->setRequired('Vyberte metodu / Select assay');

                $container->addSelect('antibody', 'Ig:', $antibody)
                    //->setDefaultValue("IgG")
                    ->setHtmlAttribute('id', 'antibody');
                    

                $container->addText('sampleId', 'Vzorek ID / Sample ID:')
                    //->setDefaultValue("John Doe")
                    ->setRequired('Vyplňte ID vzorku / Fill in the sample ID');

                $container->addText('serumIgAu', 'Koncentrace Ig v séru / Serum Ig concentration (AU/ml):')
                    ->setHtmlAttribute('id', 'serumIgAu')
                    //->setDefaultValue(2059.87)
                    ->addRule(Form::PATTERN, 'Musí být číslo / Must be a number', $regNumb);

                $container->addText('csfIgAu', 'Koncentrace Ig v CSF / CSF Ig concentration (AU/ml):')
                    ->setHtmlAttribute('id', 'csfIgAu')
                    //->setDefaultValue(22.488)
                    ->addRule(Form::PATTERN, 'Musí být číslo / Must be a number', $regNumb);

                $container->addText('serumIgTotal', 'Celková konc. Ig v séru / Total serum Ig conc. (mg/l):')
                    ->setHtmlAttribute('id', 'serumIgTotal')
                    //->setDefaultValue(11260.00)
                    ->addRule(Form::PATTERN, 'Musí být číslo / Must be a number', $regNumb);

                $container->addText('csfIgTotal', 'Celková konc. Ig v CSF / Total CSF Ig conc. (mg/l):')
                    ->setHtmlAttribute('id', 'csfIgTotal')
                    //->setDefaultValue(41.2)
                    ->addRule(Form::PATTERN, 'Musí být číslo / Must be a number', $regNumb);

                $container->addText('serumAlbTotal', 'Konc. albuminu v séru / Serum albumin conc. (mg/l):')
                    ->setHtmlAttribute('id', 'serumAlbTotal')
                    //->setDefaultValue(58230.0)
                    ->addRule(Form::PATTERN, 'Musí být číslo / Must be a number', $regNumb);

                $container->addText('csfAlbTotal', 'Konc. albuminu v CSF / CSF albumin conc. (mg/l):')
                    ->setHtmlAttribute('id', 'csfAlbTotal')
                    //->setDefaultValue(575,30)
                    ->addRule(Form::PATTERN, 'Musí být číslo / Must be a number', $regNumb);

            }, $copies, $maxCopies);

            $multiplier->addCreateButton('Přidat / Add')
                    ->setValidationScope([]);
            $multiplier->addCreateButton('Přidat 5x / Add 5x', 5)
                    ->setValidationScope([]);
            $multiplier->addRemoveButton('X')
                    ->addClass('btn btn-danger');

            $form->addProtection('Vypršel časový limit, odešlete formulář znovu / Timeout expired, send form again.');
            //$form->getElementPrototype()->target = '_blank';

            $form->addSubmit('sendSyntesaPdf', 'Spočítat / Calculate (PDF)')
                    ->setHtmlAttribute('class', 'btn btn-primary');
            $form->addSubmit('sendSyntesaXls', 'Spočítat / Calculate (Excel)')
                    ->setHtmlAttribute('class', 'btn btn-danger');
            $form->addSubmit('sendSyntesaText', 'Spočítat / Calculate (Text)')
                    ->setHtmlAttribute('class', 'btn btn-warning');
            
            // call method on success
            $form->onSuccess[] = [$this, 'syntesaCalcFormSuccesed'];

            return $form;

    }
    
    /**
     * Execute form
     * @param form
     */
    public function syntesaCalcFormSuccesed($form): void
    {
        // get values from form
        $values = $form->getHttpData();

        // calculatio of results
        $results = $this->calculatorSyntesaManager->getResult($values);
        
        // write visitor to log
        $this->visitorManager->addVisitor($values);
        
        // compilation of results
        if (isset($values['sendSyntesaPdf'])) {
            
            // Export to PDF
            $this->pdfManager->pdfReport($results);
            
        } elseif (isset($values['sendSyntesaXls'])) {

            // export to XLSX
            $this->spreadsheetManager->exportSyntesaXls($results);

        } else {
            
            // export to TXT
            $this->textManager->exportSyntesaTxt($results);

        }
        
    }
}