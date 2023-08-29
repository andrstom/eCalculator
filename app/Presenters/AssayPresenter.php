<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use App\Model\DbHandler;

class AssayPresenter extends BasePresenter
{
    private $editAssay;
    private $editAssayMono;
    private $editLayout;
    //private $editDetectionType;

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
        if (!$assay) {
            $this->error('Metoda nebyla nalezena!');
        }
    }
    
    public function renderAddMono()
    {
        $this->template->assaysMono = $this->dbHandler->getAssaysMono();
        $this->template->unitsMono = $this->dbHandler->getUnitsMono()->fetchAll();
        $this->template->dilutions = $this->dbHandler->getDilutions()->fetchAll();
    }

    public function renderEditMono($assayId)
    {
        $assay = $this->dbHandler->getAssaysMono()->get($assayId);
        $this->template->assayMono = $assay;
        if (!$assay) {
            $this->error('Metoda nebyla nalezena!');
        }
        $this->template->unitsMono = $this->dbHandler->getUnitsMono()->fetchAll();
        $allowedUnits = $this->dbHandler->getAllowedUnitsByAssayId($assayId)->fetchAll();
        $this->template->allowedUnits = $allowedUnits;
        
        $this->template->dilutions = $this->dbHandler->getDilutions()->fetchAll();
        $allowedDilutions = $this->dbHandler->getAllowedDilutionsByAssayId($assayId)->fetchAll();
        $this->template->allowedDilutions = $allowedDilutions;
    }
    
    public function renderAddLayout()
    {
        $this->template->layout = $this->dbHandler->getLayouts();
    }

    public function renderEditLayout($layoutId)
    {
        $layout = $this->dbHandler->getLayouts()->get($layoutId);
        $this->template->layout = $layout;
        if (!$layout) {
            $this->error('Layout nebyl nalezena!');
        }
    }
    
    /**
     * Assay form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentAssayForm()
    {
        $cal_layouts = $this->dbHandler->getLayouts()->fetchPairs('id', 'layout_name');
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
        $form->addRadioList('layout', 'Rozložení kontrol: *', $cal_layouts)
                ->setDefaultValue(1)
                ->setRequired('Vyplňte Název');
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
                'layout' => $values->layout,
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
                    'layout' => $values->layout,
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

    /**
     * MONO Assay form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentAssayMonoForm()
    {
        $units = $this->dbHandler->getUnitsMono()->fetchPairs('id', 'unit_name');
        $dilutions = $this->dbHandler->getDilutions()->fetchPairs('id', 'sample_type');
        $form = new Form;
        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        // Set form labels
        $form->addText('assay_short', 'Zkratka: *')
                ->setRequired('Vyplňtě Zkratku');
        $form->addText('assay_name', 'Název: *')
                ->setRequired('Vyplňte Název');
        $form->addCheckboxList('allowed_units', 'Související jednotky:*', $units)
                ->setRequired('Vyberte alespoň jednu související jednotku.');
        $form->addCheckboxList('allowed_dilutions', 'Související ředění vzorku (typ materiálu):*', $dilutions)
                ->setRequired('Vyberte alespoň jeden související ředění vzorku.');
        $form->addTextArea('notice', 'Poznámka:');
        $form->addRadioList('active', 'Aktivní:', ['ANO' => 'ANO', 'NE' => 'NE'])
                ->setDefaultValue('ANO');
        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = [$this, 'assayMonoFormSucceeded'];
        return $form;
    }

    public function assayMonoFormSucceeded($form)
    {
        // get values from form
        $values = $form->getValues();
        if ($this->editAssayMono) {
            $row = $this->editAssayMono->update([
                'assay_short' => $values->assay_short,
                'assay_name' => $values->assay_name,
                'notice' => $values->notice,
                'active' => $values->active,
                'editor' => $this->getUser()->getIdentity()->getData()['login'],
                'edited_at' => time(),
            ]);
            // update allowed units
            $this->dbHandler->getAllowedUnitsByAssayId($this->editAssayMono->id)->delete();
            // insert new selected units
            foreach ($values['allowed_units'] as $allowedUnit) {
                $this->dbHandler->getAllowedUnits()->insert([
                        'assays_id' => $this->editAssayMono->id,
                        'units_id' => $allowedUnit
                ]);
            }
            // update allowed dilutions
            $this->dbHandler->getAllowedDilutionsByAssayId($this->editAssayMono->id)->delete();
            // insert new selected dilutions
            foreach ($values['allowed_dilutions'] as $allowedDilution) {
                $this->dbHandler->getAllowedDilutions()->insert([
                        'assays_id' => $this->editAssayMono->id,
                        'dilutions_id' => $allowedDilution
                ]);
            }
        } else {
            try {
                // insert user details
                $row = $this->dbHandler->getAssaysMono()->insert([
                    'assay_short' => $values->assay_short,
                    'assay_name' => $values->assay_name,
                    'notice' => $values->notice,
                    'active' => $values->active,
                    'creator' => $this->getUser()->getIdentity()->getData()['login'],
                    'created_at' => time(),
                ]);
                // insert selected units
                foreach ($values['allowed_units'] as $allowedUnit) {
                    $this->dbHandler->getAllowedUnits()->insert([
                            'assays_id' => $row->id,
                            'units_id' => $allowedUnit
                    ]);
                }
                // insert dilutions
                foreach ($values['allowed_dilutions'] as $allowedDilution) {
                    $this->dbHandler->getAllowedDilutions()->insert([
                            'assays_id' => $row->id,
                            'dilutions_id' => $allowedDilution
                    ]);
                }
            } catch (\Nette\Database\UniqueConstraintViolationException $e) {
                throw new DuplicateNameException;
            }
        }
        // redirect and message
        $this->flashMessage('Metoda byla úspěšně vložena/upravena.');
        $this->redirect('Settings:assaylist');
    }
    
    /**
     * Layout form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentLayoutForm()
    {
        $form = new Form;
        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        // Set form labels
        $form->addText('layout_name', 'Název: *')
                ->setRequired('Vyplňtě Název');
        $form->addTextArea('notice', 'Poznámka:');
        $form->addText('cal_1', 'Název pozice "A1": *')
                ->setRequired('Vyplňte Název pozice "A1"');
        $form->addText('cal_2', 'Název pozice "B1":');
        $form->addText('cal_3', 'Název pozice "C1":');
        $form->addText('cal_4', 'Název pozice "D1":');
        $form->addText('cal_5', 'Název pozice "E1":');
        $form->addText('cal_6', 'Název pozice "F1":');
        $form->addText('cal_7', 'Název pozice "G1":');
        $form->addText('cal_8', 'Název pozice "H1":');
        $form->addText('cal_9', 'Název pozice "A2":');
        $form->addText('cal_10', 'Název pozice "B2":');
        $form->addSubmit('send', 'Uložit');
        //call method signUpFormSucceeded() on success
        $form->onSuccess[] = [$this, 'layoutFormSucceeded'];
        return $form;
    }

    public function layoutFormSucceeded($form)
    {
        // get values from form
        $values = $form->getValues();
        if ($this->editLayout) {
            // edit layout detail
            $row = $this->editLayout->update([
                'layout_name' => $values->layout_name,
                'notice' => $values->notice,
                'cal_1' => $values->cal_1,
                'cal_2' => $values->cal_2,
                'cal_3' => $values->cal_3,
                'cal_4' => $values->cal_4,
                'cal_5' => $values->cal_5,
                'cal_6' => $values->cal_6,
                'cal_7' => $values->cal_7,
                'cal_8' => $values->cal_8,
                'cal_9' => $values->cal_9,
                'cal_10' => $values->cal_10
            ]);
        } else {
            try {
                // insert layout detail
                $row = $this->dbHandler->getLayouts()->insert([
                    'layout_name' => $values->layout_name,
                    'notice' => $values->notice,
                    'cal_1' => $values->cal_1,
                    'cal_2' => $values->cal_2,
                    'cal_3' => $values->cal_3,
                    'cal_4' => $values->cal_4,
                    'cal_5' => $values->cal_5,
                    'cal_6' => $values->cal_6,
                    'cal_7' => $values->cal_7,
                    'cal_8' => $values->cal_8,
                    'cal_9' => $values->cal_9,
                    'cal_10' => $values->cal_10
                ]);
            } catch (\Nette\Database\UniqueConstraintViolationException $e) {
                throw new DuplicateNameException;
            }
        }
        // redirect and message
        $this->flashMessage('Layout byl úspěšně vložen/upraven.');
        $this->redirect('Settings:assaylist');
    }
    
    // edit ELISA assay
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

    // delete ELISA assay
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
    
    // edit MONO assay
    public function actionEditMono($assayId)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }
        $editAssayMono = $this->dbHandler->getAssaysMono()->get($assayId);
        $this->editAssayMono = $editAssayMono;
        if (!$editAssayMono) {
            $this->error('Metoda nebyla nalezena.');
        }
        $this['assayMonoForm']->setDefaults($editAssayMono->toArray());
    }
    
    // delete MONO assay
    public function actionDeleteMono($assayId)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }
        $delete = $this->dbHandler->getAssaysMono()->get($assayId);
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
    
    // edit layout
    public function actionEditLayout($layoutId)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }
        $editLayout = $this->dbHandler->getLayouts()->get($layoutId);
        $this->editLayout = $editLayout;
        if (!$editLayout) {
            $this->error('Layout nebyl nalezen.');
        }
        $this['layoutForm']->setDefaults($editLayout->toArray());
    }
    
    // delete layout
    public function actionDeleteLayout($layoutId) {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }
        $delete = $this->dbHandler->getLayouts()->get($layoutId);
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