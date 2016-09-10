<?php

class Utilities{

    private $conn;

    function __construct($con){
        $this->conn = $con;
    }
	
    
	public function getPatient($patient_id, $hospital_id) {
        $stmt = $this->conn->prepare("SELECT p.idPaciente, p.nombrePaciente, p.apellidoPaternoPaciente, p.apellidoMaternoPaciente, p.nacimientoPaciente, p.sexoPaciente,  p.idHospital, p.idEstatusPaciente, p.idEstatusVisita from Paciente p WHERE p.idPaciente = ? AND p.idHospital = ?");
        $stmt->bind_param("ii", $patient_id, $hospital_id);
        if ($stmt->execute()) {            
            $stmt->close();
            $result = mysqli_query($this->conn, "SELECT p.idPaciente, p.nombrePaciente, p.apellidoPaternoPaciente, p.apellidoMaternoPaciente, p.nacimientoPaciente, p.sexoPaciente,  p.idHospital, p.idEstatusPaciente, p.idEstatusVisita from Paciente p WHERE p.idPaciente = $patient_id AND p.idHospital = $hospital_id");
            $pacient = mysqli_fetch_assoc($result);
            return $pacient;
        } else {
            return NULL;
        }
    }

    public function emptyFields($fields, $exclusions){
        if($fields != null){
            $result = 0;
            foreach($fields as $field => $value){                
                if((empty($value) || ($value == null)) && (!(in_array($field, $exclusions)))){
                    
                    $result++;
                }
            }
            return $result;
        }else{
            return -1;
        }
    }

    public function isPatientExists($name, $lastName, $secondLastName, $birthDate){

        $stmt = $this->conn->prepare("SELECT p.idPaciente, p.nombrePaciente, p.apellidoPaternoPaciente, p.apellidoMaternoPaciente, p.nacimientoPaciente from Paciente p WHERE p.nombrePaciente LIKE ? AND p.apellidoPaternoPaciente LIKE ? AND p.apellidoMaternoPaciente LIKE ? AND p.nacimientoPaciente LIKE ?");
        $stmt->bind_param("ssss", $name, $lastName, $secondLastName, $birthDate);
        if ($stmt->execute()) {
            $pacient = array();
            $stmt->bind_result(  $idPaciente,
                                 $nombrePaciente,
                                 $apellidoPaternoPaciente, 
                                 $apellidoMaternoPaciente, 
                                 $nacimientoPaciente
                                );
            while($stmt->fetch()){
                                 $pacient['idPaciente'] = $idPaciente;
                                 $pacient['nombrePaciente'] = $nombrePaciente;
                                 $pacient['apellidoPaternoPaciente'] = $apellidoPaternoPaciente;
                                 $pacient['apellidoMaternoPaciente'] = $apellidoMaternoPaciente; 
                                 $pacient['nacimientoPaciente'] = $nacimientoPaciente;
                            }
            $stmt->close();
            return $pacient;
        } else {
            return NULL;
        }
    }

    public function armStandardResponse($code, $body){
        $res["code"] = $code;
        $res["body"] = $body;
        return $res;
    }

    public function armStandardResponseWMsg($code, $body, $msg){
        $res["code"] = $code;
        $res["body"] = $body;
        $res["message"] = $msg;
        return $res;
    }

///////////////////////////////////////
/////  POP ARRAY RECURSIVELY BY  KEY
///////////////////////////////////////
    public function recursive_array_search($needle,$haystack) {
        foreach($haystack as $key=>$value) {
            $current_key=$key;
            if($needle===$value OR (is_array($value))) {
                return $haystack[$current_key];
            }
        }
        return false;
    }

    public function isValueExistsInt($table, $field, $value){

            $stmt = $this->conn->prepare("SELECT * from $table WHERE $field = ?");
            $stmt->bind_param("i", $value);  //check how to acces to any table dynamicaly          
            if($stmt->execute()){
                $stmt->store_result();
                $num_rows = $stmt->num_rows;
                $stmt->close();
                if($num_rows > 0){
                    return TRUE;
                }else{
                    return FALSE;
                }
            }else{
                echo "FETCH ERROR";        
                exit(400);
            }   
    }

