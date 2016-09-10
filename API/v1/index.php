<?php

require_once '../include/PassHash.php';
require_once '../include/conf/Config.php';
require_once '../include/Patient.php';
require_once '../include/ClinicalData.php';
require_once '../include/Interaction.php';
require_once '../include/Alergy.php';
require_once '../include/Meds.php';
require_once '../include/Auths.php';
require_once '../include/Warnings.php';
require_once '../include/Chemist.php';
require_once '../include/Search.php';
require_once '../include/conf/DbConnect.php';
require_once '../include/Suitability.php';
require_once '../include/Statistics.php';
require_once '../include/Hospital.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();
//require '../vendor/autoload.php';

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;
$hospital_id = NULL;
$myAppID = NULL; 
$temp_user_id;
$conn = NULL;
// opening db connection
$db = new DbConnect();
$conn = $db->connect();

/*
ARM RESPONSE METHOD
*/

function armResponse($error, $code, $msg, $body){
    $response = array();
    $response["error"] = $error;
    $response["code"] = $code;
    $response["message"] = $msg;
    $response["body"] = $body;

    return $response;
}

/**
*Add function to Identify the apllication and user login
*/

function identify(\Slim\Route $route){
    // Getting request headers
    $headers = apache_request_headers();    
    $response = array();
    $app = \Slim\Slim::getInstance();
    global $myAppID;
    global $temp_user_id;
    // Verifying Authorization Header
    if((isset($headers['Public-Key'])) && (isset($headers['User-Key']))) {
        $db = new Auths();

        // get the public key
        $publicKey = $headers['Public-Key'];
        // validating public key
        $appID = $db->isValidPublicKey($publicKey);
        if (!$usrID) {
            // public key is not present in apps table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid App";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            //Look for User to be authorized
            $user = $db->getUserBasicDataRegApp($usrID, $appID);
            if($user != NULL){
                $myAppID = $appID;                 
                $temp_user_id = $user["idQuimico"];
            }else{
                $response["error"] = true;
                $response["code"] = LOGIN_USER_NOT_EXIST;
                $response["message"] = "El usuario no existe";
                echoRespnse(400, $response);
                $app->stop();        
            }            
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["code"] = LOGIN_APP_NOT_EXIST;
        $response["message"] = "Credentials misssing";
        echoRespnse(400, $response);
        $app->stop();
    }

}

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();    
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Auth'])) {
        $db = new Auths();

        // get the api key
        $api_key = $headers['Auth'];
        // validating api key
        $usrID = $db->isValidApiKey($api_key);
        if (!$usrID) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            $user = $db->getUserBasicData($usrID);
            global $user_id;
            global $hospital_id;
            global $user_turn;
            $user_id = $user["idQuimico"];
            $hospital_id = $user["idHospital"];
            $user_turn = $user["idTurno"];
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/*----------------GENERAL METHODS ------------------------*/
/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {

        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["code"] = MISSING_OR_EMPTY_FIELD;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';        
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */

function utf8_converter($array)
{
    array_walk_recursive($array, function(&$item, $key){
        if(!mb_detect_encoding($item, 'utf-8', true)){
                $item = utf8_encode($item);
        }
    });
 
    return $array;
}

function echoRespnse($status_code, $response) {

    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');
    $response = utf8_converter($response);

    echo json_encode($response);
    
}

/////END GENERAL METHODS

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', 'identify', function() use ($app) {
            //Get temporary params
            global $temp_user_id;
            global $myAppID;            
            // check for required params
            
            verifyRequiredParams(array('username', 'password'));

            // reading post params
            $username = $app->request()->post('username');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            $res =  $db->checkNubaLogin($username, $password);
            if($res["code"] == LOGIN_SUCCSESFULLY){
                $response["error"] = false;
                $response["code"] = $res["code"];
                $response["message"] = "Login successfully";
                $response["body"] = $res["body"];
                echoRespnse(201, $response);
                return;
            }else if ($res["code"] == LOGIN_FALSE) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "username or password incorrects";
                $response["body"] = $res["body"];
                echoRespnse(200, $response);
                return;
            }else if ($res["code"] == CONTROL_FAILURE){
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Sorry something went wrong please try later";
                $response["body"] = $res["body"];
                echoRespnse(200, $response);
                return;
            }

            echoRespnse(200, $response);
        });



/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
//ACTIVATE CHEMIST
$app->put('/chemist/:chemist_id/activate/', 'authenticate', function($chemist_id) use($app) {
                
        global $user_id;      
        global $hospital_id;
        global $conn;

        $db = new Chemist($conn);
        $response = array();

        $nParams = array();
        $nParams["idTurno"] = 1;
        // updating task
        $result = $db->updateChemist($chemist_id, $hospital_id, $nParams);

        if ($result["code"] == USR_UPDATE_OK) {
            // task updated successfully
            $response["error"] = false;
            $response["code"] = $result["code"];
            $response["message"] = "Quimico activado exitosamente";
            $response["body"] = $result["body"];
        } else if($result["code"] == USR_UPDATE_ID_NOT_FOUND){
            // task failed to update
            $response["error"] = true;
            $response["code"] = $result["code"];
            $response["message"] = "Quimico no encontrado";
            $response["body"] = $result["body"];
        } else if($result["code"] == USR_UPDATE_FAILED){
            // task failed to update
            $response["error"] = true;
            $response["code"] = $result["code"];
            $response["message"] = "Chemist failed to update";
            $response["body"] = $result["body"];
        }  else if($result["code"] == USR_UPDATE_FAILED_REVIEW_INVALID_FIELDS){
            // task failed to update
            $response["error"] = true;
            $response["code"] = $result["code"];
            $response["message"] = "Chemist failed to update invalid fields present";
            $response["body"] = $result["body"];
        } 
        echoRespnse(200, $response);
    });

//DEACTIVATE CHEMIST
$app->put('/chemist/:chemist_id/deactivate/', 'authenticate', function($chemist_id) use($app) {
                
        global $user_id;      
        global $hospital_id;
        global $conn;

        $db = new Chemist($conn);
        $response = array();

        $nParams = array();
        $nParams["idTurno"] = 4;
        // updating task
        $result = $db->updateChemist($chemist_id, $hospital_id, $nParams);

        if ($result["code"] == USR_UPDATE_OK) {
            // task updated successfully
            $response["error"] = false;
            $response["code"] = $result["code"];
            $response["message"] = "Quimico desactivado exitosamente";
            $response["body"] = $result["body"];
        } else if($result["code"] == USR_UPDATE_ID_NOT_FOUND){
            // task failed to update
            $response["error"] = true;
            $response["code"] = $result["code"];
            $response["message"] = "Quimico no encontrado";
            $response["body"] = $result["body"];
        } else if($result["code"] == USR_UPDATE_FAILED){
            // task failed to update
            $response["error"] = true;
            $response["code"] = $result["code"];
            $response["message"] = "Chemist failed to update";
            $response["body"] = $result["body"];
        } else if($result["code"] == USR_UPDATE_FAILED_REVIEW_INVALID_FIELDS){
            // task failed to update
            $response["error"] = true;
            $response["code"] = $result["code"];
            $response["message"] = "Chemist failed to update invalid fields present";
            $response["body"] = $result["body"];
        } 
        echoRespnse(200, $response);
    });

$app->post('/chemist/', function() use ($app){

    global $conn;
    $validFields = array(
        'nombreQuimico', 
        'apellidoPaternoQuimico', 
        'apellidoMaternoQuimico', 
        'loginQuimico', 
        'password', 
        'emailQuimico', 
        'telefonoQuimico',  
        'extensionQuimico',  
        'idHospital',  
        'idTurno');
    $response = array();
    $fieldsSet = array();
    $request_body =  array();
    $request_body = $app->request->getBody();
    $myChemist = array();
    if(count($request_body) > 0){      
        $jsonBodyAsArray = json_decode($request_body, TRUE);    
        foreach($validFields as $validField => $fieldValue){                
            if(!isset($jsonBodyAsArray[$fieldValue])){

                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = 'Field ' . $fieldValue . ' is not present';        
                echoRespnse(400, $response);
                $app->stop();
            }else{
                $myChemist[$fieldValue] = $jsonBodyAsArray[$fieldValue];
            }
        }

        validateEmail($myChemist["emailQuimico"]);
        
        $db = new Chemist($conn);
        $res = $db->createUserChemist($myChemist["nombreQuimico"], $myChemist["apellidoPaternoQuimico"], $myChemist["apellidoMaternoQuimico"], $myChemist["loginQuimico"], $myChemist["password"], $myChemist["emailQuimico"], $myChemist["telefonoQuimico"], $myChemist["extensionQuimico"], $myChemist["idHospital"], $myChemist["idTurno"]);            
        if($res["code"] == USER_CREATED_SUCCESSFULLY){
            $response["error"] = false;
            $response["message"] = "You are successfully registered";
            $response["body"] = $res["body"];
            echoRespnse(201, $response);
        }else if($res == USER_CREATE_FAILED){
            $response["error"] = true;
            $response["message"] = "Oops! An error occurred while registereing";
            echoRespnse(200, $response);
        }else if($res == USER_ALREADY_EXISTED){
            $response["error"] = true;
            $response["message"] = "Sorry this user already existed";
            echoRespnse(200, $response);
        }else if($res == USER_TELEPHONE_WRONG){
            $response["error"] = true;
            $response["message"] = "Please insert a valid phone number";
            echoRespnse(200, $response);
        }else if($res == USER_EXTENSION_WRONG){
            $response["error"] = true;
            $response["message"] = "Please insert a valid extension";
            echoRespnse(200, $response);
        }else if($res == HOSPITAL_ID_DOES_NOT_EXIST){
            $response["error"] = true;
            $response["message"] = "Please insert a valid hospital id";
            echoRespnse(200, $response);
        }else if($res == SHIFT_ID_DOES_NOT_EXIST){
            $response["error"] = true;
            $response["message"] = "Please insert a valid shift id";
            echoRespnse(200, $response);
        }       
    }else{
        $response["error"] = true;
        $response["message"] = "Please provide a valid Json";
        echoRespnse(200, $response);
    }
    
    

});

