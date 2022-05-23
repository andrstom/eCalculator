<?php
declare(strict_types=1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use Nette\Security\Passwords;
use App\Model\DbHandler;
use Nette\Mail\Message;
use Nette\Mail\IMailer;

class UserPresenter extends BasePresenter {

    /**
     * @var \App\Model\DbHandler
     * @inject
     */
    public $dbHandler;
    
    /**
     * @var Nette\Security\Passwords
     * @inject
     */
    public $passwords;
    
    private $editUser;
    private $mailer;

    public function __construct(IMailer $mailer)
    {
        $this->mailer = $mailer;
    }
    
    public function renderAdd() 
    {

        $this->template->userlist = $this->dbHandler->getUsers()->order('id DESC');
        $this->template->assays = $this->dbHandler->getAssays();
        $this->template->units = $this->dbHandler->getUnits();
        $this->template->readers = $this->dbHandler->getReaders();
        
    }
    
    public function renderRegistration() 
    {

        $this->template->assays = $this->dbHandler->getAssays();
        $this->template->units = $this->dbHandler->getUnits();
        
    }

    public function renderEdit($userId) 
    {
        $getUser = $this->dbHandler->getUsers()->get($userId);
        $this->template->getUser = $getUser;
        
        if (!$getUser) 
        {
            $this->error('Uživatel nebyl nalezen!');
        }

        $this->template->assays = $this->dbHandler->getAssays();
        $this->template->units = $this->dbHandler->getUnits();
        $this->template->readers = $this->dbHandler->getReaders();
        $usersAssays = $this->dbHandler->getUsersAssays()->where('users_id', $getUser->id);
        $this->template->usersAssays = $usersAssays;
        
    }

    /**
     * @return Nette\Application\UI\Form
     */
    protected function createComponentUserForm()
    {

        $true_false = array('ANO' => 'YES', 'NE' => 'NO');

        // set array of roles
        $roles = array('Admin' => 'Admin', 'Klient' => 'Klient');

        // load assays
        $this->template->assays = $this->dbHandler->getAssays()->fetchAll();

        // load units
        $units = $this->dbHandler->getUnits()->fetchPairs('id', 'unit_name');

        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);

        // Set form labels
        $form->addText('login', 'Login: *')
                ->setRequired('Vyplňtě Login / Fill in the Login');

        $form->addText('email', 'E-mail: *', 55)
                ->setHtmlType('email')
                ->setRequired('Vyplňte Email / Fill in the email')
                ->addRule(Form::EMAIL, 'Neplatná emailová adresa / Invalid email');

        if (!$this->editUser) {
            
            $form->addPassword('password', 'Heslo / Password: *', 20)
                    ->setOption('description', 'Alespoň 4 znaky / At least 4 characters')
                    ->setRequired('Vyplňte Heslo / Fill in the password')
                    ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků / The password must be at least %d characters long', 4);

            $form->addPassword('password2', 'Heslo znovu / Password again: *', 20)
                    ->addConditionOn($form['password'], Form::VALID)
                    ->setRequired('Vyplňte Heslo znovu / Fill in the password again')
                    ->addRule(Form::EQUAL, 'Hesla se neshodují / The passwords are not the same.', $form['password']);
        }

        if ($this->getUser()->isInRole('Admin')) {
            
            $form->addRadioList('role_short', 'Vyberte roli: *', $roles)
                    ->setRequired('Vyberte Roli');

        }
        $form->addText('company_name', 'Název laboratoře / Laboratory name:');

        $form->addText('address', 'Adresa / Address:');

        $form->addText('ico', 'IČO / Commercial ID:');

        $form->addText('gsm', 'Telefon / Phone:');

        $form->addTextArea('notice', 'Poznámka / Note:');

        $form->addRadioList('print_detail', 'Tisk detailů na report / Print laboratory info to the result:', $true_false)
                ->setDefaultValue('ANO');

        $form->addRadioList('active', 'Aktivní / Active:', $true_false)
                ->setDefaultValue('ANO');

        $form->addSubmit('send', 'Uložit / Save');

        //call method signUpFormSucceeded() on success
        $form->onSuccess[] = [$this, 'userFormSucceeded'];

        return $form;
    }

    public function userFormSucceeded($form)
    {

        // get values from form
        $values = $form->getValues();
        $readers_id = $form->getHttpData($form::DATA_TEXT | $form::DATA_KEYS, 'reader_id[]');
        
        if ($this->editUser) {

            $row = $this->editUser->update([
                'login' => $values->login,
                'email' => $values->email,
                'company_name' => $values->company_name,
                'address' => $values->address,
                'ico' => $values->ico,
                'gsm' => $values->gsm,
                'print_detail' => $values->print_detail,
                'notice' => $values->notice,
                'editor' => $this->getUser()->getIdentity()->getData()['login'],
                'edited_at' => time(),
            ]);
            
            // delete all previous readers
            $this->dbHandler->getUsersReaders()->where('user_id', $this->editUser->id)->delete();
            
            // insert new selected readers
            foreach ($readers_id as $r_id) {
                $this->dbHandler->getUsersReaders()->insert([
                        'user_id' => $this->editUser->id,
                        'reader_id' => $r_id
                ]);
                
            }
            
            // delete all previous assays
            $this->dbHandler->getUsersAssays()->where('users_id', $this->editUser->id)->delete();

            // get values from assays and units inputs
            $assays = $form->getHttpData($form::DATA_TEXT | $form::DATA_KEYS, 'assay[]');
            foreach ($assays as $k => $v) {

                $unit = $form->getHttpData($form::DATA_TEXT | $form::DATA_KEYS, 'unit[' . $k . '][]');

                // if $units empty - set default unit to 1
                if (empty($unit)) {
                    $unit = 1;
                }

                // insert into db (calc_users_assays)
                $this->dbHandler->getUsersAssays()->insert([
                    'users_id' => $this->editUser->id,
                    'assays_id' => $k,
                    'units_id' => $unit[0],
                    'creator' => $this->getUser()->getIdentity()->getData()['login'],
                    'created_at' => time()
                ]);
            }
            
        } else {

            try {

                // insert user details
                $row = $this->dbHandler->getUsers()->insert([
                    'login' => $values->login,
                    'email' => $values->email,
                    'password' => $this->passwords->hash($values->password),
                    'company_name' => $values->company_name,
                    'address' => $values->address,
                    'ico' => $values->ico,
                    'gsm' => $values->gsm,
                    'notice' => $values->notice,
                    'role_short' => $values->role_short,
                    'print_detail' => $values->print_detail,
                    'active' => $values->active,
                    'creator' => $this->getUser()->getIdentity()->getData()['login'],
                    'created_at' => time(),
                ]);
                
                // get last inserted id
                $userLastId = $row->id;
                
                // insert readers
                foreach ($readers_id as $r_id) {
                    $this->dbHandler->getUsersReaders()->insert([
                        'user_id' => $userLastId,
                        'reader_id' => $r_id
                    ]);
                }
                
                // get values from assays and units inputs
                $assays = $form->getHttpData($form::DATA_TEXT | $form::DATA_KEYS, 'assay[]');
                foreach ($assays as $k => $v) {

                    $unit = $form->getHttpData($form::DATA_TEXT | $form::DATA_KEYS, 'unit[' . $k . '][]');
                    
                    // if $units empty - set default unit to 1
                    if (empty($unit)) {
                        $unit = 1;
                    }

                    // insert into db (users_assays)
                    $this->dbHandler->getUsersAssays()->insert([
                        'users_id' => $userLastId,
                        'assays_id' => $k,
                        'units_id' => $unit[0],
                        'creator' => $this->getUser()->getIdentity()->getData()['login'],
                        'created_at' => time(),
                    ]);
                }
            } catch (\Nette\Database\UniqueConstraintViolationException $e) {

                throw new DuplicateNameException;
            }
        }

        // redirect and message
        $this->flashMessage('Registrace / Změna uživatele byla úspěšná.');
        $this->redirect('Settings:userlist');
    }

    protected function createComponentUserRegistrationForm()
    {
        
        $true_false = array('ANO' => 'YES', 'NE' => 'NO');

        // load assays
        $this->template->assays = $this->dbHandler->getAssays()->fetchAll();

        // load units
        $units = $this->dbHandler->getUnits()->fetchPairs('id', 'unit_name');

        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);

        // Set form labels
        $form->addText('login', 'Login: *')
                ->setRequired('Vyplňtě Login / Fill in the login')
                ->addRule(function ($control) {
                    return !$this->dbHandler->getUsers()->where('login = ?', $control->value)->fetch();
                    }, 'Login již existuje / Login already exist!');

        $form->addText('email', 'E-mail: *', 55)
                ->setHtmlType('email')
                ->setRequired('Vyplňte email / Fill in the email')
                ->addRule(Form::EMAIL, 'Neplatný email / Invalid email!');

        $form->addPassword('password', 'Heslo / Password: *', 20)
                ->setOption('description', 'Alespoň 4 znaky / At least 4 characters')
                ->setRequired('Vyplňte Heslo. / Fill in the password.')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků / Password must be at least %d characters', 4);

        $form->addPassword('password2', 'Heslo znovu / Password again: *', 20)
                ->addConditionOn($form['password'], Form::VALID)
                ->setRequired('Vyplňte Heslo znovu. / Fill in the password again.')
                ->addRule(Form::EQUAL, 'Hesla se neshodují. / The passwords are not the same. ', $form['password']);

        $form->addText('company_name', 'Název laboratoře / Laboratory name:');

        $form->addText('address', 'Adresa / Address:');

        $form->addText('ico', 'IČO / Commercial ID:');

        $form->addText('gsm', 'Telefon / Phone:');

        $form->addTextArea('notice', 'Poznámka / Note:');

        $form->addRadioList('print_detail', 'Tisk detailů na report / Print laboratory info to the result:', $true_false)
                ->setDefaultValue('ANO');
        //->setAttribute('style', "display:inline; margin-left: 5px");

        $form->addSubmit('send', 'Uložit / Send');

        //call method signUpFormSucceeded() on success
        $form->onSuccess[] = [$this, 'userRegistrationFormSucceeded'];

        return $form;
    }
    
    public function userRegistrationFormSucceeded($form)
    {

        // get values from form
        $values = $form->getValues();
        
        try {
            // insert user details
            $row = $this->dbHandler->getUsers()->insert([
                'login' => $values->login,
                'email' => $values->email,
                'password' => $this->passwords->hash($values->password),
                'company_name' => $values->company_name,
                'address' => $values->address,
                'ico' => $values->ico,
                'gsm' => $values->gsm,
                'notice' => $values->notice,
                'role_short' => 'Klient',
                'print_detail' => $values->print_detail,
                'active' => 'ANO',
                'creator' => $values->email,
                'created_at' => time(),
            ]);

            // get last inserted id
            $userLastId = $row->id;

            // get values from assays and units inputs
            $assays = $form->getHttpData($form::DATA_TEXT | $form::DATA_KEYS, 'assay[]');
            foreach ($assays as $k => $v) {

                $units = $form->getHttpData($form::DATA_TEXT | $form::DATA_KEYS, 'unit[' . $k . '][]');

                // if $units empty - set default unit to 1
                if (empty($units)) {
                    $units = 1;
                }

                // insert into db (users_assays)
                $this->dbHandler->getUsersAssays()->insert([
                    'users_id' => $userLastId,
                    'assays_id' => $k,
                    'units_id' => $units,
                    'creator' => $values->email,
                    'created_at' => time(),
                ]);
            }

            $mail = new Message;

            $mail->setFrom('servis@vidia.cz')
                ->addTo($values->email)
                ->setSubject('E-Calculator registrace/registration')
                ->setHtmlBody("<div style='padding: 15px 15px 15px 15px; background-color: #e5eaef; border: 1px solid #ad67b2; border-radius: 10px 10px 10px 10px;'>"
                        . "<div style='padding: 5px 5px 5px 5px; background-color: #fcdeff; border: 1px solid #dec2e0; border-radius: 10px 10px 10px 10px;'>"
                        . "<h2><b>Gratulujeme, Vaše registrace proběhla úspěšně.</b></h2>"
                        . "<p>Nyní si Vás po přihlášení E-Calculator bude pamatovat a automaticky Vám vyplní poslední 
                            vložené hodnoty z certifikátů kvality (šarži, expiraci, šedé zóny) a preferované metody a jednotky.</p>"
                        . "<p>Pokud používáte fotometr, který umožňuje export naměřených hodnot do textového souboru (TXT) nebo do Excel soboru (XLS/XLSX) – kontaktujte 
                            nás na servis@vidia.cz. Nastavíme pro Vás E-Calculator tak, aby uměl zpracovat naměřená data z fotometru.</p>"
                        . "<h3><b>Přihlašovací údaje:</b></h3>"
                        . "<p><b>Login:</b><i> ". $values->login . "</i></p>"
                        . "<p><b>Heslo:</b><i> " . $values->password . "</i></p>"
                        . "<b>Nyní se můžete <a href='http://www.vidia.cz/eCalculator/user/in'>přihlásit</a>.</b>"
                        . "<p>Děkujeme, že používáte E-Calculator</p>"
                        . "<h2>Vaše Vidia</h2>"
                        ."<hr>"
                        . "<h2><b>Congratulations, your registration was successful.</b></h2>"
                        . "<p>E-Calculator will remember you and automatically will fill in the last batch details (if entered before)</p>"
                        . "<p>When you use a photometer that exports measured values into a text file (TXT) or an Excel file (XLS/XLSX) "
                        . "for the first time, please, do not hesitate and email us at servis@vidia.cz. We will modify your account and "
                        . "from that moment onwards E-Calculator will process measured data from that photometer.</p>"
                        . "<h3><b>Login details:</b></h3>"
                        . "<p><b>Login:</b> <i>". $values->login . "</i></p>"
                        . "<p><b>Password:</b> <i>" . $values->password . "</i></p>"
                        . "<b>Now, you can <a href='http://www.vidia.cz/eCalculator/user/in'>login</a>.</b>"
                        . "<p>Thank you for using E-Calculator</p>"
                        . "<h3>Your Vidia</h3>"
                        . "</div><br>"
                        . "<p>&copy; Vidia spol. s r.o., Nad Safinou II 365, 252 50 Vestec, Česká republika | www.vidia.cz | All rights reserved</p></div>");
            if($mail) {
                try {
                    $this->mailer->send($mail);
                    $this->flashMessage('Vaše registrace byla úspěšná. Přihlašovací údaje naleznete v emailu (' . $values->email . ') / Your registration was successful. Login info can be found in the email (' . $values->email . ')', 'success');
                    $this->redirect('Homepage:');
                } catch (SendException $e) {
                    Debugger::log($e, 'mailexception');
                    $this->flashMessage('Uuups - nepodařilo se odeslat email s přihlašovacími údaji. / Failed to send email with login info!' . $e, 'success');
                    $this->redirect('Homepage:');
                }
            }

        } catch (\Nette\Database\UniqueConstraintViolationException $e) {

            throw new DuplicateNameException;
        }

        // redirect and message
        $this->flashMessage('Registrace uživatele byla úspěšná. Přihlaste se, prosím! / Registration was successful. Sign in, please.');
        $this->redirect('Homepage:default');
    }
    
    protected function createComponentSignInForm()
    {

        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);

        $form->addText('login', '* Login:')
                ->setRequired('Prosím vyplňte Login.')
                ->setHtmlAttribute('placeholder', 'login');
        
        $form->addPassword('password', '* Heslo:')
                ->setRequired('Prosím vyplňte Heslo.')
                ->setHtmlAttribute('placeholder', 'heslo');
                
        $form->addCheckbox('remember', 'Pamatovat přihlášení (10 dní).');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = [$this, 'signInFormSucceeded'];
        
        return $form;
        
    }

    /**
     * @param $form Nette\Application\UI\Form
     * @param $values Nette\Utils\ArrayHash
     */
    public function signInFormSucceeded($form, $values)
    {
        try {
            $this->getUser()->setExpiration($values->remember ? '10 days' : '20 minutes');
            $this->getUser()->login($values->login, $values->password);
            $this->redirect('Homepage:default');
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError('Nesprávné přihlašovací jméno nebo heslo.');
        }
    }

    /**
     * @return Nette\Application\UI\Form
     */
    protected function createComponentChangePasswordForm()
    {
        //dump($this->editUser->password);
        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);

        $form->addPassword('passwordNew', 'Nové heslo:', 20)
                ->setOption('description', 'Alespoň 4 znaky')
                ->setRequired('Vyplňte Heslo')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 4);

        $form->addPassword('passwordNew2', 'Nové heslo znovu:', 20)
                ->addConditionOn($form['passwordNew'], Form::VALID)
                ->setRequired('Vyplňte Heslo znovu')
                ->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['passwordNew']);

        $form->addSubmit('send', 'Změnit / Change');

        $form->onSuccess[] = [$this, 'changePasswordFormSucceeded'];
        
        return $form;

    }
    
    public function changePasswordFormSucceeded($form)
    {

        // get values from form
        $values = $form->getValues();
        
        if ($this->editUser) {
            
            try {
                
                $row = $this->editUser->update([
                    'password' => $this->passwords->hash($values->passwordNew),
                    'editor' => $this->getUser()->getIdentity()->getData()['login'],
                    'edited_at' => time(),
                ]);
                
                // redirect and message
                $this->flashMessage('Heslo bylo změněno.');
                $this->redirect('User:edit?userId=' . $this->editUser->id);
                
            } catch (Exception $e) {

                // redirect and message
                $this->error('Heslo nelze změnit. (CHYBA: ' . $e . ')');
                $this->redirect('User:edit?userId=' . $this->editUser->id);
            }
        }
        
    }
    
    
    public function actionEdit($userId)
    {
        
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $editUser = $this->dbHandler->getUsers()->get($userId);
        $this->editUser = $editUser;

        if (!$editUser) {

            $this->error('Uživatel nebyl nalezen');
        }

        $this['userForm']->setDefaults($editUser->toArray());
    }

    // delete user
    public function actionDelete($userId)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:in');
        }

        $delete = $this->dbHandler->getUsers()->get($userId);
        if (!$delete) {

            $this->error('Nelze smazat, záznam neexistuje!!!');
        } else {

            try {
                
                $delete->delete();

                // redirect and message
                $this->flashMessage('Záznam byl úspěšně odstraněn.');
                $this->redirect('Settings:userlist');
            } catch (Exception $e) {

                // redirect and message
                $this->flashMessage('Záznam nelze odstranit. (CHYBA: ' . $e . ')');
                $this->redirect('Settings:userlist');
            }
        }
    }

    // logout
    public function actionOut() {
        $this->getUser()->logout();
        $this->flashMessage('Odhlášení bylo úspěšné.');
        $this->redirect('Homepage:default');
    }

}
