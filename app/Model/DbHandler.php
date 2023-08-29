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

    public function __construct(Nette\Database\Context $database) {
            $this->database = $database;
    }
    
    public function getUsers() {
            return $this->database->table('calc_users');
    }
    
    public function getAssays() {
            return $this->database->table('calc_assays')->order('assay_name');
    }
    
    public function getAssaysMono() {
            return $this->database->table('calc_assays_mono')->order('assay_name');
    }
    
    public function getUnits() {
            return $this->database->table('calc_units');
    }
    
    public function getUnitsMono() {
            return $this->database->table('calc_units_mono');
    }
    
    public function getReaders() {
            return $this->database->table('calc_reader');
    }
    
    public function getLayouts() {
            return $this->database->table('calc_layouts');
    }
    
    /*public function getDetectionTypes() {
            return $this->database->table('calc_detection_type');
    }*/
    
    public function getUsersAssays() {
            return $this->database->table('calc_users_assays');
    }
    
    public function getUsersAssaysMono() {
            return $this->database->table('calc_users_assays_mono');
    }
    
    public function getUsersReaders() {
            return $this->database->table('calc_users_readers');
    }
    
    public function getAssaysLayouts() {
            return $this->database->table('calc_assays_layouts');
    }
    
    public function getMonotests() {
            return $this->database->table('calc_monotests');
    }
    
    public function getResultsBySession($protocol_id) {
            return $this->database->table('calc_mono_results')->where('protocol_id', $protocol_id);
    }
    
    public function getAllowedUnits() {
            return $this->database->table('calc_allowed_units');
    }
    
    public function getAllowedUnitsByAssayId($assay_id) {
            return $this->database->table('calc_allowed_units')->where('assays_id', $assay_id);
    }
    
    public function getDilutions() {
            return $this->database->table('calc_dilutions');
    }
    
    public function getAllowedDilutions() {
            return $this->database->table('calc_allowed_dilutions');
    }
    
    public function getAllowedDilutionsByAssayId($assay_id) {
            return $this->database->table('calc_allowed_dilutions')->where('assays_id', $assay_id);
    }
    
    public function getMonoProtocols() {
            return $this->database->table('calc_mono_results');
    }
}