<?php

class Alergy{

private $conn;
        function __construct($con){
            require_once dirname(__FILE__) . '/libs/Validations.php';
            require_once dirname(__FILE__) . '/engine/Motor.php';
            require_once dirname(__FILE__) . '/libs/Utilities.php';
            require_once dirname(__FILE__) . '/Patient.php';
            $this->conn = $con;

        }

        public function getAlergiesByClinicalData($clinicalData_id){
            $motor = new Motor($this->conn);
            $util = new Utilities($this->conn);
            $alergies = array();
            $response["alergies"] =  array();
            $alergies = $motor->getAlergias($idPatient);
            $alergiesArray = array();
            if(count($alergies) > 0){
                if($alergies["error"] < 900){
                    $i = 0;
                    foreach($alergies as $item => $value){
                        if($value == 100){
                            $response["error"] = $value;
                            
                        }else{
                            array_push($alergiesArray, $value);
                        }
                        
                        $i++;
                    }
                    $response["alergies"] = ($alergiesArray);
                    return $util->armStandardResponse(ALERGIES_SUCCESSFUL_ANSWER, $response);
                }else if ($alergies["error"] == 102){
                    return $util->armStandardResponse(ALERGIES_PATIENT_NO_EXIST, $response);    
                }else if ($alergies["error"] == 101){
                    return $util->armStandardResponse(CONTROL_FAILURE, $response);    
                }
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }
        }

        public function getAlergies($idPatient){
            $motor = new Motor($this->conn);
            $util = new Utilities($this->conn);
            $alergies = array();
            $response["alergies"] =  array();
            $alergies = $motor->getAlergias($idPatient);
            $alergiesArray = array();
            if(count($alergies) > 0){
                if($alergies["error"] < 900){
                    $i = 0;
                    foreach($alergies as $item => $value){
                        if($value == 100){
                            $response["error"] = $value;
                            
                        }else{
                            array_push($alergiesArray, $value);
                        }
                        
                        $i++;
                    }
                    $response["alergies"] = ($alergiesArray);
                    return $util->armStandardResponse(ALERGIES_SUCCESSFUL_ANSWER, $response);
                }else if ($alergies["error"] == 102){
                    return $util->armStandardResponse(ALERGIES_PATIENT_NO_EXIST, $response);    
                }else if ($alergies["error"] == 101){
                    return $util->armStandardResponse(CONTROL_FAILURE, $response);    
                }
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }
        }

        public function getAllAlergies(){
            $motor = new Motor($this->conn);
            $util = new Utilities($this->conn);
            $alergies = array();
            $response["alergies"] =  array();
            $alergies = $motor->alergias();
            $alergiesArray = array();
            if(count($alergies) > 0){
                if($alergies["error"] == false){
                    $i = 0;
                    foreach($alergies as $item => $value){
                        if($value == false){
                            $response["error"] = $value;                            
                        }else{
                            array_push($alergiesArray, $value);
                        }                        
                        $i++;
                    }
                    $response["alergies"] = ($alergiesArray);
                    return $util->armStandardResponse(ALERGIES_SUCCESSFUL_ANSWER, $response);
                }else {
                    return $util->armStandardResponse(ALERGIES_NO_EXIST, $response);    
                }
            }else{
                return $util->armStandardResponse(CONTROL_FAILURE, $response);
            }
        }

////////////////////
//// UNLINK ALERGIES FROM PATIENT'S PROFILE
//////////////////////

        public function unlinkAlergies($patient_id, $hospital_id, $readyAlergies){
            $patientObj = new Patient($this->conn);
            $util = new Utilities($this->conn);
            $engine = new Motor($this->conn);
            $patient = $patientObj->getPatient($patient_id, $hospital_id);       
            $alergiesDone = array();
            if($patient != NULL){
                foreach ($readyAlergies as $key => $alergy) {
                    if(isset($alergy["idAlergia"]) && (is_numeric($alergy["idAlergia"]))){
                        if($engine->unlinkAlergy($patient_id, $alergy["idAlergia"])){
                            $alergiesDone[] = $alergy;
                        }
                    }else{
                        $alergy["idAlergia"] = NOT_VALID_FIELD_LBL;
                        $alergiesDone[] = $alergy;
                    }
                }      
                $response["alergias"] = $alergiesDone; 
                if(count($alergiesDone) > 0){
                    //RETURN SUCCESS
                    return $util->armStandardResponse(ALERGIES_SUCCESSFUL_ANSWER, $response);
                }else{
                    //RETURN FAILURE
                    return $util->armStandardResponse(CONTROL_FAILURE, $response);
                }
            }else{
                //SUCH PATIENT DOES NOT EXISTS IN THIS HOSPITAL
                return $util->armStandardResponse(ALERGIES_PATIENT_NO_EXIST, $response);
            }
        }


