<?php

class Warnings{

private $conn;
    function __construct($con){
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/Patient.php';
        require_once dirname(__FILE__) . '/ClinicalData.php';
        $this->conn = $con;

    }

    private function getInteractionsIdsMatched($array1, $array2, $key){
        $matchedMeds = array();
        foreach ($array1 as $index => $med) {            
            if(in_array($med[$key], $array2)){
                $matchedMeds[] = $med;                
            }
        }       
        if(count($matchedMeds) > 0) {
            return $matchedMeds;    
        }
        return NULL;
    }

    private function simplifyInteractionsSet($interactionArray, $interaction_id, $prescription_number, $clinicalData_id){
        $interactionResult = array();
        $interaction = array();
        $interaction["idInteraccion"] = $interaction_id;
        $interaction["numeroReceta"] = $prescription_number;
        $interaction["idDatosClinicos"] = $clinicalData_id;
        $interaction["detallesInteraccion"] = $interactionArray["descripcion"][0];
        $medsIDs = $interactionArray["idMedicamentos"];
        $alergiesIDs = $interactionArray["idAlergias"];  
        if(($alergiesIDs != NULL) && ($medsIDs != NULL)){
            foreach ($medsIDs as $keyMed => $med) {                 
                foreach ($alergiesIDs as $keyAlergy => $alergy) {                    
                    $interaction["idPrincipio"] = $med["idPrincipio"];
                    $interaction["idAlergia"] = $alergy["idAlergia"];
                    $interactionResult[] = $interaction;
                }                
            }
            return $interactionResult;
        }elseif ($medsIDs != NULL) {
            foreach ($medsIDs as $keyMed => $med) {                 
                $interaction["idPrincipio"] = $med["idPrincipio"];
                $interaction["idAlergia"] = NULL;
                $interactionResult[] = $interaction;                
            }
            return $interactionResult;
        }elseif ($alergiesIDs != NULL) {
            foreach ($alergiesIDs as $keyAlergy => $alergy) {                    
                $interaction["idPrincipio"] = NULL;
                $interaction["idAlergia"] = $alergy["idAlergia"];
                $interactionResult[] = $interaction;
            }
            return $interactionResult;
        }
        $interaction["idPrincipio"] = NULL;
        $interaction["idAlergia"] = NULL;
        $interactionResult[] = $interaction;
        return $interactionResult;
    }

    private function insertInteraction($interactionsSimplified){
        $insertedInteractions = array();
        foreach($interactionsSimplified as $index => $interaction){
            $stmt = $this->conn->prepare("INSERT INTO Interaccion(
                                                                    idInteraccion,
                                                                    detallesInteraccion,
                                                                    idPrincipio,
                                                                    idAlergia,
                                                                    idDatosClinicos,
                                                                    numeroReceta
                                                                    ) values(?,?,?,?,?,?)");
                
            $stmt->bind_param("isiiii",
                                        $interaction["idInteraccion"],
                                        $interaction["detallesInteraccion"],
                                        $interaction["idPrincipio"],
                                        $interaction["idAlergia"],
                                        $interaction["idDatosClinicos"],
                                        $interaction["numeroReceta"]


            );                    
            $result = $stmt->execute();
            if($result){
                $stmt->close();
                $insertedInteractions[] = $interaction;
            }
        }         
        
        if(count($insertedInteractions) > 0){
            return $insertedInteractions;
        }
        return NULL;
    }

    public function setInteraction($prescription_number, $clinicalData_id, $user_id, $hospital_id, $fieldsList){
        
        $engine = new Motor($this->conn);
        $util = new Utilities($this->conn);
        $response = array();
        $response["interaccion"] = null;
        $response["error"] = false;
        $interactionsSimplified = array();
        $interactionsResults = array();
        //get prescription ByNumerAndClinicalDataID
        $prescription = $engine->getPrescriptionByNumerAndClinicalDataID($prescription_number, $clinicalData_id);        
        $alergies = $engine->getAlergiesByClinicalData($clinicalData_id);  

        if($prescription != null){     
            foreach ($fieldsList as $param => $objs){
                $idInteraccion = $engine->getProximaInteraccionId();
                if($objs != null){
                    $interactionN = array();
                    foreach ($objs  as $content => $fields) {
                        if($content == "idMedicamentos"){                                                                                    
                            $interactionN["idMedicamentos"] = $this->getInteractionsIdsMatched($prescription, $fields, "idPrincipio");                            
                        }else if($content == "idAlergias"){                                                                                                                
                            $interactionN["idAlergias"] = $this->getInteractionsIdsMatched($alergies, $fields, "idAlergia");
                        }else if($content == "descripcion"){                            
                            $interactionN["descripcion"] = $fields;
                        }
                    }                    
                    if(isset($interactionN["descripcion"][0]) && (strlen($interactionN["descripcion"][0]) > 0)){
                        $interactionsSimplified = $this->simplifyInteractionsSet($interactionN, $idInteraccion, $prescription_number, $clinicalData_id);                                            
                        $interactionsResults[] = $this->insertInteraction($interactionsSimplified);
                    }else{
                        $response["alert"] = true;
                        $interactionN["descripcion"] = "Este Campo es obligatorio";
                        $interactionsResults[] = $interactionN;
                    }            
                }
            }
            if(count($interactionsResults) > 0){
                $response["body"] = $interactionsResults;  
                //if all went ok then return success
                return $util->armStandardResponse(INTERACTION_SET_CREATE_OK, $response);
            }else{
                //return failure
                $response["body"] = $interactionsResults;  
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }            
        }else{
            //return error

            return $util->armStandardResponse(INTERACTION_PRESCRIPTION_NOT_EXISTS, $response);    
        }        
    }

