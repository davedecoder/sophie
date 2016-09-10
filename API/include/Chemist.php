<?php
class Chemist{

	private $conn;

	function __construct($con){		
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/PassHash.php';
        $this->conn = $con;
	}

    public function getChemist($chemist_id, $hospital_id){
        //  THIS METHOD DOES NOT RETURN PASSWORD
        $stmt = $this->conn->prepare("SELECT idQuimico, nombreQuimico, apellidoPaternoQuimico, apellidoMaternoQuimico, loginQuimico, emailQuimico, telefonoQuimico, extensionQuimico, idHospital, idTurno FROM Quimico WHERE idHospital = ? and idQuimico = ?");
        $stmt->bind_param("ii", $hospital_id, $chemist_id);
        if ($stmt->execute()) {
            $chemist = array();
            $stmt->bind_result( $idQuimico,
                                $nombreQuimico,
                                $apellidoPaternoQuimico,
                                $apellidoMaternoQuimico,
                                $loginQuimico,
                                $emailQuimico,
                                $telefonoQuimico,
                                $extensionQuimico,
                                $idHospital,    
                                $idTurno
                                );
            while($stmt->fetch()){
                                $chemist['idQuimico'] = $idQuimico;
                                $chemist['nombreQuimico'] = ucwords(strtolower($nombreQuimico));
                                $chemist['apellidoPaternoQuimico'] = ucwords(strtolower($apellidoPaternoQuimico));
                                $chemist['apellidoMaternoQuimico'] = ucwords(strtolower($apellidoMaternoQuimico));
                                $chemist['loginQuimico'] = $loginQuimico;
                                $chemist['emailQuimico'] = $emailQuimico;
                                $chemist['telefonoQuimico'] = $telefonoQuimico;
                                $chemist['extensionQuimico'] = $extensionQuimico;
                                $chemist['idHospital'] = $idHospital;
                                $chemist['idTurno'] = $idTurno;
                            }

            $stmt->close();
            return $chemist;
        } else {
            return NULL;
        }
    }

    public function validateChemist($params){
        $validations = new Validations();
        $response = array();
        $chemist = array();
        $chemistDataValid = TRUE;
        foreach ($params as $param => $value) {  
            // consider validations for each field                    
            switch ($param){
                case "nombreQuimico":
                    if($validations->isValidName($value)){
                        $chemist["nombreQuimico"] = $value;
                        break;
                    }   
                    $chemistDataValid = FALSE;
                    $chemist["nombreQuimico"] = NOT_VALID_FIELD_LBL;
                    break;
                case "apellidoPaternoQuimico":
                    if($validations->isValidName($value)){
                        $chemist["apellidoPaternoQuimico"] = $value;
                        break;
                    }   
                    $chemistDataValid = FALSE;
                    $chemist["apellidoPaternoQuimico"] = NOT_VALID_FIELD_LBL;
                    break;
                case "apellidoMaternoQuimico":
                    if($validations->isValidName($value)){
                        $chemist["apellidoMaternoQuimico"] = $value;
                        break;
                    }   
                    $chemistDataValid = FALSE;
                    $chemist["apellidoMaternoQuimico"] = NOT_VALID_FIELD_LBL;
                    break;
                case "loginQuimico":
                    /*
                    if($validations->validateEmail($value)){
                        $chemist["loginQuimico"] = $value;
                        break;    
                    }else{
                        if($validations->isValidName($value)){
                            $chemist["loginQuimico"] = $value;
                            break;
                        }   
                        $chemistDataValid = FALSE;
                        $chemist["loginQuimico"] = NOT_VALID_FIELD_LBL;
                        break;
                    }
                    */
                    $chemist["loginQuimico"] = $value;
                    break;
                case "emailQuimico":
                    if($validations->validateEmail($value)){
                        $chemist["emailQuimico"] = $value;
                        break;    
                    }else{
                        $chemistDataValid = FALSE;
                        $chemist["emailQuimico"] = NOT_VALID_FIELD_LBL;
                        break;
                    }
                case "telefonoQuimico":   
                    if($validations->validatePhoneNumber($value)){
                        $chemist["telefonoQuimico"] = $value;
                        break;
                    }
                    $chemistDataValid = FALSE;
                    $chemist["telefonoQuimico"] = NOT_VALID_FIELD_LBL;
                    break;
                case "extensionQuimico":
                    /*
                    if($validations->validatePhoneNumber($value)){
                        $chemist["extensionQuimico"] = $value;
                        break;
                    }
                    $chemistDataValid = FALSE;
                    $chemist["extensionQuimico"] = NOT_VALID_FIELD_LBL;
                    break;*/
                    $chemist["extensionQuimico"] = $value;
                    break;
                case "idHospital":
                    if((is_numeric($value)) && ($value > 0)){
                        $chemist["idHospital"] = $value;
                        break;    
                    }
                    $chemistDataValid = FALSE;
                    $chemist["idHospital"] = NOT_VALID_FIELD_LBL;
                    break;
                case "idTurno":
                    if((is_numeric($value)) && ($value >= 0)){
                        $chemist["idTurno"] = $value;
                        break;    
                    }
                    $chemistDataValid = FALSE;
                    $chemist["idTurno"] = NOT_VALID_FIELD_LBL;
                    break;
            }
        }
        $response["chemistDataValid"] = $chemistDataValid;
        $response["chemist"] = $chemist;
        return $response;
    }

