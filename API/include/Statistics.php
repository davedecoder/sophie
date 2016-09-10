<?php

class Statistics{

private $conn;
    function __construct($con){
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/Patient.php';
        $this->conn = $con;

    }  

    public function getStatisticsByType($statistics, $user_id, $hospital_id){
        $util = new Utilities($this->conn);
        $validate = new Validations();
        $fecha = date('Y-m-d H:i:s');
        $failures = array();
        $engine = new Motor($this->conn);
        $response['estadisticas'] = array();
		$fieldsSet = array();
        $counter = 0;
        $errorType = NULL;
        $errorMsg = NULL;
        if(!isset($statistics["tipo"])){
            $statistics["tipo"] = STATISTICS_TYPE_MISSING_MSG;
            return $util->armStandardResponseWMsg(WRONG_PARAMETER , $statistics, $statistics["tipo"]); 
        }
        foreach ($statistics as $param => $content) {                
            switch ($param) {
                case 'tipo':
                    if(is_numeric($content) && ($content > 0 && $content < STATISTICS_TYPE_MAX_NUM)){
                        $fieldsSet['tipo'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['tipo'] = NULL;
                        break;
                    }                 
                    $errorType = WRONG_PARAMETER; 
                    $fieldsSet['tipo'] = STATISTICS_TYPE_WRONG_MSG;
                    $errorMsg = STATISTICS_TYPE_WRONG_MSG;
                    break;
                //Añadido por duva --- Falta ajustar las variables
                case 'opArea':
                    if(is_numeric($content) && ($content >= 0) && ($content < STATISTICS_CHEMIST_MAX_NUM)){
                        $fieldsSet['opArea'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['opArea'] = 0;
                        break;
                    }
                    $errorType = WRONG_PARAMETER; 
                    $fieldsSet['opArea'] = STATISTICS_OPQUIMICO_WRONG_MSG;
                    $errorMsg = STATISTICS_OPQUIMICO_WRONG_MSG;
                    break;
				//Falta el caso de area que es un objeto.
                case 'opQuimico':
                    if(is_numeric($content) && ($content >= 0) && ($content < STATISTICS_CHEMIST_MAX_NUM)){
                        $fieldsSet['opQuimico'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['opQuimico'] = 0;
                        break;
                    }
                    $errorType = WRONG_PARAMETER; 
                    $fieldsSet['opQuimico'] = STATISTICS_OPQUIMICO_WRONG_MSG;
                    $errorMsg = STATISTICS_OPQUIMICO_WRONG_MSG;
                    break;
                case 'quimico':
                    if(($content != NULL)){
                        //validate ID                        
                        $chemist_id = $content["idQuimico"]; 
                        if(is_numeric($chemist_id) && ($chemist_id > 0)){                                                                                        
                            $chemistObj = new Chemist($this->conn);
                            //look wether the patient exists or not
                            $chemist = $chemistObj->getChemist($chemist_id, $hospital_id);                            
                            if($chemist != NULL){
                                $fieldsSet['opQuimico'] = 1;
                                $fieldsSet["quimico"] = $chemist;
                                break;
                            }
                        }else{
                            //invalid type of data
                            $nFieldSet = array();
                            $errorType = WRONG_PARAMETER;
                            $nFieldsSet["idQuimico"] = STATISTICS_OPQUIMICO_WRONG_MSG;    
                            $fieldsSet["quimico"] = $nFieldsSet;
                            $errorMsg = STATISTICS_OPQUIMICO_WRONG_MSG;
                            break;
                        }
                    }elseif ($content == NULL) {
                        $fieldsSet['quimico'] = NULL;
                        break;
                    }
                    $fieldsSet["quimico"] = NULL;
                    break;
                case 'fecha':
                    if(($content != NULL) && (is_array($content) && count($content <= 2 ))){
                        $nFieldSet = array();                       
                        $nCounter = 0;
                        foreach($content as $nParam => $nContent){                            
                            if(($nContent != NULL )){
                                if($validate->validateDate($nContent)){
                                    $nFieldSet[] = $nContent;
                                }else{
                                    $errorType = WRONG_PARAMETER;                                  
                                    $nFieldSet[] = STATISTICS_DATE_WRONG_MSG;
                                    $errorMsg = STATISTICS_DATE_WRONG_MSG;
                                }
                            }                                                       
                        }
                        if(count($nFieldSet) > 0){
                            $fieldsSet['fecha'] = $nFieldSet;
                            break;    
                        }                        
                    }elseif ($content == NULL) {
                        $fieldsSet['fecha'] = NULL;
                        break;
                    }
                    $fieldsSet['fecha'] = NULL;
                    break;
                case 'opPaciente':
                    if(is_numeric($content) && ($content >= 1)){
                        $fieldsSet['opPaciente'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['opPaciente'] = 0;
                        break;
                    }
                    $errorType = WRONG_PARAMETER;
                    $fieldsSet['opPaciente'] = STATISTICS_OPPACIENTE_WRONG_MSG;
                    $errorMsg = STATISTICS_OPPACIENTE_WRONG_MSG;
                    break;
                //Revisar el Mensaje de error. Agregado por Duva
                case 'opSexo':
                    if(is_numeric($content) && ($content > 0)){
                        $fieldsSet['opSexo'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['opSexo'] = 0;
                        break;
                    }
                    $errorType = WRONG_PARAMETER;
                    $fieldsSet['opSexo'] = STATISTICS_OPPACIENTE_WRONG_MSG;
                    $errorMsg = STATISTICS_OPPACIENTE_WRONG_MSG;
                    break;
                //Revisar el Mensaje de error. Agregado por Duva
                case 'peso':
                	if(($content != NULL) && (is_array($content) && count($content <= 2 ))){
                	    $nFieldSet = array();                       
                	    $nCounter = 0;
                	    foreach($content as $nParam => $nContent){                            
                	        if(($nContent != NULL )){
                	            if((is_float($nContent) || is_numeric($nContent)) && ($nContent >= 1)){
                	                $nFieldSet[] = $nContent;
                	            }else{
                	                $errorType = WRONG_PARAMETER;                                  
                	                $nFieldSet[] = STATISTICS_DATE_WRONG_MSG;
                	                $errorMsg = STATISTICS_DATE_WRONG_MSG;
                	            }
                	        }                                                       
                	    }
                	    if(count($nFieldSet) > 0){
                	        $fieldsSet['peso'] = $nFieldSet;
                	        break;    
                	    }                        
                	}elseif ($content == NULL) {
                	    $fieldsSet['peso'] = NULL;
                	    break;
                	}
                	$fieldsSet['peso'] = NULL;
                	break;                    
                case 'paciente':
                    if(($content != NULL)){
                        //validate ID                        
                        $patient_id = $content["idPaciente"]; 
                        if(is_numeric($patient_id) && ($patient_id > 0)){                                                                                        
                            $patientObj = new Patient($this->conn);
                            //look wether the patient exists or not
                            $patient = $patientObj->getPatientActualInfo($patient_id, $hospital_id);                            
                            if($patient != NULL){
                                $fieldsSet['opPaciente'] = 1;
                                $fieldsSet["paciente"] = $patient;
                                break;
                            }
                        }else{
                            //invalid type of data
                            $nFieldSet = array();
                            $errorType = WRONG_PARAMETER;
                            $nFieldsSet["idPaciente"] = STATISTICS_PACIENTE_WRONG_MSG;    
                            $fieldsSet["paciente"] = $nFieldsSet;
                            $errorMsg = STATISTICS_PACIENTE_WRONG_MSG;
                            break;
                        }
                    }elseif ($content == NULL) {
                        $fieldsSet['paciente'] = NULL;
                        break;
                    }
                    $fieldsSet["paciente"] = NULL;
                    break;
                case 'propiedad':
                    if(is_numeric($content) && ($content >= 1)){
                        $fieldsSet['propiedad'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['propiedad'] = NULL;
                        break;
                    }
                    $errorType = WRONG_PARAMETER;
                    $fieldsSet['propiedad'] = STATISTICS_PROPIEDAD_WRONG_MSG;
                    $errorMsg = STATISTICS_PROPIEDAD_WRONG_MSG;
                    break;
                case 'genero':
                    if(is_string($content) && (strlen($content) < 4)){
                        $fieldsSet['genero'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['genero'] = NULL;
                        break;
                    }
                    $errorType = WRONG_PARAMETER;
                    $fieldsSet['genero'] = STATISTICS_GENERO_WRONG_MSG;
                    $errorMsg = STATISTICS_GENERO_WRONG_MSG;
                    break;
                case 'edad':
                    if(($content != NULL) && (is_array($content) && count($content <= 2 ))){
                        $nFieldSet = array();                       
                        $nCounter = 0;
                        foreach($content as $nParam => $nContent){                            
                            if(($nContent != NULL )){
                                if(is_numeric($nContent) && ($nContent >= 0)){
                                    $nFieldSet[] = $nContent;
                                }else{
                                    $errorType = WRONG_PARAMETER;                                    
                                    $nFieldSet[] = STATISTICS_EDAD_WRONG_MSG;
                                    $errorMsg = STATISTICS_EDAD_WRONG_MSG;
                                }
                            }                                                       
                        }
                        if(count($nFieldSet) > 0){
                            $fieldsSet['edad'] = $nFieldSet;
                            break;    
                        }                        
                    }elseif ($content == NULL) {
                        $fieldsSet['edad'] = NULL;
                        break;
                    }
                    $fieldsSet['edad'] = NULL;
                    break;
                case 'estatus':
                    if(is_numeric($content) && ($content > 0 && $content < 4)){
                        $fieldsSet['estatus'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['estatus'] = NULL;
                        break;
                    }                    
                    $errorType = WRONG_PARAMETER;
                    $fieldsSet['estatus'] = STATISTICS_ESTATUS_WRONG_MSG;
                    $errorMsg = STATISTICS_ESTATUS_WRONG_MSG;
                    break;
                //Revisar el Mensaje de error. Agregado por Duva
                case 'opTipoMedicamento':
                    if(is_numeric($content) && ($content >= 1)){
                        $fieldsSet['opTipoMedicamento'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['opTipoMedicamento'] = 0;
                        break;
                    }
                    $errorType = WRONG_PARAMETER;
                    $fieldsSet['opTipoMedicamento'] = STATISTICS_OPPACIENTE_WRONG_MSG;
                    $errorMsg = STATISTICS_OPPACIENTE_WRONG_MSG;
                    break;
                //Revisar mensaje de error, se puede recibir lo que sea y si no existe no pasa nada.
                case 'incidencia':
                    if($content != NULL){
                        $fieldsSet['incidencia'] = $content;     
                        break;
                    }elseif ($content == NULL) {
                        $fieldsSet['genero'] = NULL;
                        break;
                    }
                    $errorType = WRONG_PARAMETER;
                    $fieldsSet['incidencia'] = STATISTICS_GENERO_WRONG_MSG;
                    $errorMsg = STATISTICS_GENERO_WRONG_MSG;
                    break;
                default:
                    # code...
                    break;
            }            
        }

        if($errorType == NULL){
            $accion = null;
            //call engine               
            $result = $engine->reportesAdministrador($hospital_id, $fieldsSet);
            
            if(isset($result['error']))
            {
                // Failed to update warning                  
                array_push($response['estadisticas'], $result);
                return $util->armStandardResponseWMsg(CONTROL_FAILURE , $response, CONTROL_FAILURE_MSG);
                
            }else {                    
                array_push($response['estadisticas'], $result); 
                return $util->armStandardResponseWMsg(STATISTICS_SUCCESSFUL_ANSWER, $response, STATISTICS_SUCCESSFUL_ANSWER_MSG);                        
            }
        }else{                
            return $util->armStandardResponseWMsg($errorType, $fieldsSet, $errorMsg); 
        }   

    }
    
}

?>