		public function createAllergy($allergy) 
		{
			$arr = array();
			if(is_string($allergy))
			{
				if (strlen($allergy)>0 && strlen($allergy)<=45) 
				{
					$aux1 = "%".$allergy."%";
					$stmt = $this->conn->prepare("select idAlergia, nombreAlergia from Alergia where nombreAlergia like ?");
					$stmt->bind_param("s",$aux1);
					if($stmt->execute())
					{
						$stmt->store_result();
						if($stmt->num_rows() > 0)
						{
							$poss = array();
							$stmt->bind_result($aux, $aux1);
							while($stmt->fetch())
							{
								$res["idAlergia"] = $aux;
								$res["nombreAlergia"] = $aux1;
								$poss[] = $res;
							}
							$stmt->close();
							for ($i = 0; $i<count($poss); $i++)
							{
								if(strcmp(strtoupper($poss[$i]["nombreAlergia"]), strtoupper($allergy)) == 0)								
								{
									$arr[] = $poss[$i];
									$arr["error"] = 103;
									return $arr;
								}
							}
						}else
							$stmt->close();
						$stmt = $this->conn->prepare("insert into Alergia values(null, ?)");
						$stmt->bind_param("s",$allergy);						
						if($stmt->execute())
						{						
							$aux["idAlergia"] = $stmt->insert_id;
							$aux["nombreAlergia"] = $allergy;
							$stmt->close();
							$arr[] = $aux;
							$arr["error"] = 100;
						}
						else
							$arr["error"]=104;
					}
				}else
					$arr["error"] = 102;
			}else
				$arr["error"] = 101;
			return $arr;
		}

        public function createAlergiesCollection($patient_id, $hospital_id, $objs){
            $patientObj = new Patient($this->conn);
            $util = new Utilities($this->conn);
            $patient = $patientObj->getPatient($patient_id, $hospital_id);       
            $alergies = array();
            if($patient != NULL){  
                $alergyName = "";
                $alergy_id = "";                      
                $validationError = FALSE;
                $alert = FALSE;
                foreach ($objs as $obj => $fields){        
                    $validObj = TRUE;   
                    foreach($fields as $field => $value){                        
                        switch ($field) {
                            case 'nombreAlergia':
                                    if(empty($value) || !is_string($value)){
                                        $alert = TRUE;
                                        $validObj = FALSE;
                                        $alergyName = NOT_VALID_FIELD_LBL;                                        
                                        break;
                                    }
                                    $alergyName = $value;                                    
                                break;
                            case 'idAlergia':
                                    if(empty($value)){
                                        $alert = TRUE;
                                        $validObj = FALSE;
                                        $alergy_id = NOT_VALID_FIELD_LBL;
                                        break;
                                    }
                                    $alergy_id = $value;                                    
                                break;
                            default:                        
                                break;
                        }
                        if(!$validObj)   {
                            continue;
                        }
                    } 
                    $alergyObj["idPaciente"] = $patient_id;
                    $alergyObj["error"] = !$validObj;
                    $alergyObj["alert"] = FALSE;
                    $alergyObj['idAlergia'] = 0;
                    $alergyObj['nombreAlergia'] = "";
                    if($validObj){
                        //Do insertion
                        $stmt = $this->conn->prepare("INSERT INTO Alergia_Paciente(idPaciente, idAlergia) values(?,?)");
                        $stmt->bind_param("ii",$patient_id, $alergy_id);
                        $result2 = $stmt->execute();

                        $stmt->close();
                        // Check for successful insertion
                        if (!$result2) {                    
                            // Failed to create alergy
                            return $util->armStandardResponse(CONTROL_FAILURE, $alergies);
                        }
                        $alergyObj['idAlergia'] = $alergy_id;
                        $alergyObj['nombreAlergia'] = $alergyName;                
                    }
                    
                    array_push($alergies, $alergyObj);

                }
                if($validationError){
                    // User successfully inserted and those w errors
                    return $util->armStandardResponse(PFTSET_CREATE_OK_W_ERRORS, $alergies);    
                }else{
                    // User successfully inserted
                    return $util->armStandardResponse(ALERGYSET_CREATE_OK, $alergies);
                }            
            }else{
                return $util->armStandardResponse(ALERGYSET_IDPATIENT_INVALID, NULL);
            }
        }
}

?>