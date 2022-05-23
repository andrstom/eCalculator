<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use App\Model\DbHandler;

class ReaderPresenter extends BasePresenter {

    private $editReader;

    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;

   
    public function renderAdd() 
    {

        $this->template->readers = $this->dbHandler->getReaders();
        
    }

    public function renderEdit($readerId) 
    {
        $reader = $this->dbHandler->getReaders()->get($readerId);
        $this->template->reader = $reader;

        if (!$reader) 
        {
            $this->error('Jednotka nebyla nalezena!');
        }

    }

    /**
     * Signup form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentReaderForm()
    {

        $true_false = array('ANO' => 'ANO', 'NE' => 'NE');

        
        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);

        // Set form labels
        $form->addText('reader_short', 'Zkratka: *')
                ->setRequired('Vyplňtě Zkratku')
                ->addRule(Form::MAX_LENGTH, '%label (max. %d znaků)', 10);
                

        $form->addText('reader_name', 'Název: *')
                ->setRequired('Vyplňte Název')
                ->addRule(Form::MAX_LENGTH, '%label (max. %d znaků)', 50);
                
        $form->addSelect('reader_output', 'Formát výstupního souboru:', ['XLS'=>'Excel (.xls)', 'TXT'=>'Textový výstup (.txt)', 'manual'=>'Manual'])
                ->setDefaultValue('XLS')
                ->setRequired('Vyberte Formát výstupního souboru');
        
        $form->addInteger('reader_xls_list', 'Pořadí listu v Excel souboru (.TXT = 0): *')
                ->setDefaultValue('1')
                ->setOption('description', '1, 2, ... (1 = list1, ...)')
                ->addRule(Form::PATTERN, '%label musí být celé číslo', '.*[0-9].*');
                
        $form->addText('reader_data_range', 'Oblast naměřených hodnot (např. A1:L8 nebo 118:2:6): *')
                ->setOption('description', 'XLS = A1:L8, TXT=řádek:sloupec:vynechané řádky')
                ->addConditionOn($form['reader_output'], Form::NOT_EQUAL, 'manual')
                    ->setRequired('Vyplňte Oblast naměřených hodnot')
                    ->addConditionOn($form['reader_output'], Form::EQUAL, 'TXT')
                        ->addRule(Form::PATTERN, 'Nesprávný tvar %label (musí být např 118:2:6)', '.*([0-9]:[0-9]:[0-9]).*')
                        ->endCondition()
                    ->addConditionOn($form['reader_output'], Form::EQUAL, 'XLS')
                        ->addRule(Form::PATTERN, 'Nesprávný tvar %label (musí být např A1:L8)', '.*([A-Z][0-9]:[A-Z][0-9]).*')
                        ->endCondition();
                        
        $form->addSelect('reader_txt_separator', 'Textový separátor:', [''=>'žádný', 'tab'=>'tabulator (→)', 'pipe'=>'svislá čárka ( | )', 'space'=>'mezera', 'comma'=>'čárka ( , )', 'semicolon'=>'středník ( ; )'])
                ->setDefaultValue('')
                ->addConditionOn($form['reader_output'], Form::EQUAL, 'TXT')
                    ->setRequired('Vyberte %label');

        $form->addTextArea('path', 'Cesta k soborům z readeru:');
        
        $form->addTextArea('notice', 'Poznámka:');

        $form->addRadioList('active', 'Aktivní:', $true_false)
                ->setDefaultValue('ANO');
        
        
        $form->addSubmit('send', 'Uložit');

        //call method signUpFormSucceeded() on success
        $form->onSuccess[] = [$this, 'readerFormSucceeded'];

        return $form;
    }

    public function readerFormSucceeded($form)
    {

        // get values from form
        $values = $form->getValues();

        if ($this->editReader) {

            $row = $this->editReader->update([
                'reader_short' => $values->reader_short,
                'reader_name' => $values->reader_name,
                'reader_output' => $values->reader_output,
                'reader_xls_list' => $values->reader_xls_list,
                'reader_data_range' => $values->reader_data_range,
                'reader_txt_separator' => $values->reader_txt_separator,
                'path' => $values->path,
                'notice' => $values->notice,
                'active' => $values->active,
                'editor' => $this->getUser()->getIdentity()->getData()['login'],
                'edited_at' => time(),
            ]);

        } else {

            try {

                // insert user details
                $row = $this->dbHandler->getReaders()->insert([
                    'reader_short' => $values->reader_short,
                    'reader_name' => $values->reader_name,
                    'reader_output' => $values->reader_output,
                    'reader_xls_list' => $values->reader_xls_list,
                    'reader_data_range' => $values->reader_data_range,
                    'reader_txt_separator' => $values->reader_txt_separator,
                    'path' => $values->path,
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
        $this->flashMessage('Reader byl úspěšně vložen/upraven.');
        $this->redirect('Settings:readerlist');
    }

    
    public function actionEdit($readerId)
    {
        
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $editReader = $this->dbHandler->getReaders()->get($readerId);
        $this->editReader = $editReader;

        if (!$editReader) {

            $this->error('Jednotka nebyla nalezena.');
        }

        $this['readerForm']->setDefaults($editReader->toArray());
    }

    // delete
    public function actionDelete($readerId)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $delete = $this->dbHandler->getReaders()->get($readerId);
        if (!$delete) {

            $this->error('Nelze smazat, záznam neexistuje!!!');
        } else {

            try {
                
                $delete->delete();

                // redirect and message
                $this->flashMessage('Záznam byl úspěšně odstraněn.');
                $this->redirect('Settings:Readerlist');
            } catch (Exception $e) {

                // redirect and message
                $this->flashMessage('Záznam nelze odstranit. (CHYBA: ' . $e . ')');
                $this->redirect('Settings:Readerlist');
            }
        }
    }
}