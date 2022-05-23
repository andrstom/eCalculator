<?php
declare(strict_types=1);
namespace App\Model;

use Nette;

class DbHandler
{
    use Nette\SmartObject;

    /**
     * @var Nette\Database\Context
     */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }
    
    public function getUsers()
    {
            return $this->database->table('calc_users');
    }
    
    public function getAssays()
    {
            return $this->database->table('calc_assays');
    }
    
    public function getUnits()
    {
            return $this->database->table('calc_units');
    }
    
    public function getReaders()
    {
            return $this->database->table('calc_reader');
    }
    
    public function getUsersAssays()
    {
            return $this->database->table('calc_users_assays');
    }
    
    public function getUsersReaders()
    {
            return $this->database->table('calc_users_readers');
    }
    
}