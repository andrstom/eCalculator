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


class MonoPresenter extends BasePresenter {
    
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

    }
}