//GET Active CHEMISTS
$app->get('/chemist/active/', 'authenticate', function() use ($app) {
    global $user_id;
    global $hospital_id;
    global $conn;

    $response = array();
    $db = new Chemist($conn);

    $res = $db->getAactiveChemists($hospital_id);
    
    if($res["code"] == CA_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "Todos los quimicos actualmente activos en la institucion";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
        return;
    } else if($res["code"] == CA_SUCCESSFUL_ANSWER_NO_ACTIVE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "Actualmente no hay quimicos activos";
        $response["body"] = $res["body"];
        echoRespnse(200, $response);
        return;
    }else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
        return;
    }
    echoRespnse(200, NULL); 
return; 
});

//GET ALL CHEMISTS
$app->get('/chemist/all/', 'authenticate', function() use ($app) {
    global $user_id;
    global $hospital_id;
    global $conn;

    $response = array();
    $db = new Chemist($conn);

    $res = $db->getALLChemists($hospital_id);
    
    if($res["code"] == CA_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "Todos los quimicos actualmente en la institucion";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
        return;
    } else if($res["code"] == CA_SUCCESSFUL_ANSWER_NO){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "Actualmente no hay quimicos en esta institucion";
        $response["body"] = $res["body"];
        echoRespnse(200, $response);
        return;
    }else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
        return;
    }
    echoRespnse(200, NULL); 
return; 
});


/*-------------------- Register Hospital ------------------------*/
$app->post('/hospital', function() use ($app){
    // check for required params
            global $conn;
            $validFields = array('nombreHospital', 
                                        'calleHospital',
                                        'numeroExteriorHospital',
                                        'numeroInteriorHospital',
                                        'coloniaHospital',
                                        'telefonoHospital',
                                        'urlHospital',
                                        'rfcHospital',
                                        'idGrupo');

            $response = array();
            $fieldsSet = array();
            $request_body =  array();
            $request_body = $app->request->getBody();
            $myHospital = array();
            if(count($request_body) > 0){
                $jsonBodyAsArray = json_decode($request_body, TRUE);
                foreach($validFields as $validField => $fieldValue){                
                    if(!isset($jsonBodyAsArray[$fieldValue])){

                        $response["error"] = true;
                        $response["code"] = 901;
                        $response["message"] = 'Field ' . $fieldValue . ' is not present';        
                        echoRespnse(400, $response);
                        $app->stop();
                    }else{
                        $myHospital[$fieldValue] = $jsonBodyAsArray[$fieldValue];
                    }
                }
                
            }else{
                $response["error"] = true;
                $response["message"] = "Please provide a valid Json";
                echoRespnse(200, $response);
            }

            $db = new Hospital($conn);

            $res = $db->createHospital($myHospital);
            
            if ($res["code"] == USR_CREATE_OK) {
                $response["error"] = false;
                $response["message"] = "Hospital successfully registered";
                $response["body"] = $res["body"];
                // echo json response
                echoRespnse(201, $response);
                return;
            } else if ($res["code"] == USR_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
                $response["body"] = $res["body"];

            } else if ($res["code"] == ITEM_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, review your fields";
                $response["body"] = $res["body"];
            } else if ($res["code"] == USR_CREATE_FAILED_REVIEW_INVALID_FIELDS) {
                $response["error"] = true;
                $response["message"] = "Sorry, invalid fields found";
                $response["body"] = $res["body"];
            }
            // echo json response
            echoRespnse(200, $response);
            return;
});



/*-------------------- Register Group ------------------------*/

$app->post('/group', function() use ($app) {
    // check for required params
    global $conn;
    $validFields = array('nombreGrupo', 
                        'urlGrupo'
                        );

    $response = array();
    $fieldsSet = array();
    $request_body =  array();
    $request_body = $app->request->getBody();
    $myGroup = array();
    if(count($request_body) > 0){
        $jsonBodyAsArray = json_decode($request_body, TRUE);
        foreach($validFields as $validField => $fieldValue){                
            if(!isset($jsonBodyAsArray[$fieldValue])){

                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = 'Field ' . $fieldValue . ' is not present';        
                echoRespnse(400, $response);
                $app->stop();
            }else{
                $myGroup[$fieldValue] = $jsonBodyAsArray[$fieldValue];
            }
        }
        
    }else{
        $response["error"] = true;
        $response["message"] = "Please provide a valid Json";
        echoRespnse(200, $response);
    }

    $db = new Hospital($conn);

    $res = $db->createGroup($myGroup);
    
    if ($res["code"] == USR_CREATE_OK) {
        $response["error"] = false;
        $response["message"] = "Group successfully registered";
        $response["body"] = $res["body"];
        // echo json response
        echoRespnse(201, $response);
        return;
    } else if ($res["code"] == USR_CREATE_FAILED) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while registereing";
        $response["body"] = $res["body"];

    } else if ($res["code"] == ITEM_ALREADY_EXISTED) {
        $response["error"] = true;
        $response["message"] = "Sorry, group already exists";
        $response["body"] = $res["body"];
    } else if ($res["code"] == USR_CREATE_FAILED_REVIEW_INVALID_FIELDS) {
        $response["error"] = true;
        $response["message"] = "Sorry, invalid fields found";
        $response["body"] = $res["body"];
    }
    // echo json response
    echoRespnse(200, $response);
    return;
            
});


/*-------------------- Register Atention Areas ------------------------*/

$app->post('/hospital/:id_hospital/atentionAreas/', function($id_hospital) use ($app) {
    // check for required params
    global $conn;

    $response = array();
    $fieldsSet = array();
    $request_body =  array();
    $request_body = $app->request->getBody();
    $atentionAreas = array(); 
    if(count($request_body) > 0){                
        $jsonBodyAsArray = json_decode($request_body, true);
        if(isset($jsonBodyAsArray['areasDeAtencion'])){
            $atentionAreas = $jsonBodyAsArray['areasDeAtencion'];     
        }else{
            $response = array();
            $response["error"] = false;
            $response["message"] = "Json uncomplete";
            $response["body"] = $jsonBodyAsArray;
            // echo json response
            echoRespnse(201, $response);
            return;
        }        
    }else{
        $response["error"] = true;
        $response["message"] = "Please provide a valid Json";
        echoRespnse(200, $response);
    }

    $db = new Hospital($conn);
    
    $res = $db->setAtentionAreas($atentionAreas);
    $res = $db->joinAtentionAreasToHospital($id_hospital, $res["body"]["atentionAreas"]);
    
    if ($res["code"] == USR_CREATE_OK) {
        $response["error"] = false;
        $response["message"] = "Server Got Your Petition, check the answer";
        $response["body"] = $res["body"];
        // echo json response
        echoRespnse(201, $response);
        return;
    } 
    // echo json response
    echoRespnse(200, $response);
    return;
            
});
//---------------------register Patient -----------------------///

$app->post('/patient', 'authenticate', function() use ($app) {
            // check for required params
            global $hospital_id;
            global $conn;
            $request_body = array();
            $response = array();
            $fieldsSet = array();
            $validFields = array('nombrePaciente',
                                       'apellidoPaternoPaciente',
                                       'apellidoMaternoPaciente', 
                                       'nacimientoPaciente', 
                                       'sexoPaciente');

            $request_body = $app->request->getBody(); 
            
            if(count($request_body) > 0){
                $jsonBodyAsArray = json_decode($request_body, TRUE);
                foreach ($validFields as $field => $fieldValue) {
                    if(isset($jsonBodyAsArray[$fieldValue])){
                        $fieldsSet[$fieldValue] = $jsonBodyAsArray[$fieldValue];
                    }else{
                        $fieldsSet[$fieldValue] = NULL;
                    }
                }
            }

            $db = new Patient($conn);
            $res = $db->createPatient($hospital_id, $fieldsSet);

            if ($res["code"] == USR_CREATE_OK) {
                $response["error"] = false;
                $response["code"] = $res["code"];
                $response["message"] = "Patient successfully registered";
                $response["body"] = $res["body"];
                echoRespnse(201, $response);
                return;
            } else if ($res["code"] == USR_CREATE_OK_W_ALERTS) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "The object was registered but it generated some alerts";
                $response["body"] = $res["body"];

            } else if ($res["code"] == USR_ALREADY_EXIST) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Sorry, this Patient already existed";
                $response["body"] = $res["body"];
            } else if ($res["code"] == USR_CREATE_FAILED_REVIEW_INVALID_FIELDS) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Sorry, you have invalid fields please check the documentation";
                $response["body"] = $res["body"];
            } else if ($res["code"] == USR_CREATE_FAILED) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Oops!, there was a problem with your request";
                $response["body"] = $res["body"];
            } else if ($res["code"] == USR_FAILED_AUTHORIZATION) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Sorry, Authorization failed";
                $response["body"] = $res["body"];
            }
            // echo json response
            echoRespnse(200, $response);
        });


