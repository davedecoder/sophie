<?php  
	class Motor 
	{
		private $conexion;
		
		function __construct($con)
		{
			$this->conexion = $con;
		}	
		
		public function busqueda($hospital, $estatus, $fecha, $genero, $edad, $identificador, $servicio, $nombre) 
		{
			$arr = array();
			if(is_numeric($hospital) && $hospital != 0)
			{
				if($estatus == 0)
					$estatusa = "p.idEstatusPaciente";
				else
					if($estatus == 1 || $estatus == 3)
						$estatusa = 1;
					else
						$estatusa = 2;
				if(count($fecha)==1)
					$fechaa = "= Date(dc.ingresoDatosClinicos)";				
				else
					if(count($fecha)==2)
						$fechaa = "= '".$fecha[1]."'";
					else
					{	
						$datetime1 = date_create($fecha[1]);
						$datetime2 = date_create($fecha[2]);
						$afecha1 = ($datetime1 < $datetime2) ? $fecha[1] : $fecha[2];
						$afecha2 = ($datetime1 < $datetime2) ? $fecha[2] : $fecha[1];		
						$fechaa = "between '".$afecha1."' and '".$afecha2."'";
					}
				if(is_numeric($genero))
					$sexoa = "p.sexoPaciente";
				else
					$sexoa = "'".$genero."'";
				if(count($edad)==1)
					$edada = "= timestampdiff(Year, p.nacimientoPaciente, CURDATE())";				
				else
					if(count($edad)==2)
						$edada = "= ".$edad[1];
					else
					{
						if($edad[1] < $edad[2])
							$edada = "between ".$edad[1]." and ".$edad[2];
						else
							$edada = "between ".$edad[2]." and ".$edad[1];
					}
				if($identificador == "")
					$iden = "dc.identificadorPacienteDatosClinicos";
				else
					$iden = $identificador;
				if($servicio == 0)
					$serv = "dc.idServicio";
					
				else 
					$serv = $servicio;
				if ($nombre == "")
					$nomb = "p.nombrePaciente = p.nombrePaciente";
				else 
					$nomb = "(p.nombrePaciente like '%$nombre%' or p.apellidoPaternoPaciente like '%$nombre%' or p.apellidoMaternoPaciente like '%$nombre%')";			
				if($estatus != 3)
				{
					$sql = "select p.idPaciente, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente,  e.tipoEstatusPaciente, dc.ingresoDatosClinicos, s.nombreServicio, p.idEstatusPaciente, dc.*
					from Paciente p, Estatus_Paciente e, DatosClinicos dc, Servicio s 
					where p.idEstatusPaciente = e.idEstatusPaciente and p.idHospital = $hospital and p.idEstatusPaciente = ".$estatusa." and p.sexoPaciente = ".$sexoa." and p.idPaciente = dc.idPaciente and dc.idServicio = s.idServicio and Date(dc.ingresoDatosClinicos) ".$fechaa." and timestampdiff(Year, p.nacimientoPaciente, CURDATE()) ".$edada." and dc.identificadorPacienteDatosClinicos = ".$iden." and dc.idServicio = ".$serv." and $nomb and (p.idPaciente, dc.idDatosClinicos) in (select idPaciente, max(idDatosClinicos) from DatosClinicos group by idPaciente)";
					
					$result = mysqli_query($this->conexion, $sql);
					if($result)
						while($row = mysqli_fetch_assoc($result))
						{
							$row["error"] = 100;
							$row["nombrePaciente"] = ucwords(strtolower($row["nombrePaciente"]));
							$row["apellidoPaternoPaciente"] = ucwords(strtolower($row["apellidoPaternoPaciente"]));
							$arr[] = $row;
						}
					for($i=0; $i<count($arr); $i++)
						if($arr[$i]['idEstatusPaciente'] == 1)
						{
							$id = $arr[$i]['idPaciente'];
							$result = mysqli_query($this->conexion, "select v.tipoEstatusVisita from Paciente p, Estatus_Visita v where p.idPaciente = $id and p.idEstatusVisita = v.idEstatusVisita");	
							$row = mysqli_fetch_assoc($result);
							$arr[$i]['estatusVisita'] = $row['tipoEstatusVisita'];
						}
						else
							$arr[$i]['estatusVisita'] = "Alta";
					//$arr["sql"] = $sql;
				}else {
					$sql = "select p.idPaciente, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente,  e.tipoEstatusPaciente, dc.ingresoDatosClinicos, s.nombreServicio, dc.*
					from Paciente p, Estatus_Paciente e, DatosClinicos dc, Servicio s
					where p.idEstatusPaciente = e.idEstatusPaciente and p.idHospital = $hospital and p.idEstatusPaciente = ".$estatusa." and p.sexoPaciente = ".$sexoa." and p.idPaciente = dc.idPaciente and dc.idServicio = s.idServicio and Date(dc.ingresoDatosClinicos) ".$fechaa." and timestampdiff(Year, p.nacimientoPaciente, CURDATE()) ".$edada." and dc.identificadorPacienteDatosClinicos = ".$iden." and dc.idServicio = ".$serv." and p.idEstatusVisita = 2 and (p.idPaciente, dc.idDatosClinicos) in (select idPaciente, max(idDatosClinicos) from DatosClinicos group by idPaciente)";					
					$result = mysqli_query($this->conexion, $sql);
					//$arr["sql"] = $sql;
					if($result)
						while($row = mysqli_fetch_assoc($result))
						{
							$row["error"] = 100;
							$row['estatusVisita'] = 'Pendiente';
							$row["nombrePaciente"] = ucwords(strtolower($row["nombrePaciente"]));
							$row["apellidoPaternoPaciente"] = ucwords(strtolower($row["apellidoPaternoPaciente"]));
							$arr[] = $row;
						}
				}
			}
			else
				$arr["error"] = 101;	
			return $arr;
		}

		public function getPrescriptionByNumberAndClinicalDataID($prescriptionNumber, $idClinicalData){
			$arr = array();
			if((is_numeric($prescriptionNumber) && $prescriptionNumber >0) && ((is_numeric($idClinicalData) && $idClinicalData > 0))) 
			{	
				$sql = "select df.*, p.nombrePrincipio from DatosFarma df, Principio p where df.numeroRecetaDatosFarma = $prescriptionNumber and df.idDatosClinicos = $idClinicalData and df.idPrincipio = p.idPrincipio";
				$row = mysqli_query($this->conexion, $sql);
				while($result = mysqli_fetch_assoc($row))
					$arr[] = $result; 
				if (count($arr) == 0)
					return null;	
				else{					
					return $arr;			
				}
					
			}
			return null;
		}
		
		public function prescripcion_numero($numero, $dc) 
		{
			$arr = array();
			if(is_numeric($numero) && $numero >0)
			{
				$stmt = $this->conexion->prepare("select p.nombrePrincipio, df.concentracionDatosFarma, u.abreviaturaUnidad, f.nombreFrecuencia, v.nombreVia, pr.nombrePresentacion, us.nombreUso, nf.detalleNotaFarma, df.cronicoDatosFarma, df.notaDatosFarma, q.nombreQuimico
				from DatosFarma df, Presentacion pr, Via v, Unidad u, Nota_Farma nf, Frecuencia f, Principio p, Uso us, Quimico q
				where df.idDatosClinicos = ? and df.numeroRecetaDatosFarma = ? and df.idPresentacion = pr.idPresentacion and df.idVia = v.idVia and df.idUnidad = u.idunidad and df.idFrecuencia = f.idFrecuencia and df.idPrincipio = p.idPrincipio and df.idUso = us.idUso and df.idQuimico = q.idQuimico and df.numeroRecetaDatosFarma = nf.numeroRecetaDatosFarma and df.idDatosClinicos = nf.idDatosClinicos");
				$stmt->bind_param("ii",$dc,$numero);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = "select df.idDatosFarma, p.nombrePrincipio, df.concentracionDatosFarma, u.abreviaturaUnidad, f.nombreFrecuencia, v.nombreVia, pr.nombrePresentacion, us.nombreUso, nf.detalleNotaFarma, df.cronicoDatosFarma, df.notaDatosFarma, q.nombreQuimico
				from DatosFarma df, Presentacion pr, Via v, Unidad u, Nota_Farma nf, Frecuencia f, Principio p, Uso us, Quimico q
				where df.idDatosClinicos = $dc and df.numeroRecetaDatosFarma = $numero and df.idPresentacion = pr.idPresentacion and df.idVia = v.idVia and df.idUnidad = u.idunidad and df.idFrecuencia = f.idFrecuencia and df.idPrincipio = p.idPrincipio and df.idUso = us.idUso and df.idQuimico = q.idQuimico and df.numeroRecetaDatosFarma = nf.numeroRecetaDatosFarma and df.idDatosClinicos = nf.idDatosClinicos";
					$row = mysqli_query($this->conexion, $sql);
					while($result = mysqli_fetch_assoc($row))
						$arr[] = $result;
					if (count($arr) == 0)
						$arr["error"] = 101;	
					else
						$arr["error"] = 100;
				}				
			}
			return $arr;	
		}
		
		public function pft_actual($paciente) 
		{
			$arr = array();
			if(is_numeric($paciente) && $paciente > 0)
			{
				$sql = "select nombrePaciente from Paciente where idPaciente = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if(isset($result["nombrePaciente"]))
					{
						$sql = "SELECT Distinct p.nombrePrincipio, df.*, nf.detalleNotaFarma, i.*, u.abreviaturaUnidad, f.nombreFrecuencia, v.nombreVia, pr.nombrePresentacion, us.nombreUso, q.nombreQuimico, concat_ws(' ',q.apellidoPaternoQuimico, q.apellidoMaternoQuimico) as apellidoQuimico
						from DatosFarma df 	
							left join Quimico q on df.idQuimico = q.idQuimico	
							left join Nota_Farma nf on (df.numeroRecetaDatosFarma = nf.numeroRecetaDatosFarma and df.idDatosClinicos = nf.idDatosClinicos)
							left join Principio p on df.idPrincipio = p.idPrincipio
							left join Idoneidad i on df.idDatosFarma = i.idDatosFarma
							left join Presentacion pr on df.idPresentacion = pr.idPresentacion
							left join Via v on df.idVia = v.idVia
							left join Unidad u on df.idUnidad = u.idUnidad
							left join Frecuencia f on df.idFrecuencia = f.idFrecuencia
							left join Uso us on df.idUso = us.idUso
						where df.idDatosClinicos in (select idDatosClinicos from DatosClinicos where idPaciente = ?)	
						and (df.numeroRecetaDatosFarma, Date(df.capturaDatosFarma), df.idDatosClinicos) in (select distinct max(df.numeroRecetaDatosFarma), max(Date(df.capturaDatosFarma)), max(df.idDatosClinicos) from DatosFarma df, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = df.idDatosClinicos)
						and i.idIdoneidad in (select max(i.idIdoneidad) from DatosClinicos dc, DatosFarma df, Idoneidad i where dc.idPaciente = ? and df.idDatosClinicos = dc.idDatosClinicos and i.idDatosFarma = df.idDatosFarma)
						order by p.nombrePrincipio desc";						
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("iii",$paciente, $paciente, $paciente);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $paciente, $sql);
							$row = mysqli_query($this->conexion, $sql);							
							while($result = mysqli_fetch_assoc($row))																			
								$arr[] = $result;																		
						}									
					}
					else
						$arr["error"] = 102;
				}
			}
			else
				$arr["error"] = 101;
			return $arr;															
		}
		
		public function pft_historial($paciente)
		{
			$arr = array();
			if(is_numeric($paciente) && $paciente > 0)
			{
				$sql = "select nombrePaciente from Paciente where idPaciente = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);					
					if(isset($result["nombrePaciente"]))
					{						
						$sql = "SELECT p.nombrePrincipio, df.*, nf.detalleNotaFarma, i.*, u.abreviaturaUnidad, f.nombreFrecuencia, v.nombreVia, pr.nombrePresentacion, us.nombreUso, q.nombreQuimico, concat_ws(' ',q.apellidoPaternoQuimico, q.apellidoMaternoQuimico) as apellidoQuimico, df.idDatosFarma, if(1=1,1,0) as revisar
												from DatosFarma df 	
													left join Quimico q on df.idQuimico = q.idQuimico	
													left join Nota_Farma nf on (df.numeroRecetaDatosFarma = nf.numeroRecetaDatosFarma and df.idDatosClinicos = nf.idDatosClinicos)
													left join Principio p on df.idPrincipio = p.idPrincipio
													left join Idoneidad i on (df.idDatosFarma = i.idDatosFarma and i.idIdoneidad in (
													Select max(idIdoneidad) From Idoneidad group by idDatosFarma))
													left join Presentacion pr on df.idPresentacion = pr.idPresentacion
													left join Via v on df.idVia = v.idVia
													left join Unidad u on df.idUnidad = u.idUnidad
													left join Frecuencia f on df.idFrecuencia = f.idFrecuencia
													left join Uso us on df.idUso = us.idUso																						
												where df.idDatosClinicos in (select idDatosClinicos from DatosClinicos where idPaciente = ?)							
												group by df.capturaDatosFarma, p.nombrePrincipio
												order by df.capturaDatosFarma desc";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i", $paciente);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $paciente, $sql);
							$row = mysqli_query($this->conexion, $sql);							
							$auxi = 0;
							while($result = mysqli_fetch_assoc($row))
							{								
								if (!isset($aux))
									$aux = $result['capturaDatosFarma'];																								
								if ($aux == $result['capturaDatosFarma'])
									$arr[$auxi][] = $result;
								else
								{
									$aux = $result['capturaDatosFarma'];	
									$auxi++;																							
									$arr[$auxi][] = $result;
								}																													
							}							
						}
						else
							$arr["error"] = 103;										
					}
					else
						$arr["error"] = 102;
				}
			}
			else
				$arr["error"] = 101;
			return $arr;								
		}
		
		
		#PFT a partir de un dato clínico
		public function pft_clinicos($clinicos) 
		{
			$arr = array();
			if(is_numeric($clinicos) && $clinicos > 0)
			{
				$sql = "select idPaciente from DatosClinicos where idDatosClinicos = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$clinicos);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $clinicos, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);										
					if(isset($result["idPaciente"]))
					{
						$sql = "SELECT Distinct p.nombrePrincipio, df.concentracionDatosFarma, u.abreviaturaUnidad, f.nombreFrecuencia, v.nombreVia, Date(df.capturaDatosFarma), ps.nombrePresentacion, us.nombreUso, nf.detalleNotaFarma, df.cronicoDatosFarma, df.notaDatosFarma, dc.doctorDatosClinicos, q.nombreQuimico
						from DatosFarma df, DatosClinicos dc, Presentacion ps, Via v, Unidad u, Nota_Farma nf, Frecuencia f, Principio p, Uso us, Quimico q
						where dc.idDatosClinicos = ? and df.idDatosClinicos = dc.idDatosClinicos and df.idPresentacion = ps.idPresentacion and df.idVia = v.idVia and df.idUnidad = u.idunidad and df.idFrecuencia = f.idFrecuencia and df.idPrincipio = p.idPrincipio and df.idUso = us.idUso and df.idDatosClinicos = nf.idDatosClinicos and df.numeroRecetaDatosFarma = nf.numeroRecetaDatosFarma and df.idQuimico = q.idQuimico 
						order by df.capturaDatosFarma, df.numeroRecetaDatosFarma, p.nombrePrincipio asc";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i",$clinicos);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $clinicos, $sql);
							$row = mysqli_query($this->conexion, $sql);										
							while($result = mysqli_fetch_assoc($row))																								
								$arr[] = $result;														
							$arr["error"] = 100;	
						}									
					}
					else
						$arr["error"] = 102;
				}
			}
			else
				$arr["error"] = 101;
			return $arr;				
		}
		
		public function conciliacion($dc, $numero) 
		{
			$arr = array();
			$lastPFT = array();
			if(is_numeric($dc) && $dc > 0)
				if(is_numeric($numero) && $numero > 0)
				{					 
					$result = mysqli_query($this->conexion, "Select idPaciente from DatosClinicos where idDatosClinicos = $dc");
					$row = mysqli_fetch_assoc($result);
					$farmaData = $this->pft_historial($row['idPaciente']);
					$alerta = false;
					$fecha = date('Y-m-d H:i:s');					
					for ($i=0; $i<count($farmaData); $i++)
						if ($farmaData[$i][0]['numeroRecetaDatosFarma'] == $numero)
						{
							if ($numero > 1)
								$lastPFT = $farmaData[$i+1];
							$farmaData = $farmaData[$i];
							break;
						}
					for ($i=0; $i<count($farmaData); $i++)
					{						
						$cont = 0;
						for ($j=$i+1; $j<count($farmaData); $j++)
							if($farmaData[$i]["idPrincipio"] == $farmaData[$j]["idPrincipio"])						
								$cont++;
						if($cont > 0)
						{
							$alerta = true;
							$auxp = $farmaData[$i]["nombrePrincipio"];
							mysqli_query($this->conexion, "insert into Alerta values(null, '$auxp: repetido $cont vez/veces','$fecha', '', $dc, $numero, null, 1)");
							$arr["error"] = 103;
						}
					}
					if(!$alerta)
						if($numero >1)
						{   							
							for($i = 0; $i<(count($lastPFT)); $i++)	
							{						
								if($lastPFT[$i]["cronicoDatosFarma"] == 1)
								{
									$auxnom = true;
									for($j=0; $j<count($farmaData); $j++)                        				
										if($lastPFT[$i]["idPrincipio"] == $farmaData[$j]["idPrincipio"])
										{                        					
											$auxnom = false;
											if(!isset($lastPFT[$i]["idFrecuencia"]) || ($lastPFT[$i]["idFrecuencia"] == $farmaData[$j]["idFrecuencia"]))
												if(!isset($lastPFT[$i]["concentracionDatosFarma"]) || ($lastPFT[$i]["concentracionDatosFarma"] == $farmaData[$j]["concentracionDatosFarma"]))
													break;
												else
												{
													$alerta["mensaje"] = "Concentraciones diferentes.";  
													$alerta["medicamento"] = $lastPFT[$i]["nombrePrincipio"];
													break;
												}
											else
											{
												$alerta["medicamento"] = $lastPFT[$i]["nombrePrincipio"];	
												$alerta["mensaje"] = "Frencuencia diferente.";
												break;
											}
										}
									if($auxnom)
									{
										$alerta["medicamento"] = $lastPFT[$i]["nombrePrincipio"];	
										$alerta["mensaje"] = "Medicamento crónico faltante.";
									}
									if(isset($alerta["mensaje"]))
									{										
										mysqli_query($this->conexion, "insert into Alerta values (null, '".$alerta["medicamento"].": ".$alerta["mensaje"]."', '$fecha', '', $dc,$numero,null, 1)");										
										$alerta = array();
										$arr["error"] = 103;                        				                    
									}									                        				                        			
								}
								else
								{
									for($j=0; $j<count($farmaData); $j++)                        				
										if($lastPFT[$i]["idPrincipio"] == $farmaData[$j]["idPrincipio"])
										{                        					
											if(!isset($lastPFT[$i]["idFrecuencia"]) || ($lastPFT[$i]["idFrecuencia"] == $farmaData[$j]["idFrecuencia"]))
												if(!isset($lastPFT[$i]["concentracionDatosFarma"]) || ($lastPFT[$i]["concentracionDatosFarma"] == $farmaData[$j]["concentracionDatosFarma"]))
													break;
												else
												{
													$alerta["mensaje"] = "Concentraciones diferentes.";  
													$alerta["medicamento"] = $lastPFT[$i]["nombrePrincipio"];
													break;
												}
											else
											{
												$alerta["medicamento"] = $lastPFT[$i]["nombrePrincipio"];	
												$alerta["mensaje"] = "Frencuencia diferente.";
												break;
											}
										}
									if(isset($alerta["mensaje"]))
									{										
										mysqli_query($this->conexion, "insert into Alerta values (null, '".$alerta["medicamento"].": ".$alerta["mensaje"]."', '$fecha', '', $dc,$numero,null, 1)");										
										$alerta = array();
										$arr["error"] = 103;                        				                    
									}									
								}
							}	
						}  
				}
				else
					$arr["error"] = 102;	
			else 
				$arr["error"] = 101;
			//return $arr;
		}

		// TEMPLATE Alertas
		
		public function alertaDatos_Pendientes($dc, $numero, $tipo) 
		{
			$arr = array();
			if (is_numeric($dc)	&& $dc > 0)
				if (is_numeric($numero)	&& $numero > 0)
					if (is_numeric($tipo) && $tipo > 0 && $tipo < 4)
					{
						$sql = "select p.nombrePaciente, p.apellidoPaternoPaciente, p.apellidoMaternoPaciente from Paciente p, DatosClinicos dc where dc.idDatosClinicos = ? and dc.idPaciente = p.idPaciente";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i",$dc);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $dc, $sql);
							$row = mysqli_query($this->conexion, $sql);
							$result = mysqli_fetch_assoc($row);							
							if (isset($result["nombrePaciente"]))
							{	
								$fecha = date('Y-m-d H:i:s');
								if(!isset($result["apellidoMaternoPaciente"]))
									$result["apellidoMaterno"] = '';
								if ($tipo == 1){
									$descripcionAlerta = 'Falta completar datos clínicos';									
									$op = 3; 
								}									
								if ($tipo == 2){
									$descripcionAlerta = 'Falta completar la prescripción';	
									$op = 1;								
								}									
								if ($tipo == 3)
								{
									$descripcionAlerta = 'Falta completar la idoneidad';									
									$op = 3;
								}
								$sql = "INSERT INTO Alerta (descripcionAlerta, fechaAlerta, idDatosClinicos, numeroReceta, idTipoAlerta) VALUES(?,?,?,?,?)";
								$stmt = $this->conexion->prepare($sql);
								$stmt->bind_param("ssiii",$descripcionAlerta, $fecha,$dc, $numero, $op);
								if($stmt->execute())
									$arr["error"] = 100;	
							}
							else 
								$arr["error"] = 104;							
						}							
					}
					else
						$arr["error"] = 103;
				else
					$arr["error"] = 102;
			else
				$arr["error"] = 101;
		}

		public function accionAlertas()
		{
			$result = mysqli_query($this->conexion,"select * from Accion");
			$arr = array();
			while ($row = mysqli_fetch_assoc($result)) 
				$arr[] = $row;
			return $arr;
		}

		public function getAlerta_Pendiente($paciente)
		{
			$arr = array();
			if(is_numeric($paciente) && $paciente > 0)
			{
				$sql = "select nombrePaciente from Paciente where idPaciente = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);

					if (isset($result["nombrePaciente"]))
					{
						$sql = "select a.* from Alerta a, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = a.idDatosClinicos and a.idAccion is not null order by fechaAlerta desc";
						$sql1 = "select a.* from Alerta a, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = a.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 1 order by fechaAlerta desc";
						$sql2 = "select a.* from Alerta a, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = a.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 2 order by fechaAlerta desc";
						$sql3 = "select a.* from Alerta a, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = a.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 3 order by fechaAlerta desc";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i",$paciente);
						if($stmt->execute())
						{

							$myArray = array();
							$stmt->close();
							$sql = str_replace("?", $paciente, $sql);
							$row = mysqli_query($this->conexion, $sql);						
							while($resultA = mysqli_fetch_assoc($row))																
								$myArray[] = $resultA;
							$arr["all"] = $myArray;

							$myArray = array();
							$sql1 = str_replace("?", $paciente, $sql1);
							$row = mysqli_query($this->conexion, $sql1);						
							while($result1 = mysqli_fetch_assoc($row))																
								$myArray[] = $result1;
							$arr["1"] = $myArray;

							$myArray = array();
							$sql2 = str_replace("?", $paciente, $sql2);
							$row = mysqli_query($this->conexion, $sql2);						
							while($result2 = mysqli_fetch_assoc($row))																
								$myArray[] = $result2;
							$arr["2"] = $myArray;

							$myArray = array();
							$sql3 = str_replace("?", $paciente, $sql3);
							$row = mysqli_query($this->conexion, $sql3);						
							while($result3 = mysqli_fetch_assoc($row))																
								$myArray[] = $result3;
							$arr["3"] = $myArray;														
							
							$myArray = array();
							$row = $this->getInteraccion($paciente);
							foreach ($row as $key => $value) 							
								$myArray[] = $value;
							$arr['interaccion'] = $myArray;	
							$arr["error"] = 100;	
						}
					}							
				}
				else
					$arr["error"] = 102;
			}
			else
				$arr["error"] = 101;
			return $arr;
		}
		
		public function getAlerta_Pendiente_Conciliacion($paciente)
		{
			$arr = array();
			if(is_numeric($paciente) && $paciente > 0)
			{
				$sql = "select nombrePaciente from Paciente where idPaciente = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);						
					if (isset($result["nombrePaciente"]))
					{
						$sql = "select select a.* from Alertas a, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = a.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 1";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i",$paciente);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $paciente, $sql);
							$row = mysqli_query($this->conexion, $sql);											
							while($result = mysqli_fetch_assoc($row))																
								$arr[] = $result;							
							$arr["error"] = 100;	
						}
					}					
					else
						$arr["error"] = 102;		
				}
				else
					$arr["error"] = 102;				
			}
			else
				$arr["error"] = 101;
			return $arr;
		}
		
		public function getAlerta_Pendiente_Idoneidad($paciente)
		{
			$arr = array();
			if(is_numeric($paciente) && $paciente > 0)
			{
				$sql = "select nombrePaciente from Paciente where idPaciente = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);						
					if (isset($result["nombrePaciente"]))
					{
						$sql = "select a.* from Alertas a, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = a.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 2";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i",$paciente);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $paciente, $sql);
							$row = mysqli_query($this->conexion, $sql);					
							while($result = mysqli_fetch_assoc($row))																	
								$arr[] = $result;							
							$arr["error"] = 100;	
						}
					}
					else
						$arr["error"] = 102;							
				}
				else
					$arr["error"] = 102;
			}
			else
				$arr["error"] = 101;
			return $arr;
		}

		private function getInteraccionByClinialDataAndPrescription($dc, $pres) 
		{
			$arr = array();
			$dc = intval($dc);
			$pres = intval($pres);
			if ($dc > 0 && $pres > 0)	
			{
				$sql = "Select i.*, p.nombrePrincipio from Interaccion i, Principio p where i.idDatosClinicos = $dc and i.numeroReceta = $pres and p.idPrincipio = i.idPrincipio";
				$result = mysqli_query($this->conexion, $sql);
				if ($result)
					while($row = mysqli_fetch_assoc($result))
						$arr[] = $row;
			}
			return $arr;
		}
		
		public function getInteraccion($paciente, $dc = null) 
		{
			$arr = array();
			if(is_numeric($paciente) && $paciente >0)
			{
				$sql = "select nombrePaciente from Paciente where idPaciente = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if(isset($result["nombrePaciente"]))
					{
						$aux = "";
						if(isset($dc))
							if(is_numeric($dc) && $dc > 0)
								$aux = " and dc.idDatosClinicos = $dc ";
						$sql = "select i.* from Interaccion i, DatosClinicos dc where dc.idPaciente = ? and dc.idDatosClinicos = i.idDatosClinicos ".$aux." order by i.idInteraccion asc";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i",$paciente);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $paciente, $sql);
							$row = mysqli_query($this->conexion, $sql);					
							while($result = mysqli_fetch_assoc($row))								
								$arr[] = $result;
							if(count($arr) > 0)
							{
								$prim = 0;
								$ot = array();
								for ($i = 0; $i<count($arr); $i++)
								{
									if($prim != $arr[$i]["idInteraccion"])
									{
										$ot[] = array();										
										$prim = $arr[$i]["idInteraccion"];	
										$ot[count($ot)-1]['detallesInteraccion'] = $arr[$i]["detallesInteraccion"];
										$ot[count($ot)-1]['sugerenciaInteraccion'] = $arr[$i]["sugerenciaInteraccion"];									
										$ot[count($ot)-1]['alimentoInteraccion'] = $arr[$i]["alimentoInteraccion"];									
										$ot[count($ot)-1]['categorizacionInteraccion'] = $arr[$i]["categorizacionInteraccion"];									
										$ot[count($ot)-1]['tipoInteraccion'] = $arr[$i]["tipoInteraccion"];												
										$ot[count($ot)-1]['numeroReceta'] = $arr[$i]["numeroReceta"];
										$ot[count($ot)-1]['fechaCapturaInteraccion'] = $arr[$i]["fechaCapturaInteraccion"];		
										$ot[count($ot)-1]['medicamentos'] = array();							
									}
									$result = mysqli_query($this->conexion, "select idPrincipio, nombrePrincipio from Principio where idPrincipio = ".$arr[$i]["idPrincipio"]);
									$row = mysqli_fetch_assoc($result);
									$aux = array_search($row["nombrePrincipio"], $ot[count($ot)-1]['medicamentos']);
									if(!$aux)
										$ot[count($ot)-1]['medicamentos'][] = $row;
								}								
								$arr = $ot;								
							}
						}
					}
					else
					$arr["error"]=102;			
				}
			}
			else
				$arr["error"]=101;
			return $arr;	
		}


