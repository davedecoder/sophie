<?php
class Hospital{

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

    private function hospitalExists($hospitalObj) {
        $stmt = $this->conn->prepare("SELECT * from Hospital WHERE nombreHospital LIKE ? AND idGrupo = ?");
        $stmt->bind_param("si",$hospitalObj["nombreHospital"], $hospitalObj["idGrupo"]);
        if($stmt->execute()){
            $hospital = array();
            $stmt->bind_result(
                    $idHospital,
                    $nombreHospital,
                    $calleHospital,
                    $numeroExteriorHospital,
                    $numeroInteriorHospital,
                    $coloniaHospital,
                    $telefonoHospital,
                    $urlHospital,
                    $rfcHospital,
                    $logoHospital,
                    $idGrupo
                );
            while($stmt->fetch()){
                $hospital['idHospital'] = $idHospital;
                $hospital['nombreHospital'] = $nombreHospital;
                $hospital['calleHospital'] = $calleHospital;
                $hospital['numeroExteriorHospital'] = $numeroExteriorHospital;
                $hospital['numeroInteriorHospital'] = $numeroInteriorHospital;
                $hospital['coloniaHospital'] = $coloniaHospital;
                $hospital['telefonoHospital'] = $telefonoHospital;
                $hospital['urlHospital'] = $urlHospital;
                $hospital['rfcHospital'] = $rfcHospital;
                $hospital['logoHospital'] = $logoHospital;
                $hospital['idGrupo'] = $idGrupo;
            }
            $stmt->close();
            return $hospital;
        }else{
            return NULL;
        }        
    }

    private function groupExists($groupObj) {
        $stmt = $this->conn->prepare("SELECT * from Grupo WHERE nombreGrupo LIKE ?");
        $stmt->bind_param("s",$groupObj["nombreGrupo"]);
        if($stmt->execute()){
            $group = array();
            $stmt->bind_result(
                    $idGrupo,
                    $nombreGrupo,
                    $urlGrupo
                );
            while($stmt->fetch()){
                $group['idGrupo'] = $idGrupo;
                $group['nombreGrupo'] = $nombreGrupo;
                $group['urlGrupo'] = $urlGrupo;
            }
            $stmt->close();
            return $group;
        }else{
            return NULL;
        }        
    }

