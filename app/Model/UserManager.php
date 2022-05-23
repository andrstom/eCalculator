<?php
declare(strict_types=1);
namespace App\Model;

use Nette;
use Nette\Utils\Strings;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\AuthenticationException;

/**
* Users management.
*/
class UserManager implements Nette\Security\IAuthenticator
{
    use Nette\SmartObject;
    
    const
            TABLE_NAME = 'calc_users',
            COLUMN_ID = 'id',
            COLUMN_LOGIN = 'login',
            COLUMN_PASSWORD_HASH = 'password',
            COLUMN_EMAIL = 'email',
            COLUMN_COMPANY = 'company_name',
            COLUMN_ADDRESS = 'address',
            COLUMN_ICO = 'ico',
            COLUMN_GSM = 'gsm',
            COLUMN_NOTICE = 'notice',
            COLUMN_ROLE_SHORT = 'role_short',
            COLUMN_PRINT_DETAIL = 'print_detail',
            COLUMN_READER = 'reader_id',
            COLUMN_ACTIVE = 'active',
            COLUMN_CREATOR = 'creator',
            COLUMN_CREATED_AT = 'created_at';
            

    /** @var Nette\Database\Context */
    private $database;
    
    private $passwords;


    public function __construct(Nette\Database\Context $database, Nette\Security\Passwords $passwords)
    {
            $this->database = $database;
            $this->passwords = $passwords;
    }

    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials): Nette\Security\IIdentity
    {
            [$login, $password] = $credentials;

            $row = $this->database->table(self::TABLE_NAME)
                    ->where(self::COLUMN_LOGIN, $login)->fetch();

            if (!$row) {

                throw new Nette\Security\AuthenticationException('Chybný login.', self::IDENTITY_NOT_FOUND);

            } elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {

                throw new Nette\Security\AuthenticationException('Chybné heslo.', self::INVALID_CREDENTIAL);

            } elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
                    $row->update([
                            self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
                    ]);
            }

            $arr = $row->toArray();
            unset($arr[self::COLUMN_PASSWORD_HASH]);
            
            return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE_SHORT], $arr);
            
    }


    /**
     * Adds new user.
     * @param  string
     * @param  string
     * @param  string
     * @return void
     * @throws DuplicateNameException
     */
    public function add($username, $login, $password, $email, $company_name, $address, $ico, $gsm, $notice, $role_short, $print_detail, $reader_id, $active)
    {
            try {
                    
                    $this->database->table(self::TABLE_NAME)->insert([
                            self::COLUMN_LOGIN => $login,
                            self::COLUMN_EMAIL => $email,
                            self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
                            self::COLUMN_COMPANY => $company_name,
                            self::COLUMN_ADDRESS => $address,
                            self::COLUMN_ICO => $ico,
                            self::COLUMN_GSM => $gsm,
                            self::COLUMN_NOTICE => $notice,
                            self::COLUMN_ROLE_SHORT => $role_short,
                            self::COLUMN_PRINT_DETAIL => $print_detail,
                            self::COLUMN_READER => $reader_id,
                            self::COLUMN_ACTIVE => $active,
                    ]);
                    
            } catch (\Nette\Database\UniqueConstraintViolationException $e) {
                    
                    throw new DuplicateNameException;
                    
            }
            
    }
}

class DuplicateNameException extends \Exception
{
}
