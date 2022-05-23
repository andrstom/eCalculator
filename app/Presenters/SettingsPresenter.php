<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use App\Model\DbHandler;

class SettingsPresenter extends BasePresenter
{   
    
    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;
    
    
    public function renderUserlist()
    {
        
        $this->template->userlist = $this->dbHandler->getUsers();
        
    }
    
    public function renderAssaylist()
    {
        
        $this->template->assaylist = $this->dbHandler->getAssays();
        
    }

    public function renderUnitlist()
    {
        
        $this->template->unitlist = $this->dbHandler->getUnits();
        
    }
    
    public function renderReaderlist()
    {
        
        $this->template->readerlist = $this->dbHandler->getReaders();
        
    }
    
}
