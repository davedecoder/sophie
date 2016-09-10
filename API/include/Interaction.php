<?php

class Interaction{

private $conn;
    function __construct($con){
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/ClinicalData.php';
        require_once dirname(__FILE__) . '/Meds.php';
        $this->conn = $con;

    }

    private function medsListIsPresentInPrescription($fieldsSet, $prescription){        
        if(is_array($fieldsSet["medicamentos"])){
            foreach ($fieldsSet["medicamentos"] as $key => $med) {
                $medExists = FALSE;
                foreach ($prescription as $prescriptionKey => $prescriptionMed) {
                    if($med["idPrincipio"] == $prescriptionMed["idPrincipio"]){
                        $medExists = TRUE;
                    }
                }
                if(!$medExists){
                    return FALSE;
                }
            }
            return TRUE;
        }
        return FALSE;        
    }
    
    private function interactionMedFood($user_id, $clinicalData_id, $prescription_number, $fieldsSet){
        
    }

    public function createInteraction($hospital_id, $user_id, $clinicalData_id, $prescription_number, $fieldsSet){
        //verify the given prescription is in the provided hospital domain
        $util = new Utilities($this->conn);
        $clinicalData = new ClinicalData($this->conn);        
        if($clinicalData->getClinicalData($clinicalData_id, $hospital_id) != NULL){
            
            $engine = new Motor($this->conn);
            $prescription = array();
            $prescription = $engine->getPrescriptionByNumberAndClinicalDataID($prescription_number, $clinicalData_id);                          
            if($prescription != NULL){
                //validate there's at least one med 
                //check if meds lists exists in the prescription
                if($this->medsListIsPresentInPrescription($fieldsSet, $prescription)){
                    if((is_numeric($fieldsSet["categorizacionInteraccion"])) && ($fieldsSet["categorizacionInteraccion"] > 0 ) && ($fieldsSet["categorizacionInteraccion"] <= 3)){
                        //interaction type                    
                        if((is_numeric($fieldsSet["tipoInteraccion"])) && ($fieldsSet["tipoInteraccion"] > 0 ) && ($fieldsSet["tipoInteraccion"] <= 3)){
                            //get the latest interaction_id
                            $myResult = array();
                            $stmt = $this->conn->prepare("SELECT idInteraccion FROM Interaccion ORDER BY idInteraccion DESC LIMIT 0, 1 ");
                            $stmt->execute();
                            $stmt->bind_result( $idInteraccion);
                            while($stmt->fetch()){
                                $myResult["idInteraccion"] = $idInteraccion;
                            }
                            $stmt->close();   
                            if(($myResult) == NULL){
                                $myResult["idInteraccion"] = 0;
                            }                            
                            $fieldsSet["idInteraccion"] = $myResult["idInteraccion"] + 1;                                
                            $fieldsSet["idQuimico"] = $user_id;
                            $fieldsSet["idDatosClinicos"] = $clinicalData_id;
                            $fieldsSet["numeroReceta"] = $prescription_number;
                            
                            
                            if($fieldsSet["tipoInteraccion"] == INTERACTION_TYPE_MED_FOOD){
                                //med-food
                                //prepare query
                                $stmt = $this->conn->prepare("INSERT INTO Interaccion(
                                                                                    idInteraccion,
                                                                                    alimentoInteraccion, 
                                                                                    detallesInteraccion, 
                                                                                    sugerenciaInteraccion, 
                                                                                    categorizacionInteraccion, 
                                                                                    tipoInteraccion,
                                                                                    idPrincipio,
                                                                                    idQuimico,
                                                                                    idDatosClinicos,
                                                                                    numeroReceta
                                                                                    ) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("isssiiiiii", 
                                                            $fieldsSet["idInteraccion"],
                                                            $fieldsSet["alimentoInteraccion"],
                                                            $fieldsSet["detallesInteraccion"],
                                                            $fieldsSet["sugerenciaInteraccion"],
                                                            $fieldsSet["categorizacionInteraccion"],
                                                            $fieldsSet["tipoInteraccion"],
                                                            $fieldsSet["medicamentos"][0]["idPrincipio"],
                                                            $fieldsSet["idQuimico"],
                                                            $fieldsSet["idDatosClinicos"],
                                                            $fieldsSet["numeroReceta"]
                                                 );                                            
                                $result = $stmt->execute();
                                if($result){
                                    
                                    //return success
                                    return $util->armStandardResponse(INTERACTION_POST_SUCCESS, $fieldsSet);
                                }
                                else{
                                    //return 500 error
                                    return $util->armStandardResponse(CONTROL_FAILURE, $fieldsSet);
                                }
                            }
                            else if($fieldsSet["tipoInteraccion"] == INTERACTION_TYPE_MED_MED){
                                //med-med
                                $systemError = FALSE;
                                foreach($fieldsSet["medicamentos"] as $key => $med ){
                                    //prepare query
                                    $stmt = $this->conn->prepare("INSERT INTO Interaccion(
                                                                                        idInteraccion,
                                                                                        alimentoInteraccion, 
                                                                                        detallesInteraccion, 
                                                                                        sugerenciaInteraccion, 
                                                                                        categorizacionInteraccion, 
                                                                                        tipoInteraccion,
                                                                                        idPrincipio,
                                                                                        idQuimico,
                                                                                        idDatosClinicos,
                                                                                        numeroReceta
                                                                                        ) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->bind_param("isssiiiiii",
                                                                $fieldsSet["idInteraccion"],
                                                                $fieldsSet["alimentoInteraccion"],
                                                                $fieldsSet["detallesInteraccion"],
                                                                $fieldsSet["sugerenciaInteraccion"],
                                                                $fieldsSet["categorizacionInteraccion"],
                                                                $fieldsSet["tipoInteraccion"],
                                                                $med["idPrincipio"],
                                                                $fieldsSet["idQuimico"],
                                                                $fieldsSet["idDatosClinicos"],
                                                                $fieldsSet["numeroReceta"]
                                                     );                                            
                                    $result = $stmt->execute();
                                    if(!$result){   
                                        $systemError = TRUE;
                                    }                
                                    $stmt->close();
                                }
                                if(!$systemError){                                      
                                    //return success                                    
                                    return $util->armStandardResponse(INTERACTION_POST_SUCCESS, $fieldsSet);      
                                }
                                else{
                                    //return 500 error
                                    return $util->armStandardResponse(CONTROL_FAILURE, $fieldsSet);
                                }
                            }                       
                        }
                        else{
                            //interaction type invalid                            
                            return $util->armStandardResponse(INTERACTION_INVALID_FIELDS, $fieldsSet);
                        }                        
                    }
                    else{
                        //categorization type invalid                    
                        return $util->armStandardResponse(INTERACTION_INVALID_FIELDS, $fieldsSet);                        
                    }                    
                }                    
                else{
                    //there are no meds present                    
                    return $util->armStandardResponse(INTERACTION_MEDS_UNKNOWN, $fieldsSet);                
                }
            }
            else{
                //the prescription does not exists
                return $util->armStandardResponse(INTERACTION_PRESCRIPTION_UNKNOWN, $fieldsSet);                
            } 
           

        }
        else{
            //the clinical Data does not belong to the current hospital
            return $util->armStandardResponseWMsg(INTERACTION_HOSPITAL_UNKNOWN, $fieldsSet, "hospital does not exists");
        }        
    }


    
}

?>