    public function updateChemist($chemist_id, $hospital_id, $params){
        //This method does not updates password
        $util =  new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $validations = new Validations();
        $chemist = $this->getChemist($chemist_id, $hospital_id);       
        $chemistDataValid = TRUE;
        if($chemist != NULL){    
            $tmpChemist = array_merge($chemist, $params);            
            $validationResponse = $this->validateChemist($tmpChemist);            
            $chemistDataValid = $validationResponse["chemistDataValid"];
            $chemist = $validationResponse["chemist"];
            $chemist["idQuimico"] = $chemist_id;
            if($chemistDataValid == FALSE){
                $response["quimico"] = $chemist;
                $response["quimico"]["error"] = TRUE;
                $response["quimico"]["alert"] = TRUE;            
                return $util->armStandardResponse(USR_UPDATE_FAILED_REVIEW_INVALID_FIELDS, $response);                    
            }
        }else{
            $response["quimico"] = array();
            $response["quimico"]["error"] = TRUE;
            $response["quimico"]["alert"] = TRUE;            
            return $util->armStandardResponse(USR_UPDATE_ID_NOT_FOUND, $response);                
        }
        //proceed to insert data

        $result = $engine->updateChemist($chemist);
        
        if($result){           
            $response["quimico"] = $chemist;
            $response["quimico"]["error"] = FALSE;
            $response["quimico"]["alert"] = FALSE;            
            return $util->armStandardResponse(USR_UPDATE_OK, $response);                                     
        }else{  
            $response["quimico"] = array();
            $response["quimico"]["error"] = TRUE;
            $response["quimico"]["alert"] = TRUE;            
            return $util->armStandardResponse(USR_UPDATE_FAILED, $response);
        }
    }

    public function getAllChemists($hospital_id){

        $util =  new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $allMyChemists = NULL;
        $allMyChemists = $engine->allChemists($hospital_id);
        if(($allMyChemists != NULL ) && (count($allMyChemists) > 0)){                
            return $util->armStandardResponse(CA_SUCCESSFUL_ANSWER, $allMyChemists);    
        }else if(($allMyChemists != NULL ) && (count($allMyChemists) < 1)){                
            return $util->armStandardResponse(CA_SUCCESSFUL_ANSWER_NO_ACTIVE, $allMyChemists);    
        }else{
            return $util->armStandardResponse(CONTROL_FAILURE, $allMyChemists);
        }
    }

    public function getAactiveChemists($hospital_id){

        $util =  new Utilities($this->conn);
        $engine = new Motor($this->conn);
        $allActiveChemists = NULL;
        $allActiveChemists = $engine->activeChemists($hospital_id);
        if(($allActiveChemists != NULL ) && (count($allActiveChemists) > 0)){                
            return $util->armStandardResponse(CA_SUCCESSFUL_ANSWER, $allActiveChemists);    
        }else if(($allActiveChemists != NULL ) && (count($allActiveChemists) < 1)){                
            return $util->armStandardResponse(CA_SUCCESSFUL_ANSWER_NO_ACTIVE, $allActiveChemists);    
        }else{
            return $util->armStandardResponse(CONTROL_FAILURE, $allActiveChemists);
        }
    }

     private function isUserQuimicoExists($loginQuimico) {
        if($stmt = $this->conn->prepare('SELECT * from Quimico WHERE loginQuimico = ?')){
            $stmt->bind_param("s", $loginQuimico);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            return $num_rows > 0;
        }else{
            echo "COMUNICATION ERROR";        
            exit(400);
        }        
    }