// TEMPLATE ALERGIAS
		public function getAlergiesByClinicalData($clinicalData_id){
			if(is_numeric($clinicalData_id) && ($clinicalData_id > 0)){
				$arr = array();
				$sql = "SELECT a.* FROM datosclinicos dc , paciente p, alergia_paciente ap, alergia a WHERE dc.idDatosClinicos = $clinicalData_id and p.idPaciente = dc.idPaciente and ap.idPaciente = p.idPaciente and a.idAlergia = ap.idAlergia";
				$row = mysqli_query($this->conexion, $sql);					
				while($result = mysqli_fetch_assoc($row))																
					$arr[] = $result;
				return $arr;
			}else{
				return NULL;
			}
		}
		public function getAlergias($idP) //Recive id de paciente
		{
			$arr = array();
			if(is_numeric($idP) && $idP > 0)
			{
				$sql = "select nombrePaciente from Paciente where idPaciente = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$idP);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $idP, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if (isset($result["nombrePaciente"]))
					{
						$sql = "select a.* from Alergia_Paciente ap, Alergia a where ap.idPaciente = ? and ap.idAlergia = a.idAlergia";
						$stmt = $this->conexion->prepare($sql);
						$stmt->bind_param("i",$idP);
						if($stmt->execute())
						{
							$stmt->close();
							$sql = str_replace("?", $idP, $sql);
							$row = mysqli_query($this->conexion, $sql);					
							while($result = mysqli_fetch_assoc($row))	
							{	    		
								$result['nombreAlergia'] = ucwords(strtolower($result["nombreAlergia"]));		    			 											
								$arr[] = $result;
							}								
						}
						$arr["error"] = 100;
					}
					else
						$arr["error"] = 102;
				}
			}
			else
				$arr["error"] = 101;
			return $arr;			
		}