    public function createHospital($hospitalObj){        
        $util = new Utilities($this->conn);
        $validator = new Validations();
        $validObj = TRUE;
        $regexp = "/^[a-zA-Z_\s áéíóúüñÁÉÍÓÚÜÑ]{1,}$/iu";         
        $hospital = array();
        //first check if the Quimico user already exists in db
        foreach($hospitalObj as $field => $value){
            //
            switch ($field) {
                case 'nombreHospital':
                    if(is_string($value)){                                                                                        
                        $hospital['nombreHospital'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $hospital["nombreHospital"] = NOT_VALID_FIELD_LBL;
                    break;
                case 'calleHospital':
                    if(is_string($value)){                                                                                        
                        $hospital['calleHospital'] = $value;
                        break;
                    }
                    break;
                case 'numeroExteriorHospital':
                    if(is_int($value)){
                        $hospital["numeroExteriorHospital"] = $value;
                        break;
                    }elseif(is_string($value) && ($value != NULL) && (!empty($value)) && ctype_digit ($value)){
                        $hospital["numeroExteriorHospital"] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $hospital["idGrupo"] = NOT_VALID_FIELD_LBL;
                    break;
                case 'numeroInteriorHospital':
                    if(is_int($value)){
                        $hospital["numeroInteriorHospital"] = $value;
                        break;
                    }elseif(is_string($value) || (empty($value))){
                        $hospital["numeroInteriorHospital"] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $hospital["numeroInteriorHospital"] = NOT_VALID_FIELD_LBL;
                    break;
                case 'coloniaHospital':
                    if(is_string($value)){
                        $hospital["coloniaHospital"] = $value;
                        break;
                    }
                    break;
                case 'telefonoHospital':
                    if($value != NULL && (!empty($value))){
                        if($validator->validatePhoneNumber($value)){
                            $hospital["telefonoHospital"] = $value;
                            break;
                        }
                        $validObj = FALSE;
                        $hospital["telefonoHospital"] = NOT_VALID_FIELD_LBL;
                        break;
                    }
                    break;
                case 'urlHospital':
                    if(is_string($value)){
                        $hospital['urlHospital'] = $value;
                    }
                    break;
                case 'rfcHospital':
                    if(is_string($value)){
                        $hospital['rfcHospital'] = $value;
                    }
                    break;
                case 'idGrupo':
                    //is valid
                    if(is_int($value)){
                        $hospital["idGrupo"] = $value;
                        break;
                    }elseif(is_string($value) && ($value != NULL) && (!empty($value)) && ctype_digit ($value)){
                        $hospital["idGrupo"] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $hospital["idGrupo"] = NOT_VALID_FIELD_LBL;
                    break;
                default:
                    # code...
                    break;
            }            
        }
        if($validObj){
            $existingHospital = $this->hospitalExists($hospital);
            if($existingHospital == NULL){
                //insert Hospital 
                $stmt = $this->conn->prepare("INSERT INTO Hospital(nombreHospital, 
                                                                    calleHospital, 
                                                                    numeroExteriorHospital, 
                                                                    numeroInteriorHospital, 
                                                                    coloniaHospital, 
                                                                    telefonoHospital, 
                                                                    urlHospital, 
                                                                    rfcHospital,
                                                                    idGrupo) values(?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiisissi", $hospital['nombreHospital'],
                                                $hospital['calleHospital'],
                                                $hospital['numeroExteriorHospital'],
                                                $hospital['numeroInteriorHospital'],
                                                $hospital['coloniaHospital'],
                                                $hospital['telefonoHospital'],
                                                $hospital['urlHospital'],
                                                $hospital['rfcHospital'],
                                                $hospital['idGrupo']
                                                );
                $result = $stmt->execute();
                // Check for successful insertion
                if ($result) {
                    $hospital_id = $stmt->insert_id;
                    $hospital["idHospital"] = $hospital_id;
                    $stmt->close();
                    $response["hospital"] = $hospital;
                    return $util->armStandardResponse(USR_CREATE_OK, $response);                    
                } 
                else {
                    // Failed to create user                               
                    $response["hospital"] = $hospital;
                    $response["error"] = $stmt->error;
                    $stmt->close(); 
                    return $util->armStandardResponse(USR_CREATE_FAILED, $response);
                } 
            }else{
                //hospital exists
                $response["hospital"] = $existingHospital;
                return $util->armStandardResponse(ITEM_ALREADY_EXISTED, $response);
            }
        }else{
            //return error
            $response["hospital"] = $hospital;
            return $util->armStandardResponse(USR_CREATE_FAILED_REVIEW_INVALID_FIELDS, $response);
        }
    }

    public function createGroup($groupObj){        
        $util = new Utilities($this->conn);
        $validator = new Validations();
        $validObj = TRUE;
        $regexp = "/^[a-zA-Z_\s áéíóúüñÁÉÍÓÚÜÑ]{1,}$/iu";         
        $group = array();
        //first check if the Quimico user already exists in db
        foreach($groupObj as $field => $value){
            //
            switch ($field) {
                case 'nombreGrupo':
                    if(is_string($value) && (!empty($value)) && $value != NULL){                                                                                        
                        $group['nombreGrupo'] = $value;
                        break;
                    }
                    $validObj = FALSE;
                    $group["nombreGrupo"] = NOT_VALID_FIELD_LBL;
                    break;
                case 'urlGrupo':
                    if(is_string($value)){                                                                                        
                        $group['urlGrupo'] = $value;
                        break;
                    }
                    break;
                default:
                    # code...
                    break;
            }            
        }
        if($validObj){
            $existingGroup = $this->groupExists($group);  
            if($existingGroup == NULL){
                //insert Hospital 
                $stmt = $this->conn->prepare("INSERT INTO Grupo(nombreGrupo, 
                                                                    urlGrupo) values(?, ?)");
                $stmt->bind_param("ss", $group['nombreGrupo'],
                                        $group['urlGrupo']
                                                );
                $result = $stmt->execute();
                // Check for successful insertion
                if ($result) {
                    $group_id = $stmt->insert_id;
                    $group["idGrupo"] = $group_id;
                    $stmt->close();
                    $response["group"] = $group;
                    return $util->armStandardResponse(USR_CREATE_OK, $response);                    
                } 
                else {
                    // Failed to create user
                    $stmt->close();            
                    $response["group"] = $group;
                    return $util->armStandardResponse(USR_CREATE_FAILED, $response);
                } 
            }else{
                //group exists
                $response["group"] = $existingGroup;
                return $util->armStandardResponse(ITEM_ALREADY_EXISTED, $response);
            }
        }else{
            //return error
            $response["group"] = $group;
            return $util->armStandardResponse(USR_CREATE_FAILED_REVIEW_INVALID_FIELDS, $response);
        }
    }

    function joinAtentionAreasToHospital($hospitalId, $aaArray){
        $relations = array();
        $util = new Utilities($this->conn);
        foreach ($aaArray as $key => $value) {
            if(isset($value["idServicio"])){
                $stmt = $this->conn->prepare("INSERT INTO Servicio_Hospital(idServicio, idHospital) VALUES (?,?)");
                $stmt->bind_param("ii",$value["idServicio"], $hospitalId);
                $result = $stmt->execute();
                if($result){
                    $relation = array();
                    $relation["idServicio"] = $value["idServicio"];
                    $relation["idHospital"] = $hospitalId;
                    $relation["status"] = "Succesfuly inserted";
                    $relations[] = $relation;
                }else{
                    if($stmt->errno == 1062){
                        //duplicated                        
                        $relation = array();
                        $relation["idServicio"] = $value["idServicio"];
                        $relation["idHospital"] = $hospitalId;
                        $relation["status"] = "Duplicated";
                        $relations[] = $relation;
                    }else{
                        //duplicated                        
                        $relation = array();
                        $relation["idServicio"] = $value["idServicio"];
                        $relation["idHospital"] = $hospitalId;
                        $relation["status"] = $stmt->error;
                        $relations[] = $relation;
                    } 
                }
                $stmt->close();
            }else{

            }
        }   
        $response["relations"] = $relations;
        return $util->armStandardResponse(USR_CREATE_OK, $response); 
    }

    function setAtentionAreas($atentionAreas){
        $myAtentionAras = array();
        $util = new Utilities($this->conn);
        foreach ($atentionAreas as $item => $value) {
            //validate Values
            if(is_string($value) && (!empty($value)) && $value != NULL){                                                                                                        
                //insert it to database
                $stmt = $this->conn->prepare("INSERT INTO Servicio (nombreServicio) values (?)");
                $stmt->bind_param("s", $value);
                $result = $stmt->execute();
                //check for succesful insertion
                if($result){
                    $newService = array();
                    $newService["nombreServicio"] = $value;
                    $newService["idServicio"] = $stmt->insert_id;
                    $newService["status"] = "success";
                    $myAtentionAras[] = $newService;

                }else{
                    if($stmt->errno == 1062){
                        //duplicated                        
                        $nstmt = $this->conn->prepare("SELECT s.idServicio from Servicio s WHERE s.nombreServicio = ?");
                        $nstmt->bind_param("s", $value);
                        if ($nstmt->execute()) {
                            $nstmt->bind_result( $idServicio);                            
                            while($nstmt->fetch()){
                                $newService = array();
                                $newService["idServicio"] = $idServicio;                                 
                                $newService["nombreServicio"] = $value;
                                $newService["status"] = "duplicated";
                                $myAtentionAras[] = $newService;                                               
                            }
                            $nstmt->close();
                        } else {
                            //internal server error
                            $newService = array();
                            $newService["nombreServicio"] = $value;
                            $newService["idServicio"] = "-";
                            $newService["status"] = "internal server error";
                            $myAtentionAras[] = $newService;
                        }                        
                    }else{
                        //internal server error
                        $newService = array();
                        $newService["nombreServicio"] = $value;
                        $newService["idServicio"] = "-";
                        $newService["status"] = "internal server error";
                        $myAtentionAras[] = $newService;
                    }                    
                }
                $stmt->close();
            }else{
                $newService = array();
                $newService["nombreServicio"] = $value;
                $newService["idServicio"] = "-";
                $newService["status"] = "invalid field";
                $myAtentionAras[] = $newService;
            }
        }
        $response["atentionAreas"] = $myAtentionAras;
        return $util->armStandardResponse(USR_CREATE_OK, $response);    
    }
}

?>