/*------------------UPDATING patient method ------------------------------*/
/**
 * Updating existing patient
 * method PUT
 * params --
 * url - /patients/:id
 */
$app->put('/patient/:id', 'authenticate', function($patient_id) use($app) {
            // reading post params

            $validParams =array("nombrePaciente",
                                "apellidoPaternoPaciente",
                                "apellidoMaternoPaciente",
                                "nacimientoPaciente",
                                "sexoPaciente",
                                "idEstatusPaciente",
                                "idEstatusVisita");
            $params =  array();
            $request_body = array();
            $request_body = $app->request->getBody();                        
            if(count($request_body) < 1){
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Oops!, there was a problem with your request";
                $response["body"] = $res["body"];
                echoRespnse(200, $response);
                return;
            }
            $jsonBody = json_decode($request_body);
            
            foreach ($jsonBody as $param => $value) {
                if(in_array($param, $validParams)){
                    // consider validations for each field
                    //Validations Missings

                    switch ($param){
                        case "nombrePaciente":
                            $params["nombrePaciente"] = $value;//$app->request->put('nombrePaciente');                            
                            break;
                        case "apellidoPaternoPaciente":
                            $params["apellidoPaternoPaciente"] = $value;//$app->request->put('apellidoPaternoPaciente');
                            break;
                        case "apellidoMaternoPaciente":
                            $params["apellidoMaternoPaciente"] = $value;//$app->request->put('apellidoMaternoPaciente');
                            break;
                        case "nacimientoPaciente":
                            $params["nacimientoPaciente"] = $value;//$app->request->put('nacimientoPaciente');
                            break;
                        case "sexoPaciente":
                            $params["sexoPaciente"] = $value;//$app->request->put('sexoPaciente');
                            break;
                        case "idEstatusPaciente":
                            $params["idEstatusPaciente"] = $value;//$app->request->put('idEstatusPaciente');
                            break;
                        case "idEstatusVisita":
                            $params["idEstatusVisita"] = $value;//$app->request->put('idEstatusVisita');
                            break;
                    }
                }else{
                    $response = array();
                    $response["error"] = true;
                    $response["message"] = 'Field ' . $param . ' is invalid';        
                    echoRespnse(400, $response);
                    $app->stop();
                }
            }

            global $user_id;      
            global $hospital_id;
            global $conn;

            $db = new Patient($conn);
            $response = array();

            // updating task
            $result = $db->updatePatient($patient_id, $hospital_id, $params);

            if ($result["code"] == USR_UPDATE_OK) {
                // task updated successfully
                $response["error"] = false;
                $response["code"] = $result["code"];
                $response["message"] = "Patient successfully updated";
                $response["body"] = $result["body"];
            } else if($result["code"] == USR_UPDATE_ID_NOT_FOUND){
                // task failed to update
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Patient failed to update ID not found. Please try again!";
                $response["body"] = $result["body"];
            } else if($result["code"] == USR_UPDATE_FAILED){
                // task failed to update
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Patient failed to update duplicate patients found";
                $response["body"] = $result["body"];
            }  else if ($res["code"] == USR_UPDATE_FAILED_REVIEW_INVALID_FIELDS) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Sorry, you have invalid fields please check the documentation";
                $response["body"] = $res["body"];
            }
            echoRespnse(200, $response);
        });

/*---------------------- Get the patient Fields ------------------*/

/**
 * Listing single patient of particual id
 * method GET
 * url /patient/:id
 * Will return 404 if the patient doesn't belongs to users's hospital
 */
$app->get('/patient/:id', 'authenticate', function($patient_id) {
            global $user_id;
            global $hospital_id;
            global $conn;
            $response = array();
            $db = new Patient($conn);
            // fetch task
            $result = $db->getPatient($patient_id, $hospital_id);

            if ($result != NULL) {
                $patient["patient"] =  $result;                
                $response["error"] = false;
                $response["code"] = USR_FOUND_OK;
                $response["message"] = "The server recerived your request";
                $response["body"]["patient"] = $patient["patient"];
                echoRespnse(200, $response);
            } else {
                $patient["patient"] =  $result;                
                $response["error"] = false;
                $response["code"] = USR_PATIENT_NOT_FOUND_OK;
                $response["message"] = "The requested resource doesn't exists";
                $response["body"]["patient"] = $patient["patient"];
                echoRespnse(404, $response);
            }
        });


/*---------------------- Get the last clinical data of a patient --------------------------*/
$app->get('/patient/:id/lastClinicalData', 'authenticate', function($patient_id) {
            
            global $hospital_id;
            global $conn;
            $response = array();
            $db = new ClinicalData($conn);
            // fetch task
            $result = $db->getLastClinicalData($patient_id, $hospital_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["code"] = CD_PETITION_RECEIVED;
                $response["message"] = "The server received your petition succesfully";
                $result["error"] = false;
                $response["body"]["clinical data"] = $result;
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["code"] = CD_NOT_EXISTS;
                $response["message"] = "The requested resource doesn't exists";
                $result["error"] = true;
                $response["body"]["clinical data"] = $result;                                
                echoRespnse(200, $response);
            }
        });
/*--------------------------------DELETE PATIENT METHOD -----------------------------------*/
/**
 * Deleting patient. Chemist can delete only their patients the ones who belong to theur hospitals
 * method DELETE
 * url /patients
 */
$app->delete('/patient/:id', 'authenticate', function($patient_id) use($app) {
        
            global $hospital_id;
            global $conn;
            $db = new Patient($conn);
            $response = array();
            $result = $db->deletePatient($patient_id, $hospital_id);
            if ($result["code"] == USR_DELETE_OK) {
                // task deleted successfully
                $response["error"] = false;
                $response["code"] = $result["code"];
                $response["message"] = "patient deleted succesfully";
            } else if($result["code"] == USR_PATIENT_NOT_FOUND_OK){
                // task failed to delete
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Patient not found";
            }else if($result["code"] == USR_DELETE_FAILED){
                // task failed to delete
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Patient failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/*-------------------------register Clinic Data ------------------------*/

$app->post('/clinicalData/:id', 'authenticate', function($patient_id) use ($app) {
            global $user_id;
            global $hospital_id;
            global $conn;
            $response = array();
            $fieldsSet = array();
            $request_body = array();
            // check for required params
            $validFields = (array('pesoDatosClinicos', 
                                       'tallaDatosClinicos',
                                       'ingresoDatosClinicos',
                                       'identificadorPacienteDatosClinicos', 
                                       'camaDatosClinicos', 
                                       'motivoDatosClinicos', 
                                       'diagnosticoDatosClinicos', 
                                       'doctorDatosClinicos', 
                                       'idServicio',
                                       'observacionDatosClinicos'
                                        ));
            $request_body = $app->request->getBody();//$_REQUEST;
            if(count($request_body) > 0){            
                $jsonBodyAsArray = json_decode($request_body, TRUE);
                $nCounter = 0;
                foreach ($validFields as $field => $fieldValue){
                    if(isset($jsonBodyAsArray[$fieldValue])){
                        $fieldsSet[$fieldValue] = $jsonBodyAsArray[$fieldValue];
                    }else{
                        $fieldsSet[$fieldValue] = NULL;
                    }                    
                }
            }
            $db = new ClinicalData($conn);
            $res = $db->createClinicalData($patient_id, $hospital_id, $fieldsSet);            
            if ($res["code"] == CD_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["code"] = $res["code"];
                $response["message"] = "Clinical Data successfully registered";
                $response["body"] = $res["body"];               
                echoRespnse(201, $response);
                return;
            } else if ($res["code"] == CD_PATIENT_NOT_EXISTS) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Oops! Patient does not exists";
                $response["body"] = $res["body"]; 

            }else if ($res["code"] == CD_CREATE_FAILED) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Sorry, there was a problem trying to create the record please try again";
                $response["body"] = $res["body"]; 
            }else if ($res["code"] == CD_INVALID_FIELDS) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Please Review the provided values some of them might be wrong ";
                $response["body"] = $res["body"]; 
            }else if ($res["code"] == CD_CREATE_OK_W_ALERTS) {
                $response["error"] = false;
                $response["code"] = $res["code"];
                $response["message"] = "Your records were created but generated some warnings";
                $response["body"] = $res["body"]; 
            }
            // echo json response
            echoRespnse(200, $response);
        });

/*------------------UPDATING clinical Data method ------------------------------*/
/**
 * Updating patient's clinical data
 * method PUT
 * params --
 * url - /clinicalsData/:id
 */
