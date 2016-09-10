<?php 
	function RemoveLAS($string) {
		$newString = $string;
		$newString = str_replace(" DE "," ",$newString);
		$newString = str_replace("LA "," ",$newString);
		$newString = str_replace("LAS "," ",$newString);
		$newString = str_replace("LOS "," ",$newString);
		$newString = str_replace("DU "," ",$newString);
		$newString = str_replace("DO "," ",$newString);
		$newString = str_replace("-"," ",$newString);
		return $newString;
	}
		
	function Iniciales($string)
		{
		foreach( explode( " ", $string ) as $palabra ) {
	      $cadena .= $palabra[0];
		}
		return $cadena;
	} 
				
	$datos = $_POST; 
	if(isset($datos["tipo"]))
	{
		require_once '../API/include/conf/DbConnect.php';
		require_once '../_lib/conn.php'; 
		$db = new DbConnect();
		$conn = $db->connect();
		$llave = $datos["k"];
		$tipo = $datos["tipo"];
		$result = mysqli_query($conn, "select q.loginQuimico from MyKeys m, Quimico q where m.api_key = '$llave' and q.idQuimico = m.idQuimico");
		$row = mysqli_fetch_assoc($result);
		$result = mysqli_query($mysqli, "Select usr_usrid, usr_insid, usr_nivid, usr_login, ins_clave from usuarios, institucion where usr_login = '".$row["loginQuimico"]."' AND ins_insid = usr_insid");
		$row = mysqli_fetch_assoc($result);
		session_start();
		$_SESSION['UserID'] = $row['usr_usrid'];
		$_SESSION['Logged'] = $row['usr_login'];
		$_SESSION['ULevel'] = $row['usr_nivid'];		
		if ($tipo == 2)
		{
			require_once '../API/include/engine/Motor.php';		
			$consecutivo = mysqli_fetch_assoc(mysqli_query($mysqli,"SELECT * FROM institucion_consecutivo WHERE icn_insid = '".$row['usr_insid']."'"));
			
			$nuevo = $consecutivo['icn_conse']+1;
			
			$Prefix = $row['ins_clave'].date('y');
			if ($nuevo<10) { $ClaveNueva = $Prefix.'-00000'.$nuevo; }
			if ($nuevo>10 && $nuevo<100) { $ClaveNueva = $Prefix.'-0000'.$nuevo; }
			if ($nuevo>100 && $nuevo<1000) { $ClaveNueva = $Prefix.'-000'.$nuevo; }
			if ($nuevo>1000 && $nuevo<10000) { $ClaveNueva = $Prefix.'-00'.$nuevo; }
			if ($nuevo>10000 && $nuevo<100000) { $ClaveNueva = $Prefix.'-0'.$nuevo; }
			if ($nuevo>100000 && $nuevo<1000000) { $ClaveNueva = $Prefix.'-'.$nuevo; }			
			
			mysqli_query($mysqli,"INSERT INTO reportes (rep_insid, rep_usrid, rep_clave) VALUES ('".$row['usr_insid']."', '".$_SESSION['UserID']."', '".$ClaveNueva."')");
			$ReporteID2 = mysqli_insert_id($mysqli);			
			mysqli_query($mysqli,"UPDATE institucion_consecutivo SET icn_conse='".$nuevo."' WHERE icn_insid = '".$row['usr_insid']."'");
			mysqli_query($mysqli,"INSERT INTO reacciones (rac_repid) VALUES (".$ReporteID2.")");
			$ReporteID = mysqli_insert_id($mysqli);			
			
			$result = mysqli_query($conn, "Select p.idPaciente, p.nombrePaciente, p.apellidoPaternoPaciente, p.apellidoMaternoPaciente, p.nacimientoPaciente, dc.identificadorPacienteDatosClinicos, dc.camaDatosClinicos, s.nombreServicio, dc.pesoDatosClinicos, dc.tallaDatosClinicos, dc.imcDatosClinicos, dc.ascDatosClinicos, IF(p.sexoPaciente = 'M', '2', '1') as sexoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS anios, MOD(TIMESTAMPDIFF(MONTH,p.nacimientoPaciente,CURDATE()),12) AS meses
				FROM DatosFarma df right join DatosClinicos dc on df.idDatosClinicos = dc.idDatosClinicos inner join Paciente p on dc.idPaciente = p.idPaciente inner join Servicio s on dc.idServicio = s.idServicio where  df.idDatosFarma = ".$datos['df']);
			$paciente = mysqli_fetch_assoc($result);	
			$auxNom = Iniciales(RemoveLAS($paciente['nombrePaciente']));
			$auxAP = Iniciales(RemoveLAS($paciente['apellidoPaternoPaciente']));
			$auxAM = (isset($paciente['apellidoMaternoPaciente'])) ? Iniciales(RemoveLAS($paciente['apellidoMaternoPaciente'])) : '';
			$ini = strtoupper($auxAP . $auxAM . $auxNom);
			$result = mysqli_query($mysqli, "Select srv_srvid from servicios where srv_sname = '".$paciente['nombreServicio']."' and srv_insid = ".$row['usr_insid']);
			$servicio = mysqli_fetch_assoc($result);
			$query="INSERT INTO pacientes (pac_repid, pac_clave, pac_ncama, pac_srvid, pac_nombr, pac_appat, pac_apmat, pac_psexo, pac_binit, pac_bdate, pac_banos, pac_meses, pac_pesok, pac_altur, pac_masac, pac_areac) VALUES ('".$ReporteID2."', '".strtoupper($ClaveNueva)."', '".$paciente['camaDatosClinicos']."', '".$servicio['srv_srvid']."', '".strtoupper($paciente['nombrePaciente'])."', '".strtoupper($paciente['apellidoPaternoPaciente'])."', '".strtoupper($paciente['apellidoMaternoPaciente'])."', '".$paciente['sexoPaciente']."', '".$ini."', '".$paciente['nacimientoPaciente']."', '".$paciente['anios']."', '".$paciente['meses']."', '".$paciente['pesoDatosClinicos']."', '".$paciente['tallaDatosClinicos']."', '".$paciente['imcDatosClinicos']."', '".$paciente['ascDatosClinicos']."')";						
			mysqli_query($mysqli, $query);
			
			$eng = new Motor($conn);
			$pft = $eng->pft_actual($paciente['idPaciente']);			
			
			for ($i=0; $i<count($pft); $i++) 
				if($pft[$i]['idDatosFarma'] == $datos['df'])				
					mysqli_query($mysqli, "INSERT INTO medicamentos_sospechosos (mds_racid, mds_prnid, mds_prins, mds_dosis, mds_uniid, mds_mdtot, mds_dokgs, mds_gpcid, mds_notas) VALUES ('".$ReporteID."', '".$pft[$i]['idPrincipio']."', '".$pft[$i]['prescritoDatosFarma']."', '".strtoupper($pft[$i]['concentracionDatosFarma'])."', '".$pft[$i]['idUnidad']."', '".strtoupper($pft[$i]['nombreFrecuencia'])."', '', '0', '".strtoupper($pft[$i]['notaDatosFarma'])."')");								
				else			
					mysqli_query($mysqli, "INSERT INTO concomitantes (con_racid, con_prnid, con_prins, con_dosis, con_uniid) VALUES ('".$ReporteID."', '".$pft[$i]['idPrincipio']."', '".$pft[$i]['prescritoDatosFarma']."', '".strtoupper($pft[$i]['concentracionDatosFarma'])."', '".$pft[$i]['idUnidad']."')");				
			//header('Location: ../../../_NUBA/informacion.php?id='.$ReporteID);
			header('Location: ../_NUBA/informacion.php?id='.$ReporteID2); 
		}	
		else	
			header('Location: ../_NUBA/home.php'); 
	}
	else
		header('Location: ../');
?>