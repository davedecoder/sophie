<?php

class Auths{
	private $conn;


	function __construct(){
		require_once dirname(__FILE__) . '/conf/DbConnect.php';
        require_once dirname(__FILE__) . '/libs/Validations.php';
        require_once dirname(__FILE__) . '/engine/Motor.php';
        require_once dirname(__FILE__) . '/libs/Utilities.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
	}

	public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT idQuimico FROM MyKeys WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($idQuimico);
        $num_rows = $stmt->num_rows;
        $stmt->fetch();
        $stmt->close();
        if($num_rows > 0){
            return $idQuimico;
        }else{
            return 0;
        }        
    }

    private function getApiKeyById($user_id) {

        $stmt = $this->conn->prepare("SELECT api_key FROM MyKeys WHERE idQuimico = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $stmt->bind_result($api_key['api_key']);
            $stmt->close();
            return $api_key['api_key'];
        } else {
            return NULL;
        }
    }

    public function getUserBasicData($idQuimico) {
        $stmt = $this->conn->prepare("SELECT * FROM Quimico WHERE idQuimico = ?");
        $stmt->bind_param("i", $idQuimico);
        if ($stmt->execute()) {
            $userBasicData = array();
            $stmt->bind_result( $idQuimico,
                                $nombreQuimico,
                                $apellidoPaternoQuimico,
                                $apellidoMaternoQuimico,
                                $contrasenaQuimico,
                                $loginQuimico,
                                $emailQuimico,
                                $telefonoQuimico,
                                $extensionQuimico,
                                $idHospital,
                                $idTurno
                );
             while($stmt->fetch()){
                                $userBasicData['idQuimico'] = $idQuimico;
                                $userBasicData['nombreQuimico'] = $nombreQuimico;
                                $userBasicData['apellidoPaternoQuimico'] = $apellidoPaternoQuimico;
                                $userBasicData['apellidoMaternoQuimico'] = $apellidoMaternoQuimico;
                                $userBasicData['contrasenaQuimico'] = $contrasenaQuimico;
                                $userBasicData['loginQuimico'] = $loginQuimico;
                                $userBasicData['emailQuimico'] = $emailQuimico;
                                $userBasicData['telefonoQuimico'] = $telefonoQuimico;
                                $userBasicData['extensionQuimico'] = $extensionQuimico;
                                $userBasicData['idHospital'] = $idHospital;
                                $userBasicData['idTurno'] = $idTurno;
                            }
            $stmt->close();
            return $userBasicData;
        } else {
            return NULL;
        }
    }

    public function checkNubaLogin($username, $password) {
        // fetching user by email
        $util = new Utilities($this->conn);
        $stmt = $this->conn->prepare("SELECT * FROM Quimico WHERE loginQuimico = ?");

        $stmt->bind_param("s", $username);
        
        if ($stmt->execute()) {
            $userBasicData = array();
            $stmt->bind_result( $idQuimico,
                                $nombreQuimico,
                                $apellidoPaternoQuimico,
                                $apellidoMaternoQuimico,
                                $contrasenaQuimico,
                                $loginQuimico,
                                $emailQuimico,
                                $telefonoQuimico,
                                $extensionQuimico,
                                $idHospital,
                                $idTurno
                );
             while($stmt->fetch()){
                                $userBasicData['idQuimico'] = $idQuimico;
                                $userBasicData['nombreQuimico'] = $nombreQuimico;
                                $userBasicData['apellidoPaternoQuimico'] = $apellidoPaternoQuimico;
                                $userBasicData['apellidoMaternoQuimico'] = $apellidoMaternoQuimico;
                                $userBasicData['contrasenaQuimico'] = $contrasenaQuimico;
                                $userBasicData['loginQuimico'] = $loginQuimico;
                                $userBasicData['emailQuimico'] = $emailQuimico;
                                $userBasicData['telefonoQuimico'] = $telefonoQuimico;
                                $userBasicData['extensionQuimico'] = $extensionQuimico;
                                $userBasicData['idHospital'] = $idHospital;
                                $userBasicData['idTurno'] = $idTurno;
                            }
            // Found user with the email
            // Now verify the password                      
            $stmt->close();
            if (PassHash::check_password($userBasicData["contrasenaQuimico"], $password)) {

                // User password is correct
                $api_key = $this->getApiKeyById($userBasicData["idQuimico"]);
                if($api_key != NULL){                 
                    $userAuth['loginQuimico'] = $userBasicData['loginQuimico'];
                    $userAuth['api_key'] = $api_key;
                    return $util->armStandardResponse(LOGIN_SUCCSESFULLY, $userAuth);
                }else{
                    return $util->armStandardResponse(CONTROL_FAILURE, NULL);
                }
                
            } else {
                // user password is incorrect
                return $util->armStandardResponse(LOGIN_FALSE, NULL);
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return $util->armStandardResponse(LOGIN_FALSE, NULL);
        }
    }

}
?>