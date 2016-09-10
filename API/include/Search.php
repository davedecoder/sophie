<?php

class Search{

private $conn;
    function __construct($con){
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        require_once dirname(__FILE__) . '/Patient.php';
        $this->conn = $con;

    }

    public function search($hospital_id, $fieldsSet){
        $util = new Utilities($this->conn);
        $validator = new Validations();
        $searchData = NULL;
        $searchData["estatus"] = NULL;         
        $fecha =  array();
        $fecha[0] = "";
        $searchData["fecha"] = $fecha;
        $searchData["genero"] = NULL;        
        $edad = array();
        $edad[0] = "";
        $searchData["edad"] = $edad;
        $searchData["identificador"] = NULL; 
        $searchData["idServicio"] = NULL;
        $searchData["nombre"] = NULL;
        $validObj = TRUE;                 
        $alert = FALSE;                       
        //check if the patient exists
        foreach ($fieldsSet as $obj => $fields){
                foreach($fields as $field => $value){
                    switch ($field) {
                        case 'estatus':                                              
                            if(empty($value)){
                                //launch alert
                                $searchData['estatus'] = 0;
                                break;
                            }else if((is_numeric($value) && ($value >= 0))){                                                            
                                $searchData['estatus'] = $value;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData['estatus'] = NOT_VALID_FIELD_LBL;
                            break;
                        case 'fecha_1':                    
                            if(empty($value)){
                                //launch Alert
                                $fecha[0] = "";
                                $searchData['fecha']= $fecha;
                                break;
                            }else if(is_string($value)){
                                $fecha[1] = $value;
                                $searchData['fecha']= $fecha;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData['fecha_1'] = NOT_VALID_FIELD_LBL;
                            break;
                        case 'fecha_2':                    
                            if(empty($value)){
                                //launch Alert
                                $fecha[0] = "";
                                $searchData['fecha']= $fecha;
                                break;
                            }else if(is_string($value)){
                                $fecha[2] = $value;
                                $searchData['fecha']= $fecha;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData['fecha_2'] = NOT_VALID_FIELD_LBL;
                            break;
                        case 'genero': 
                            if(empty($value)){
                                //launch alert
                                $searchData['genero'] = 0;
                                break;
                            }else if((is_numeric($value) && ($value >= 0)) || (is_string($value))){
                                $searchData["genero"] = $value; 
                                break;
                            }
                            $validObj = FALSE;
                            $searchData["genero"] = NOT_VALID_FIELD_LBL;
                            break;
                        case "identificador":
                            if(empty($value)){
                                $searchData["identificador"] = "";
                                break;
                            }else if(is_numeric($value) && ($value >= 0)){
                                $searchData["identificador"] = $value;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData["identificador"] = NOT_VALID_FIELD_LBL;
                            break;
                        case 'edad_1':                    
                            if(empty($value)){
                                //launch Alert
                                $edad[0] = "";
                                $searchData['edad'] = $edad;
                                break;
                            }else if(is_numeric($value) && ($value >= 0)){
                                $edad[1] = $value;
                                $searchData['edad'] = $edad;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData['edad_1'] = NOT_VALID_FIELD_LBL;
                            break;
                        case 'edad_2':                    
                            if(empty($value)){
                                //launch Alert
                                $edad[0] = "";
                                $searchData['edad'] = $edad;
                                break;
                            }else if(is_numeric($value) && ($value >= 0)){
                                $edad[2] = $value;
                                $searchData['edad'] = $edad;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData['edad_2'] = NOT_VALID_FIELD_LBL;
                            break;
                        case "idServicio":
                            if(empty($value)){
                                $searchData["idServicio"] = 0;
                                break;
                            }else if(is_numeric($value) && ($value >= 0)){
                                $searchData["idServicio"] = $value;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData["idServicio"] = NOT_VALID_FIELD_LBL;
                            break;
                        case 'nombre':                    
                            if(empty($value)){
                                //launch Alert
                                $searchData['nombre'] = "";
                                break;
                            }else if(is_string($value)){
                                $searchData['nombre'] = $value;
                                break;
                            }
                            $validObj = FALSE;
                            $searchData['nombre'] = NOT_VALID_FIELD_LBL;
                            break;                
                        default:
                            # code...
                            break;
                    }
                }
            }
            if($validObj){

                
                $engine =  new Motor($this->conn);
                $result = $engine->busqueda($hospital_id, 
                                            $searchData["estatus"], 
                                            $searchData["fecha"], 
                                            $searchData["genero"], 
                                            $searchData["edad"], 
                                            $searchData["identificador"], 
                                            $searchData["idServicio"], 
                                            $searchData["nombre"]);
                $response = array();
                // Check for search answer
                if (isset($result["error"]) && $result["error"] != 100) {
                    // Failed to create user
                    $response["error"] =  $validObj;
                    $response["search"] = $searchData;
                    return $util->armStandardResponse(SEARCH_ENGINE_FAILURE, $response);
                } else {
                    // Search performed successfully
                    
                    $response["error"] =  !$validObj;
                    $response["search"] = $result;
                    return $util->armStandardResponse(SEARCH_SUCCESSFUL_ANSWER, $response);
                }
            }else{
                $searchData["error"] =  !$validObj;
                $searchData["alert"] = $alert;
                $response["search"] = $searchData;
                return $util->armStandardResponse(SEARCH_INVALID_FIELDS, $response);    
            }       

        return $util->armStandardResponse(500, NULL);
    }
    
}

?>
