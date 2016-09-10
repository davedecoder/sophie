<?
private function insertMeds($medsList, $prescriptionNumber, $clinicalData_id, $user_id, $idoneidad, $alert, $actualDate){        
        $doneMeds = array();
        $engine = new Motor($this->conn);
        $util = new Utilities($this->conn);
        $response["medicamentos"] = $doneMeds;
        foreach ($medsList as $farmaData) {
            ////////////////
            $farmaData['idDatosFarma'] = 0; 
            $farmaData['numeroRecetaDatosFarma'] = $prescriptionNumber;                                                      
            $farmaData['idDatosClinicos'] = $clinicalData_id;
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
                                                                idQuimico) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            
            $stmt->bind_param("iissiisiiiiiiiii",   $farmaData['concentracionDatosFarma'],
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
                                                 $user_id);                
            $result = $stmt->execute();
            if($result)
            {
                $farmadata_id = $stmt->insert_id;
                $stmt->close();
                //***
                mysqli_query($this->conn,"insert into Nota_Farma values(null,'',".$farmaData['idDatosClinicos'].",".$farmaData['numeroRecetaDatosFarma'].",$user_id)");
                mysqli_query($this->conn,"insert into Idoneidad(idIdoneidad, fechaIdoneidad, idDatosFarma, idQuimico) values($idoneidad, '$actualDate', $farmadata_id, $user_id)");
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
            $warnings->updateWarningStatusByTypeAndCDId($clinicalData_id - 1, ALERT_STATUS_DF_INCOMPLETE_AND_CONCILIATION, ALERT_ALLOW); 
            $warnings->updateWarningStatusByTypeAndCDId($clinicalData_id, ALERT_STATUS_DF_INCOMPLETE_AND_CONCILIATION, ALERT_ALLOW); 
            $warnings->updateSetWarningWhereAnd("idAccion", "idDatosClinicos", "numeroReceta", $clinicalData_id, $prescriptionNumber-1, ALERT_ALLOW); 
            if($alert){
                $engine->alertaDatos_Pendientes($clinicalData_id, $doneMeds[0]['numeroRecetaDatosFarma'], ALERT_TYPE_DF_INCOMPLETE);
                return $util->armStandardResponse(PFTSET_CREATE_OK_W_WARNINGS, $response);
            }else{
                $engine->conciliacion($clinicalData_id, $doneMeds[0]['numeroRecetaDatosFarma']);
                return $util->armStandardResponse(PFTSET_CREATE_OK, $response);
            }                        
        }
        return NULL;
    }

?>