    public function existAlertsForClinicalData($patient_id, $hospital_id, $idClinicalData, $idDatoFarma){
        $util = new Utilities($this->conn);
        $patientObj = new Patient($this->conn);
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        $response['alertas'] = NULL;
        if($patient != null){
            $engine = new Motor($this->conn);
            $alertas = $engine->getAlerta_Pendiente($patient_id);
            if($alertas["error"] == 100){
                $response['alertas'] = $alertas;
                return $util->armStandardResponse(ALERTS_SUCCESSFUL_ANSWER, $response);    
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }
        }else{                  
            return $util->armStandardResponse(ALERTS_PATIENT_NOT_EXIST, $response);
        }
    }

    public function getAlertMissing($patient_id, $hospital_id){
        $util = new Utilities($this->conn);
        $patientObj = new Patient($this->conn);
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        $response['alertas'] = NULL;
        if($patient != null){
            $engine = new Motor($this->conn);
            $alertas = $engine->getAlerta_Pendiente($patient_id);
            if($alertas["error"] == 100){
                $response['alertas'] = $alertas;
                return $util->armStandardResponse(ALERTS_SUCCESSFUL_ANSWER, $response);    
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }
        }else{                  
            return $util->armStandardResponse(ALERTS_PATIENT_NOT_EXIST, $response);
        }

    }

    public function getHospitalAlerts($hospital_id){
        $util = new Utilities($this->conn);
        $response['alertas'] = NULL;
        $engine = new Motor($this->conn);
        $alertas = $engine->getTotalAlertas($hospital_id);
        if($alertas["error"] == 102){
            return $util->armStandardResponse(ALERTS_HOSPITAL_NOT_EXIST, $response);
        }
        if($alertas["error"] == 100){
            $response['alertas'] = $alertas;
            return $util->armStandardResponse(ALERTS_SUCCESSFUL_ANSWER, $response);    
        }else{
            return $util->armStandardResponse(CONTROL_FAILURE, $response);
        }

    }

    public function updateWarningStatusByCDId($clinicalData_id, $value){
        $util = new Utilities($this->conn);
        $result = $util->upDateIntegerColumnIn("Alerta", "idAccion", "idDatosClinicos", $clinicalData_id, $value);
        return $result;
    }

    public function updateWarningStatusByTypeAndCDId($clinicalData_id, $typeId, $value){
        $util = new Utilities($this->conn);
        $result = $util->upDateIntegerColumnIn_1("Alerta", "idAccion", "idDatosClinicos", "idTipoAlerta", $clinicalData_id, $typeId, $value);
        return $result;
    }

    public function updateWarningsColumnWhereAnd($set, $where, $and, $token1, $token2, $value){
        $util = new Utilities($this->conn);        
        $result = $util->upDateIntegerColumnIn_1("Alerta", $set, $where, $and, $token1, $token2, $value);
        return $result;
    }

    public function updateConciliationAlertActionValue($clinicalData_id, $prescription_number, $value){
        $util = new Utilities($this->conn);
        $result = $util->upDateIntegerColumnIn_2("Alerta", "idAccion", "idDatosClinicos", "numeroReceta", "idTipoAlerta", $clinicalData_id, $prescription_number, ALERT_ID_DF_INCOMPLETE_AND_CONCILIATION, $value);        
        return $result;
    }

    public function updateSuitabilityAlertActionValue($clinicalData_id, $prescription_number, $value){
        $util = new Utilities($this->conn);
        $result = $util->upDateIntegerColumnIn_2("Alerta", "idAccion", "idDatosClinicos", "numeroReceta", "idTipoAlerta", $clinicalData_id, $prescription_number, ALERT_STATUS_DF_SUITABILITY, $value);        
        return $result;
    }

