<?php

class Meds{

private $conn;
    function __construct($con){
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/Patient.php';
        require_once dirname(__FILE__) . '/ClinicalData.php';
        $this->conn = $con;

    }
///////////////
///EXIT REPORT 
//////////////
    public function getExitReport($patient_id, $hospital_id){
        $util = new Utilities($this->conn);        
        $patientObj = new Patient($this->conn);
        $engine = new Motor($this->conn);
        $response["reporte egreso"] = null;
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        if($patient != null){
            //get info from the engine
            $answer = $engine->getReporteSalida($patient_id);
            $response["reporte egreso"] = $answer;
            if($answer["error"] == 100){
                return $util->armStandardResponse(PFC_SUCCESSFUL_ANSWER, $response);    
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);   
            }            
        }else{
            return $util->armStandardResponse(MEDS_PATIENT_NOT_EXIST, $response);
        }
    }
//////////////////////////
/// GET FIRST CONCILIATION
//////////////////////////
    public function getFirtsConciliation($hospital_id, $patient_id){
        $util = new Utilities($this->conn);        
        $patientObj = new Patient($this->conn);
        $engine = new Motor($this->conn);
        $response["primera conciliacion"] = null;
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        if($patient != null){
            //get info from the engine
            $answer = $engine->getReporteEntrada($patient_id);
            $response["primera conciliacion"] = $answer;
            if($answer["error"] == 100){
                return $util->armStandardResponse(PFC_SUCCESSFUL_ANSWER, $response);    
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);   
            }            
        }else{
            return $util->armStandardResponse(MEDS_PATIENT_NOT_EXIST, $response);
        }
    }
