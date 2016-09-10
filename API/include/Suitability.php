<?php

class Suitability{

private $conn;
    function __construct($con){
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/Meds.php';
        require_once dirname(__FILE__) . '/ClinicalData.php';
        $this->conn = $con;

    }

    private function getElementMedByIdInList($idDatosFarma, $meds){
        foreach($meds as $med => $variable){
            $myMed = (array)$variable;
            if($myMed["idDatosFarma"] == $idDatosFarma){
                return $myMed; 
            }
        }
        return null;
    }


    public function setSuitability($prescription_number, $clinicalData_id, $user_id, $hospital_id, $medsList){
        $meds = new Meds($this->conn);
        $engine = new Motor($this->conn);
        $util = new Utilities($this->conn);
        $insertedMeds = array();
        $doneMeds = array();
        $response = array();
        $suitaPattern = "/I[0-9]DatosFarma/";
        $response["medicamentos"] = null;
        //get prescription ByNumerAndClinicalDataID
        $prescription = $engine->getPrescriptionByNumberAndClinicalDataID($prescription_number, $clinicalData_id);        
        if($prescription != null){
            $idoneidad = $engine->getProximaIdoneidad();
            foreach($prescription as $obj => $meds){//check if med in DB is in the input list                
                $myMed = array();
                $myMed = $this->getElementMedByIdInList($meds["idDatosFarma"], $medsList);                
                if($myMed != null){                    
                    //validate input
                    $suitabilityItem = array();
                    foreach($myMed as $field => $value){
                        if(($field != "idDatosFarma") && (preg_match($suitaPattern, $field))){
                            if(($value == "ok") || ($value == "if") || ($value == "x")){
                                $suitabilityItem[$field] = $value;                                
                            }else{
                                //return error
                                return $util->armStandardResponse(SUITABILITY_INVALID_INPUT_FORMAT, $response);                   
                            }
                        }else if($field == "idDatosFarma"){
                            if(is_numeric($value)){
                                $suitabilityItem["idDatosFarma"] = $value;
                            }else{
                                //return error
                                return $util->armStandardResponse(SUITABILITY_INVALID_INPUT_FORMAT, $response);        
                            }
                        }else if($field == "notaIdoneidad"){
                            $suitabilityItem["notaIdoneidad"] = $value;
                        }
                    }
                    array_push($insertedMeds, $suitabilityItem);
                }else{
                    //cannot be NULL

                    //return error
                    return $util->armStandardResponse(SUITABILITY_INCOMPLETE_INPUT_MEDSLIST, $response);        
                }
            }
            $nowDate = date('Y-m-d H:i:s');
            foreach($insertedMeds as $suitabilityItem){
                //if everything ok insert into DB then
                    $stmt = $this->conn->prepare("INSERT INTO Idoneidad(
                                                                            idIdoneidad,
                                                                            I1DatosFarma,
                                                                            I2DatosFarma,
                                                                            I3DatosFarma,
                                                                            I4DatosFarma,
                                                                            I5DatosFarma,
                                                                            I6DatosFarma,
                                                                            I7DatosFarma,
                                                                            I8DatosFarma,
                                                                            notaIdoneidad,
                                                                            fechaIdoneidad,
                                                                            idDatosFarma,
                                                                            idQuimico
                                                                            ) values(?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        
                    $stmt->bind_param("issssssssssii",   
                                                    $idoneidad,
                                                    $suitabilityItem["I1DatosFarma"],
                                                    $suitabilityItem["I2DatosFarma"],
                                                    $suitabilityItem["I3DatosFarma"],
                                                    $suitabilityItem["I4DatosFarma"],
                                                    $suitabilityItem["I5DatosFarma"],
                                                    $suitabilityItem["I6DatosFarma"],
                                                    $suitabilityItem["I7DatosFarma"],
                                                    $suitabilityItem["I8DatosFarma"],
                                                    $suitabilityItem["notaIdoneidad"],
                                                    $nowDate,
                                                    $suitabilityItem["idDatosFarma"],
                                                    $user_id

                    );
                    $suitabilityItem["idIdoneidad"] = $idoneidad;
                    $suitabilityItem["fechaIdoneidad"] = $nowDate;
                    $suitabilityItem["idQuimico"] = $user_id;
                    $result = $stmt->execute();
                    if($result)
                    {                        
                        $stmt->close();                        
                    }else {                    
                        // Failed to create medset                               
                        return $util->armStandardResponse(CONTROL_FAILURE, $response);
                    }
                    array_push($doneMeds, $suitabilityItem);
            }
            $response["medicamentos"] = $doneMeds;    
            $engine->checkIdoneidad($idoneidad);        
            //if all went ok then return success
            return $util->armStandardResponse(SUITABILITY_SET_CREATE_OK, $response);
        }else{
            //return error

            return $util->armStandardResponse(SUITABILITY_PRESCRIPTION_NOT_EXISTS, $response);    
        }        
    }

    public function updateSuitability($suitability_id, $user_id, $hospital_id, $medsList){
        $meds = new Meds($this->conn);
        $engine = new Motor($this->conn);
        $util = new Utilities($this->conn);
        $insertedMeds = array();
        $doneMeds = array();
        $response = array();
        $suitaPattern = "/I[0-9]DatosFarma/";
        $response["medicamentos"] = null;
        //get suitability ID
        $prescription = $engine->getIdoneidadByID($suitability_id);
        if($prescription != null){
            foreach($prescription as $obj => $meds){//check if med in DB is in the input list                
                $myMed = array();
                
                $myMed = $this->getElementMedByIdInList($meds["idDatosFarma"], $medsList);                
                if($myMed != null){                    
                    //validate input
                    $suitabilityItem = array();
                    foreach($myMed as $field => $value){
                        if(($field != "idDatosFarma") && (preg_match($suitaPattern, $field))){                            
                            if(($value == "ok") || ($value == "if") || ($value == "x")){
                                $suitabilityItem[$field] = $value;                                
                            }else{
                                //return error
                                return $util->armStandardResponse(SUITABILITY_INVALID_INPUT_FORMAT, $response);                   
                            }
                        }else if($field == "idDatosFarma"){
                            
                            if(is_numeric($value)){
                                $suitabilityItem["idDatosFarma"] = $value;
                            }else{
                                //return error
                                return $util->armStandardResponse(SUITABILITY_INVALID_INPUT_FORMAT, $response);        
                            }
                        }else if($field == "notaIdoneidad"){
                            $suitabilityItem["notaIdoneidad"] = $value;
                        }
                    }
                    array_push($insertedMeds, $suitabilityItem);
                }else{
                    //CANNOT be NULL

                    //return error
                    return $util->armStandardResponse(SUITABILITY_INCOMPLETE_INPUT_MEDSLIST, $response);        
                }
            }
            $nowDate = date('Y-m-d H:i:s');
            foreach($insertedMeds as $suitabilityItem){
                //if everything ok insert into DB then
                    $stmt = $this->conn->prepare("UPDATE Idoneidad Ido SET
                                                                            Ido.I1DatosFarma = ?,
                                                                            Ido.I2DatosFarma = ?,
                                                                            Ido.I3DatosFarma = ?,
                                                                            Ido.I4DatosFarma = ?,
                                                                            Ido.I5DatosFarma = ?,
                                                                            Ido.I6DatosFarma = ?,
                                                                            Ido.I7DatosFarma = ?,
                                                                            Ido.I8DatosFarma = ?,
                                                                            Ido.fechaIdoneidad = ?,
                                                                            Ido.notaIdoneidad = ?,
                                                                            Ido.idQuimico = ? 
                                                                            WHERE Ido.idDatosFarma = ? AND Ido.idIdoneidad = ?" );
                        
                    $stmt->bind_param("ssssssssssiii",   
                                                    $suitabilityItem["I1DatosFarma"],
                                                    $suitabilityItem["I2DatosFarma"],
                                                    $suitabilityItem["I3DatosFarma"],
                                                    $suitabilityItem["I4DatosFarma"],
                                                    $suitabilityItem["I5DatosFarma"],
                                                    $suitabilityItem["I6DatosFarma"],
                                                    $suitabilityItem["I7DatosFarma"],
                                                    $suitabilityItem["I8DatosFarma"],
                                                    $nowDate,
                                                    $suitabilityItem["notaIdoneidad"],                                                    
                                                    $user_id,
                                                    $suitabilityItem["idDatosFarma"],
                                                    $suitability_id


                    );
                    $suitabilityItem["fechaIdoneidad"] = $nowDate;
                    $suitabilityItem["idQuimico"] = $user_id;
                    $result = $stmt->execute();
                    if($result)
                    {
                        $stmt->close();
                    }else {                    
                        // Failed to create medset                               
                        return $util->armStandardResponse(CONTROL_FAILURE, $response);
                    }
                    array_push($doneMeds, $suitabilityItem);
            }
            $response["medicamentos"] = $doneMeds;
            $engine->checkIdoneinda($suitability_id);
            //if all went ok then return success
            return $util->armStandardResponse(SUITABILITY_SET_CREATE_OK, $response);
        }else{
            //return error

            return $util->armStandardResponse(SUITABILITY_PRESCRIPTION_NOT_EXISTS, $response);    
        }      
    }
}

?>