    public function updateDCCompletitionAlertActionValue($clinicalData_id, $prescription_number, $value){
        $util = new Utilities($this->conn);      
        $result = $util->upDateIntegerColumnIn_2("Alerta", "idAccion", "idDatosClinicos", "numeroReceta", "idTipoAlerta", $clinicalData_id, $prescription_number, ALERT_ID_CD_INCOMPLETE, $value);        
        return $result;
    }

    public function updateDCCompletitionAlertByAlertType($clinicalData_id, $value){
        $util = new Utilities($this->conn);      
        $result = $util->upDateIntegerColumnIn_1INT("Alerta", "idAccion", "idDatosClinicos", "idTipoAlerta", $clinicalData_id, ALERT_ID_CD_INCOMPLETE, $value);        
        return $result;
    }

	public function updateWarnings($alerta) {
		$util = new Utilities($this->conn);
		$error = false;
		$fecha = date('Y-m-d H:i:s');
		for($i = 0; $i< count($alerta); $i++)
		{
			$alerta[$i]["idAccion"] = ($alerta[$i]["idAccion"] == 1) ? 1 : NULL;
			$stmt = $this->conn->prepare("UPDATE Alerta SET
                                                            fechaAlerta = ?,
                                                            notaAlerta = ?,
                                                            idAccion = ?
                                                        WHERE idAlerta = ?");
                
            $stmt->bind_param("ssii",
                                        $fecha,
                                        $alerta[$i]["notaAlerta"],
                                        $alerta[$i]["idAccion"],
                                        $alerta[$i]["idAlerta"]
            );                    
            $result = $stmt->execute();
            if($result)
            {
                $stmt->close();
            }else {                    
                // Failed to update warning                               
                $error = true;
                $stmt->close();
            }
		}		
		if ($error)
		{
			$response['alertas'] = $alerta;
			return $util->armStandardResponse(CONTROL_FAILURE, $response);
		}
		else 
		{
			$response['alertas'] = $alerta;
			return $util->armStandardResponse(ALERTS_SUCCESSFUL_ANSWER, $response);	
		}
	}    

    public function createWarningByType($alerts, $clinicalData_id, $prescription_id){
        $util = new Utilities($this->conn);
        $error = false;
        $fecha = date('Y-m-d H:i:s');
        $error = false;
        $failures = array();
        $response['alertas'] = array();

        foreach ($alerts as $alert => $object) {
            $alert = array();
            $error = false;
            foreach ($object as $key => $value) {
                switch ($key) {
                                case 'descripcionAlerta':
                                    if(strlen($value) > 0){
                                        $alert['descripcionAlerta'] = $value;
                                        break;                                        
                                    }
                                    $alert['descripcionAlerta'] = "Sin descripcion";
                                    break;
                                case 'idTipoAlerta':
                                    if(is_numeric($value) && $value > 0){
                                        
                                        $alert['idTipoAlerta'] = $value;
                                        break;
                                    }
                                    $alert['idTipoAlerta'] = NOT_VALID_FIELD_LBL;
                                    $error = true;
                                    break;
                                
                                default:
                                    # code...
                                    break;
                            }            

            }
            if(!$error){
                $accion = null;
                $stmt = $this->conn->prepare("INSERT INTO Alerta(   
                                                descripcionAlerta,
                                                fechaAlerta,
                                                idDatosClinicos,
                                                numeroReceta,
                                                idAccion,
                                                idTipoAlerta
                                            ) values (?,?,?,?,?,?)");
                    
                $stmt->bind_param("ssiiii",
                                            $alert['descripcionAlerta'],
                                            $fecha,
                                            $clinicalData_id,
                                            $prescription_id,
                                            $accion,
                                            $alert['idTipoAlerta']

                );                    

                $result = $stmt->execute();
                if($result)
                {
                    array_push($response['alertas'], $alert);
                    $stmt->close();
                }else {                    
                    // Failed to update warning   
                    $error = true;
                    array_push($failures, $alert);
                    $response['alertas erroneas'] =  array();
                    $response['alertas erroneas'] = $failures;                    
                    $stmt->close();
                    return $util->armStandardResponse(CONTROL_FAILURE , $response); 
                }
            }else{                
                array_push($failures, $alert);
            }            

        }

        if((count($response['alertas']) > 0) && (count($failures) > 0)){            
            $response['alertas erroneas'] =  array();
            $response['alertas erroneas'] = $failures;
            return $util->armStandardResponse(ALERTS_ERROR, $response); 
        }elseif (count($response['alertas']) > 0) {
            return $util->armStandardResponse(ALERTS_SUCCESSFUL_ANSWER, $response); 
        }elseif(count($failures) > 0){
            $response['alertas'] =  array();
            $response['alertas'] = $failures;
            return $util->armStandardResponse(ALERTS_SINTAX_ERROR, $response);    
        }

    }
    
}

?>
