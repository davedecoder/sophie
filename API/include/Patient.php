<?php
class Patient{

	private $conn;
    public $validFields = array('nombrePaciente',
                                       'apellidoPaternoPaciente',
                                       'apellidoMaternoPaciente', 
                                       'nacimientoPaciente', 
                                       'sexoPaciente',
                                       'idPaciente',
                                       'idEstatusVisita',
                                       'idEstatusPaciente');

	function __construct($con){		
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        $this->conn = $con;
	}


    public function validatePatient($inputPatient){
        $patient = array();
        $validator = new Validations();
        $validObj = TRUE;
        $regexp = "/^[a-zA-Z_\s áéíóúüñÁÉÍÓÚÜÑ]{1,}$/iu"; 
        $patient = array();
        if(count($inputPatient) > MAX_ITERATIONS){
            $response["valid"] = FALSE;
            $response["patient"] = $patient;
            return $response;
        }
        foreach($inputPatient as $field => $value){
            switch ($field) {
                case 'nombrePaciente':                                              
                    if(is_string($value) && preg_match($regexp, $value)){                                                                                        
                        $patient['nombrePaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['nombrePaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "apellidoPaternoPaciente":
                    if(is_string($value) && preg_match($regexp, $value)){                                                                                        
                        $patient['apellidoPaternoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['apellidoPaternoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "apellidoMaternoPaciente":
                    if(empty($value)){
                        $patient['apellidoMaternoPaciente'] = $value;
                        break;
                    }
                    if(is_string($value) && preg_match($regexp, $value)){                                                                                        
                        $patient['apellidoMaternoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['apellidoMaternoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "nacimientoPaciente":
                    if($validator->validateDate($value)){                                                                                        
                        $patient['nacimientoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['nacimientoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "sexoPaciente":
                    if(is_string($value) && strlen($value) < 6 && preg_match($regexp, $value)){                                                                                        
                        $patient['sexoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['sexoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "idEstatusPaciente":
                    if(is_numeric($value) && ($value < 5) &&($value >= 0) ){                                                                                        
                        $patient['idEstatusPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['idEstatusPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "idEstatusVisita":
                    if(is_numeric($value) && ($value < 5) &&($value >= 0) ){                                                                                        
                        $patient['idEstatusVisita'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['idEstatusVisita'] = NOT_VALID_FIELD_LBL;
                    break;
                default:
                    break;
            }
        }
        $response = array();
        if($validObj){
            $response["valid"] = TRUE;
            $response["patient"] = $patient;
            return $response;
        }else{
            $response["valid"] = FALSE;
            $response["patient"] = $patient;
            return $response;
        }

    }

    public function signOutPatient($patient_id, $hospital_id, $user_id){

        $util =  new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $patientObj = new Patient($this->conn);
        $result = NULL;
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        if($patient != null){
            //check if patient is signed out
            if($patient["idEstatusPaciente"] == 2){
                return $util->armStandardResponse(PATIENT_ALREADY_SIGNED_OUT, $result);
            }else{
                $result = $engine->egresarPaciente($patient_id);
                if(($result != NULL) && (isset($result['error']))){ 
                    if($result['error'] == 100){
                        return $util->armStandardResponse(PATIENT_SUCCESSFUL_SIGNED_OUT, $result);        
                    }else{
                        return $util->armStandardResponse(CONTROL_FAILURE, $result);    
                    }           
                }else{
                    return $util->armStandardResponse(CONTROL_FAILURE, $result);
                }
            }
        }else{
            return $util->armStandardResponse(USR_PATIENT_NOT_FOUND_OK, $result);
        }
                
    }

    public function getPatientActualInfo($patient_id, $hospital_id) {

        $engine = new Motor($this->conn);
        return $engine->patientInternById($patient_id, $hospital_id);
    }

    public function getPatient($patient_id, $hospital_id) {
        $stmt = $this->conn->prepare("SELECT p.idPaciente, p.nombrePaciente, p.apellidoPaternoPaciente, p.apellidoMaternoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS edad, p.nacimientoPaciente, p.sexoPaciente,  p.idHospital, p.idEstatusPaciente, p.idEstatusVisita from Paciente p WHERE p.idPaciente = ? AND p.idHospital = ?");
        $stmt->bind_param("ii", $patient_id, $hospital_id);
        if ($stmt->execute()) {
            $patient = array();
            $stmt->bind_result( $idPaciente,
                                $nombrePaciente,
                                $apellidoPaternoPaciente,
                                $apellidoMaternoPaciente,
                                $edad,
                                $nacimientoPaciente,
                                $sexoPaciente,
                                $idHospital,
                                $idEstatusPaciente,
                                $idEstatusVisita
                                );
            while($stmt->fetch()){
                                $patient['idPaciente'] = $idPaciente;
                                $patient['nombrePaciente'] = ucwords(strtolower($nombrePaciente));
                                $patient['apellidoPaternoPaciente'] = ucwords(strtolower($apellidoPaternoPaciente));
                                $patient['apellidoMaternoPaciente'] = ucwords(strtolower($apellidoMaternoPaciente));
                                $patient['nacimientoPaciente'] = $nacimientoPaciente;
                                $patient['sexoPaciente'] = $sexoPaciente;
                                $patient['idHospital'] = $idHospital;
                                $patient['idEstatusPaciente'] = $idEstatusPaciente;
                                $patient['idEstatusVisita'] = $idEstatusVisita;
                                $patient['edad'] = $edad;
                            }

            $stmt->close();
            return $patient;
        } else {
            return NULL;
        }
    }


	public function createPatient($idHospital, $fieldsSet){
        $response = array();
        $util =  new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $response["patient"] =  "";
        $validator = new Validations();
        $patient = NULL;
        $validObj = TRUE;
        $alert = FALSE;      
        $regexp = "/^[a-zA-Z_\s áéíóúüñÁÉÍÓÚÜÑ]{1,}$/iu"; 
        //validate the inputs format
        foreach($fieldsSet as $field => $value){
            switch ($field) {
                case 'nombrePaciente':
                    //echo 'nombrePaciente' . $value;
                    if(is_string($value) && preg_match($regexp, $value)){                                                                                        
                        $patient['nombrePaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['nombrePaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "apellidoPaternoPaciente":
                    //temporary
                    if(empty($value)){
                        $patient['apellidoMaternoPaciente'] = "";
                        break;
                    }
                    //temporary
                    if(is_string($value) && preg_match($regexp, $value)){
                        $patient['apellidoPaternoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['apellidoPaternoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "apellidoMaternoPaciente":
                    if(empty($value)){
                        $patient['apellidoMaternoPaciente'] = "";
                        break;
                    }
                    if(is_string($value) && preg_match($regexp, $value)){                                                                                        
                        $patient['apellidoMaternoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['apellidoMaternoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "nacimientoPaciente":
                    if($validator->validateDate($value)){                                                                                        
                        $patient['nacimientoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['nacimientoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                case "sexoPaciente":
                    if(is_string($value) && strlen($value) < 5 && preg_match($regexp, $value)){                                                                                        
                        $patient['sexoPaciente'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $patient['sexoPaciente'] = NOT_VALID_FIELD_LBL;
                    break;
                default:
                    break;
            }
        }
        if($validObj){
            $existingPatient = $util->isPatientExists($patient["nombrePaciente"], $patient["apellidoPaternoPaciente"], $patient["apellidoMaternoPaciente"], $patient["nacimientoPaciente"]);
            if ($existingPatient == NULL){
                // insert query                    
                
                $patient['idEstatusPaciente'] = PATIENT_STATUS;
                $patient['idEstatusVisita'] = VISIT_STATUS;
                $patient['idHospital'] = $idHospital;
                //prepare query
                $stmt = $this->conn->prepare("INSERT INTO Paciente(nombrePaciente, 
                                                                    apellidoPaternoPaciente, 
                                                                    apellidoMaternoPaciente, 
                                                                    nacimientoPaciente, 
                                                                    sexoPaciente, 
                                                                    idHospital, 
                                                                    idEstatusPaciente, 
                                                                    idEstatusVisita) values(?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiii", $patient["nombrePaciente"],
                                              $patient["apellidoPaternoPaciente"], 
                                              $patient["apellidoMaternoPaciente"], 
                                              $patient["nacimientoPaciente"], 
                                              $patient["sexoPaciente"], 
                                              $patient['idHospital'], 
                                              $patient['idEstatusPaciente'], 
                                              $patient['idEstatusVisita']);                                            
                $result = $stmt->execute();  
                
                // Check for successful insertion
                if ($result) {
                    $patient_id = $stmt->insert_id;
                    $patient["idPaciente"] = $patient_id;
                    $stmt->close();
                    if($alert){                        
                        $response["patient"] = $patient;
                        return $util->armStandardResponse(USR_CREATE_OK_W_ALERTS, $response);    
                    }                 
                    // User successfully inserted
                    $response["patient"] = $patient;
                    return $util->armStandardResponse(USR_CREATE_OK, $response);
                } 
                else {
                    // Failed to create user
                    $stmt->close();            
                    $response["patient"] = $patient;
                    return $util->armStandardResponse(USR_CREATE_FAILED, $response);
                }
            }
            else{
                //reactivate Patient
                $params['idEstatusPaciente'] = 1;
                $this->updatePatient($existingPatient['idPaciente'] ,$idHospital, $params);
                $response["patient"] = $existingPatient;
                return $util->armStandardResponse(USR_UPDATE_OK, $response);
            }
        }else{
            $response["patient"] = $patient;
            return $util->armStandardResponse(USR_CREATE_FAILED_REVIEW_INVALID_FIELDS, $response);
        }   
        
        return $util->armStandardResponse(500, NULL);

    }

    public function armPatient($patient){
        
        $fieldsSet = array();
        
        foreach ($this->validFields as $key => $fieldValue) {
            if(isset($patient[$fieldValue])){
                $fieldsSet[$fieldValue] = $patient[$fieldValue];
            }
        }
        if(count($fieldsSet) > 0){
            return $fieldsSet;
        }
        return NULL;
    }


    public function updatePatient($patient_id, $hospital_id, $params){
        $util =  new Utilities($this->conn);
        $patient = $this->getPatient($patient_id, $hospital_id);        
        $validPatient = TRUE;
        $response = array();
        if($patient != NULL){    
            $patientToUpdate = array_merge($patient, $params);
            $validationResult = $this->validatePatient($patientToUpdate);
            $patient = array();
            $validPatient = $validationResult["valid"];
            $patient = $validationResult["patient"];
            $patient["idPaciente"] = $patient_id;
        }else{
            return $util->armStandardResponse(USR_UPDATE_ID_NOT_FOUND, NULL);                
        }
        if($validPatient){
            $stmt = $this->conn->prepare("UPDATE Paciente P set 
                                                        P.nombrePaciente = ?, 
                                                        P.apellidoPaternoPaciente = ?, 
                                                        P.apellidoMaternoPaciente = ?, 
                                                        P.nacimientoPaciente = ?, 
                                                        P.sexoPaciente = ?, 
                                                        P.idEstatusPaciente = ?,
                                                        P.idEstatusVisita = ?
                                                        WHERE P.idPaciente = ? AND P.idHospital = ?");
            $stmt->bind_param("sssssiiii", $patient["nombrePaciente"],
                                            $patient["apellidoPaternoPaciente"],
                                            $patient["apellidoMaternoPaciente"],
                                            $patient["nacimientoPaciente"],
                                            $patient["sexoPaciente"],
                                            $patient["idEstatusPaciente"],
                                            $patient["idEstatusVisita"], $patient_id, $hospital_id);
            
            if($stmt->execute()){
                
                $num_affected_rows = $stmt->affected_rows;

                $stmt->close();
                $response = $patient;            
                return $util->armStandardResponse(USR_UPDATE_OK, $response);                                     
            }else{  
                $response["patient"] = array();        
                return $util->armStandardResponse(USR_UPDATE_FAILED, $response);
            }
        }else{
            
            $response["patient"] = $patient;
            $response["patient"]["error"] = TRUE;
            $response["patient"]["alert"] = TRUE;            
            return $util->armStandardResponse(USR_UPDATE_FAILED_REVIEW_INVALID_FIELDS, $response);                   
        }
        
    }

    public function deletePatient($patient_id, $hospital_id) {
        $util = new Utilities($this->conn);
        $response =  null;
        $stmt = $this->conn->prepare("DELETE p FROM Paciente p WHERE p.idPaciente = ? AND p.idHospital = ?");
        $stmt->bind_param("ii", $patient_id, $hospital_id);
        if($stmt->execute()){
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();    
            if($num_affected_rows > 0){
                return $util->armStandardResponse(USR_DELETE_OK, $response);    
            }else{
                return $util->armStandardResponse(USR_PATIENT_NOT_FOUND_OK, $response);  
            }            
        }else{
            return $util->armStandardResponse(USR_DELETE_FAILED, $response);  
        }        
    }

    public function getAllHospitalizedPatients($hospital_id){

        $util =  new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $allHospitalizedPatients = $engine->pacientesInternos($hospital_id);
        if(count($allHospitalizedPatients) >= 0){                
            return $util->armStandardResponse(PAH_SUCCESSFUL_ANSWER, $allHospitalizedPatients);    
        }else{
            return $util->armStandardResponse(CONTROL_FAILURE, $allHospitalizedPatients);
        }
    }

}
?>