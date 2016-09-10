<?php

class ClinicalData{
	private $conn;
    private $lastClinicalData;

	function __construct($con){
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/Warnings.php';
        require_once dirname(__FILE__) . '/Meds.php';
        $this->conn = $con;
	}

/////////////////////////////////
///// DELETE CLINICAL DATA
/////////////////////////////////

    public function deleteClinicalData($clinicalData_id, $hospital_id) {
        $util = new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $response =  null;
        $stmt = $this->conn->prepare("DELETE dc FROM DatosClinicos dc, Paciente p WHERE dc.idDatosClinicos = ?  AND p.idPaciente = dc.idPaciente AND p.idHospital = ?");
        $stmt->bind_param("ii", $clinicalData_id, $hospital_id);
        if($stmt->execute()){
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();    
            if($num_affected_rows > 0){
                return $util->armStandardResponse(CD_DELETE_OK, $response);    
            }else{
                return $util->armStandardResponse(CD_NOT_FOUND, $response);  
            }            
        }else{
            return $util->armStandardResponse(CD_DELETE_FAILED, $response);  
        } 
    }

/////////////////////////////////
///// GET PATIENTS CLINICAL DATA BY ENTER DATE
/////////////////////////////////

    public function getAllClinicalDataGropByEnterDate($patient_id, $hospital_id){        
        $row = mysqli_query($this->conn, "SELECT * FROM Servicio s, DatosClinicos cd, Paciente p WHERE cd.idPaciente = $patient_id AND s.idServicio = cd.idServicio AND p.idHospital = $hospital_id AND p.idPaciente = cd.idPaciente AND cd.ingresoDatosClinicos = cd.ingresoDatosClinicos ORDER BY cd.ingresoDatosClinicos");
        $currentDate = date("Y-m-d H:i:s");
        $clinicalDataSet = array();
        $clinicalDataGroup = array();
        
        while($result = mysqli_fetch_assoc($row)){                
          
            $clinicalData = array();                
            $clinicalData = $result;
            $clinicalData["doctorDatosClinicos"] = ucwords(strtolower($clinicalData["doctorDatosClinicos"]));            
            
            if($currentDate != $result["ingresoDatosClinicos"]){
                if(count($clinicalDataGroup) > 0){
                    $clinicalDataSet[] = $clinicalDataGroup;
                }
                $currentDate = $result["ingresoDatosClinicos"];
                $clinicalDataGroup = array();
                $clinicalDataGroup[] = $clinicalData;
            }else{
                $clinicalDataGroup[] = $clinicalData;
            }
        }
        if(count($clinicalDataGroup) > 0){
            $clinicalDataSet[] = $clinicalDataGroup;
        }

        if(count($clinicalDataSet) > 0){
            return $clinicalDataSet;
        }                  
        return NULL;       
    }

/////////////////////////////////////////////
///// /////////////////////////////////////////////
///// GET ALL CLINICAL DATA
/////////////////////////////////////////////
/////////////////////////////////////////////