//QUIMICOS
		//UPDATE CHEMIST
		public function updateChemist($chemist){

			//this method does not update the password
			$stmt = $this->conexion->prepare("UPDATE Quimico Q set 
	                                                        Q.nombreQuimico = ?, 
	                                                        Q.apellidoPaternoQuimico = ?, 
	                                                        Q.apellidoMaternoQuimico = ?, 
	                                                        Q.loginQuimico = ?, 
	                                                        Q.emailQuimico = ?, 
	                                                        Q.telefonoQuimico = ?,
	                                                        Q.extensionQuimico = ?,
	                                                        Q.idHospital = ?,
	                                                        Q.idTurno = ?
	                                                        WHERE Q.idQuimico = ?");
	        $stmt->bind_param("sssssiiiii", $chemist["nombreQuimico"],
	                                        $chemist["apellidoPaternoQuimico"],
	                                        $chemist["apellidoMaternoQuimico"],
	                                        $chemist["loginQuimico"],
	                                        $chemist["emailQuimico"],
	                                        $chemist["telefonoQuimico"],
	                                        $chemist["extensionQuimico"],
	                                        $chemist["idHospital"],
	                                        $chemist["idTurno"], $chemist["idQuimico"]);
	        
	        if($stmt->execute()){
	            $stmt->close();
	    		return TRUE;        
	        }else{  
            	$stmt->close();
	            return FALSE;
	        }
		}
		//ACTIVATE CHEMIST
		public function activeChemists($hospital){
			$arr = array();
			$result = mysqli_query($this->conexion, "SELECT idQuimico, nombreQuimico, apellidoPaternoQuimico, apellidoMaternoQuimico, emailQuimico, telefonoQuimico, extensionQuimico, idTurno FROM Quimico WHERE idHospital = $hospital and idTurno = 1");
			while($row = mysqli_fetch_assoc($result))
			{
				$arr[] = $row;												
			}
			return $arr;	
		}

		public function allChemists($hospital){
			$arr = array();
			$result = mysqli_query($this->conexion, "SELECT idQuimico, nombreQuimico, apellidoPaternoQuimico, apellidoMaternoQuimico, emailQuimico, telefonoQuimico, extensionQuimico, idTurno FROM Quimico  WHERE idHospital = $hospital");
			while($row = mysqli_fetch_assoc($result))
			{
				$arr[] = $row;												
			}
			return $arr;	
		}

// TEMPLATE INICIO
	//Pendientes
		public function pacientesPendientes($hospital,$fechaHoy){      //FALTA AVERIGUAR CUAL ES EL TURNO ACTAL Y LA FECHA DE HOY.
			$result = mysqli_query($this->conexion, "select count( * ) from Paciente where idEstatusVisita = 1 and idEstatusPaciente = 1 and idHospital = $idHospital");
			$arr = array();
			while ($row = mysqli_fetch_assoc($result)) 
				$arr[] = $row;
			return $arr;		
		}
	//Internos	
		public function patientInternById($patient_id, $hospital){
			$arr = array();
			$result = mysqli_query($this->conexion, "select p.idPaciente, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS edad, IF(p.sexoPaciente = 'M', 'Masculino', 'Femenino') as sexoPaciente, if(p.idEstatusPaciente = 1, 'Pendiente', 'Visitado') as estatusVisita, s.nombreServicio, dc.identificadorPacienteDatosClinicos, dc.ingresoDatosClinicos from Paciente p, DatosClinicos dc, Servicio s where p.idHospital = $hospital and p.idPaciente = $patient_id and p.idEstatusPaciente = 1 and p.idPaciente = dc.idPaciente and dc.idServicio = s.idServicio and (p.idPaciente, dc.idDatosClinicos) in (select max(idPaciente), max(idDatosClinicos) from DatosClinicos group by idPaciente) order by dc.ingresoDatosClinicos desc");
			while($row = mysqli_fetch_assoc($result))
			{
				$row["nombrePaciente"] = ucwords(strtolower($row["nombrePaciente"]));
				$row["apellidoPaternoPaciente"] = ucwords(strtolower($row["apellidoPaternoPaciente"]));
				$arr[] = $row;												
			}
			return $arr;	
		}
        public function getPatientById($patient_id, $hospital){
			$arr = array();
			$result = mysqli_query($this->conexion, "select p.idPaciente, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS edad, IF(p.sexoPaciente = 'M', 'Masculino', 'Femenino') as sexoPaciente, if(p.idEstatusPaciente = 1, 'Pendiente', 'Visitado') as estatusVisita, s.nombreServicio, dc.identificadorPacienteDatosClinicos, dc.ingresoDatosClinicos from Paciente p, DatosClinicos dc, Servicio s where p.idHospital = $hospital and p.idPaciente = $patient_id and p.idEstatusPaciente = 1 and p.idPaciente = dc.idPaciente and dc.idServicio = s.idServicio and (p.idPaciente, dc.idDatosClinicos) in (select max(idPaciente), max(idDatosClinicos) from DatosClinicos group by idPaciente) order by dc.ingresoDatosClinicos desc");
			while($row = mysqli_fetch_assoc($result))
			{
				$row["nombrePaciente"] = ucwords(strtolower($row["nombrePaciente"]));
				$row["apellidoPaternoPaciente"] = ucwords(strtolower($row["apellidoPaternoPaciente"]));
				$arr[] = $row;												
			}
			return $arr;	
		}
		public function pacientesInternos($hospital){
			$arr = array();
			$result = mysqli_query($this->conexion, "select p.idPaciente, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS edad, IF(p.sexoPaciente = 'M', 'Masculino', 'Femenino') as sexoPaciente, if(p.idEstatusPaciente = 1, 'Pendiente', 'Visitado') as estatusVisita, s.nombreServicio, dc.identificadorPacienteDatosClinicos, dc.ingresoDatosClinicos, dc.camaDatosClinicos, dc.doctorDatosClinicos, dc.observacionDatosClinicos, dc.diagnosticoDatosClinicos, dc.motivoDatosClinicos from Paciente p left join  DatosClinicos dc on (dc.idPaciente = p.idPaciente and (p.idPaciente, dc.idDatosClinicos) in (select max(idPaciente), max(idDatosClinicos) from DatosClinicos group by idPaciente)) left join  Servicio s on (dc.idServicio = s.idServicio) where p.idHospital = $hospital and p.idEstatusPaciente = 1 order by dc.ingresoDatosClinicos desc");
			while($row = mysqli_fetch_assoc($result))
			{
				$row["nombrePaciente"] = ucwords(strtolower($row["nombrePaciente"]));
				$row["apellidoPaternoPaciente"] = ucwords(strtolower($row["apellidoPaternoPaciente"]));
				$arr[] = $row;												
			}
			return $arr;	
		}
	//pendientes con descripcion
		public function describeInternos($hospital){
			$result = mysqli_query($this->conexion,"select dc.camaDatosClinicos as cama, dc.ingresoDatosClinicos as fechaIngreso, dc.tallaDatosClinicos as talla, dc.pesoDatosClinicos as peso, p.nombrePaciente as nombre, p.sexoPaciente as genero from Paciente p, DatosClinicos dc where p.idHospital=$hospital and p.idEstatusPaciente=1 and dc.idPaciente = p.idPaciente");
			$arr = array();
			while ($row = mysqli_fetch_assoc($result)) 
				$arr[] = $row;
			return $arr;				
		}

		//Recuperar el total de alertas pendientes
		public function getTotalAlertas($hospital) 
		{
			$arr = array();
			if(is_numeric($hospital) && $hospital >0)
			{
				$sql = "select nombreHospital from Hospital where idHospital = ?";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$hospital);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $hospital, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if (isset($result["nombreHospital"]))
					{
						$aux = array();						
						$sql = "Select a.*, p.idPaciente from Paciente p, DatosClinicos dc, Alerta a where p.idHospital = $hospital and dc.idPaciente = p.idPaciente and a.idDatosClinicos = dc.idDatosClinicos and a.idAccion is null";						
						$row = mysqli_query($this->conexion, $sql);					
						while($result = mysqli_fetch_assoc($row))							
							$aux[] = $result;															
						$arr["all"] = $aux;	
						$aux = array();						
						$sql = "Select a.*, p.idPaciente, concat_ws(' ',p.nombrePaciente, p.apellidoPaternoPaciente) as Paciente from Paciente p, DatosClinicos dc, Alerta a where p.idHospital = $hospital and dc.idPaciente = p.idPaciente and a.idDatosClinicos = dc.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 1";						
						$row = mysqli_query($this->conexion, $sql);					
						while($result = mysqli_fetch_assoc($row))
						{
							$result["Paciente"] = ucwords(strtolower($result["Paciente"]));
							$aux[] = $result;															
						}
						$arr["1"] = $aux;							
						$aux = array();						
						$sql = "Select a.*, p.idPaciente, concat_ws(' ',p.nombrePaciente, p.apellidoPaternoPaciente) as Paciente from Paciente p, DatosClinicos dc, Alerta a where p.idHospital = $hospital and dc.idPaciente = p.idPaciente and a.idDatosClinicos = dc.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 2";						
						$row = mysqli_query($this->conexion, $sql);					
						while($result = mysqli_fetch_assoc($row))
						{
							$result["Paciente"] = ucwords(strtolower($result["Paciente"]));
							$aux[] = $result;												
						}			
						$arr["2"] = $aux;					
						$aux = array();								
						$sql = "Select a.*, p.idPaciente, concat_ws(' ',p.nombrePaciente, p.apellidoPaternoPaciente) as Paciente from Paciente p, DatosClinicos dc, Alerta a where p.idHospital = $hospital and dc.idPaciente = p.idPaciente and a.idDatosClinicos = dc.idDatosClinicos and a.idAccion is null and a.idTipoAlerta = 3";						
						$row = mysqli_query($this->conexion, $sql);					
						while($result = mysqli_fetch_assoc($row))
						{
							$result["Paciente"] = ucwords(strtolower($result["Paciente"]));
							$aux[] = $result;
						}
						$row = mysqli_query($this->conexion, "SELECT i.*, i.detallesInteraccion as descripcionAlerta, p.idPaciente, concat_ws(' ',p.nombrePaciente, p.apellidoPaternoPaciente) as Paciente from Interaccion i, DatosClinicos dc, Paciente p where dc.idPaciente = p.idPaciente and dc.idDatosClinicos = i.idDatosClinicos and p.idHospital = $hospital");
						while($result3 = mysqli_fetch_assoc($row))																
						{
							$result["Paciente"] = ucwords(strtolower($result["Paciente"]));
							$aux[] = $result3;															
						}
						$arr["3"] = $aux;					
						$aux = array();								
						$arr["error"] = 100;	
					}
					else
						$arr["error"] = 102;
				}				
			}			
			else
				$arr["error"] = 101;
			return $arr;
		}
		//CLINICAL DATA FUNCTIONS
		public function insertClinicalData($clinicalData){
			// insert query                  
            $stmt = $this->conexion->prepare("INSERT INTO DatosClinicos(ingresoDatosClinicos, 
                                                                capturaDatosClinicos, 
                                                                pesoDatosClinicos, 
                                                                tallaDatosClinicos, 
                                                                imcDatosClinicos, 
                                                                ascDatosClinicos, 
                                                                identificadorPacienteDatosClinicos, 
                                                                camaDatosClinicos,
                                                                motivoDatosClinicos,
                                                                diagnosticoDatosClinicos,
                                                                doctorDatosClinicos,
                                                                observacionDatosClinicos,
                                                                idPaciente,
                                                                idServicio) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiddssssssii", 
                                            $clinicalData["ingresoDatosClinicos"],
                                            $clinicalData["capturaDatosClinicos"], 
                                            $clinicalData["pesoDatosClinicos"], 
                                            $clinicalData["tallaDatosClinicos"], 
                                            $clinicalData["imcDatosClinicos"], 
                                            $clinicalData["ascDatosClinicos"], 
                                            $clinicalData["identificadorPacienteDatosClinicos"], 
                                            $clinicalData["camaDatosClinicos"], 
                                            $clinicalData["motivoDatosClinicos"], 
                                            $clinicalData["diagnosticoDatosClinicos"], 
                                            $clinicalData["doctorDatosClinicos"], 
                                            $clinicalData["observacionDatosClinicos"], 
                                            $clinicalData["idPaciente"], 
                                            $clinicalData["idServicio"]);                                               
            $result = $stmt->execute();
            $clinicalData_id = $stmt->insert_id;                
            $stmt->close(); 

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                $clinicalData["idDatosClinicos"] = $clinicalData_id;
                return $clinicalData;
            } else {
                return NULL;
            }
		}

