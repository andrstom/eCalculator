<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use App\Model\DbHandler;

class AssayPresenter extends BasePresenter {

    private $editAssay;

    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;

   
    public function renderAdd() 
    {

        $this->template->assays = $this->dbHandler->getAssays();
        
    }

    public function renderEdit($assayId) 
    {
        $assay = $this->dbHandler->getAssays()->get($assayId);
        $this->template->assay = $assay;

        if (!$assay) 
        {
            $this->error('Metoda nebyla nalezena!');
        }

    }

    /**
     * Signup form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentAssayForm()
    {

        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);

        // Set form labels
        $form->addText('assay_short', 'Zkratka: *')
                ->setRequired('Vyplňtě Zkratku');

        $form->addText('assay_name', 'Název: *')
                ->setRequired('Vyplňte Název');

        $form->addTextArea('notice', 'Poznámka:');

        $form->addRadioList('active', 'Aktivní:', ['ANO' => 'ANO', 'NE' => 'NE'])
                ->setDefaultValue('ANO');
        
        $form->addSubmit('send', 'Uložit');

        //call method signUpFormSucceeded() on success
        $form->onSuccess[] = [$this, 'assayFormSucceeded'];

        return $form;
    }

    public function assayFormSucceeded($form)
    {

        // get values from form
        $values = $form->getValues();

        if ($this->editAssay) {

            $row = $this->editAssay->update([
                'assay_short' => $values->assay_short,
                'assay_name' => $values->assay_name,
                'notice' => $values->notice,
                'active' => $values->active,
                'editor' => $this->getUser()->getIdentity()->getData()['login'],
                'edited_at' => time(),
            ]);

        } else {

            try {

                // insert user details
                $row = $this->dbHandler->getAssays()->insert([
                    'assay_short' => $values->assay_short,
                    'assay_name' => $values->assay_name,
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
        $this->flashMessage('Metoda byla úspěšně vložena/upravena.');
        $this->redirect('Settings:assaylist');
    }

    
    public function actionEdit($assayId)
    {
        
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $editAssay = $this->dbHandler->getAssays()->get($assayId);
        $this->editAssay = $editAssay;

        if (!$editAssay) {

            $this->error('Metoda nebyla nalezena.');
        }

        $this['assayForm']->setDefaults($editAssay->toArray());
    }

    // delete user
    public function actionDelete($assayId)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $delete = $this->dbHandler->getAssays()->get($assayId);
        if (!$delete) {

            $this->error('Nelze smazat, záznam neexistuje!!!');
        } else {

            try {
                
                $delete->delete();

                // redirect and message
                $this->flashMessage('Záznam byl úspěšně odstraněn.');
                $this->redirect('Settings:assaylist');
            } catch (Exception $e) {

                // redirect and message
                $this->flashMessage('Záznam nelze odstranit. (CHYBA: ' . $e . ')');
                $this->redirect('Settings:assaylist');
            }
        }
    }
}