$app->put('/clinicalData/:id', 'authenticate', function($clinicalData_id) use($app) {
            // reading post params            
            global $hospital_id;
            global $conn;
            global $user_id;
            $response = array();
            $fieldsSet = array();
            $request_body = array();
            //check for required params
            $validParams = array('pesoDatosClinicos',
                                 'ingresoDatosClinicos',
                                 'tallaDatosClinicos',                                 
                                 'identificadorPacienteDatosClinicos', 
                                 'camaDatosClinicos', 
                                 'motivoDatosClinicos', 
                                 'diagnosticoDatosClinicos', 
                                 'observacionDatosClinicos',
                                 'doctorDatosClinicos', 
                                 'idServicio');
            $request_body = $app->request->getBody();            
            if(count($request_body) < 1){
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Oops!, there was a problem with your request";
                $response["body"] = $res["body"];
                echoRespnse(200, $response);
                return;
            }
            $jsonBody = json_decode($request_body, TRUE);

            foreach($validParams as $params => $param){
                if(isset($jsonBody[$param])){
                    $fieldsSet[$param] = $jsonBody[$param];
                }
            } 

            $db = new ClinicalData($conn);
            $response = array();
            // updating task
            $result = $db->updateClinicalData($clinicalData_id, $hospital_id, $user_id, $fieldsSet);
            
            if ($result["code"] == CD_UPDATE_OK) {
                // task updated successfully
                $response["error"] = false;
                $response["code"] = $result["code"];
                $response["message"] = "Clinical Data updated successfully";
                $response["body"] = $result["body"];
            } else if($result["code"] == CD_UPDATE_ID_NOT_FOUND){
                // task failed to update
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Clinical Data failed to update ID not found. Please try again!";
                $response["body"] = $result["body"];
            } else if($result["code"] == CD_UPDATE_FAILED){
                // task failed to update
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Clinical Data failed to update. Please try again!";
                $response["body"] = $result["body"];
            } else if($result["code"] == CD_UPDATE_OK_W_ALERTS){
                // task failed to update
                $response["error"] = false;
                $response["code"] = $result["code"];
                $response["message"] = "Clinical Data updated successfully but with some warnings!";
                $response["body"] = $result["body"];
            } else if($result["code"] == CD_INVALID_FIELDS){
                // task failed to update
                $response["error"] = false;
                $response["code"] = $result["code"];
                $response["message"] = "Por favor revise los campos invalidos!";
                $response["body"] = $result["body"];
            }
            echoRespnse(200, $response);
        });

/*---------------------- Get the clinicalData Fields ------------------*/

/**
 * Listing single clinical data of particual id
 * method GET
 * url /clinicalsData/:id
 * Will return 404 if the clinicalsData doesn't belongs to users's hospital
 */
$app->get('/clinicalData/:id', 'authenticate', function($clinicalData_id) {
            
            global $hospital_id;
            global $conn;
            $response = array();
            $db = new ClinicalData($conn);
            // fetch task
            $result = $db->getClinicalData($clinicalData_id, $hospital_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["code"] = CD_PETITION_RECEIVED;
                $response["message"] = "The server received your petition succesfully";
                $result["error"] = false;
                $response["body"]["clinicalData"] = $result;
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["code"] = CD_NOT_EXISTS;
                $response["message"] = "The requested resource doesn't exists";
                $result["error"] = true;
                $response["body"]["clinicalData"] = $result;                                
                echoRespnse(200, $response);
            }
        });

/*--------------------------------DELETE CLINICAL DATA METHOD -----------------------------------*/
/**
 * Deleting clinical data. Chemists can delete only their patient's clinical data the ones who belong to theur hospitals
 * method DELETE
 * url /clinicalsData
 */
$app->delete('/clinicalData/:id', 'authenticate', function($clinicalData_id) use($app) {
        
            global $hospital_id;
            global $conn;
            $db = new ClinicalData($conn);
            $response = array();
            $result = $db->deleteClinicalData($clinicalData_id, $hospital_id);
            if ($result["code"] == CD_DELETE_OK) {
                // task deleted successfully
                $response["error"] = false;
                $response["code"] = $result["code"];
                $response["message"] = "Clinial Data deleted succesfully";
            } else if($result["code"] == CD_NOT_FOUND){
                // task failed to delete
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Clinial Data not found";
            }else if($result["code"] == CD_DELETE_FAILED){
                // task failed to delete
                $response["error"] = true;
                $response["code"] = $result["code"];
                $response["message"] = "Clinial Data failed to delete. Clinial Data try again!";
            }
            echoRespnse(200, $response);
        });

/*-----------------------------Alergy Methods -------------------------*/

$app->post('/alergy/:patient_id', 'authenticate', function($patient_id) use ($app) {
            // check for required params
            global $hospital_id;
            global $conn;
            $fieldsSet = array();
            $validParams = array("alergias");
            $validFields = array("nombreAlergia",
                                 "idAlergia"
                                );
            $response = array();
            $request_body = array();
            $request_body = $app->request->getBody();//$_REQUEST;
            if(count($request_body) > 0){
                $jsonBody = json_decode($request_body);

                foreach ($jsonBody as $param => $objs) {
                   if(in_array($param, $validParams)){                    
                        foreach ($objs  as $content => $fields) { 
                            foreach ($fields as $field => $fieldValue){
                                if(!in_array($field, $validFields)){
                                    $response["error"] = true;
                                    $response["code"] = 901;
                                    $response["message"] = 'Field ' . $field . ' is invalid';        
                                    echoRespnse(400, $response);
                                    $app->stop();
                                }                                  
                            } 
                            array_push($fieldsSet, $fields);
                        }                        
                   }else{
                        $response["error"] = true;
                        $response["code"] = 901;
                        $response["message"] = 'Param ' . $param . ' is invalid';        
                        echoRespnse(400, $response);
                        $app->stop();
                        
                   }                
                }
                $db = new Alergy($conn);
                $res = $db->createAlergiesCollection($patient_id, $hospital_id, $fieldsSet);
                if($res["code"] == ALERGYSET_CREATE_OK){
                    $response["error"] = false;
                    $response["code"] = $res["code"];
                    $response["message"] = "Alergies set succesfully registered";               
                    $response["body"] = $res["body"];
                    echoRespnse(201, $response);
                    return;
                }else if($res["code"] == ALERGYSET_IDPATIENT_INVALID){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "Invalid id of Patient registered";
                    $response["body"] = $res["body"];
                }else if($res["code"] == ALERGYSET_REVIEW_ALERGY){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "Invalid id Alergy format";
                    $response["body"] = $res["body"];
                }else if($res["code"] == CONTROL_FAILURE){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "there was a problem only this alergies could be POSTED";
                    $response["body"] = $res["body"];
                }
                // echo json response
                echoRespnse(200, $response);
                
            }else{
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = 'Param ' . $param . ' is invalid';        
                echoRespnse(400, $response);
                $app->stop();    
            }
            return;
                    
        });