//////////////////////////////////////////
/// GET LAST DATOS FARMA BY CLINICAL DATA
//////////////////////////////////////////
    public function getLastDatosFarmaByClinicalData($clinicalData_id){
        $farmaDataSet= array();
        $farmaData = array();
        $result = mysqli_query($this->conn, "SELECT * FROM DatosFarma df1 
                                                    WHERE idDatosClinicos = $clinicalData_id AND numeroRecetaDatosFarma = (SELECT MAX(df2.numeroRecetaDatosFarma) 
                                                    FROM DatosFarma df2 WHERE df1.idDatosClinicos = df2.idDatosClinicos)");
        while($farmaData = mysqli_fetch_assoc($result)){            
            $farmaDataSet[] = $farmaData;
        }
        if(count($farmaDataSet) > 0){            
            return $farmaDataSet;
        }
        return NULL;

    }
/////////////////////////////////////
/// GET NEXT PRESCIPTION NUMBER
////////////////////////////////////
    public function getNextPrescriptionNumber($patient_id, $hospital_id){
    	$engine = new Motor($this->conn);
        $utils = new Utilities($this->conn);
        $lastPrescriptionSet = $engine->pft_historial($patient_id);
        $searchResult = $utils->recursive_array_search("numeroRecetaDatosFarma",$lastPrescriptionSet);
        if((count($searchResult) > 0) && (isset($searchResult[0]["numeroRecetaDatosFarma"]))){            
            return $searchResult[0]["numeroRecetaDatosFarma"] + 1;
        }else{
            return 1;
        }
    }
//////////////////////////////////////
///UPDATE CAPTURE DATE OF THE MEDS SET
////////////////////////////////////////
    private function updateMedsSetCaptureDate($medsList, $newDate){
        $nMedsList = array();
        foreach ($medsList as $med => $contentValue ) {
            $contentValue["capturaDatosFarma"] = $newDate;  
            $nMedsList[] = $contentValue;
        }
        return $nMedsList;
    }
////////////////////////////////////////
/// INSERT THE MEDSSET INTO THE DB
///////////////////////////////////

    private function insertMeds($medsList, $prescriptionNumber, $lastClinicalData_id, $user_id, $idoneidad, $alert, $actualDate){        
        $doneMeds = array();
        $engine = new Motor($this->conn);
        $util = new Utilities($this->conn);
        $response["medicamentos"] = $doneMeds;
        foreach ($medsList as $farmaData) {
            ////////////////
            $farmaData['idDatosFarma'] = 0; 
            $farmaData['numeroRecetaDatosFarma'] = $prescriptionNumber;                                                      
            $farmaData['idDatosClinicos'] = $lastClinicalData_id;
            $farmaData['error'] = FALSE;
            $farmadata_id;

            //Do insertions
            $stmt = $this->conn->prepare("INSERT INTO DatosFarma(
                                                                concentracionDatosFarma,
                                                                cronicoDatosFarma,
                                                                inicioDatosFarma,
                                                                notaDatosFarma,
                                                                numeroRecetaDatosFarma,
                                                                numeroAplicacionDatosFarma,
                                                                capturaDatosFarma,
                                                                prescritoDatosFarma,
                                                                idDatosClinicos,
                                                                idPrincipio,
                                                                idFrecuencia,
                                                                idUnidad,
                                                                idVia,
                                                                idUso,
                                                                idPresentacion,
                                                                idTipoPrescripcion,
                                                                idTipoMedicamento,
                                                                idQuimico) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            
       $stmt->bind_param("sissiisiiiiiiiiiii",   $farmaData['concentracionDatosFarma'],
                                                 $farmaData['cronicoDatosFarma'],
                                                 $farmaData['inicioDatosFarma'],
                                                 $farmaData['notaDatosFarma'],
                                                 $farmaData['numeroRecetaDatosFarma'],
                                                 $farmaData['numeroAplicacionDatosFarma'],
                                                 $farmaData['capturaDatosFarma'],
                                                 $farmaData['prescritoDatosFarma'],
                                                 $farmaData['idDatosClinicos'],
                                                 $farmaData['idPrincipio'],
                                                 $farmaData['idFrecuencia'],
                                                 $farmaData['idUnidad'],
                                                 $farmaData['idVia'],
                                                 $farmaData['idUso'],
                                                 $farmaData['idPresentacion'],
                                                 $farmaData['idTipoPrescripcion'],
                                                 $farmaData['idTipoMedicamento'],
                                                 $user_id);                
            $result = $stmt->execute();
            if($result)
            {
                $farmadata_id = $stmt->insert_id;
                $stmt->close();
                //***
                mysqli_query($this->conn,"insert into Nota_Farma values(null,'',".$farmaData['idDatosClinicos'].",".$farmaData['numeroRecetaDatosFarma'].",$user_id)");                
                $farmaData['idDatosFarma'] = $farmadata_id;
                array_push($doneMeds, $farmaData);    
            }else {                    
                // Failed to create medset
                $response["medicamentos"] = $doneMeds;
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }               
            
        }       
        if(count($doneMeds) > 0){
            $response["medicamentos"] = $doneMeds;

            $warnings = new Warnings($this->conn);            
            //update Previous CONCILIATION AND INCOMPLETE FARAMA DATA alerts status to accept
            $warnings->updateConciliationAlertActionValue($lastClinicalData_id, $doneMeds[0]['numeroRecetaDatosFarma'] - 1, ALERT_ALLOW);
            //update Previous SUITABILITY alerts status to accept
            $warnings->updateSuitabilityAlertActionValue($lastClinicalData_id, $doneMeds[0]['numeroRecetaDatosFarma'] - 1, ALERT_ALLOW); 
            if($alert){
                $engine->alertaDatos_Pendientes($lastClinicalData_id, $doneMeds[0]['numeroRecetaDatosFarma'], ALERT_TYPE_DF_INCOMPLETE);
                return $util->armStandardResponse(PFTSET_CREATE_OK_W_WARNINGS, $response);
            }else{
                $engine->conciliacion($lastClinicalData_id, $doneMeds[0]['numeroRecetaDatosFarma']);
                return $util->armStandardResponse(PFTSET_CREATE_OK, $response);
            }                        
        }
        return NULL;
    }

///////////////////////////////////////
///// VALIDATIONS /////////////////////
///////////////////////////////////////

///////////////////////////////////////
///// VALIDATE MEDS FIELDS
///////////////////////////////////////
    private function validateMedFields($patient_id, $objs, $actualDate, $currentDate, $clinicalDataDate){
        $alert = FALSE;          
        $validObj = TRUE;
        $response = array();
        $medsList = array();
        foreach ($objs as $obj => $fields){        
            //shared data            
            $farmaData = array();
            $farmaData["idPaciente"] = $patient_id;

            foreach($fields as $field => $value){                        
                switch ($field) {
                    case 'concentracionDatosFarma':
                            if(($value != NULL) && (!empty($value))){
                                if(is_string($value) && ($value >= 0)){
                                    $farmaData['concentracionDatosFarma'] = $value;
                                    break;        
                                }else{
                                    $validObj = FALSE;
                                    $farmaData['concentracionDatosFarma'] = NOT_VALID_FIELD_LBL;
                                    break;
                                }                                
                            }
                            $alert = TRUE;                                                                                       
                            $farmaData['concentracionDatosFarma'] = NULL;
                            break;                                                            
                    case 'cronicoDatosFarma':                             
                            if(($value != NULL) && (!empty($value))){
                                if((is_bool($value) === TRUE) || ($value == '0') || ($value == '1')){
                                    $farmaData['cronicoDatosFarma'] = $value;
                                    break;        
                                }else{
                                    $validObj = FALSE;
                                    $farmaData['cronicoDatosFarma'] = NOT_VALID_FIELD_LBL;
                                    break;
                                }                                
                            }                                                                                       
                            $farmaData['cronicoDatosFarma'] = FALSE;
                            break;
                    case 'inicioDatosFarma':
                            if(($value != NULL) && (!empty($value))){
                                //temporarily this data will be in string format
                                //$validator = new Validations();                                
                                //if($validator->verify_time_format($value)){
                                if(is_string($value)){ 
                                    $farmaData['inicioDatosFarma'] = $value;
                                    break;        
                                }else{
                                    $validObj = FALSE;
                                    $farmaData['inicioDatosFarma'] = NOT_VALID_FIELD_LBL;
                                    break;
                                }                                
                            }                                                                    
                            $farmaData['inicioDatosFarma'] = NULL;
                            break;    
                    case 'notaDatosFarma':                                    
                            if(($value == NULL) || is_string($value) || empty($value)){
                                $farmaData['notaDatosFarma'] = $value;                                                                            
                                break;
                            }
                            $validObj = FALSE;
                            $farmaData['notaDatosFarma'] = NOT_VALID_FIELD_LBL;                                                
                            break;
                    case 'numeroAplicacionDatosFarma':
                            if(($value != NULL) && (!empty($value))){
                                if(is_numeric($value) && ($value >= 0)){
                                    $farmaData['numeroAplicacionDatosFarma'] = $value;
                                    break;        
                                }else{
                                    $validObj = FALSE;
                                    $farmaData['numeroAplicacionDatosFarma'] = NOT_VALID_FIELD_LBL;
                                    break;
                                }                                
                            }                                                                                       
                            $farmaData['numeroAplicacionDatosFarma'] = NULL;
                            break;
                    case 'prescritoDatosFarma':
                            if(($value != NULL) && (!empty($value))){
                                if((is_bool($value) === TRUE) || ($value == '0') || ($value == '1')){
                                    $farmaData['prescritoDatosFarma'] = $value;
                                    break;        
                                }else{
                                    $validObj = FALSE;
                                    $farmaData['prescritoDatosFarma'] = NOT_VALID_FIELD_LBL;
                                    break;
                                }                                
                            }                                                                                       
                            $farmaData['prescritoDatosFarma'] = FALSE;
                            break;
                    case 'capturaDatosFarma':
                            if(($value != NULL) && (!empty($value))){
                                $validator = new Validations();
                                if($validator->validateDateTime($value)){
                                    //check if the date provided is a valid candidate to modify the whole set
                                    if($value != $currentDate){
                                        if(($value >= $clinicalDataDate) && ($value <= $actualDate)){                                                                                               
                                            $farmaData['capturaDatosFarma'] = $value;
                                            $medsList = $this->updateMedsSetCaptureDate($medsList, $value);
                                            $currentDate = $value;
                                            break;
                                        }
                                        $validObj = FALSE;
                                        $farmaData['capturaDatosFarma'] = NOT_VALID_FIELD_LBL;
                                        break;    
                                    }
                                    $farmaData['capturaDatosFarma'] = $value;
                                    break;    
                                }else{
                                    $validObj = FALSE;
                                    $farmaData['capturaDatosFarma'] = NOT_VALID_FIELD_LBL;
                                    break;
                                }                                
                            }                                                                    
                            $farmaData['capturaDatosFarma'] = $actualDate;
                            break;
                    case 'idPrincipio':
                            //CONSIDER ADD VALIDATION IN CASE THE ACTIVE PRINCIPLE DOENOT EXISTS
                            if(is_numeric($value) && ($value >= 1)){
                                $farmaData['idPrincipio'] = $value;    
                                break;
                            }                                     
                            $validObj = FALSE;
                            $farmaData['idPrincipio'] =  NOT_VALID_FIELD_LBL;                                 
                            break;
                    case 'idFrecuencia':
                            if(($value != NULL) && (!empty($value))){
                                if(is_numeric($value) && ($value >= 1)){
                                    $farmaData['idFrecuencia'] = $value;    
                                    break;
                                }                                     
                                $validObj = FALSE;
                                $farmaData['idFrecuencia'] =  NOT_VALID_FIELD_LBL;                                 
                                break;     
                            }
                            $alert = TRUE;
                            $farmaData['idFrecuencia'] =  NULL;                                 
                            break;          
                    case 'idUnidad':
                            if(($value != NULL) && (!empty($value))){
                                if(is_numeric($value) && ($value >= 1)){
                                    $farmaData['idUnidad'] = $value;    
                                    break;
                                }                                     
                                $validObj = FALSE;
                                $farmaData['idUnidad'] =  NOT_VALID_FIELD_LBL;                                 
                                break;     
                            }
                            $alert = TRUE;
                            $farmaData['idUnidad'] =  NULL;                                 
                            break;          
                    case 'idVia':
                            if(($value != NULL) && (!empty($value))){
                                if(is_numeric($value) && ($value >= 1)){
                                    $farmaData['idVia'] = $value;    
                                    break;
                                }                                     
                                $validObj = FALSE;
                                $farmaData['idVia'] =  NOT_VALID_FIELD_LBL;                                 
                                break;     
                            }
                            $alert = TRUE;
                            $farmaData['idVia'] =  NULL;                                 
                            break;          
                    case 'idUso':
                            if(($value != NULL) && (!empty($value))){
                                if(is_numeric($value) && ($value >= 1)){
                                    $farmaData['idUso'] = $value;    
                                    break;
                                }                                     
                                $validObj = FALSE;
                                $farmaData['idUso'] =  NOT_VALID_FIELD_LBL;                                 
                                break;     
                            }
                            $farmaData['idUso'] =  NULL;                                 
                            break;          
                    case 'idPresentacion':
                            if(($value != NULL) && (!empty($value))){
                                if(is_numeric($value) && ($value >= 1)){
                                    $farmaData['idPresentacion'] = $value;    
                                    break;
                                }                                     
                                $validObj = FALSE;
                                $farmaData['idPresentacion'] =  NOT_VALID_FIELD_LBL;                                 
                                break;     
                            }
                            $alert = TRUE;
                            $farmaData['idPresentacion'] =  NULL;                                 
                            break;
                    case 'idTipoPrescripcion':
                        if(($value != NULL) && (!empty($value))){
                            if((is_numeric($value)) && ($value >= 0)){                                                            
                                $farmaData['idTipoPrescripcion'] = $value;
                                break;
                            }else{
                                //launch alert
                                $validObj = FALSE;                                    
                                $farmaData['idTipoPrescripcion'] = NOT_VALID_FIELD_LBL;
                                break;    
                            }                                
                        }
                        $alert = TRUE;
                        $validObj = FALSE;
                        $farmaData['idTipoPrescripcion'] = NOT_VALID_FIELD_LBL;                            
                        break;  
                    case 'idTipoMedicamento':
                        if(($value != NULL) && (!empty($value))){
                            if((is_numeric($value)) && ($value >= 0)){                                                            
                                $farmaData['idTipoMedicamento'] = $value;
                                break;
                            }else{
                                //launch alert
                                $validObj = FALSE;                                    
                                $farmaData['idTipoMedicamento'] = NOT_VALID_FIELD_LBL;
                                break;    
                            }                                
                        }
                        $farmaData['idTipoMedicamento'] = null;                            
                        break;          
                    default:                        
                        break;
                } 
                                           
            }
            $farmaData["alert"] = $alert;            

            array_push($medsList, $farmaData);                    
        }
        $response["alert"] = $alert;
        $response["validObj"] = $validObj;
        $response["medsList"] = $medsList;
        return $response;

    }


///////////////////////////////////////
///// CREATE MEDS SET API POST /meds/:patient_id
///////////////////////////////////////

    public function createMedsSet($patient_id, $hospital_id, $user_id, $objs){
    	$patientObj = new Patient($this->conn);
    	$clinicalDataObj = new ClinicalData($this->conn);
    	$util = new Utilities($this->conn);        
        $patient = $patientObj->getPatient($patient_id, $hospital_id);               
        $response = array();
        $doneMeds = array();
        $response["medicamentos"] = null;
        $engine = new Motor($this->conn);
        $prescriptionNumber = 0;
        $lastClinicalData_id = 0;        
        if($patient != NULL){
            //Get a valid presciption number
            $prescriptionNumber = $this->getNextPrescriptionNumber($patient_id, $hospital_id);
            if($prescriptionNumber > 0){                
                //array_push($response, $idIdentifier);                
                $clinicalData = $clinicalDataObj->getLastClinicalData($patient_id, $hospital_id);
                $actualDate = date('Y-m-d H:i:s');
                $currentDate = date('Y-m-d H:i:s');
                $clinicalDataDate = $clinicalData["ingresoDatosClinicos"];
                $lastClinicalData_id = $clinicalData["idDatosClinicos"];
                $idoneidad = $engine->getProximaIdoneidad();
                $alert = FALSE;          
                $validObj = TRUE;                      
                $validationResult = $this->validateMedFields($patient_id, $objs, $actualDate, $currentDate, $clinicalDataDate);
                $alert = $validationResult["alert"];
                $validObj = $validationResult["validObj"];
                $medsList = $validationResult["medsList"];

                $response["medicamentos"] = $medsList;

                if(!$validObj){
                    return $util->armStandardResponse(PFTSET_NOT_VALID, $response);
                }else{
                    //proceed to inser the pftset  
                    return $this->insertMeds($medsList, $prescriptionNumber, $lastClinicalData_id, $user_id, $idoneidad, $alert, $actualDate);                    
                }
            }else{
                //Clinical Data is missing
                return $util->armStandardResponse(PFTSET_CLINICALDATA_NOT_EXISTS, $response);
            }
        }else{
            //patient does not exists
            return $util->armStandardResponse(PFTSET_PATIENT_NOT_EXISTS, $response);
        }
    }
///////////////////////////////////////
///// GET PATIENT LAST PRESCRIPTION
///////////////////////////////////////

    public function getPatientLastPrescription($patient_id, $hospital_id){           
        $patientObj = new Patient($this->conn);
        $util =  new Utilities($this->conn);
        $clinicalDataObj = new ClinicalData($this->conn);
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        $response['ultimaPrescripcion'] = NULL;
        if($patient != NULL){            
            $engine = new Motor($this->conn);
            
            //$lastPrescription = $engine->pft_actual($patient_id);
            //$clinicalData = $clinicalDataObj->getLastClinicalData($patient_id, $hospital_id);
            //$lastPrescriptionSet = $this->getLastDatosFarmaByClinicalData($clinicalData["idDatosClinicos"]);            
            $lastPrescriptionSet = $engine->pft_historial($patient_id);
            $myResponse = array();
            if(count($lastPrescriptionSet) > 0){
                $myResponse = $lastPrescriptionSet;    
                $response['ultimaPrescripcion'] = $myResponse;
            }else{
                $response['ultimaPrescripcion'] = NULL;
            }
            
            return $util->armStandardResponse(MEDS_SUCCESSFUL_ANSWER, $response);
        }else{                  
            return $util->armStandardResponse(MEDS_PATIENT_NOT_EXIST, $response);
        }

    }
///////////////////////////////////////
///// GET LAST PRESCRIPTION  
///////////////////////////////////////

    public function getLastPrescription($patient_id, $hospital_id){        
        $patientObj = new Patient($this->conn);
        $util =  new Utilities($this->conn);
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        $response['ultimaPrescripcion'] = NULL;
        if($patient != NULL){            
            $engine = new Motor($this->conn);
            //$lastPrescription = $engine->pft_actual($patient_id);
            $lastPrescription = $engine->pft_historial($patient_id);
            if(!isset($lastPrescription["error"])){  
                $lastPrescriptionArr = array();
                foreach($lastPrescription as $item => $value){
                        if(is_integer($value))
                            $response["error"] = $value;                            
                        else
                            array_push($lastPrescriptionArr, $value);                                                                    
                    }              
                $response['ultimaPrescripcion'] = $lastPrescriptionArr;
                return $util->armStandardResponse(MEDS_SUCCESSFUL_ANSWER, $response);    
            }else if($lastPrescription["error"] == 102){
                return $util->armStandardResponse(MEDS_NOT_EXIST, $response);
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }            
        }else{                  
            return $util->armStandardResponse(MEDS_PATIENT_NOT_EXIST, $response);
        }

    }

///////////////////////////////////////
///// UPDATE THE INPUT MEDS LIST TO DATABASE
///////////////////////////////////////

private function updateMedsListToDataBase($lastClinicalData_id, $medsList, $user_id, $alert){
    $updatedMedsList = array();
    $response["medicamentos"] = array();
    $engine = new Motor($this->conn);
    $util = new Utilities($this->conn);
    foreach ($medsList as $index => $medToUpdate) {
        $medToUpdate["idDatosClinicos"] = $lastClinicalData_id;
        $stmt = $this->conn->prepare("UPDATE DatosFarma df SET 
                    df.concentracionDatosFarma = ?,
                    df.cronicoDatosFarma = ?,
                    df.inicioDatosFarma = ?,
                    df.notaDatosFarma = ?,
                    df.numeroRecetaDatosFarma = ?,
                    df.numeroAplicacionDatosFarma = ?,
                    df.capturaDatosFarma = ?,
                    df.prescritoDatosFarma = ?,
                    df.idPrincipio = ?,
                    df.idFrecuencia = ?,
                    df.idUnidad = ?,
                    df.idVia = ?,
                    df.idUso = ?,
                    df.idPresentacion = ?,
                    df.idQuimico = ?
                    WHERE df.idDatosFarma = ? AND df.idDatosClinicos = ?");

        $stmt->bind_param("iissiisiiiiiiiiii",   $medToUpdate['concentracionDatosFarma'],
                                                $medToUpdate['cronicoDatosFarma'],
                                                $medToUpdate['inicioDatosFarma'],
                                                $medToUpdate['notaDatosFarma'],
                                                $medToUpdate['numeroRecetaDatosFarma'],
                                                $medToUpdate['numeroAplicacionDatosFarma'],
                                                $medToUpdate['capturaDatosFarma'],
                                                $medToUpdate['prescritoDatosFarma'],
                                                $medToUpdate['idPrincipio'],
                                                $medToUpdate['idFrecuencia'],
                                                $medToUpdate['idUnidad'],
                                                $medToUpdate['idVia'],
                                                $medToUpdate['idUso'],
                                                $medToUpdate['idPresentacion'],
                                                $user_id,
                                                $medToUpdate['idDatosFarma'],
                                                $medToUpdate['idDatosClinicos']);                
        $result = $stmt->execute();        
        // Check for successful insertion
        if (!$result) {                    
            // Failed to create medset
            $stmt->close();
            return $util->armStandardResponse(CONTROL_FAILURE, $response);
        }
        $updatedMedsList[] = $medToUpdate;
        $stmt->close();
    }
    if(count($updatedMedsList) > 0){
        $response["medicamentos"] = $updatedMedsList;
        $warnings = new Warnings($this->conn);            
        //update Previous CONCILIATION AND INCOMPLETE FARAMA DATA alerts status to accept
        $warnings->updateConciliationAlertActionValue($lastClinicalData_id, $updatedMedsList[0]['numeroRecetaDatosFarma'] - 1, ALERT_ALLOW);
        //update Previous SUITABILITY alerts status to accept
        $warnings->updateSuitabilityAlertActionValue($lastClinicalData_id, $updatedMedsList[0]['numeroRecetaDatosFarma'] - 1, ALERT_ALLOW); 
        if($alert){
            $engine->alertaDatos_Pendientes($lastClinicalData_id, $updatedMedsList[0]['numeroRecetaDatosFarma'], ALERT_TYPE_DF_INCOMPLETE);
            return $util->armStandardResponse(PFTSET_CREATE_OK_W_WARNINGS, $response);
        }else{
            $engine->conciliacion($lastClinicalData_id, $updatedMedsList[0]['numeroRecetaDatosFarma']);
            return $util->armStandardResponse(PFTSET_CREATE_OK, $response);
        }
    }
    $response["medicamentos"] = $medsList;
    return $util->armStandardResponse(CONTROL_FAILURE, $response);

}

///////////////////////////////////////
///// PUT UPDATE MEDS SET
///////////////////////////////////////

    public function updatePatientLastPrescription($patient_id, $hospital_id, $user_id, $objs){
        $patientObj = new Patient($this->conn);
        $util = new Utilities($this->conn);
        $clinicalDataObj = new ClinicalData($this->conn);
        $patient = $patientObj->getPatient($patient_id, $hospital_id);
        $prescriptionNumber = 0;
        $clinicalData_id = 0;
        $generalAlert = FALSE;               
        $response = array();
        $farmaData = array();
        $response["medicamentos"] = $objs;
        if($patient != NULL){
            //Get the last prescription
            $engine = new Motor($this->conn);
            $pft_historial = $engine->pft_historial($patient_id);            
            if(!isset($pft_historial["error"])){
                $clinicalData = $clinicalDataObj->getLastClinicalData($patient_id, $hospital_id);                                
                $actualDate = date('Y-m-d H:i:s');
                $currentDate = date('Y-m-d H:i:s');
                $clinicalDataDate = $clinicalData["ingresoDatosClinicos"];
                $lastClinicalData_id = $clinicalData["idDatosClinicos"];
                $alert = FALSE;          
                $validObj = TRUE;                      
                $validationResult = $this->validateMedFields($patient_id, $objs, $actualDate, $currentDate, $clinicalDataDate);
                $alert = $validationResult["alert"];
                $validObj = $validationResult["validObj"];
                $medsList = $validationResult["medsList"];                
                if(!$validObj){
                    $response["medicamentos"] = $medsList;
                    return $util->armStandardResponse(PFTSET_NOT_VALID, $response);
                    
                }else{
                    //Validate active principles are present in such input
                    $lastPrescription = $pft_historial[count($pft_historial)];
                    $inputMedsList  = array();
                    foreach ($lastPrescription as $index => $med) {
                        foreach ($medsList as $key => $inputMed) {
                            if($med["idPrincipio"] == $inputMed["idPrincipio"]){                                                                                    
                                $result = array();
                                $result = array_merge($med, $inputMed);
                                $inputMedsList[] = $result;
                            }
                        }
                    }
                    return $this->updateMedsListToDataBase($lastClinicalData_id, $inputMedsList, $user_id, $alert);
                }
            }else{
                //there is no prescription                
                return $util->armStandardResponse(PFTUPDATE_PRESCRIPTION_NOT_EXISTS, $response);
            }
        }else{
            //patient does not exists
            return $util->armStandardResponse(PFTUPDATE_PATIENT_NOT_EXISTS, $response);
        }
    }
}
?>