    public function createUserChemist($nombreQuimico, $apellidoPaternoQuimico, $apellidoMaternoQuimico, $loginQuimico, $password, $emailQuimico, $telefonoQuimico, $extensionQuimico, $idHospital, $idTurno){
        require_once 'PassHash.php';
        $response = array();
        $utils = new Utilities($this->conn);
        $validator = new Validations();
        $loginQuimico = strtolower($loginQuimico);
        //first check if the Quimico user already exists in db
        if(!$this->isUserQuimicoExists($loginQuimico)){
            //validatePhone Number
            if($validator->validatePhoneNumber($telefonoQuimico)){
                //validate extension
                if($validator->validatePhoneNumber($extensionQuimico)){
                    //check if Hospital id exists
                    if($utils->isValueExistsString("Hospital", "idHospital", $idHospital) > 0){
                        //check if Shift Exists
                        if($utils->isValueExistsString("Turno", "idTurno", $idTurno) > 0){
                            //Generating password hash
                            $password_hash = PassHash::hash($password);    
                            $stmt = $this->conn->prepare("INSERT INTO Quimico(nombreQuimico,
                                                                              apellidoPaternoQuimico,
                                                                              apellidoMaternoQuimico, 
                                                                              loginQuimico, 
                                                                              contrasenaQuimico,  
                                                                              emailQuimico, 
                                                                              telefonoQuimico,
                                                                              extensionQuimico,
                                                                              idHospital,
                                                                              idTurno) 
                                                                              values(?,?,?,?,?,?,?,?,?,?)");
                            $stmt->bind_param("ssssssssii", $nombreQuimico, $apellidoPaternoQuimico, $apellidoMaternoQuimico, $loginQuimico, $password_hash, $emailQuimico, $telefonoQuimico, $extensionQuimico, $idHospital, $idTurno);
                            $result = $stmt->execute();
                            //check for successful insertion
                            if($result){
                                
                                $quimico_id = $stmt->insert_id;
                                $quimicoObj["idQuimico"] = $quimico_id;
                                $quimicoObj["nombreQuimico"] = $nombreQuimico;
                                $quimicoObj["apellidoPaternoQuimico"] = $apellidoPaternoQuimico;
                                $quimicoObj["apellidoMaternoQuimico"] = $apellidoMaternoQuimico;
                                $quimicoObj["loginQuimico"] = $loginQuimico;
                                $quimicoObj["password_hash"] = $password_hash;
                                $quimicoObj["emailQuimico"] = $emailQuimico;
                                $quimicoObj["telefonoQuimico"] = $telefonoQuimico;
                                $quimicoObj["extensionQuimico"] = $extensionQuimico;
                                $quimicoObj["idHospital"] = $idHospital;
                                $quimicoObj["idTurno"] = $idTurno;

                                $response["quimico"] = $quimicoObj;
                                            

                                $stmt->close();
                                //insert API_KEY
                                //Generating API key
                                $api_key = $utils->generateApiKey();
                                
                                $stmt = $this->conn->prepare("INSERT INTO MyKeys(api_key, idQuimico) 
                                                                              values(?,?)");
                                
                                $stmt->bind_param("si",  $api_key, $quimico_id);
                                $result = $stmt->execute();
                                
                                if($result){

                                    $response["quimico"]["API_KEY"] = $api_key;
                                    $response["quimico"]["error"] = FALSE;
                                    $response["quimico"]["alert"] = FALSE;

                                    $stmt->close();
                                    //user successfully inserted
                                    return $utils->armStandardResponse(USER_CREATED_SUCCESSFULLY, $response);                    
                                }else{
                                    $stmt->close();
                                    // Failed to create user
                                    return USER_CREATE_FAILED;
                                }
                                //user successfully inserted
                                return USER_CREATED_SUCCESSFULLY;
                            }else{
                                // Failed to create user
                                return USER_CREATE_FAILED;
                            }
                        }else{
                            return SHIFT_ID_DOES_NOT_EXIST;
                        }
                    }else{
                        return HOSPITAL_ID_DOES_NOT_EXIST;
                    }
                }else{
                    return USER_EXTENSION_WRONG;
                }
            }else{
                return USER_TELEPHONE_WRONG;
            }            
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
 
        return $response;
    }

}
?>