$app->get('/alergy/:patient_id', 'authenticate', function($patient_id) use ($app) {
    global $conn;
    $db = new Alergy($conn);
    $response = array();
    $res = $db->getAlergies($patient_id);
    if($res["code"] == ALERGIES_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == ALERGIES_PATIENT_NO_EXIST){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "The requested patient does not exist";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "there was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    }
});



//UNLINK PATIENTS ALERGY
$app->put('/patient/:patient_id/unlink/alergies/', 'authenticate', function($patient_id) use ($app) {
    global $conn;
    global $hospital_id;
    $db = new Alergy($conn);
    $response = array();
    $validParams = array("alergias");
    $validFields = array("idAlergia");
    $request_body = array();
    $readyAlergies = array();
    $request_body = $app->request->getBody();
    if(count($request_body) > 0){
        $jsonBody = json_decode($request_body,TRUE);
        $nCounter = 0;
        foreach ($validParams as $param => $valueParam) {
            $nCounter++;
            if($nCounter >= MAX_ITERATIONS){
                break;
            }
            if(isset($jsonBody[$valueParam])){
                $alergies = $jsonBody[$valueParam];
                foreach ($alergies as $key => $alergy) {
                    $readyAlergy = array();
                    foreach ($validFields as $key => $fieldValue) {
                        if(isset($alergy[$fieldValue])){
                            $readyAlergy[$fieldValue] = $alergy[$fieldValue];
                        }
                    }
                    $readyAlergies[] = $readyAlergy;
                }
            }
        }
    }
    $res = $db->unlinkAlergies($patient_id, $hospital_id, $readyAlergies);
    if($res["code"] == ALERGIES_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == ALERGIES_PATIENT_NO_EXIST){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "The requested patient does not exist";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "HUBO UN PROBLEMA CON EL SERVICIO ES POSIBLE QUE EL PACIENTE NO TENGA NINGUNA ALERGIA.";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    }
});

/*-----------------------------PFT METHODS ----------------------------*/
//////////////////
//////////////////
/// MEDS API CALLS
//////////////////
//////////////////
//////////////////
/// MEDS POST
//////////////////
$app->post('/meds/:patient_id', 'authenticate', function($patient_id) use ($app) {
            // check for required params
            global $hospital_id;
            global $user_id;
            global $conn;
            $medsSet = array();
            $validParams = array("medicamentos");
            $validFields = array(
                                'idPrincipio',
                               'concentracionDatosFarma', 
                               'cronicoDatosFarma', 
                               'inicioDatosFarma', 
                               'notaDatosFarma', 
                               'numeroAplicacionDatosFarma',
                               'idFrecuencia',
                               'idUnidad',
                               'idVia',
                               'idUso',
                               'idPresentacion',
                               'prescritoDatosFarma',
                               'capturaDatosFarma',
                               'idTipoPrescripcion',
                               'idTipoMedicamento'
                               );
            $response = array();
            $request_body = array();
            $request_body = $app->request->getBody();//$_REQUEST;
            if(count($request_body) > 0){
                $jsonBody = json_decode($request_body, TRUE);
                $counter = 0;
                foreach ($jsonBody as $param => $objs) {
                    $counter++;
                    if($counter >= MAX_ITERATIONS){
                        break;
                    }
                   if(in_array($param, $validParams)){
                        $nCounter = 0;
                        foreach ($objs  as $content => $fields){
                            $nCounter++;
                            if($counter >= MAX_ITERATIONS){
                                break;
                            }
                            $meds = array();
                            foreach ($validFields as $key => $value) {
                                if(isset($fields[$value])){
                                    $meds[$value] = $fields[$value];
                                }else{
                                    $meds[$value] = NULL;
                                }
                            }
                            $medsSet[] = $meds;
                        }                        
                   }else{
                        $response["error"] = true;
                        $response["code"] = 901;
                        $response["message"] = 'Param ' . $param . ' is invalid';        
                        echoRespnse(400, $response);
                        $app->stop();
                        
                   }                
                }
                //pass it to the dbHandler
                $db = new Meds($conn);
                $res = $db->createMedsSet($patient_id, $hospital_id, $user_id, $medsSet);                                
                if($res["code"] == PFTSET_NOT_VALID){                    
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "Invalid fields detected"; 
                    $response["body"] = $res["body"];
                    echoRespnse(201, $response);
                    return;
                }
                else if($res["code"] == PFTSET_CREATE_OK){                    
                    $response["error"] = false;
                    $response["code"] = $res["code"];
                    $response["message"] = "PFT set succesfully registered";        
                    $response["body"] = $res["body"];
                    echoRespnse(201, $response);
                    return;
                }else if($res["code"] == PFTSET_PATIENT_NOT_EXISTS){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "Invalid id of Patient registered";
                    $response["body"] = $res["body"];
                }else if($res["code"] == PFTSET_CLINICALDATA_NOT_EXISTS){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "Invalid id of Clinical Data ";
                    $response["body"] = $res["body"];
                }else if($res["code"] == CONTROL_FAILURE){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "there was a problem with the server";
                    $response["body"] = $res["body"];
                }else if($res["code"] == PFTSET_CREATE_OK_W_WARNINGS){
                    $response["error"] = false;
                    $response["code"] = $res["code"];
                    $response["message"] = "there was meds lists with warnings";
                    $response["body"] = $res["body"];
                }
                // echo json response
                echoRespnse(200, $response);
                
            }else{
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = "Oops!, there was a problem with your request";
                echoRespnse(400, $response);
                $app->stop();    
            }
            return;
                    
        });

//////////////////
//////////////////
/// MEDS PUT
//////////////////
//We shall change this url
$app->put('/meds/:patient_id', 'authenticate', function($patient_id) use ($app) {
            // check for required params
            global $hospital_id;
            global $user_id;
            global $conn;
            $fieldsSet = array();
            $validParams = array("medicamentos");
            $validFields = array('idDatosFarma',
                                'concentracionDatosFarma', 
                               'cronicoDatosFarma', 
                               'inicioDatosFarma', 
                               'notaDatosFarma', 
                               'numeroAplicacionDatosFarma',
                               'idPrincipio',
                               'idFrecuencia',
                               'idUnidad',
                               'idVia',
                               'idUso',
                               'idPresentacion',
                               'prescritoDatosFarma',
                               'capturaDatosFarma');
            $response = array();
            $request_body = array();
            $request_body = $app->request->getBody();//$_REQUEST;
            if(count($request_body) > 0){
                $jsonBody = json_decode($request_body, TRUE);
                $counter = 0;
                foreach ($jsonBody as $param => $objs) {                    
                   if($counter >= MAX_ITERATIONS){
                    break;
                   }
                   $counter++;
                   if(in_array($param, $validParams)){
                        $nCounter = 0;
                        foreach ($objs  as $content => $fields) { 
                            if($nCounter >= MAX_ITERATIONS){
                                break;
                            }
                            $nCounter++;
                            $med = array();                       
                            foreach ($validFields as $field => $key){
                                if(isset($fields[$key])){
                                    $med[$key] = $fields[$key];
                                }
                            } 
                            array_push($fieldsSet, $med);
                        }
                   }else{
                        $response["error"] = true;
                        $response["code"] = 901;
                        $response["message"] = 'Param ' . $param . ' is invalid';        
                        echoRespnse(400, $response);
                        $app->stop();
                        
                   }                
                }
                //pass it to the dbHandler
                $db = new Meds($conn);
                $res = $db->updatePatientLastPrescription($patient_id, $hospital_id, $user_id, $fieldsSet);
                if($res["code"] == PFTUPDATE_CREATE_OK){
                    $response["error"] = false;
                    $response["code"] = $res["code"];
                    $response["message"] = "PFT set succesfully updated";               
                    $response["body"] = $res["body"];
                    echoRespnse(201, $response);
                    return;
                }else if($res["code"] == PFTUPDATE_PATIENT_NOT_EXISTS){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "Invalid id of Patient registered";
                    $response["body"] = $res["body"];
                }else if($res["code"] == PFTUPDATE_PRESCRIPTION_NOT_EXISTS){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "prescription does not exists";
                    $response["body"] = $res["body"];
                }else if($res["code"] == CONTROL_FAILURE){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "there was a problem with the server";
                    $response["body"] = $res["body"];
                }else if($res["code"] == PFTUPDATE_CREATE_OK_W_ERRORS){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "there was meds lists with erros only some could be UPDATED";
                    $response["body"] = $res["body"];
                }else if($res["code"] == PFTUPDATE_SYNTAX_ERROR){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "One or more important field keys are missing";
                    $response["body"] = $res["body"];
                }else if($res["code"] == PFTSET_NOT_VALID){
                    $response["error"] = true;
                    $response["code"] = $res["code"];
                    $response["message"] = "One or more important field keys are missing";
                    $response["body"] = $res["body"];
                }
                // echo json response
                echoRespnse(200, $response);
                
            }else{
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = "Oops!, there was a problem with your request";
                echoRespnse(400, $response);
                $app->stop();    
            }
            return;
                    
        });

$app->get('/patient/:patient_id/lastPrescription/', 'authenticate', function($patient_id) use ($app) {
    global $user_id;
    global $hospital_id;
    global $conn;
    $response = array();
    $db = new Meds($conn);
    // fetch task

    $res = $db->getPatientLastPrescription($patient_id, $hospital_id);
    
    if($res["code"] == MEDS_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == MEDS_PATIENT_NOT_EXIST){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "The requested patient does not exist";
        $response["body"] = $res["body"];
        echoRespnse(200, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    } else if($res["code"] == MEDS_NOT_EXIST){
        $response["error"] = false;
        $response["code"] = $res["code"];
        $response["message"] = "The patient do not have any prescription";
        $response["body"] = $res["body"];
        echoRespnse(200, $response);
    }   

});

//EXIT REPORT
$app->get('/patient/:patient_id/exitReport/', 'authenticate', function($patient_id) use ($app) {
    global $user_id;
    global $hospital_id;
    global $conn;
    $response = array();
    $db = new Meds($conn);
    // fetch task

    $res = $db->getExitReport($patient_id, $hospital_id);
    
    if($res["code"] == PFC_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "El servidor recibio su peticion";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == MEDS_PATIENT_NOT_EXIST){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "El paciente solicitado no existe";
        $response["body"] = $res["body"];
        echoRespnse(200, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    }

});

//SIGN OUT PATIENT
$app->put('/patient/:patient_id/exit/', 'authenticate', function($patient_id) use($app){
    global $conn;
    global $user_id;
    global $hospital_id;
    
    $db = new Patient($conn);
    
    $res = $db->signOutPatient($patient_id, $hospital_id, $user_id);    
    if($res["code"] == PATIENT_SUCCESSFUL_SIGNED_OUT){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "El paciente fue dado de alta con exito";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == PATIENT_ALREADY_SIGNED_OUT){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "El paciente ya habia sido dado de alta";
        $response["body"] = $res["body"];
        echoRespnse(500, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(500, $response);
    } else if($res["code"] == USR_PATIENT_NOT_FOUND_OK){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "El Paciente no existe";
        $response["body"] = $res["body"];
        echoRespnse(500, $response);
    }

});
////////////////

$app->get('/patient/all/hospitalized/', 'authenticate', function() use ($app) {
    
    global $user_id;
    global $hospital_id;
    global $conn;
    $response = array();
    $db = new Patient($conn);
    // fetch task

    $res = $db->getAllHospitalizedPatients($hospital_id);
    
    if($res["code"] == PAH_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
        return;
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
        return;
    }
    echoRespnse(200, NULL); 
return;
});

$app->get('/services', 'authenticate', function() use ($app) {    
    global $hospital_id;    
    $response = array();
	global $conn;    
    $mt = new Motor($conn);
    // fetch task
    
    $res = $mt->servicios($hospital_id);
    
    if($res["error"]==100){
        $response["error"] = FALSE;
        $response["code"] = $res["error"];
        $response["message"] = "The server received your petition";
        unset($res["error"]);
        $response["body"] = $res;        
        echoRespnse(200, $response);
    } else 
    {
        switch ($res["error"]) 
        {
            case 101:
                $response["message"] = "The hospital does not exist";
                break;
            case 102:
                $response["message"] = "The hospital id is wrong";
                break; 
            default:
                $response["message"] = "Something went wrong";
                break;           
        }        
        $response["error"] = true;
        $response["code"] = 900;                
        echoRespnse(200, $response);
    }   

});

$app->get('/alergy', 'authenticate', function() use ($app) {            
    global $conn;
    $db = new Alergy($conn);
    $response = array();
    $res = $db->getAllAlergies();
    if($res["code"] == ALERGIES_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == ALERGIES_NO_EXIST){
        $response["error"] = false;
        $response["code"] = $res["code"];
        $response["message"] = "The requested patient do not have any alergy";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "there was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    }  

});

$app->post('/alergy', 'authenticate', function() use ($app) {
	$response = array();
	global $conn;
	$al = new Alergy($conn);
	$fieldsSet = array();
	$validFields = (array('nombreAlergia'));	
    $request_body = $app->request->getBody(); 
    //echo "Field ";
    if(count($request_body) > 0){
        $jsonBody = json_decode($request_body);
        foreach ($jsonBody as $field => $objs) {

            if(!in_array($field, $validFields)){
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = 'Field ' . $field . ' is invalid';        
                echoRespnse(400, $response);
                $app->stop();                    
            }
        }
        array_push($fieldsSet, $jsonBody);
    }
	$res = $al->createAllergy($fieldsSet[0]->nombreAlergia);
	switch ($res["error"]) 
	{
		case 100:	$response["error"] = FALSE;
					$response["code"] = $res["error"];
					$response["message"] = "The allergy was created";
					unset($res["error"]);
					$response["body"] = $res;        
					echoRespnse(200, $response);
					break;	
		case 101:	$response["error"] = TRUE;
					$response["code"] = $res["error"];
					$response["message"] = "The allergy type is invalid";        
					echoRespnse(200, $response);
					break;
		case 102:	$response["error"] = TRUE;
					$response["code"] = $res["error"];
					$response["message"] = "The allergy name is too big";        
					echoRespnse(200, $response);
					break;
		case 103:	$response["error"] = FALSE;
					$response["code"] = $res["error"];
					$response["message"] = "The allergy already existed";
					unset($res["error"]);
					$response["body"] = $res;        
					echoRespnse(200, $response);
					break;
		default:	$response["error"] = TRUE;
					$response["code"] = 900;
					$response["message"] = "Something went wrong";        
					echoRespnse(200, $response);
					break;
	}	
});

$app->get('/medicine', 'authenticate', function() use ($app) {            
    $response = array();
    global $conn;    
    $mt = new Motor($conn);
    // fetch task
    
    $res = $mt->medicamentos();
    
    if(!$res["error"]){
        $response["error"] = FALSE;
        $response["code"] = $res["error"];
        $response["message"] = "The server received your petition";
        unset($res["error"]);
        $response["body"] = $res;        
        echoRespnse(200, $response);
    } else 
    {              
        $response["error"] = true;
        $response["code"] = 900;                
        $response["message"] = "Something went wrong";
        echoRespnse(200, $response);
    }   

});

$app->get('/opMedicine', 'authenticate', function() use ($app) {            
    $response = array();  
    global $conn;  
    $mt = new Motor($conn);
    $res = array();
    // fetch task
    
    $fre = $mt->opcionesFrecuencia();
    $via = $mt->opcionesVia();
    $con = $mt->opcionesConcentracion();
    $pre = $mt->opcionesPresentacion();
    $uso = $mt->opcionesUso();
    
    if(!$fre["error"])
    {
    	unset($fre["error"]);
    	$res["frecuencia"] = $fre;
    }
    if(!$via["error"])
    {
    	unset($via["error"]);
    	$res["via"] = $via;
    }
    if(!$con["error"])
    {
    	unset($con["error"]);
    	$res["unidad"] = $con;
    }
    if(!$pre["error"])
    {
    	unset($pre["error"]);
    	$res["presentacion"] = $pre;
    }
    if(!$uso["error"])
    {
    	unset($uso["error"]);
    	$res["uso"] = $uso;
    }
    if(count($res)>0)
    {
        $response["error"] = FALSE;
        $response["code"] = 100;
        $response["message"] = "The server received your petition";
        $response["body"] = $res;        
        echoRespnse(200, $response);
    } else 
    {              
        $response["error"] = true;
        $response["code"] = 900;                
        $response["message"] = "Something went wrong";
        echoRespnse(200, $response);
    }   

});


$app->get('/warnings/:patient_id', 'authenticate', function($patient_id) use($app){
    global $user_id;
    global $hospital_id;
    global $conn;
    $response = array();
    $db = new Warnings($conn);
    // fetch task

    $res = $db->getAlertMissing($patient_id, $hospital_id);
    
    if($res["code"] == ALERTS_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == ALERTS_PATIENT_NOT_EXIST){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "The requested patient does not exist";
        $response["body"] = $res["body"];
        echoRespnse(200, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    } 

});




$app->get('/warnings', 'authenticate', function() use($app){
    global $user_id;
    global $hospital_id;
    global $conn;
    $response = array();
    $db = new Warnings($conn);
    // fetch task

    $res = $db->getHospitalAlerts($hospital_id);
    
    if($res["code"] == ALERTS_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == ALERTS_HOSPITAL_NOT_EXIST){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "The requested Hospital does not exist";
        $response["body"] = $res["body"];
        echoRespnse(200, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    } 

});

$app->put('/warnings/:warning_id', 'authenticate', function($warning_id) use($app){
	global $conn;
	
	$db = new Warnings($conn);
	$request_body = $app->request->getBody(); 	
	$alerts = array();
	$validParams = array("alertas");
	$validFields = array('idAlerta',
	                    'idAccion',   
	                   'notaAlerta');
	if(count($request_body) > 0){
	                $jsonBody = json_decode($request_body);
	
	                foreach ($jsonBody as $param => $objs) {                    
	                   if(in_array($param, $validParams)){
	                        
	                        foreach ($objs  as $content => $fields) { 
	                        	$fieldsSet = array();                       
	                            foreach ($fields as $field => $fieldValue){
	                                if(in_array($field, $validFields)){
	                              		$fieldsSet[$field] = $fieldValue;     
	                                }
	                            }
	                            $alerts[] = $fieldsSet;	                            
	                        }                        
	                   }else{
	                        $response["error"] = true;
	                        $response["code"] = 901;
	                        $response["message"] = 'Param ' . $param . ' is invalid';        
	                        echoRespnse(412 , $response);
	                        $app->stop();
	                        
	                   }                
	                }
	}
	
	$res = $db->updateWarnings($alerts);	
	if($res["code"] == ALERTS_SUCCESSFUL_ANSWER){
	    $response["error"] = FALSE;
	    $response["code"] = $res["code"];
	    $response["message"] = "The server received your petition";
	    $response["body"] = $res["body"];
	    
	    echoRespnse(200, $response);
	} else if($res["code"] == CONTROL_FAILURE){
	    $response["error"] = true;
	    $response["code"] = $res["code"];
	    $response["message"] = "There was a problem with the server";
	    $response["body"] = $res["body"];
	    echoRespnse(500, $response);
	}             
});

//IDONEIDAD
//SET
$app->post('/suitability/prescriptionNumber/:prescription_number/clinicalData/:clinicalData_id', 'authenticate', function($prescription_number, $clinicalData_id) use ($app){
    global $hospital_id;
    global $user_id;
    global $conn; 
    $fieldsSet = array();
    $validParams = array("medicamentos");
    $validFields = array(
                          'idDatosFarma',
                          'I1DatosFarma',
                          'I2DatosFarma',
                          'I3DatosFarma',
                          'I4DatosFarma',
                          'I5DatosFarma',
                          'I6DatosFarma',
                          'I7DatosFarma',
                          'I8DatosFarma',
                          'notaIdoneidad');
    $response = array();
    $request_body = array();
    $request_body = $app->request->getBody();
    if(count($request_body) > 0){
        $jsonBody = json_decode($request_body);

        foreach ($jsonBody as $param => $objs) {
           if(in_array($param, $validParams)){
                foreach ($objs  as $content => $fields) {
                    foreach($validFields as $validField => $fieldValue){
                        $myMed = (array)$fields;                                                
                        if(!isset($myMed[$fieldValue])){
                            $response["error"] = true;
                            $response["code"] = 901;
                            $response["message"] = 'Field ' . $fieldValue . ' is not present';        
                            echoRespnse(412, $response);
                            $app->stop();
                        }
                    }                 
                    array_push($fieldsSet, $fields);
                }                        
           }else{
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = 'Param ' . $param . ' is invalid';        
                echoRespnse(412, $response);
                $app->stop();
                
           }                
        }
        //pass it to the dbHandler
        $db = new Suitability($conn);
        $res = $db->setSuitability($prescription_number, $clinicalData_id, $user_id, $hospital_id, $fieldsSet);
        if($res["code"] == SUITABILITY_SET_CREATE_OK){                    
            $response["error"] = false;
            $response["code"] = $res["code"];
            $response["message"] = "Suitability set succesfully registered"; 
            $response["body"] = $res["body"];
            echoRespnse(201, $response);
            return;
        }else if($res["code"] == SUITABILITY_INVALID_INPUT_FORMAT){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "Invalid input format";
            $response["body"] = $res["body"];
        }else if($res["code"] == SUITABILITY_PRESCRIPTION_NOT_EXISTS){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "Invalid prescription ";
            $response["body"] = $res["body"];
        }else if($res["code"] == CONTROL_FAILURE){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "there was a problem with the server";
            $response["body"] = $res["body"];
        }else if($res["code"] == SUITABILITY_INCOMPLETE_INPUT_MEDSLIST){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "there was meds lists with errors";
            $fullResponse = $res["body"];
            $response["body"] = $fullResponse["body"];
        }
        // echo json response
        echoRespnse(200, $response);
        
    }else{
        $response["error"] = true;
        $response["code"] = 901;
        $response["message"] = "Oops!, there was a problem with your request";
        echoRespnse(400, $response);
        $app->stop();    
    }
    return;
});

//UPDATE
$app->put('/suitability/:suitability_id', 'authenticate', function($suitability_id) use ($app){
    global $hospital_id;
    global $user_id;
    global $conn; 
    $fieldsSet = array();
    $validParams = array("medicamentos");
    $validFields = array(
                          'idDatosFarma',
                          'I1DatosFarma',
                          'I2DatosFarma',
                          'I3DatosFarma',
                          'I4DatosFarma',
                          'I5DatosFarma',
                          'I6DatosFarma',
                          'I7DatosFarma',
                          'I8DatosFarma',
                          'notaIdoneidad');
    $response = array();
    $request_body = array();
    $request_body = $app->request->getBody();
    if(count($request_body) > 0){
        $jsonBody = json_decode($request_body);

        foreach ($jsonBody as $param => $objs) {
           if(in_array($param, $validParams)){
                foreach ($objs  as $content => $fields) {
                    foreach($validFields as $validField => $fieldValue){
                        $myMed = (array)$fields;                                                
                        if(!isset($myMed[$fieldValue])){
                            $response["error"] = true;
                            $response["code"] = 901;
                            $response["message"] = 'Field ' . $fieldValue . ' is not present';        
                            echoRespnse(412, $response);
                            $app->stop();
                        }
                    }                 
                    array_push($fieldsSet, $fields);
                }                        
           }else{
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = 'Param ' . $param . ' is invalid';        
                echoRespnse(412, $response);
                $app->stop();
                
           }                
        }
        //pass it to the dbHandler
        $db = new Suitability($conn);
        $res = $db->updateSuitability($suitability_id, $user_id, $hospital_id, $fieldsSet);
        if($res["code"] == SUITABILITY_SET_CREATE_OK){                    
            $response["error"] = false;
            $response["code"] = $res["code"];
            $response["message"] = "PFT set succesfully registered"; 
            $response["body"] = $res["body"];
            echoRespnse(201, $response);
            return;
        }else if($res["code"] == SUITABILITY_INVALID_INPUT_FORMAT){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "Invalid input format";
            $response["body"] = $res["body"];
        }else if($res["code"] == SUITABILITY_PRESCRIPTION_NOT_EXISTS){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "Invalid prescription ";
            $response["body"] = $res["body"];
        }else if($res["code"] == CONTROL_FAILURE){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "there was a problem with the server";
            $response["body"] = $res["body"];
        }else if($res["code"] == SUITABILITY_INCOMPLETE_INPUT_MEDSLIST){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "there was meds lists with errors";
            $fullResponse = $res["body"];
            $response["body"] = $fullResponse;
        }
        // echo json response
        echoRespnse(200, $response);
        
    }else{
        $response["error"] = true;
        $response["code"] = 901;
        $response["message"] = "Oops!, there was a problem with your request";
        echoRespnse(400, $response);
        $app->stop();    
    }
    return;
});



//SEARCH

$app->post('/search', 'authenticate', function() use ($app) {
            global $user_id;
            global $hospital_id;
            global $conn;
            $response = array();
            $fieldsSet = array();
            $request_body = array();
            // check for required params
            $validFields = (array('estatus', 
                                       'fecha_1', 
                                       'fecha_2', 
                                       'genero', 
                                       'identificador', 
                                       'edad_1',
                                       'edad_2',  
                                       'idServicio',
                                       'nombre'));
            $request_body = $app->request->getBody();//$_REQUEST;
            if(count($request_body) > 0){            
                $jsonBody = json_decode($request_body);
                foreach ($jsonBody as $field => $objs) {
                    if(!in_array($field, $validFields)){
                        $response["error"] = true;
                        $response["code"] = 901;
                        $response["message"] = 'Field ' . $field . ' is invalid';        
                        echoRespnse(412, $response);
                        $app->stop();
                    }
                }
                array_push($fieldsSet, $jsonBody);
            }
            $db = new Search($conn);
            $res = $db->search($hospital_id, $fieldsSet);
            
            if ($res["code"] == SEARCH_SUCCESSFUL_ANSWER) {
                $response["error"] = false;
                $response["code"] = $res["code"];
                $response["message"] = "Search done successfully";
                $response["body"] = $res["body"];               
                echoRespnse(201, $response);
                return;
            } else if ($res["code"] == SEARCH_INVALID_FIELDS) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Review invalid fields";
                $response["body"] = $res["body"]; 

            } else if ($res["code"] == SEARCH_ENGINE_FAILURE) {
                $response["error"] = true;
                $response["code"] = $res["code"];
                $response["message"] = "Failure on the engine side";
                $response["body"] = $res["body"]; 

            }
            // echo json response
            echoRespnse(200, $response);
        });


//New Interaction 
$app->post('/interaction/clinicalData/:clinicalData_id/prescriptionNumber/:prescription_number', 'authenticate', function($clinicalData_id, $prescription_number) use ($app) {
            // check for required params
            global $hospital_id;
            global $user_id;
            global $conn;
            $fieldsSet = array();
            $validParams = array(
                                'medicamentos',
                                'alimentoInteraccion',
                                'detallesInteraccion', 
                                'sugerenciaInteraccion', 
                                'categorizacionInteraccion', 
                                'tipoInteraccion');
            $response = array();
            $request_body = array();
            $request_body = $app->request->getBody();//$_REQUEST;            
            if(count($request_body) > 0){
                $jsonBody = json_decode($request_body, TRUE);
                $counter = 0;
                foreach ($validParams as $param => $value) {
                    if(isset($jsonBody[$value])){
                        $fieldsSet[$value] = $jsonBody[$value];
                    }else{
                        $fieldsSet[$value] = NULL;
                    }
                }
                //pass it to the dbHandler
                $db = new Interaction($conn);                
                $res = $db->createInteraction($hospital_id, $user_id, $clinicalData_id, $prescription_number, $fieldsSet);                                
                if($res["code"] == INTERACTION_POST_SUCCESS){                    
                    $response["error"] = FALSE;
                    $response["code"] = $res["code"];
                    $response["body"] = $res["body"];
                    echoRespnse(201, $response);
                    return;
                }
                else if($res["code"] == INTERACTION_PRESCRIPTION_NOT_EXIST){                    
                    $response["error"] = FALSE;
                    $response["code"] = $res["code"];
                    $response["body"] = $res["body"];
                }
                else if($res["code"] == INTERACTION_HOSPITAL_UNKNOWN){                    
                    $response["error"] = FALSE;
                    $response["code"] = $res["code"];
                    $response["body"] = $res["body"];
                }
                else if($res["code"] == INTERACTION_PRESCRIPTION_UNKNOWN){                    
                    $response["error"] = FALSE;
                    $response["code"] = $res["code"];
                    $response["body"] = $res["body"];
                }
                else if($res["code"] == INTERACTION_INVALID_FIELDS){                    
                    $response["error"] = FALSE;
                    $response["code"] = $res["code"];
                    $response["body"] = $res["body"];
                }
                else if($res["code"] == INTERACTION_MEDS_UNKNOWN){                    
                    $response["error"] = FALSE;
                    $response["code"] = $res["code"];
                    $response["body"] = $res["body"];
                }
                else if($res["code"] == CONTROL_FAILURE){                    
                    $response["error"] = TRUE;
                    $response["code"] = $res["code"];
                    $response["body"] = $res["body"];
                    echoRespnse(500, $response);
                    return;
                }
                // echo json response
                echoRespnse(200, $response);
                
            }else{
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = "Oops!, there was a problem with your request";
                echoRespnse(400, $response);
                $app->stop();    
            }
            return;
                    
        });

//Conciliation

$app->post('/conciliation/:conciliation_id', 'authenticate', function($conciliation_id) use ($app){
    global $hospital_id;
    global $user_id;
    global $conn; 
    $fieldsSet = array();
    $validParams = array("interaccion");
    $validFields = array(
                          'idMedicamentos',
                          'idAlergias',
                          'descripcion');
    $response = array();
    $request_body = array();
    $request_body = $app->request->getBody();
    if(count($request_body) > 0){
        $jsonBody = json_decode($request_body);        
        foreach ($jsonBody as $param => $objs) {
           if(in_array($param, $validParams)){
                foreach ($objs  as $content => $fields) {
                    foreach($validFields as $validField => $fieldValue){
                        $myMed = (array)$fields;                                                
                        if(!isset($myMed[$fieldValue])){
                            $response["error"] = true;
                            $response["code"] = 901;
                            $response["message"] = 'Field ' . $fieldValue . ' is not present';        
                            echoRespnse(412, $response);
                            $app->stop();
                        }
                    }                 
                    array_push($fieldsSet, $fields);
                }
           }else{
                $response["error"] = true;
                $response["code"] = 901;
                $response["message"] = 'Param ' . $param . ' is invalid';        
                echoRespnse(412, $response);
                $app->stop();
                
           }                
        }
        //pass it to the dbHandler
        $db = new Warnings($conn);
        $res = $db->setInteraction($prescription_number, $clinicalData_id, $user_id, $hospital_id, $fieldsSet);
        if($res["code"] == INTERACTION_SET_CREATE_OK){                    
            $response["error"] = false;
            $response["code"] = $res["code"];
            $response["message"] = "PFT set succesfully registered"; 
            $response["body"] = $res["body"];
            echoRespnse(201, $response);
            return;
        }else if($res["code"] == INTERACTION_PRESCRIPTION_NOT_EXISTS){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "Invalid prescription ";
            $response["body"] = $res["body"];
        }else if($res["code"] == CONTROL_FAILURE){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = "there was a problem with the server";
            $response["body"] = $res["body"];
        }
        // echo json response
        echoRespnse(200, $response);
        
    }else{
        $response["error"] = true;
        $response["code"] = 901;
        $response["message"] = "Oops!, there was a problem with your request";
        echoRespnse(400, $response);
        $app->stop();    
    }
    return;
});

$app->get('/patient/:patient_id/firstConciliation/', 'authenticate', function($patient_id) use ($app) {
    global $user_id;
    global $hospital_id;
    global $conn;
    $response = array();
    $db = new Meds($conn);
    // fetch task

    $res = $db->getFirtsConciliation($hospital_id, $patient_id);
    
    if($res["code"] == PFC_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
        return;
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "There was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
        return;
    }
    echoRespnse(200, NULL); 
return;
});

//---------------------------->
//REMINDER/////////////////////
///////////////////////////////

$app->post('/warnings/idClinicalData/:clinicaldata_id/prescription/:prescription_id', 'authenticate', function($clinicaldata_id, $prescription_id) use ($app) {

    global $user_id;
    global $conn;
    $response = array();

    $db = new Warnings($conn);
    $request_body = $app->request->getBody();   
    $alerts = array();
    $validParams = array("alertas");
    $validFields = array('idTipoAlerta',   
                         'descripcionAlerta');
    if(count($request_body) > 0){
                    $jsonBody = json_decode($request_body);
    
                    foreach ($jsonBody as $param => $objs) {                    
                       if(in_array($param, $validParams)){
                            
                            foreach ($objs  as $content => $fields) { 
                                $fieldsSet = array();                       
                                foreach ($fields as $field => $fieldValue){
                                    if(in_array($field, $validFields)){
                                        $fieldsSet[$field] = $fieldValue;     
                                    }
                                }
                                $alerts[] = $fieldsSet;                             
                            }                        
                       }else{
                            $response["error"] = true;
                            $response["code"] = 901;
                            $response["message"] = 'Param ' . $param . ' is invalid';        
                            echoRespnse(412 , $response);
                            $app->stop();
                            
                       }                
                    }
    }else{
        $response["error"] = true;
        $response["code"] = 901;
        $response["message"] = "Oops!, there was a problem with your request";
        echoRespnse(400, $response);
        $app->stop();    
    }
    
    $res = $db->createWarningByType($alerts, $clinicaldata_id, $prescription_id);    
    if($res["code"] == ALERTS_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "Alerta Creada";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "Hubo un error por favor intente mas tarde";
        $response["body"] = $res["body"];
        echoRespnse(500, $response);
    } else if($res["code"] == ALERTS_SINTAX_ERROR){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "Error de sintaxis";
        $response["body"] = $res["body"];
        echoRespnse(500, $response);
    } else if($res["code"] == ALERTS_ERROR){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "Error de sintaxis, solo algunas alertas pudieron ser creadas";
        $response["body"] = $res["body"];
        echoRespnse(500, $response);
    }    


});
//STATISTICS

$app->post('/statistics/', 'authenticate', function() use ($app){
    global $user_id;
    global $conn;
    global $hospital_id;
    $response = array();
    $res = NULL;
    $db = new Statistics($conn);
    $request_body = $app->request->getBody();   
    $statistics = array();
    $paramsLevelZero = array(
        'tipo',
        'opQuimico',
        'quimico',
        'fecha',
        'opPaciente',
        'paciente',
        'propiedad',
        'opSexo',
        'sexo',
        'peso',
        'edad',
        'estatus',
        'opArea',
        'area',
        'opTipoMedicamento',
        'incidencia',
        'tipoMedicamento'
    );
    if(count($request_body) > 0){
        $jsonBody = json_decode($request_body, TRUE);
        $fieldsSet = array();  
        $counter = 0;               
        foreach ($paramsLevelZero as $key => $value) {
            if(isset($jsonBody[$value])){
                $fieldsSet[$value] = $jsonBody[$value];
            }else{
                $fieldsSet[$value] = NULL;
            }
        }
        $res = $db->getStatisticsByType($fieldsSet, $user_id, $hospital_id);    
        if($res["code"] == STATISTICS_SUCCESSFUL_ANSWER){
            $response["error"] = FALSE;
            $response["code"] = $res["code"];
            $response["message"] = $res["message"];
            $response["body"] = $res["body"];
            
            echoRespnse(200, $response);
        } else if($res["code"] == CONTROL_FAILURE){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = $res["message"];
            $response["body"] = $res["body"];
            echoRespnse(500, $response);
        } else if($res["code"] == WRONG_PARAMETER){
            $response["error"] = true;
            $response["code"] = $res["code"];
            $response["message"] = $res["message"];
            $response["body"] = $res["body"];
            echoRespnse(500, $response);
        }
    }else{
        $response["error"] = true;
        $response["code"] = 901;
        $response["message"] = "Oops!, there was a problem with your request";
        echoRespnse(400, $response);
        $app->stop();    
    }    
});

// Suitabilitys Historyc
$app->get('/patient/:patient_id/suitability/history', 'authenticate', function($patient_id) use ($app) {
    global $conn;
    $db = new Motor($conn);
    $response = array();
    $res = $db->historialIdoneidades($patient_id);
    if(!isset($res["error"])){
        $response["error"] = FALSE;
        $response["code"] = 100;
        $response["message"] = "The server received your petition";
        $response["body"] = $res;
        
        echoRespnse(200, $response);
    } else if($res["error"] == 103){
        $response["error"] = true;
        $response["code"] = $res["error"];
        $response["message"] = "The requested patient does not exist";
        $response["body"] = NULL;
        echoRespnse(400, $response);
    } else if($res["error"] == 102){
        $response["error"] = true;
        $response["code"] = $res["error"];
        $response["message"] = "there was a problem with the server";
        $response["body"] = NULL;
        echoRespnse(400, $response);
    } else if($res["error"] == 101){
        $response["error"] = true;
        $response["code"] = $res["error"];
        $response["message"] = "there was a problem with the identification of the patient";
        $response["body"] = NULL;
        echoRespnse(400, $response);
    }
    /*if($res["code"] == ALERGIES_SUCCESSFUL_ANSWER){
        $response["error"] = FALSE;
        $response["code"] = $res["code"];
        $response["message"] = "The server received your petition";
        $response["body"] = $res["body"];
        
        echoRespnse(200, $response);
    } else if($res["code"] == ALERGIES_PATIENT_NO_EXIST){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "The requested patient does not exist";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    } else if($res["code"] == CONTROL_FAILURE){
        $response["error"] = true;
        $response["code"] = $res["code"];
        $response["message"] = "there was a problem with the server";
        $response["body"] = $res["body"];
        echoRespnse(400, $response);
    }*/
});

//history Report
$app->get('/patient/:patient_id/historyReport', 'authenticate', function($patient_id) use ($app) {
    global $conn;
    $db = new Motor($conn);
    $response = array();
    $res = $db->getReporteHistorico($patient_id);
    if(!isset($res["error"])){
        $response["error"] = FALSE;
        $response["code"] = 100;
        $response["message"] = HISTORY_SUCCESSFUL_ANSWER_MSG;
        $response["body"] = $res;
        
        echoRespnse(200, $response);
    }  else if($res["error"] == 102){
        $response["error"] = true;
        $response["code"] = $res["error"];
        $response["message"] = HISTORY_ERROR_102_MSG;
        $response["body"] = NULL;
        echoRespnse(400, $response);
    } else if($res["error"] == 101){
        $response["error"] = true;
        $response["code"] = $res["error"];
        $response["message"] = HISTORY_ERROR_101_MSG;
        $response["body"] = NULL;
        echoRespnse(400, $response);
    }
});


$app->run();
?>