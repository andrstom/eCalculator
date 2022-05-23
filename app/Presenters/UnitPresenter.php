<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use App\Model\DbHandler;

class UnitPresenter extends BasePresenter {

    private $editUnit;

    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;

   
    public function renderAdd() 
    {

        $this->template->units = $this->dbHandler->getUnits();
        
    }

    public function renderEdit($unitId) 
    {
        $unit = $this->dbHandler->getUnits()->get($unitId);
        $this->template->unit = $unit;

        if (!$unit) 
        {
            $this->error('Jednotka nebyla nalezena!');
        }

    }

    /**
     * Signup form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentUnitForm()
    {

        $true_false = array('ANO' => 'ANO', 'NE' => 'NE');

        
        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);

        // Set form labels
        $form->addText('unit_short', 'Zkratka: *')
                ->setRequired('Vyplňtě Zkratku');

        $form->addText('unit_name', 'Název: *')
                ->setRequired('Vyplňte Název');

        $form->addTextArea('notice', 'Poznámka:');

        $form->addRadioList('active', 'Aktivní:', $true_false)
                ->setDefaultValue('ANO');
        
        $form->addSubmit('send', 'Uložit');

        //call method signUpFormSucceeded() on success
        $form->onSuccess[] = [$this, 'unitFormSucceeded'];

        return $form;
    }

    public function unitFormSucceeded($form)
    {

        // get values from form
        $values = $form->getValues();

        if ($this->editUnit) {

            $row = $this->editUnit->update([
                'unit_short' => $values->unit_short,
                'unit_name' => $values->unit_name,
                'notice' => $values->notice,
                'active' => $values->active,
                'editor' => $this->getUser()->getIdentity()->getData()['login'],
                'edited_at' => time(),
            ]);

        } else {

            try {

                // insert user details
                $row = $this->dbHandler->getUnits()->insert([
                    'unit_short' => $values->unit_short,
                    'unit_name' => $values->unit_name,
                    'notice' => $values->notice,
                    'active' => $values->active,
                    'creator' => $this->getUser()->getIdentity()->getData()['login'],
                    'created_at' => time(),
                ]);

            } catch (\Nette\Database\UniqueConstraintViolationException $e) {

                throw new DuplicateNameException;
            }
        }

        // redirect and message
        $this->flashMessage('Jednotka byla úspěšně vložena/upravena.');
        $this->redirect('Settings:unitlist');
    }

    
    public function actionEdit($unitId)
    {
        
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $editUnit = $this->dbHandler->getUnits()->get($unitId);
        $this->editUnit = $editUnit;

        if (!$editUnit) {

            $this->error('Jednotka nebyla nalezena.');
        }

        $this['unitForm']->setDefaults($editUnit->toArray());
    }

    // delete
    public function actionDelete($unitId)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $delete = $this->dbHandler->getUnits()->get($unitId);
        if (!$delete) {

            $this->error('Nelze smazat, záznam neexistuje!!!');
        } else {

            try {
                
                $delete->delete();

                // redirect and message
                $this->flashMessage('Záznam byl úspěšně odstraněn.');
                $this->redirect('Settings:unitlist');
            } catch (Exception $e) {

                // redirect and message
                $this->flashMessage('Záznam nelze odstranit. (CHYBA: ' . $e . ')');
                $this->redirect('Settings:unitlist');
            }
        }
    }
}