    public function getALlClinicalData($patient_id, $hospital_id){        
        $output = array();
        $row = mysqli_query($this->conn, "SELECT cd.idDatosClinicos,
                                                 cd.ingresoDatosClinicos, 
                                                 cd.capturaDatosClinicos, 
                                                 cd.pesoDatosClinicos, 
                                                 cd.tallaDatosClinicos, 
                                                 cd.imcDatosClinicos, 
                                                 cd.ascDatosClinicos,  
                                                 cd.identificadorPacienteDatosClinicos, 
                                                 cd.camaDatosClinicos, 
                                                 cd.motivoDatosClinicos, 
                                                 cd.diagnosticoDatosClinicos,
                                                 cd.observacionDatosClinicos, 
                                                 cd.doctorDatosClinicos, 
                                                 cd.idPaciente, 
                                                 cd.idServicio, 
                                                 s.nombreServicio,
                                                 p.idEstatusPaciente
                                                 FROM Servicio s, DatosClinicos cd, Paciente p WHERE cd.idPaciente = $patient_id AND s.idServicio = cd.idServicio AND p.idHospital = $hospital_id AND p.idPaciente = cd.idPaciente ORDER BY cd.idDatosClinicos ASC");
        while($result = mysqli_fetch_assoc($row)){
            if($result != NULL ){
                $clinicalData = array();                
                $clinicalData = $result;
                $clinicalData["doctorDatosClinicos"] = ucwords(strtolower($clinicalData["doctorDatosClinicos"]));
                $output[] = $clinicalData;        
            }
        }
        if(count($output) > 0){
            return $output;
        }                  
        return NULL;       
    }

/////////////////////////////////////////////
///// GET LAST CLINICAL DATA
/////////////////////////////////////////////

    public function getLastClinicalData($patient_id, $hospital_id){
        $stmt = $this->conn->prepare("SELECT cd.idDatosClinicos,
                                             cd.ingresoDatosClinicos, 
                                             cd.capturaDatosClinicos, 
                                             cd.pesoDatosClinicos, 
                                             cd.tallaDatosClinicos, 
                                             cd.imcDatosClinicos, 
                                             cd.ascDatosClinicos,  
                                             cd.identificadorPacienteDatosClinicos, 
                                             cd.camaDatosClinicos, 
                                             cd.motivoDatosClinicos, 
                                             cd.diagnosticoDatosClinicos,
                                             cd.observacionDatosClinicos, 
                                             cd.doctorDatosClinicos, 
                                             cd.idPaciente, 
                                             cd.idServicio, 
                                             s.nombreServicio,
                                             p.idEstatusPaciente
                                             from Servicio s, DatosClinicos cd, Paciente p WHERE cd.idPaciente = ? AND cd.capturaDatosClinicos IN (SELECT max(capturaDatosClinicos) FROM DatosClinicos WHERE idPaciente = ?) AND s.idServicio = cd.idServicio AND p.idPaciente = ? AND p.idHospital = ? ");
        $stmt->bind_param("iiii", $patient_id, $patient_id, $patient_id, $hospital_id);
        if($stmt->execute()){
            $clinicalData = array();                                        
            $stmt->close();
            $result = mysqli_query($this->conn, "SELECT cd.idDatosClinicos,
                                                 cd.ingresoDatosClinicos, 
                                                 cd.capturaDatosClinicos, 
                                                 cd.pesoDatosClinicos, 
                                                 cd.tallaDatosClinicos, 
                                                 cd.imcDatosClinicos, 
                                                 cd.ascDatosClinicos,  
                                                 cd.identificadorPacienteDatosClinicos, 
                                                 cd.camaDatosClinicos, 
                                                 cd.motivoDatosClinicos, 
                                                 cd.diagnosticoDatosClinicos,
                                                 cd.observacionDatosClinicos, 
                                                 cd.doctorDatosClinicos, 
                                                 cd.idPaciente, 
                                                 cd.idServicio, 
                                                 s.nombreServicio,
                                                 p.idEstatusPaciente
                                                 from Servicio s, DatosClinicos cd, Paciente p WHERE cd.idPaciente = $patient_id AND cd.capturaDatosClinicos IN (SELECT max(capturaDatosClinicos) FROM DatosClinicos WHERE idPaciente = $patient_id) AND s.idServicio = cd.idServicio AND p.idPaciente = $patient_id AND p.idHospital = $hospital_id");
			$clinicalData = mysqli_fetch_assoc($result);
            if($clinicalData != NULL ){
                $clinicalData["doctorDatosClinicos"] = ucwords(strtolower($clinicalData["doctorDatosClinicos"]));
            }			
            return $clinicalData;
        }
        return NULL;        
    }

/////////////////////////////////////////////
///// GET PATIENTS SIGN OUT BY CLINICAL DATA
/////////////////////////////////////////////

    public function getPatientSignOutByClinicalData($clinicalData_id){
        $row = mysqli_query($this->conn, "SELECT * FROM Egreso e WHERE e.idDatosClinicos = $clinicalData_id ");
        $myOutput = array();     
        while($result = mysqli_fetch_assoc($row)){                
            $myOutput = $result;
        }

        if(count($myOutput) > 0){
            return $myOutput;
        }                  
        return NULL;  
    }
/////////////////////////////////////////////
///// GET CLINICAL DATA
/////////////////////////////////////////////

	public function getClinicalData($clinicalData_id, $hospital_id) {
        $stmt = $this->conn->prepare("SELECT cd.idDatosClinicos, cd.ingresoDatosClinicos, cd.capturaDatosClinicos, cd.pesoDatosClinicos, cd.tallaDatosClinicos, cd.imcDatosClinicos, cd.ascDatosClinicos,  cd.identificadorPacienteDatosClinicos, cd.camaDatosClinicos, cd.motivoDatosClinicos, cd.diagnosticoDatosClinicos, cd.observacionDatosClinicos, cd.doctorDatosClinicos, cd.idPaciente, cd.idServicio, s.nombreServicio  from Servicio s, DatosClinicos cd, Paciente p WHERE cd.idDatosClinicos = ? AND p.idPaciente = cd.idPaciente AND p.idHospital = ? AND s.idServicio = cd.idServicio");
        $stmt->bind_param("ii", $clinicalData_id, $hospital_id);
        if ($stmt->execute()) {
            $clinicalData = array();          
            $stmt->close();
            $result = mysqli_query($this->conn, "SELECT cd.idDatosClinicos, cd.ingresoDatosClinicos, cd.capturaDatosClinicos, cd.pesoDatosClinicos, cd.tallaDatosClinicos, cd.imcDatosClinicos, cd.ascDatosClinicos,  cd.identificadorPacienteDatosClinicos, cd.camaDatosClinicos, cd.motivoDatosClinicos, cd.diagnosticoDatosClinicos, cd.observacionDatosClinicos, cd.doctorDatosClinicos, cd.idPaciente, cd.idServicio, s.nombreServicio  from Servicio s, DatosClinicos cd, Paciente p WHERE cd.idDatosClinicos = $clinicalData_id AND p.idPaciente = cd.idPaciente AND p.idHospital = $hospital_id AND s.idServicio = cd.idServicio");
            $clinicalData = mysqli_fetch_assoc($result);
            if($clinicalData != null){
                $clinicalData["doctorDatosClinicos"] = ucwords(strtolower($clinicalData["doctorDatosClinicos"]));    
            }            
            return $clinicalData;
        } else {
            return NULL;
        }
    }

/////////////////////////////////////////////
///// VALIDATE CLINICAL DATA
/////////////////////////////////////////////

    private function validateClinicalData($fieldsSet, $lastEnterDateTime){

        $response = array();
        $validator = new Validations();
        $util = new Utilities($this->conn);
        $clinicalData = array();
        $alert = FALSE;
        $validObj = TRUE;
        foreach($fieldsSet as $field => $value){                
            switch ($field) {
                case 'ingresoDatosClinicos':
                    if(($value != NULL) && (!empty($value))){
                        if($validator->validateDateTime($value)){
                            $clinicalData['ingresoDatosClinicos'] = $value;
                            break;        
                        }else{
                            $validObj = FALSE;
                            $clinicalData['ingresoDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;
                        }                                
                    }                                                                                      
                    $clinicalData['ingresoDatosClinicos'] = $lastEnterDateTime;
                    break;
                case 'pesoDatosClinicos':                                            
                    if(($value != NULL) && (!empty($value))){
                        if((is_numeric($value) && ($value >= 0))){                             
                            $clinicalData["pesoDatosClinicos"] = $value;                            
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['pesoDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $clinicalData['pesoDatosClinicos'] = NULL;                            
                    break;
                case 'tallaDatosClinicos':
                    if(($value != NULL) && (!empty($value))){
                        if((is_numeric($value) && ($value >= 0))){                                                            
                            $clinicalData['tallaDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['tallaDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $clinicalData['tallaDatosClinicos'] = NULL;                            
                    break;    
                case 'identificadorPacienteDatosClinicos':
                    if(($value != NULL) && (!empty($value))){
                        if((is_string($value) && (strlen($value) >= 0) && (strlen($value) < 32))){                                                            
                            $clinicalData['identificadorPacienteDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert                            
                            $validObj = FALSE;  
                            $clinicalData['identificadorPacienteDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $clinicalData['identificadorPacienteDatosClinicos'] = NULL;                            
                    break;                            
                case "camaDatosClinicos":
                    if(($value != NULL) && (!empty($value))){
                        if((is_string($value) && (strlen($value) >= 0) && (strlen($value) < 32))){                                                            
                            $clinicalData['camaDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['camaDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $clinicalData['camaDatosClinicos'] = NULL;                            
                    break;                            
                case "motivoDatosClinicos":
                    if(($value != NULL) && (!empty($value))){
                        if((is_string($value) && (strlen($value) >= 0) && (strlen($value) < 256))){                                                            
                            $clinicalData['motivoDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['motivoDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $clinicalData['motivoDatosClinicos'] = NULL;                            
                    break;             
                case "diagnosticoDatosClinicos":
                    if(($value != NULL) && (!empty($value))){
                        if((is_string($value) && (strlen($value) >= 0) && (strlen($value) < 256))){                                                            
                            $clinicalData['diagnosticoDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['diagnosticoDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $clinicalData['diagnosticoDatosClinicos'] = NULL;                            
                    break;
                case "observacionDatosClinicos":
                    if(($value != NULL) && (!empty($value))){
                        if((is_string($value) && (strlen($value) >= 0) && (strlen($value) < 256))){                                                            
                            $clinicalData['observacionDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['observacionDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $clinicalData['observacionDatosClinicos'] = NULL;                            
                    break;
                case "doctorDatosClinicos":                    
                    if(($value != NULL) && (!empty($value))){
                        if((is_string($value) && (strlen($value) >= 0) && (strlen($value) < 140))){                                                            
                            $clinicalData['doctorDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['doctorDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $clinicalData['doctorDatosClinicos'] = NULL;                            
                    break;
                case "idServicio":
                //consider check if the service exists
                    if(($value != NULL) && (!empty($value))){
                        if((is_numeric($value) && ($value >= 0) && ($util->isValueExistsInt('Servicio', 'idServicio', $value)))){                                                            
                            $clinicalData['idServicio'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['idServicio'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $validObj = FALSE;
                    $clinicalData['idServicio'] = NOT_VALID_FIELD_LBL;                            
                    break; 
                /*case 'tipoDatosClinicos':
                    if(($value != NULL) && (!empty($value))){
                        if((is_numeric($value)) && ($value >= 0)){                                                            
                            $clinicalData['tipoDatosClinicos'] = $value;
                            break;
                        }else{
                            //launch alert
                            $validObj = FALSE;                                    
                            $clinicalData['tipoDatosClinicos'] = NOT_VALID_FIELD_LBL;
                            break;    
                        }                                
                    }
                    $alert = TRUE;
                    $validObj = FALSE;
                    $clinicalData['tipoDatosClinicos'] = NOT_VALID_FIELD_LBL;                            
                    break;*/ 
                default:
                    # code...
                    break;
            }
        }
        $response["alert"] = $alert;
        $response["validObj"] = $validObj;
        $response["clinicalData"] = $clinicalData;
        return $response;
    }

/////////////////////////////////////////////
///// POST API CREATE CLINICAL DATA
/////////////////////////////////////////////

	public function createClinicalData($patient_id, $hospital_id, $fieldsSet){
        $util = new Utilities($this->conn);
        $clinicalData = array();
        $validObj = TRUE;                 
        $alert = FALSE;   
        $now = date('Y-m-d H:i:s');                  
        $imcClinicalData = null;
        $ascClinicalData = null;
        $enterDateSetByUsr = FALSE;
        $clinicalDataSet = array();
        $lastEnterDateTime = NULL;
        global $lastClinicalData;
        //check if the patient exists
        if($util->getPatient($patient_id, $hospital_id) != NULL){
            
            //validate the data each by each
            $clinicalDataSet = $this->getAllClinicalDataGropByEnterDate($patient_id, $hospital_id);
            if($clinicalDataSet == NULL){
                $lastEnterDateTime = date("Y-m-d H:i:s");
            }else{
                $lastEnterDateTime = $clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"];
            }
            $validationResult = $this->validateClinicalData($fieldsSet, $lastEnterDateTime);
            
            $alert = $validationResult["alert"];
            $validObj = $validationResult["validObj"];
            $clinicalData = $validationResult["clinicalData"];
            $clinicalData["idPaciente"] = $patient_id;
            $clinicalData["error"] =  !$validObj;
            $clinicalData["alert"] = $alert;

            if($validObj){  
                $clinicalData["capturaDatosClinicos"] = $now;     

                if(($clinicalData["pesoDatosClinicos"] != NULL) && ($clinicalData["tallaDatosClinicos"] != NULL) && ($clinicalData["pesoDatosClinicos"] > 0) && ($clinicalData["tallaDatosClinicos"] > 0)){
                    //Calculate imc and isc
                    $imcClinicalData = $clinicalData["pesoDatosClinicos"]/pow(($clinicalData["tallaDatosClinicos"]/ 100), 2);
                    $ascClinicalData = sqrt(($clinicalData["pesoDatosClinicos"] * $clinicalData["tallaDatosClinicos"])/3600);   
                }         
                $clinicalData['imcDatosClinicos'] = $imcClinicalData;
                $clinicalData['ascDatosClinicos'] = $ascClinicalData; 
                
                
                //PROCESSS TO ENTER THE CORRECT PATIENT ENTER DATE
                $enterDate = new DateTime($clinicalData["ingresoDatosClinicos"]);
                $thisCaptureDate = new DateTime($clinicalData["capturaDatosClinicos"]);
                if($clinicalDataSet != NULL){
                    //AT LEAST ONE CLINICAL DATA SET
                    $lastCDSet = $clinicalDataSet[count($clinicalDataSet) - 1];
                    $lastCD = $clinicalDataSet[count($clinicalDataSet) - 1][count($lastCDSet) - 1];
                                        
                    $lastCDID = $lastCD["idDatosClinicos"];

                    //chech if the las clinical data id exists in the 'egreso' table
                    //so we know if this new clinical data is a re enter or not
                    $signOut = $this->getPatientSignOutByClinicalData($lastCDID);
                    
                    //if null then is not re enter                     
                    if($signOut != NULL){
                        $signOutDate = new DateTime($signOut["capturaEgreso"]);
                        //it is a re-enter                           
                        if(($enterDate <= $thisCaptureDate) && ($enterDate > $signOutDate)){
                            return $this->insertClinicalData($clinicalData, $lastCDID, $alert, $patient_id, $hospital_id);
                        }else{
                            $clinicalData["error"] =  TRUE;
                            $clinicalData["alert"] = TRUE;
                            $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                            $response["clinical data"] = $clinicalData;
                            return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                        }
                    }else{
                        //is not a re-enter
                        //check if exists more than one set of clinicald data
                        if(count($clinicalDataSet) > 1){
                            //there is at least 2 existing set of clinical data
                            //once we validate the correct date proceed to change it to
                            //all the clinical data of the curret clinical data set                            
                            $penultimateCDSet = $clinicalDataSet[count($clinicalDataSet) - 2];
                            $olderCaptureDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 1][0]["capturaDatosClinicos"]);
                            $oldestCaptureDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 2][count($penultimateCDSet) - 1]["capturaDatosClinicos"]);
                            $lastEnterDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"]);
                            if($enterDate == $lastEnterDateClinicalData){
                                return $this->insertClinicalData($clinicalData, $lastCDID, $alert, $patient_id, $hospital_id);
                            }
                            else if(($enterDate <= $olderCaptureDateClinicalData) && ($enterDate > $oldestCaptureDateClinicalData)){
                                //change the date to all the clinical Data of the current set                                
                                $enterDate = $enterDate->format('Y-m-d H:i:s');
                                $util->upDateStringColumnIn("DatosClinicos", "ingresoDatosClinicos", "ingresoDatosClinicos", $clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"], $enterDate);
                                return $this->insertClinicalData($clinicalData, $lastCDID, $alert, $patient_id, $hospital_id);                                
                            }else{
                                $clinicalData["error"] =  TRUE;
                                $clinicalData["alert"] = TRUE;
                                $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                                $response["clinical data"] = $clinicalData;
                                return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                            }
                        }else{
                            //there is only one set of Clinical data so we must assure the supplied enter date 
                            //is the correct one
                            $olderCaptureDateClinicalData = new DateTime($clinicalDataSet[0][0]["capturaDatosClinicos"]);
                            $oldestDate = new DateTime("2014-01-01 00:00:00");
                            $lastEnterDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"]);
                            if($enterDate == $lastEnterDateClinicalData){                                
                                return $this->insertClinicalData($clinicalData, $lastCDID, $alert, $patient_id, $hospital_id);
                            }else if(($enterDate <= $olderCaptureDateClinicalData) && ($enterDate > $oldestDate)){                                
                                $enterDate = $enterDate->format('Y-m-d H:i:s');
                                $util->upDateStringColumnIn("DatosClinicos", "ingresoDatosClinicos", "ingresoDatosClinicos", $clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"], $enterDate);
                                return $this->insertClinicalData($clinicalData, $lastCDID, $alert, $patient_id, $hospital_id);
                            }else{
                                $clinicalData["error"] =  TRUE;
                                $clinicalData["alert"] = TRUE;
                                $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                                $response["clinical data"] = $clinicalData;
                                return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                            }
                        }                        
                    }
                }else{
                    //THIS IS THE FIRST RECORD
                    //check the given date is correct  
                    
                    $oldestDate = new DateTime("2014-01-01 00:00:00");                    
                    if(($enterDate > $oldestDate) && ($enterDate <= $thisCaptureDate)){
                        return $this->insertClinicalData($clinicalData, $clinicalData, $alert, $patient_id, $hospital_id);
                    }else{
                        $clinicalData["error"] =  TRUE;
                        $clinicalData["alert"] = TRUE;
                        $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                        $response["clinical data"] = $clinicalData;
                        return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                    }                    
                }               
            }else{
                $clinicalData["error"] =  !$validObj;
                $clinicalData["alert"] = $alert;
                $response["clinical data"] = $clinicalData;
                return $util->armStandardResponse(CD_INVALID_FIELDS, $response);    
            }
        }else{
            $clinicalData["error"] =  $validObj;
            $clinicalData["alert"] = $alert;
            $response["clinical data"] = $clinicalData;
            return $util->armStandardResponse(CD_PATIENT_NOT_EXISTS, $response);
        }       

        return $util->armStandardResponse(500, NULL);
    }   

/////////////////////////////////////////////
///// INSERT CLINICAL DATA INTO DATABASE 
/////////////////////////////////////////////

    private function insertClinicalData($clinicalData, $lastCDID, $alert, $patient_id, $hospital_id){        
        
        $util = new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $response = array();
        $patient = array();
        $clinicalDataResult = $engine->insertClinicalData($clinicalData);        
        $clinicalData_id = $clinicalDataResult["idDatosClinicos"];
        if($clinicalDataResult != NULL){
            //update Patient Status 
            $patient['idPaciente'] = $clinicalDataResult["idPaciente"];
            $patient['idEstatusPaciente'] = PATIENT_INTERN;
            $patientOBJ = new Patient($this->conn);            
            $fullPatient = $patientOBJ->armPatient($patient);
            $patientOBJ->updatePatient($patient['idPaciente'], $hospital_id, $fullPatient);
            //get NEXT prescription number
            $meds = new Meds($this->conn);
            $nextPrescriptionNumber = $meds->getNextPrescriptionNumber($patient_id, $hospital_id);
            if($nextPrescriptionNumber <= 0){
                $nextPrescriptionNumber = 1;
            }            
            //look for prior alert and deactivate it
            $warnings = new Warnings($this->conn);
            //$warnings->updateWarningStatusByTypeAndCDId($lastCDID, ALERT_ID_CD_INCOMPLETE, ALERT_ALLOW); 
            //update Previous CONCILIATION AND INCOMPLETE FARAMA DATA alerts status to accept
            $warnings->updateConciliationAlertActionValue($lastCDID, $nextPrescriptionNumber - 1, ALERT_ALLOW);
            //update Previous SUITABILITY alerts status to accept
            $warnings->updateSuitabilityAlertActionValue($lastCDID, $nextPrescriptionNumber - 1, ALERT_ALLOW); 
            //update Previous DC INCOMPLETE alerts status to "accept"
            //$warnings->updateDCCompletitionAlertActionValue($lastCDID, $nextPrescriptionNumber - 1, ALERT_ALLOW); 
            $warnings->updateDCCompletitionAlertByAlertType($lastCDID, ALERT_ALLOW);
            if($alert){//create alert inside database                                                
                $clinicalDataResult["error"] = FALSE;
                $clinicalDataResult["alert"] = $alert;
                $response["clinical data"] = $clinicalDataResult;                
                //generate Alert
                $engine = new Motor($this->conn);
                $engine->alertaDatos_Pendientes($clinicalData_id, $nextPrescriptionNumber, ALERT_TYPE_CD_INCOMPLETE);
                //return result
                return $util->armStandardResponse(CD_CREATE_OK_W_ALERTS, $response);    
            }
            $clinicalData["error"] =  FALSE;
            $clinicalData["alert"] = $alert;
            $response["clinical data"] = $clinicalDataResult;
            return $util->armStandardResponse(CD_CREATED_SUCCESSFULLY, $response);
        } else {
            // Failed to create user
            $clinicalData["error"] =  TRUE;
            $clinicalData["alert"] = $alert;
            $response["clinical data"] = $clinicalData;
            return $util->armStandardResponse(CD_CREATE_FAILED, $response);
        }
    }

/////////////////////////////////////////////
///// UPDATE CLINICAL DATA TO DATA BASE
/////////////////////////////////////////////

    private function updateThisClinicalData($clinicalData_id, $clinicalData, $alert){
        //review posible validation of data        
        $response = array();    
        $util = new Utilities($this->conn);
        $response["clinical data"] = $clinicalData;
        $stmt = $this->conn->prepare("UPDATE DatosClinicos CD set 
                                                        CD.ingresoDatosClinicos = ?,
                                                        CD.capturaDatosClinicos = ?,
                                                        CD.pesoDatosClinicos = ?, 
                                                        CD.tallaDatosClinicos = ?, 
                                                        CD.imcDatosClinicos = ?,
                                                        CD.ascDatosClinicos = ?,
                                                        CD.identificadorPacienteDatosClinicos = ?, 
                                                        CD.camaDatosClinicos = ?, 
                                                        CD.motivoDatosClinicos = ?, 
                                                        CD.diagnosticoDatosClinicos = ?,
                                                        CD.observacionDatosClinicos = ?,
                                                        CD.doctorDatosClinicos = ?,
                                                        CD.idServicio = ?
                                                        WHERE CD.idDatosClinicos = ? ");        
        
        
        $stmt->bind_param("ssiiddssssssii", 
                                        $clinicalData["ingresoDatosClinicos"],
                                        $clinicalData["capturaDatosClinicos"],
                                        $clinicalData["pesoDatosClinicos"],
                                        $clinicalData["tallaDatosClinicos"],
                                        $clinicalData["imcDatosClinicos"],
                                        $clinicalData["ascDatosClinicos"],
                                        $clinicalData["identificadorPacienteDatosClinicos"],
                                        $clinicalData["camaDatosClinicos"],
                                        $clinicalData["motivoDatosClinicos"],
                                        $clinicalData["diagnosticoDatosClinicos"],
                                        $clinicalData["observacionDatosClinicos"],
                                        $clinicalData["doctorDatosClinicos"],
                                        $clinicalData["idServicio"],
                                        $clinicalData_id);

        
        if($stmt->execute()){
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();      
            $response["clinical data"] = $clinicalData;      
            //get LAST prescription number by clinical data                        
            $meds = new Meds($this->conn);                        
            $farmaData = $meds->getLastDatosFarmaByClinicalData($clinicalData_id);
            if($farmaData == NULL || $farmaData < 1){
                $prescriptionNumber = 1;
            }else{
                $prescriptionNumber = $farmaData[0]["numeroRecetaDatosFarma"];
            }
            //look for prior alert and deactivate it
            $warnings = new Warnings($this->conn);
            //update Previous DC INCOMPLETE alerts status to "accept"
            //$warnings->updateDCCompletitionAlertActionValue($clinicalData_id, $prescriptionNumber, ALERT_ALLOW); 
            $warnings->updateDCCompletitionAlertByAlertType($clinicalData_id, ALERT_ALLOW);
            //if there exist an alert then generate such alert
            if($alert){  
                //generate Alert
                $engine = new Motor($this->conn);
                $engine->alertaDatos_Pendientes($clinicalData_id, $prescriptionNumber, 1);
                //return result
                return $util->armStandardResponse(CD_UPDATE_OK_W_ALERTS, $response);
            }           
            return $util->armStandardResponse(CD_UPDATE_OK, $response);    
        }else{                   
            return $util->armStandardResponse(CD_UPDATE_FAILED, $response);
        }
    }
 
/////////////////////////////////////////////
///// PUT API UPDATE CLINICAL DATA 
/////////////////////////////////////////////

    public function updateClinicalData($clinicalData_id, $hospital_id, $user_id, $params){                        
        $util = new Utilities($this->conn);
        $response = array();        
        $alert = FALSE;
        $validObj = TRUE;
        $lastEnterDateTime = NULL;
        $clinicalData = array();
        $clinicalDataSet = array();
        $clinicalData = $this->getClinicalData($clinicalData_id, $hospital_id);
                
        if($clinicalData != NULL){              
            $patient_id = $clinicalData["idPaciente"];
            $clinicalDataSet = $this->getAllClinicalDataGropByEnterDate($patient_id, $hospital_id);
            if($clinicalDataSet == NULL){
                $lastEnterDateTime = date("Y-m-d H:i:s");
            }else{
                $lastEnterDateTime = $clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"];
            }
            $tempClinicalData = array();
            $tempClinicalData = array_merge($clinicalData, $params);
            
            $validationResult = $this->validateClinicalData($tempClinicalData, $lastEnterDateTime);            
            //validate input Field    
            $alert = $validationResult["alert"];
            $validObj = $validationResult["validObj"];                        
            $clinicalData["error"] =  !$validObj;
            $clinicalData["alert"] = $alert;
            if($validObj){
                $clinicalData = array_merge($clinicalData, $validationResult["clinicalData"]);
                if(($clinicalData["pesoDatosClinicos"] != NULL) && ($clinicalData["tallaDatosClinicos"] != NULL) && ($clinicalData["pesoDatosClinicos"] > 0) && ($clinicalData["tallaDatosClinicos"] > 0)){
                    //Calculate imc and isc
                    $clinicalData['imcDatosClinicos'] = $clinicalData["pesoDatosClinicos"]/pow(($clinicalData["tallaDatosClinicos"]/ 100), 2);
                    $clinicalData['ascDatosClinicos'] = sqrt(($clinicalData["pesoDatosClinicos"] * $clinicalData["tallaDatosClinicos"])/3600);   
                }         
                //PROCESSS TO ENTER THE CORRECT PATIENT ENTER DATE
                $enterDate = new DateTime($clinicalData["ingresoDatosClinicos"]);
                $thisCaptureDate = new DateTime($clinicalData["capturaDatosClinicos"]);
                if($clinicalDataSet != NULL){
                    //AT LEAST ONE CLINICAL DATA SET
                    $lastCD = $clinicalDataSet[count($clinicalDataSet) - 1][0];
                    $lastCDID = $lastCD["idDatosClinicos"];
                    //chech if the las clinical data id exists in the 'egreso' table
                    //so we know if this new clinical data is a re enter or not
                    $signOut = $this->getPatientSignOutByClinicalData($lastCDID);
                                        
                    //if null then is not re enter                    
                    if($signOut != NULL){
                        //it is a re-enter                        
                        if(($enterDate <= $thisCaptureDate)){
                            return $this->updateThisClinicalData($clinicalData_id, $clinicalData, $alert);
                        }else{
                            $clinicalData["error"] =  TRUE;
                            $clinicalData["alert"] = TRUE;
                            $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                            $response["clinical data"] = $clinicalData;
                            return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                        }
                    }else{
                        //is not a re-enter
                        //check if exists more than one set of clinicald data
                        if(count($clinicalDataSet) > 1){
                            //there is at least 2 existing set of clinical data
                            //once we validate the correct date proceed to change it to
                            //all the clinical data of the curret clinical data set  
                            $penultimateCDSet = $clinicalDataSet[count($clinicalDataSet) - 2];
                            $olderCaptureDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 1][0]["capturaDatosClinicos"]);
                            $oldestCaptureDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 2][count($penultimateCDSet) - 1]["capturaDatosClinicos"]);
                            $lastEnterDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"]);
                            if($enterDate == $lastEnterDateClinicalData){
                                return $this->updateThisClinicalData($clinicalData_id, $clinicalData, $alert);
                            }
                            else if(($enterDate <= $olderCaptureDateClinicalData) && ($enterDate > $oldestCaptureDateClinicalData)){
                                //change the date to all the clinical Data of the current set                                
                                $enterDate = $enterDate->format('Y-m-d H:i:s');
                                $util->upDateStringColumnIn("DatosClinicos", "ingresoDatosClinicos", "ingresoDatosClinicos", $clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"], $enterDate);
                                return $this->updateThisClinicalData($clinicalData_id, $clinicalData, $alert);
                            }else{
                                $clinicalData["error"] =  TRUE;
                                $clinicalData["alert"] = TRUE;
                                $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                                $response["clinical data"] = $clinicalData;
                                return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                            }
                        }else{
                            //there is only one set of Clinical data so we must assure the supplied enter date 
                            //is the correct one
                            $olderCaptureDateClinicalData = new DateTime($clinicalDataSet[0][0]["capturaDatosClinicos"]);
                            $oldestDate = new DateTime("2014-01-01 00:00:00");
                            $lastEnterDateClinicalData = new DateTime($clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"]);
                            if($enterDate == $lastEnterDateClinicalData){                                
                                return $this->updateThisClinicalData($clinicalData_id, $clinicalData, $alert);
                            }else if(($enterDate <= $olderCaptureDateClinicalData) && ($enterDate > $oldestDate)){                                
                                $enterDate = $enterDate->format('Y-m-d H:i:s');
                                $util->upDateStringColumnIn("DatosClinicos", "ingresoDatosClinicos", "ingresoDatosClinicos", $clinicalDataSet[count($clinicalDataSet) - 1][0]["ingresoDatosClinicos"], $enterDate);
                                return $this->updateThisClinicalData($clinicalData_id, $clinicalData, $alert);
                            }else{
                                $clinicalData["error"] =  TRUE;
                                $clinicalData["alert"] = TRUE;
                                $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                                $response["clinical data"] = $clinicalData;
                                return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                            }
                        }                        
                    }
                }else{
                    //THIS IS THE FIRST RECORD
                    //check the given date is correct  
                    
                    $oldestDate = new DateTime("2014-01-01 00:00:00");                    
                    if(($enterDate > $oldestDate) && ($enterDate <= $thisCaptureDate)){
                        return $this->updateThisClinicalData($clinicalData_id, $clinicalData, $alert);
                    }else{
                        $clinicalData["error"] =  TRUE;
                        $clinicalData["alert"] = TRUE;
                        $clinicalData["ingresoDatosClinicos"] = NOT_VALID_FIELD_LBL;
                        $response["clinical data"] = $clinicalData;
                        return $util->armStandardResponse(CD_INVALID_FIELDS, $response);
                    }                    
                }
            }else{
                $clinicalData["error"] =  !$validObj;
                $clinicalData["alert"] = $alert;
                $response["clinical data"] = $clinicalData;
                return $util->armStandardResponse(CD_INVALID_FIELDS, $response);    
            }
        }else{
            $response["clinical data"] = array();
            $response["clinical data"]["error"] = TRUE;
            $response["clinical data"]["alert"] = TRUE;  
            return $util->armStandardResponse(CD_UPDATE_ID_NOT_FOUND, $response);                            
        }        
        
    }

}
?>