    public function upDateStringColumnIn($table, $set, $where, $token, $value){
        $stmt = $this->conn->prepare("UPDATE $table SET $set = ? WHERE $where = ?");           
        $stmt->bind_param("ss", $value, $token);  //check how to acces to any table dynamicaly          
        
        if($stmt->execute()){
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            if($num_rows > 0){
                return TRUE;
            }else{
                return FALSE;
            }
        }else{
            echo "FETCH ERROR";        
            exit(400);
        }
        return FALSE;
    }

    public function upDateIntegerColumnIn($table, $set, $where, $token, $value){
        $stmt = $this->conn->prepare("UPDATE $table SET $set = ? WHERE $where = ?");           
        $stmt->bind_param("is", $value, $token);  //check how to acces to any table dynamicaly          
        
        if($stmt->execute()){
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            if($num_rows > 0){
                return TRUE;
            }else{
                return FALSE;
            }
        }else{
            echo "FETCH ERROR";        
            exit(400);
        }
        return FALSE;
    }

    public function binary_search_associative_array($value, $key, $list, $left, $right){
        if($left > $right){
            return NULL;
        }

        $mid = ($left + $right) / 2;
        if($list[$mid][$key] == $value){
            return $list[$mid];
        }elseif ($list[$mid][$key] > $value) {
            return $this->binary_search_associative_array($value, $key, $list, $left, $mid - 1);
        }elseif ($list[$mid][$key] < $value) {
            return $this->binary_search_associative_array($value, $key, $list, $mid + 1, $right);
        }

    }

    public function upDateIntegerColumnIn_2($table, $set, $where, $and, $and2, $token1, $token2, $token3, $value){
        
        $stmt = $this->conn->prepare("UPDATE $table SET $set = ? WHERE $where = ? AND $and = ? AND $and2 = ?");           
        $stmt->bind_param("iiii", $value, $token1, $token2, $token3);  //check how to acces to any table dynamicaly          
        
        if($stmt->execute()){
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            if($num_rows > 0){
                return TRUE;
            }else{
                return FALSE;
            }
        }else{
            echo "FETCH ERROR";        
            exit(400);
        }
        return FALSE;
    }
    public function upDateIntegerColumnIn_1INT($table, $set, $where, $and, $token1, $token2, $value){
        $stmt = $this->conn->prepare("UPDATE $table SET $set = ? WHERE $where = ? AND $and = ?");           
        $stmt->bind_param("iii", $value, $token1, $token2);  //check how to acces to any table dynamicaly          
        
        if($stmt->execute()){
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            if($num_rows > 0){
                return TRUE;
            }else{
                return FALSE;
            }
        }else{
            echo "FETCH ERROR";        
            exit(400);
        }
        return FALSE;
    }

    public function upDateIntegerColumnIn_1($table, $set, $where, $and, $token1, $token2, $value){
        $stmt = $this->conn->prepare("UPDATE $table SET $set = ? WHERE $where = ? AND $and = ?");           
        $stmt->bind_param("iss", $value, $token1, $token2);  //check how to acces to any table dynamicaly          
        
        if($stmt->execute()){
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            if($num_rows > 0){
                return TRUE;
            }else{
                return FALSE;
            }
        }else{
            echo "FETCH ERROR";        
            exit(400);
        }
        return FALSE;
    }

    public function isValueExistsString($table, $field, $value){
            $stmt = $this->conn->prepare("SELECT * from $table WHERE $field = ?");
            $stmt->bind_param("s", $value);  //check how to acces to any table dynamicaly          
            if($stmt->execute()){
                $stmt->store_result();
                $num_rows = $stmt->num_rows;
                $stmt->close();
                if($num_rows > 0){
                    return TRUE;
                }else{
                    return FALSE;
                }
            }else{
                echo "FETCH ERROR";        
                exit(400);
            }   
    }

    public function generateApiKey() {
        return md5(uniqid(rand(), true));
    }



}

?>