// TEMPLATE perfil Paciente
	// PERFIL y DATOS CLINICOS MAS RECIENTES
		public function perfilPaciente($idPaciente)
		{
			$arr = array();
			if (is_numeric($idPaciente) && $idPaciente != 0)
			{
				$sql = "SELECT dc.pesoDatosClinicos as peso,
					dc.ingresoDatosClinicos as fechaIngreso,
					dc.capturaDatosClinicos as fechaCaptura, 
					dc.tallaDatosClinicos as talla,
					dc.imcDatosClinicos as imc,
					dc.observacionesDatosClinicos as observaciones,
					dc.iscDatosClinicos as isc, 
					dc.identificadorPacienteDatosClinicos as cuenta, 
					dc.camaDatosClinicos as cama, 
					dc.motivoDatosClinicos as motivoIngreso, 
					dc.diagnosticoDatosClinicos as diagnostico, 
					dc.doctorDatosClinicos as doctor,
					s.nombreServicio,
				    p.nombrePaciente,
				    p.apellidoMaternoPaciente as aMaterno,
				    p.apellidoPaternoPaciente as aPaterno,
				    p.nacimientoPaciente as fechaEdad
					from Datosclinicos dc, Servicio s, Paciente p
					where p.idPaciente = ?
				    and p.idPaciente = dc.idPaciente
					and capturaDatosClinicos = (select max(capturaDatosClinicos) from datosclinicos where idPaciente = $idPaciente)
					and s.idServicio = dc.idServicio";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$idPaciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if(isset($result["nombrePaciente"]))
					{ 					
						$arr[] = $result;					
						$arr["error"] = 100;						
					}else
						$arr["error"] = 102;											
				}								
			}
			else 
				$arr['error'] = 101;								
			return $arr;			
		}

	// HISTORIAL DE DATOS CLINICOS
		public function historialDC($idPaciente) 
		{
			$result = mysqli_query($this->conexion,"SELECT dc.pesoDatosClinicos as peso,
				dc.capturaDatosClinicos as fechaCaptura, 
				dc.identificadorPacienteDatosClinicos as cuenta, 
				dc.camaDatosClinicos as cama, 
				dc.motivoDatosClinicos as motivoIngreso, 
				dc.diagnosticoDatosClinicos as diagnostico, 
				dc.doctorDatosClinicos as doctor,
				dc.observacionesDatosClinicos as observaciones,
				s.nombreServicio,
                p.nombrePaciente,
                p.apellidoMaternoPaciente as aMaterno,
                p.apellidoPaternoPaciente as aPaterno,
                p.nacimientoPaciente as fechaEdad
				from DatosClinicos dc, Servicio s, Paciente p
				where p.idPaciente = $idPaciente
                and p.idPaciente = dc.idPaciente
				and s.idServicio = dc.idServicio
                order by dc.capturaDatosClinicos desc");
			$arr = array();
			while ($row = mysqli_fetch_assoc($result)) 
				$arr[] = $row;
			return $arr;			
		}


// TEMPLATE CAPTURA DC
		public function opcionesConcentracion()
		{
			$result = mysqli_query($this->conexion,"select * from Unidad order by nombreUnidad asc");
			$arr = array();
			if($result)
			{
				while ($row = mysqli_fetch_assoc($result)) 
					$arr[] = $row;
				$arr["error"] = false;
			}else
				$arr["error"] = true;
			return $arr;
		}
	
		public function opcionesPresentacion()
		{
			$result = mysqli_query($this->conexion,"select * from Presentacion order by nombrePresentacion asc");
			$arr = array();
			if($result)
			{
				while ($row = mysqli_fetch_assoc($result)) 
					$arr[] = $row;
				$arr["error"] = false;
			}else
				$arr["error"] = true;
			return $arr;
		}
	
		public function opcionesVia()
		{
			$result = mysqli_query($this->conexion,"select * from Via order by nombreVia asc");
			$arr = array();
			if($result)
			{
				while ($row = mysqli_fetch_assoc($result))
				{
					$row["nombreVia"] = utf8_decode($row["nombreVia"]);
					$arr[] = $row;
				}
				$arr["error"] = false;
			}else
				$arr["error"] = true;
			return $arr;
		}
			
		public function opcionesFrecuencia()
		{
			$result = mysqli_query($this->conexion,"select * from Frecuencia order by nombreFrecuencia asc");
			$arr = array();
			if($result)
			{
				while ($row = mysqli_fetch_assoc($result)) 
					$arr[] = $row;
				$arr["error"] = false;
			}else
				$arr["error"] = true;
			return $arr;
		}
		
		public function opcionesUso()
		{
			$result = mysqli_query($this->conexion,"select * from Uso order by nombreUso asc");
			$arr = array();
			if($result)
			{
				while ($row = mysqli_fetch_assoc($result)) 
					$arr[] = $row;
				$arr["error"] = false;
			}else
				$arr["error"] = true;
			return $arr;
		}
	
	// insercion a CAMBIOS
		public function setCambios($idrow, $tabla, $idUsuario, $accion)
		{
			$stmt = $this->conexion->prepare("INSERT INTO Cambios($tabla,$idRow,$idUsuario, $accion
	                                                     values(?,?,?,?)");
	    	$stmt->bind_param("siii",$tabla,$idRow,$idUsuario,$accion );
	        if($stmt->execute()){	        	
	        	$stmt->close();
	        }else{
	        	return "Error al insertar en cambios";
	        }
	    }
	    
	    public function servicios($hospital) 
	    {
	    	$arr = array();
	    	if(is_numeric($hospital) && $hospital > 0)
	    	{
	    		$sql = "select nombreHospital from Hospital where idHospital = ?";
	    		$stmt = $this->conexion->prepare($sql);
	    		$stmt->bind_param("i",$hospital);
	    		if($stmt->execute())
	    		{	        	
	    			$stmt->close();
	    			$sql = str_replace("?", $hospital, $sql);
	    			$row = mysqli_query($this->conexion, $sql);
	    			$result = mysqli_fetch_assoc($row);
	    			if(isset($result["nombreHospital"]))
	    			{
	    				$result = mysqli_query($this->conexion, "select s.* from Servicio s, Servicio_Hospital sh where sh.idHospital = $hospital and sh.idServicio = s.idServicio order by s.nombreServicio");
	    				while($row = mysqli_fetch_assoc($result))
	    					$arr[]=$row;
	    				$arr["error"] = 100;	
	    			}else
	    				$arr["error"] = 102;
	    		}	
	    	}else
	    		$arr["error"] = 101;
	    	return $arr;
	    }

	    public function unlinkAlergy($patient_id, $alergy_id){


	    	$stmt = $this->conexion->prepare("DELETE FROM Alergia_Paciente WHERE idPaciente = ? AND idAlergia = ?");
	    	$stmt->bind_param("ii", $patient_id, $alergy_id);
	    	$result = $stmt->execute();
	    	if($result){
	    		$stmt->close();
	    		return TRUE;
	    	}else{
	    		return FALSE;
	    	}

	    }
	    
	    public function alergias() 
	    {
	    	$arr = array();
	    	$result = mysqli_query($this->conexion, "select * from Alergia order by nombreAlergia");	
	    	if ($result)
	    	{
	    		while ($row = mysqli_fetch_assoc($result))
	    		{	    		
	    			$row['nombreAlergia'] = ucwords(strtolower($row["nombreAlergia"]));		    			 
	    			$arr[] = $row;					
	    		}
	    		$arr["error"] = false;
	    	}else
	    		$arr["error"] = true;
	    	return $arr;
	    }
	    
	    public function medicamentos() 
	    {
	    	$arr = array();
	    	$result = mysqli_query($this->conexion, "select * from Principio group by nombrePrincipio order by nombrePrincipio");	
	    	if ($result)
	    	{
	    		while ($row = mysqli_fetch_assoc($result)) 
	    		{
	    			$arr[] = $row;
	    		}
	    		$arr["error"] = false;
	    	}else
	    		$arr["error"] = true;
	    	return $arr;	
	    }

	    public function historialIdoneidades($paciente)
	    {
	    	$arr = array();
	    	if (is_numeric($paciente) && $paciente > 0)
	    	{
	    		$sql = "Select nombrePaciente from Paciente where idPaciente = ?";
	    		$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$result = mysqli_query($this->conexion, $sql);
					$row = mysqli_fetch_assoc($result);
					if (isset($row['nombrePaciente']))
					{
						$result = mysqli_query($this->conexion, "Select p.nombrePrincipio, i.*, q.nombreQuimico, concat_ws(' ',q.apellidoPaternoQuimico, q.apellidoMaternoQuimico) as apellidoQuimico, df.concentracionDatosFarma,
						f.nombreFrecuencia, u.abreviaturaUnidad
						From Idoneidad i 
						Left Join DatosFarma df on df.idDatosFarma = i.idDatosFarma				
						Left Join DatosClinicos dc on dc.idDatosClinicos = df.idDatosClinicos
						Left Join Frecuencia f on df.idFrecuencia = f.idFrecuencia
						Left Join Unidad u on df.idUnidad = u.idUnidad
						Left Join Quimico q on q.idQuimico = i.idQuimico
						Left Join Principio p on df.idPrincipio = p.idPrincipio	
						Where dc.idPaciente = $paciente	and i.I1DatosFarma is not null				
						order by i.idIdoneidad desc, p.nombrePrincipio asc");
						$i = 0;
						while ($row = mysqli_fetch_assoc($result)) 
						{
							if (!isset($auxID))
								$auxID = $row['idIdoneidad'];
							if ($auxID == $row['idIdoneidad'])
							{
								$row["nombreQuimico"] = ucwords(strtolower($row["nombreQuimico"]));
								$row["apellidoQuimico"] = ucwords(strtolower($row["apellidoQuimico"]));
								$arr[$i][] = $row;
							}
							else
							{
								$i++;
								$auxID = $row['idIdoneidad'];
								$row["nombreQuimico"] = ucwords(strtolower($row["nombreQuimico"]));
								$row["apellidoQuimico"] = ucwords(strtolower($row["apellidoQuimico"]));
								$arr[$i][] = $row;
							}
						}
					}
					else
						$arr['error'] = 103;
				}
				else
					$arr['error'] = 102;	
	    	}
	    	else
	    		$arr['error'] = 101;
	    	return $arr;
	    }

	    public function getIdoneidadByID($idIdoneidad){
	    	$arr = array();
			if((is_numeric($idIdoneidad)) && ($idIdoneidad >0)) 
			{	
				$sql = "select * from Idoneidad where idIdoneidad = $idIdoneidad";
				$row = mysqli_query($this->conexion, $sql);
				while($result = mysqli_fetch_assoc($row))
					$arr[] = $result; 
				if (count($arr) == 0)
					return null;	
				else{					
					return $arr;			
				}
					
			}
			return null;
	    }

	    public function getProximaIdoneidad()
	    {
	    	$result = mysqli_query($this->conexion, "select count(idIdoneidad) as aux, max(idIdoneidad) as num from Idoneidad");
	    	$row = mysqli_fetch_assoc($result);
	    	if($row["aux"] == 0)
		    	return 1;
		    $aux = $row["num"] + 1;
		    return $aux;
	    }  

	    public function getIdoneidadByPrescription($pres, $clin)
        {
        	$arr = array();
        	$result = mysqli_query($this->conexion, "Select p.nombrePrincipio, i.*, q.nombreQuimico, concat_ws(' ',q.apellidoPaternoQuimico, q.apellidoMaternoQuimico) as apellidoQuimico
						From Idoneidad i 
						Left Join DatosFarma df on df.idDatosFarma = i.idDatosFarma										
						Left Join Quimico q on q.idQuimico = i.idQuimico
						Left Join Principio p on df.idPrincipio = p.idPrincipio	
						Where df.numeroRecetaDatosFarma = $pres and df.idDatosClinicos = $clin and i.I1DatosFarma is not null				
						order by i.idIdoneidad desc, p.nombrePrincipio asc");
        	$i = 0;
        	while ($row = mysqli_fetch_assoc($result))
        	{         		        		
        		if(!isset($aux))
        			$aux = $row['idIdoneidad'];
        		if($aux != $row['idIdoneidad'])
        		{
        			$i++;
        			$aux = $row['idIdoneidad'];
        		}
        		$arr[$i][] = $row;
        	}
        	return $arr;
        }                

	    public function getProximaInteraccionId()
	    {
	    	$result = mysqli_query($this->conexion, "select count(idInteraccion) as aux, max(idInteraccion) as num from Interaccion");
	    	$row = mysqli_fetch_assoc($result);
	    	if($row["aux"] == 0)
		    	return 1;
		    $aux = $row["num"] + 1;
		    return $aux;
	    } 
	    
	    public function checkIdoneidad($id) 
	    {
	    	$arr = array();
	    	if(is_numeric($id) && $id > 0)
	    	{
	    		$x = 0; $if = 0;
	    		$arr = $this->getIdoneidadByID($id);
	    		for($i=0; $i <count($arr); $i++)
	    		{
	    			if($arr[$i]["I1DatosFarma"] == "x"
	    				|| $arr[$i]["I2DatosFarma"] == "x"
	    				|| $arr[$i]["I3DatosFarma"] == "x"
	    				|| $arr[$i]["I4DatosFarma"] == "x"
	    				|| $arr[$i]["I5DatosFarma"] == "x"
	    				|| $arr[$i]["I6DatosFarma"] == "x"
	    				|| $arr[$i]["I7DatosFarma"] == "x"
	    				|| $arr[$i]["I8DatosFarma"] == "x")
	    			{
	    				$x++;
	    				continue;
	    			}	
	    			if($arr[$i]["I1DatosFarma"] == "if"
	    				|| $arr[$i]["I2DatosFarma"] == "if"
	    				|| $arr[$i]["I3DatosFarma"] == "if"
	    				|| $arr[$i]["I4DatosFarma"] == "if"
	    				|| $arr[$i]["I5DatosFarma"] == "if"
	    				|| $arr[$i]["I6DatosFarma"] == "if"
	    				|| $arr[$i]["I7DatosFarma"] == "if"
	    				|| $arr[$i]["I8DatosFarma"] == "if")
	    				$if++;
	    		}
	    		$fecha = date('Y-m-d H:i:s');
	    		$result = mysqli_query($this->conexion, "select numeroRecetaDatosFarma, idDatosClinicos from DatosFarma where idDatosFarma = ".$arr[0]["idDatosFarma"]);
	    		$row = mysqli_fetch_assoc($result);
	    		if($x > 0)
	    		{	    			
	    			mysqli_query($this->conexion, "insert into Alerta values (null, 'Prescripción no idonea', '$fecha', '".$arr[0]["notaIdoneidad"]."', ".$row["idDatosClinicos"].", ".$row["numeroRecetaDatosFarma"].", null, 2)");
	    			return;
	    		}
	    		if($if > 0)	    			
	    			mysqli_query($this->conexion, "insert into Alerta values (null, 'Prescripción idonea con monitorización', '$fecha', '".$arr[0]["notaIdoneidad"]."', ".$row["idDatosClinicos"].", ".$row["numeroRecetaDatosFarma"].", null, 2)");
	    	}
	    } 

	    public function getReporteHistorico($paciente)
	    {
	    	$arr = array();
	    	if(is_numeric($paciente) && $paciente > 0)
			{
				$sql = "SELECT  max(dc.idDatosClinicos) as idDatosClinicos, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS edad, dc.ingresoDatosClinicos, s.nombreServicio, dc.doctorDatosClinicos, dc.diagnosticoDatosClinicos,
						h.calleHospital, h.numeroExteriorHospital, h.telefonoHospital, h.coloniaHospital, h.logoHospital, h.nombreHospital, p.nacimientoPaciente, dc.camaDatosClinicos, dc.pesoDatosClinicos, dc.tallaDatosClinicos
						from Paciente p
						INNER JOIN Hospital h on p.idHospital = h.idHospital
						INNER JOIN DatosClinicos dc on (p.idPaciente = dc.idPaciente and p.idPaciente = ?)
						INNER JOIN Servicio s on dc.idServicio = s.idServicio";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if(isset($result['idDatosClinicos']))
					{
						$result["nombrePaciente"] = ucwords(strtolower($result["nombrePaciente"]));
						$result["apellidoPaternoPaciente"] = ucwords(strtolower($result["apellidoPaternoPaciente"]));
						$result["doctorDatosClinicos"] = ucwords(strtolower($result["doctorDatosClinicos"]));
						unset($result['idDatosClinicos']);
						$arr = $result;						
						$aux = $this->pft_historial($paciente);
						if (isset($aux['error']))													
							$arr['error'] = $aux['error'] + 2;
						else
						{
							for ($i=0; $i<count($aux); $i++)
							{
								$aux[$i]['idoneidades'] = $this->getIdoneidadByPrescription($aux[$i][0]['numeroRecetaDatosFarma'], $aux[$i][0]['idDatosClinicos']);
								$aux[$i]['interacciones'] = $this->getInteraccionByClinialDataAndPrescription($aux[$i][0]['idDatosClinicos'], $aux[$i][0]['numeroRecetaDatosFarma']);
							}
							$arr['medicamentos'] = $aux;
							$arr['Alergias'] = $this->getAlergias($paciente);
							unset($arr['Alergias']['error']);							
						}
					}
				}
				else
					$arr['error'] = 102;
	    	}
	    	else
	    		$arr['error'] = 101;

	    	return $arr;
	    }
		
		public function getReporteSalida($paciente) 
		{
			$arr = array();
			if(is_numeric($paciente) && $paciente > 0)
			{
				$sql = "SELECT  max(dc.idDatosClinicos) as idDatosClinicos, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS edad, dc.ingresoDatosClinicos, s.nombreServicio, dc.doctorDatosClinicos, dc.diagnosticoDatosClinicos,
						h.calleHospital, h.numeroExteriorHospital, h.telefonoHospital, h.coloniaHospital, h.logoHospital, h.nombreHospital, p.nacimientoPaciente, dc.camaDatosClinicos, dc.pesoDatosClinicos, dc.tallaDatosClinicos
						from Paciente p
						INNER JOIN Hospital h on p.idHospital = h.idHospital
						INNER JOIN DatosClinicos dc on (p.idPaciente = dc.idPaciente and p.idPaciente = ?)
						INNER JOIN Servicio s on dc.idServicio = s.idServicio";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if(isset($result['idDatosClinicos']))
					{
						$result["nombrePaciente"] = ucwords(strtolower($result["nombrePaciente"]));
						$result["apellidoPaternoPaciente"] = ucwords(strtolower($result["apellidoPaternoPaciente"]));
						$result["doctorDatosClinicos"] = ucwords(strtolower($result["doctorDatosClinicos"]));
						unset($result['idDatosClinicos']);
						$arr = $result;						
						$sql = "SELECT p.nombrePrincipio, u.abreviaturaUnidad, df.concentracionDatosFarma, df.capturaDatosFarma, pr.nombrePresentacion, v.nombreVia, f.nombreFrecuencia
						FROM DatosFarma df
						INNER JOIN DatosClinicos dc on  (dc.idDatosClinicos = df.idDatosClinicos and dc.idPaciente = $paciente)
						LEFT JOIN Principio p on (df.idPrincipio = p.idPrincipio)
						LEFT JOIN Unidad u on (df.idUnidad = u.idUnidad)
						LEFT JOIN Presentacion pr on df.idPresentacion = pr.idPresentacion
						LEFT JOIN Via v on df.idVia = v.idVia
						LEFT JOIN Frecuencia f on df.idFrecuencia = f.idFrecuencia
						where dc.capturaDatosClinicos >= (select max(ingresoDatosClinicos) from DatosClinicos where idPaciente = $paciente)
						Group by df.idPrincipio, df.concentracionDatosFarma";
						$result = mysqli_query($this->conexion, $sql);
						while($row = mysqli_fetch_assoc($result))
							$arr['medicamentos'][] = $row;					
						$arr['error'] = 100;
					}
				}
				else
					$arr['error'] = 102;
	    	}
	    	else
	    		$arr['error'] = 101;

	    	return $arr;	
		}
		
	    public function getReporteEntrada($paciente)
	    {
	    	$arr = array();
	    	if(is_numeric($paciente) && $paciente > 0)
	    	{
	    		$sql = "SELECT Distinct dc.idDatosClinicos, p.nombrePaciente, concat_ws(' ',p.apellidoPaternoPaciente, p.apellidoMaternoPaciente) as apellidoPaternoPaciente, TIMESTAMPDIFF(YEAR,p.nacimientoPaciente,CURDATE()) AS edad, dc.ingresoDatosClinicos, s.nombreServicio, dc.doctorDatosClinicos, dc.diagnosticoDatosClinicos,
	    				h.calleHospital, h.numeroExteriorHospital, h.telefonoHospital, h.coloniaHospital, h.logoHospital, h.nombreHospital, p.nacimientoPaciente, dc.camaDatosClinicos, dc.pesoDatosClinicos, dc.tallaDatosClinicos
	    				from Paciente p
	    				INNER JOIN Hospital h on p.idHospital = h.idHospital
	    				INNER JOIN DatosClinicos dc on (p.idPaciente = dc.idPaciente and p.idPaciente = ?)
	    				INNER JOIN Servicio s on dc.idServicio = s.idServicio
	    				LEFT JOIN DatosFarma df on (dc.idDatosClinicos = df.idDatosClinicos and df.numeroRecetaDatosFarma = 1)
	    				";
	    		$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$row = mysqli_query($this->conexion, $sql);
					$result = mysqli_fetch_assoc($row);
					if(isset($result['idDatosClinicos']))
					{
						$result["nombrePaciente"] = ucwords(strtolower($result["nombrePaciente"]));
						$result["apellidoPaternoPaciente"] = ucwords(strtolower($result["apellidoPaternoPaciente"]));
						$result["doctorDatosClinicos"] = ucwords(strtolower($result["doctorDatosClinicos"]));
						$dc = $result['idDatosClinicos'];
						unset($result['idDatosClinicos']);
						$arr = $result;						
						$sql = "SELECT df.concentracionDatosFarma, df.inicioDatosFarma, df.notaDatosFarma, p.nombrePrincipio, pr.nombrePresentacion, v.nombreVia, u.nombreUso, f.nombreFrecuencia, q.nombreQuimico,
								concat_ws(' ',q.apellidoPaternoQuimico, q.apellidoMaternoQuimico) as apellidoQuimico, DATE_FORMAT(df.capturaDatosFarma, '%d/%m/%Y') as capturaDatosFarma, df.concentracionDatosFarma, un.abreviaturaUnidad
								FROM DatosFarma df
								INNER JOIN Quimico q on df.idQuimico = q.idQuimico
								INNER JOIN Principio p on df.idPrincipio = p.idPrincipio
								LEFT JOIN Presentacion pr on df.idPresentacion = pr.idPresentacion
								LEFT JOIN Via v on df.idVia = v.idVia
								LEFT JOIN Uso u on df.idUso = u.idUso
								LEFT JOIN Unidad un on df.idUnidad = un.idUnidad
								LEFT JOIN Frecuencia f on df.idFrecuencia = f.idFrecuencia
								where df.numeroRecetaDatosFarma = 1 and df.idDatosClinicos = $dc";
						$result = mysqli_query($this->conexion, $sql);
						while($row = mysqli_fetch_assoc($result))
						{
							$row["nombreQuimico"] = ucwords(strtolower($row["nombreQuimico"]));
							$row["apellidoQuimico"] = ucwords(strtolower($row["apellidoQuimico"]));
							$arr['medicamentos'][] = $row;
						}
						$arr['error'] = 100;
					}
				}
				else
					$arr['error'] = 102;
	    	}
	    	else
	    		$arr['error'] = 101;

	    	return $arr;
	    } 
		
		public function reportesAdministrador($hospital, $opciones) 
		{
			$arr = array();
			if (is_numeric($hospital) && $hospital >0)
			{
				if ($opciones["tipo"]<4 && $opciones["tipo"]>0)
				{										
					$select = "";
					$from = "";
					$where = "";
					$group = "";
					$auxp = 0;
					$auxq = 0;
					$auxa = 0;
					$auxs = 0;
					$auxtm = 0;
					$quimico = array();
					$paciente = array();
					$area = array();
					$sexo = array();
					$resultado = array();
					$tipoMedicamento = array();
					
					switch ($opciones["tipo"]) 
					{
						case 1:
								$select = "COUNT( DISTINCT i.idIdoneidad ) AS total";
								$from = "Idoneidad i 
								inner join DatosFarma df on i.idDatosFarma = df.idDatosFarma
								inner join DatosClinicos dc on df.idDatosClinicos = dc.idDatosClinicos
								inner join Paciente p on (dc.idPaciente = p.idPaciente and p.idHospital = $hospital) ";
								$where = "WHERE i.I1DatosFarma IS NOT NULL ";
								break;
						case 2:	
								$select = "count(df.idPrincipio) as total, pr.nombrePrincipio";
								$from = "DatosFarma df inner join Principio pr on df.idPrincipio = pr.idPrincipio
								inner join DatosClinicos dc on df.idDatosClinicos = dc.idDatosClinicos
								inner join Paciente p on (dc.idPaciente = p.idPaciente and p.idHospital = $hospital) ";
								$group = "Group by df.idPrincipio Order by total desc Limit 20";
								break;
						case 3:
								$select = "count(distinct p.idPaciente) as total";
								$from = "Paciente p left join DatosClinicos dc on (dc.idPaciente = p.idPaciente and p.idHospital = $hospital) ";								
								break;								
					}
					
					if (isset($opciones["fecha"]))
					{
						$fechaaux = $opciones["fecha"];
						if (strlen($where) == 0)																
							$where .= "where ";
						else
							if (strlen($where)>6)	
								$where .= "and ";
						if (count($fechaaux) == 1)
						{
							$where .= "fecha like '".$fechaaux[0]."%' ";
							$resultado["fecha"] = $fechaaux[0];
						}
						else
						{
							$datetime1 = date_create($fechaaux[0]);
							$datetime2 = date_create($fechaaux[1]);
							$afecha1 = ($datetime1 < $datetime2) ? $fechaaux[0] : $fechaaux[1];
							$afecha2 = ($datetime1 < $datetime2) ? $fechaaux[1] : $fechaaux[0];
							$where .= "fecha between '$afecha1' and '$afecha2' ";
						}
						if ($opciones["tipo"] == 1)
							$where = str_replace("fecha", "i.fechaIdoneidad", $where);
						if ($opciones["tipo"] == 2)
							$where = str_replace("fecha", "df.capturaDatosFarma", $where);																			
						if ($opciones["tipo"] == 3 && !isset($opciones['estatus']))
							$where = str_replace("fecha", "dc.ingresoDatosClinicos", $where);																			
					}					
					if (isset($opciones["edad"]))
					{
						if (strlen($where) == 0)																
							$where .= "where ";
						else
							if (strlen($where)>6)	
								$where .= "and ";
						if (count($opciones["edad"]) == 1)
						{
							$where .= "timestampdiff(Year, p.nacimientoPaciente, CURDATE()) = '".$opciones["edad"][0]."' ";
							$resultado["edad"] = $opciones["edad"][0];
						}
						else 
						{
							$resultado["edad"] = $opciones["edad"][0]." - ".$opciones["edad"][1];
							if ($opciones["edad"][0]<$opciones["edad"][1])
								$where .= "timestampdiff(Year, p.nacimientoPaciente, CURDATE()) between '".$opciones["edad"][0]."' and '".$opciones["edad"][1]."' ";		
							else
								$where .= "timestampdiff(Year, p.nacimientoPaciente, CURDATE()) between '".$opciones["edad"][1]."' and '".$opciones["edad"][0]."' ";		
						}
					}
					if (isset($opciones["opSexo"]))
					{
						if ($opciones["opSexo"] != 0)
						{
							if(isset($opciones["sexo"]))
								$sexo[] = $opciones["sexo"];
							else
							{
								$sexo[0] = "M";
								$sexo[1] = "F";
							}
						}
					}
					
					if (isset($opciones["peso"]) && count($opciones['peso']) > 0)
					{
						if (strlen($where) == 0)																
							$where .= "where ";
						else
							if (strlen($where)>6)	
								$where .= "and ";
						if (count($opciones["peso"]) == 1)
						{
							$where .= "dc.imcDatosClinicos = '".$opciones["peso"][0]."' ";
							$resultado["peso"] = $opciones["peso"][0];							
						}
						else
						{
							$resultado["peso"] = $opciones["peso"][0]." - ".$opciones["peso"][1];
							if ($opciones["peso"][0]<$opciones["peso"][1])
								$where .= "dc.imcDatosClinicos between '".$opciones["peso"][0]."' and '".$opciones["peso"][1]."' ";		
							else
								$where .= "dc.imcDatosClinicos between '".$opciones["peso"][1]."' and '".$opciones["peso"][0]."' ";
						}
					}
					if (isset($opciones["opArea"]))
					{
						if ($opciones["opArea"] != 0)
						{
							if (isset($opciones["area"]))
								$area[] = $opciones["area"];
							else 
							{													
								$result = mysqli_query($this->conexion, "select s.* from Servicio s, Servicio_Hospital sh where sh.idHospital = $hospital and sh.idServicio = s.idServicio");	
								while($row = mysqli_fetch_assoc($result))
									$area[] = $row;
							}
						}
					}
					if (isset($opciones["opQuimico"]))
					{
						if ($opciones["opQuimico"] != 0)
						{
							if (isset($opciones["quimico"]))
								$quimico[] = $opciones["quimico"];
							else
							{
								$result = mysqli_query($this->conexion, "Select * from Quimico where idHospital = $hospital");
								while($row = mysqli_fetch_assoc($result))
									$quimico[] = $row;
							}
						}
					}
					if (isset($opciones["opPaciente"]))
					{
						if ($opciones["opPaciente"] != 0)
						{
							if (isset($opciones["paciente"]))
								$paciente[] = $opciones["paciente"];
							else 
							{
								$result = mysqli_query($this->conexion, "Select * from Paciente where idHospital = $hospital");	
								while($row = mysqli_fetch_assoc($result))
									$paciente[] = $row;
							}
						}
					}
					if (isset($opciones["estatus"]))
					{
						if ($opciones["estatus"] == 1)
						{
							$resultado['estatus'] = "Ingresos";
							$select = str_replace("p.idPaciente", "dc.ingresoDatosClinicos", $select);
							if (strlen($where) == 0)																
								$where .= "where ";
							else
								if (strlen($where)>6)	
									$where .= "and ";
							$where .= "dc.ingresoDatosClinicos is not null ";
							if (strpos($where, "fecha"))
								$where = str_replace("fecha", "dc.ingresoDatosClinicos", $where);
						}	
						if ($opciones["estatus"] == 2)
						{
							$resultado['estatus'] = "Traslados de Area";
							$select = str_replace("p.idPaciente", "dc.ingresoDatosClinicos", $select);
							if (strlen($where) == 0)																
								$where .= "where ";
							else
								if (strlen($where)>6)	
									$where .= "and ";
							$where .= "dc.ingresoDatosClinicos <> dc.capturaDatosClinicos and dc.ingresoDatosClinicos in (Select ingresoDatosClinicos from DatosClinicos Group By ingresoDatosClinicos having count(ingresoDatosClinicos) > 1) ";
							if (strpos($where, "fecha"))
								$where = str_replace("fecha", "dc.capturaDatosClinicos", $where);
						}
						if ($opciones["estatus"] == 3)
						{
							$resultado['estatus'] = "Egresos";
							$select = str_replace("p.idPaciente", "e.idEgreso", $select);
							$from .= "inner join Egreso e on dc.idDatosClinicos = e.idDatosClinicos";
							if (strpos($where, "fecha"))
								$where = str_replace("fecha", "e.capturaEgreso", $where);
						}
					}
					if (isset($opciones["propiedad"]))
					{
						if ($opciones["propiedad"] == 1)
						{
							$select = "SUM(case when i.I1DatosFarma = 'x' then 1 else 0 end) as TotalI1,
							SUM(case when i.I2DatosFarma = 'x' then 1 else 0 end) as TotalI2,
							SUM(case when i.I3DatosFarma = 'x' then 1 else 0 end) as TotalI3,
							SUM(case when i.I4DatosFarma = 'x' then 1 else 0 end) as TotalI4,
							SUM(case when i.I5DatosFarma = 'x' then 1 else 0 end) as TotalI5,
							SUM(case when i.I6DatosFarma = 'x' then 1 else 0 end) as TotalI6,
							SUM(case when i.I7DatosFarma = 'x' then 1 else 0 end) as TotalI7,
							SUM(case when i.I8DatosFarma = 'x' then 1 else 0 end) as TotalI8";					
						}
						if ($opciones["propiedad"] == 2)
						{
							$select = "SUM(case when i.I1DatosFarma = 'if' then 1 else 0 end) as TotalI1,
							SUM(case when i.I2DatosFarma = 'if' then 1 else 0 end) as TotalI2,
							SUM(case when i.I3DatosFarma = 'if' then 1 else 0 end) as TotalI3,
							SUM(case when i.I4DatosFarma = 'if' then 1 else 0 end) as TotalI4,
							SUM(case when i.I5DatosFarma = 'if' then 1 else 0 end) as TotalI5,
							SUM(case when i.I6DatosFarma = 'if' then 1 else 0 end) as TotalI6,
							SUM(case when i.I7DatosFarma = 'if' then 1 else 0 end) as TotalI7,
							SUM(case when i.I8DatosFarma = 'if' then 1 else 0 end) as TotalI8";
						}
					}
					if (isset($opciones["opTipoMedicamento"]))
					{
						if ($opciones["opTipoMedicamento"] != 0)
						{
							$group = "Order by total desc Limit 50";
							if (isset($opciones["tipoMedicamento"]))
								$tipoMedicamento[] = $opciones["tipoMedicamento"];
							else 
							{
								/*$result = mysqli_query($this->conexion, "Select * from TipoMedicamento");	
								while($row = mysqli_fetch_assoc($result))
									$tipoMedicamento[] = $row;*/
								for ($i = 0; $i < 6; $i++)
									$tipoMedicamento[$i] = array(); 
								$tipoMedicamento[0]['idTipoMedicamento'] = 1;
								$tipoMedicamento[0]['nombreTipoMedicamento'] = 'AH';
								$tipoMedicamento[1]['idTipoMedicamento'] = 2;
								$tipoMedicamento[1]['nombreTipoMedicamento'] = 'ARH';
								$tipoMedicamento[2]['idTipoMedicamento'] = 3;
								$tipoMedicamento[2]['nombreTipoMedicamento'] = 'CH';
								$tipoMedicamento[3]['idTipoMedicamento'] = 4;
								$tipoMedicamento[3]['nombreTipoMedicamento'] = 'CR';
								$tipoMedicamento[4]['idTipoMedicamento'] = 5;
								$tipoMedicamento[4]['nombreTipoMedicamento'] = 'H';
								$tipoMedicamento[5]['idTipoMedicamento'] = 6;
								$tipoMedicamento[5]['nombreTipoMedicamento'] = 'QX';
							}
						}
					}
					if(isset($opciones['incidencia']))
					{
						$select .= ", pr.nombrePrincipio ";
						$from .= "left join Principio pr on pr.idPrincipio = df.idPrincipio ";
						$group = "Group By df.idPrincipio Order by total desc Limit 20";
					}
					if (count($paciente) > $auxp || count($quimico) > $auxq || count($area) > $auxa || count($sexo) > $auxs || count($tipoMedicamento) > $auxtm)
					{
						if (strlen($where) == 0)
							$where = "where ";
						$whereaux = $where;						
						while (count($paciente) > $auxp || count($quimico) > $auxq || count($area) > $auxa || count($sexo) > $auxs || count($tipoMedicamento) > $auxtm) 
						{
							$auxs = 0;
							$auxa = 0;
							$auxq = 0;
							$auxtm = 0;
							$where = $whereaux;
							if (count($paciente) > $auxp)
							{
								if (strlen($where)>6)	
									$where .= "and ";
								$where .= "dc.idPaciente = ".$paciente[$auxp]["idPaciente"]." ";
								$resultado["paciente"] = $paciente[$auxp]["nombrePaciente"]." ".$paciente[$auxp]["apellidoPaternoPaciente"];
								$auxp++;
							}
							$whereaux1 = $where;																									
							if (count($quimico) > $auxq || count($area) > $auxa || count($sexo) > $auxs || count($tipoMedicamento) > $auxtm)
							{								
								while (count($quimico) > $auxq || count($area) > $auxa || count($sexo) > $auxs || count($tipoMedicamento) > $auxtm) 
								{
									$auxs = 0;
									$auxa = 0;
									$auxtm = 0;
									$where = $whereaux1;
									if (count($quimico) > $auxq)
									{
										if (strlen($where)>6)	
											$where .= "and ";
										$where .= "i.idQuimico = ".$quimico[$auxq]["idQuimico"]." ";
										$resultado["quimico"] = $quimico[$auxq]["nombreQuimico"]." ".$quimico[$auxq]["apellidoPaternoQuimico"];
										$auxq++;
									}
									$whereaux2 = $where;																		
									if (count($area) > $auxa || count($sexo) > $auxs || count($tipoMedicamento) > $auxtm)
									{																						
										while (count($area) > $auxa || count($sexo) > $auxs || count($tipoMedicamento) > $auxtm) 
										{
											$auxs = 0;
											$auxtm = 0;
											$where = $whereaux2;
											if (count($area) > $auxa)
											{
												if (strlen($where)>6)	
													$where .= "and ";
												$where .= "dc.idServicio = ".$area[$auxa]["idServicio"]." ";
												$resultado["area"] = $area[$auxa]["nombreServicio"];
												$auxa++;
											}
											$whereaux3 = $where;
											if (count($sexo) > $auxs || count($tipoMedicamento) > $auxtm)
											{
												while (count($sexo) > $auxs || count($tipoMedicamento) > $auxtm) 
												{
													$auxtm = 0;													
													$where = $whereaux3;		
													if (count($sexo) > $auxs)
													{
														if (strlen($where)>6)	
															$where .= "and ";
														$where .= "p.sexoPaciente = '".$sexo[$auxs]."' ";
														$resultado["sexo"] = ($sexo[$auxs] == "F") ? "Femenino" : "Masculino";
														$auxs++;
													}
													$whereaux4 = $where;
													if (count($tipoMedicamento) > $auxtm)
													{
														while (count($tipoMedicamento) > $auxtm) 
														{
															$where = $whereaux4;
															if (strlen($where)>6)	
																$where .= "and ";
															$where .= "df.idTipoMedicamento = ".$tipoMedicamento[$auxtm]['idTipoMedicamento']." ";
															$resultado["TipoMedicamento"] = $tipoMedicamento[$auxtm]['nombreTipoMedicamento'];															
															$resultado["resultado"] = $this->generarConsulta($select, $from, $where, $group);
															$arr[] = $resultado; 
															$auxtm++;
														}
													}
													else
													{
														$resultado["resultado"] = $this->generarConsulta($select, $from, $where, $group);
														$arr[] = $resultado; 
													}
												}
												
											}
											else
											{
												$resultado["resultado"] = $this->generarConsulta($select, $from, $where, $group);
												$arr[] = $resultado; 
											}
										}
									}
									else
									{												
										$resultado["resultado"] = $this->generarConsulta($select, $from, $where, $group);
										$arr["resultado"] = $resultado; 
									}
								}
							}
							else
							{												
								$resultado["resultado"] = $this->generarConsulta($select, $from, $where, $group);
								$arr["resultado"] = $resultado; 
							}
						}
					}
					else
					{												
						$resultado["resultado"] = $this->generarConsulta($select, $from, $where, $group);
						$arr["resultado"] = $resultado; 
					}
				}
				else
					$arr["error"] = 102;				
			}
			else
				$arr["error"] = 101;
			return $arr;
		}
		
		private function generarConsulta($select, $from, $where, $group) 
		{			
			$sql = "Select $select From $from $where $group";							
			$result = mysqli_query($this->conexion, $sql);
			$rows = array();
			while($row = mysqli_fetch_assoc($result))
				$rows[] = $row;
			if (count($rows)== 1)
				$rows = $rows[0];
			if (count($rows)==0)
				$rows[] = $sql;
			return $rows;
		}
		
		public function egresarPaciente($paciente) 
		{
			$arr = array();
			if (isset($paciente) && $paciente >0)
			{				
				$sql = "Select max(df.numeroRecetaDatosFarma) as numeroRecetaDatosClinicos, df.idDatosClinicos from DatosFarma df inner join DatosClinicos dc on (dc.idDatosClinicos = df.idDatosClinicos and dc.idPaciente = ?)";
				$stmt = $this->conexion->prepare($sql);
				$stmt->bind_param("i",$paciente);
				if ($stmt->execute())
				{
					$stmt->close();
					$sql = str_replace("?", $paciente, $sql);
					$result = mysqli_query($this->conexion, $sql);
					$row = mysqli_fetch_assoc($result);
					if (isset($row['idDatosClinicos']))
					{
						$fecha = date('Y-m-d H:i:s');
						mysqli_query($this->conexion, "insert into Egreso values (null, '$fecha', ".$row['idDatosClinicos'].", ".$row['numeroRecetaDatosClinicos'].")");
						mysqli_query($this->conexion, "update Paciente set idEstatusPaciente = 2 where idPaciente = $paciente");
						$arr["error"] = 100;
					}
					else
						$arr["error"] = 103;
				}
				else
					$arr["error"] = 102;
			}	
			else
				$arr["error"] = 101;
			return $arr;
		}

	}	
	
?>
