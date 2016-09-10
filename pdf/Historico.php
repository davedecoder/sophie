<?php
require('tfpdf.php');	

class Historico extends tfpdf {

	protected $pageNumber = 0;
	protected $GLOBALS = array();
	function getHeader(){
		return $GLOBALS['hospital'];
	}

	function Globalicer($value){
	    $this->GLOBALS = $value;
	}

	function Header(){	
		$this->pageNumber++;
		$data = $this->GLOBALS;
		if(isset($data['logo']) ){
			$this->Image($data["logo"],10,10,18);
		}
		if(isset($data["nuba"]) ){
			$this->Image($data["nuba"],175,11,18);
		}	
		$this->SetFont('Arial','I',10);
		$this->setTextColor(52,73,94);
		$this->setX(190);
		global $pageNumber;
		$this->Cell(20,5, $this->pageNumber);
		$this->Cell(20,5);
		$this->Ln();
		$this->Cell(50,5," ");
			if(isset($data["calleHospital"])&&isset($data["numeroExteriorHospital"])&&isset($data["coloniaHospital"]))
				$this->Cell(150,5,'Dirección: Calle '.$data["calleHospital"]." #".$data["numeroExteriorHospital"]." Col.".$data["coloniaHospital"]);
			else
				$this->Cell(150,5,'Dirección:  -');
		$this->Ln();
		$this->Cell(50);
		if(isset($data["telefonoHospital"]))
			$this->Cell(50,5,'Telefono: '.$data["telefonoHospital"]);
		else
			$this->Cell(50,5,'Telefono:  -');
		$this->Ln();
		$this->Cell(50);
		$this->setTextColor(0,0,0);
		$this->Cell(30,5," ");
		$this->Ln();
		$this->Cell(175);
		$this->Ln(5);
		$this->Line(10,32,200,32);
	}

	function patientData($data){		
		$this->Ln();
		$this->SetFont('Arial','B',9);
		$column_1 = $this->GetX();
		$column_2 = $column_1 + 40;
		$column_3 = $column_1 + 125;
		$column_4 = $column_1 + 140;
		$this->Cell(25,6,'PACIENTE: ');
		$this->SetFont('Arial','',9);
		$this->setX($column_2);
		if(isset($data["nombrePaciente"])&&isset($data["apellidoPaternoPaciente"]))
			$this->Cell(110,6," ".$data["nombrePaciente"]." ".$data["apellidoPaternoPaciente"]);
		else
			$this->Cell(110,6," - ");
		$this->setX($column_3);
		$this->SetFont('Arial','B',9);
		$this->Cell(15,6,'CAMA: ');
		$this->SetFont('Arial','',9);
		$this->setX($column_4);
		if(isset($data["camaDatosClinicos"]))
			$this->Cell(15,6," ".$data["camaDatosClinicos"]);
		else
			$this->Cell(15,6," - ");	
		$this->Ln();
		$this->SetFont('Arial','B',9);
		$this->Cell(55,6,'FEC. DE NAC: ');
		$this->setX($column_2);
		$this->SetFont('Arial','',9);
		if(isset($data["nacimientoPaciente"]))
			$this->Cell(80,6," ".$data["nacimientoPaciente"]);
		else
			$this->Cell(80,6," - ");
		$this->SetFont('Arial','B',9);
		$this->setX($column_3);
		$this->Cell(15,6,'EDAD: ');
		$this->SetFont('Arial','I',9);
		$this->setX($column_4);
		if(isset($data["edad"]))
			$this->Cell(120,6," ".$data["edad"]." años");	
		else
			$this->Cell(120,6," - ");
		$this->Ln();
		$this->SetFont('Arial','B',9);
		$this->Cell(50,6,'FEC. DE INGRESO: ');
		$this->SetFont('Arial','I',9);
		$this->setX($column_2);
		if(isset($data["ingresoDatosClinicos"]))
			$this->Cell(85,6,substr($data["ingresoDatosClinicos"],0,16));
		else
			$this->Cell(85,6," - ");
		$this->SetFont('Arial','B',9);
		$this->setX($column_3);
		$this->Cell(15,6,'PESO: ');
		$this->SetFont('Arial','',9);
		$this->setX($column_4);
		if(isset($data["pesoDatosClinicos"]))
			$this->Cell(80,6,substr($data["pesoDatosClinicos"],0,16));
		else
			$this->Cell(80,6," - ");	
		$this->Ln();
		$this->SetFont('Arial','B',9);
		$this->Cell(28,6,'MÉDICO: ');
		$this->setX($column_2);
		$this->SetFont('Arial','',9);
		if(isset($data["doctorDatosClinicos"]))
			$this->Cell(107,6," ".$data["doctorDatosClinicos"]);
		else
			$this->Cell(107,6," - ");
		$this->SetFont('Arial','B',9);
		$this->setX($column_3);
		$this->Cell(15,6,'TALLA: ');
		$this->SetFont('Arial','',9);
		if(isset($data["tallaDatosClinicos"]))
			$this->Cell(80,6,substr($data["tallaDatosClinicos"],0,16));
		else
			$this->Cell(80,6," - ");	
		$this->Ln();
		$this->SetFont('Arial','B',9);
		$this->Cell(28,6,'SERVICIO: ');
		$this->SetFont('Arial','',9);
		$this->setX($column_2);
		if(isset($data["nombreServicio"]))
			$this->Cell(117,6," ".$data["nombreServicio"]);	
		else
			$this->Cell(117,6," - ");	
		$this->Ln();
		$this->SetFont('Arial','B',9);
		$this->Cell(38,6,'DIAGNÓSTICO: ');
		$this->SetFont('Arial','',9);
		$this->setX($column_2);
		if(isset($data["diagnosticoDatosClinicos"]))
			$this->MultiCell(157,6," ".$data["diagnosticoDatosClinicos"],0,"L");
		else
			$this->Cell(157,6," - ");	
		$this->Ln();
		$this->SetFont('Arial','B',9);
		$this->Cell(28,6,'ALÉRGIAS: ');
		$this->SetFont('Arial','',9);
		$this->setX($column_2);
		if(isset($data["Alergias"])){
			$res = $this->findValueInArrayByIndex($data['Alergias'], 'nombreAlergia', ", ");
			$this->Cell(87,6,$res);	
		}			
		else
		$this->Cell(87,6," - ");
		$this->Ln();		
	}

	function findValueInArrayByIndex ($data, $indexName, $separator){	
		$output = "";
		if(isset($data[$indexName])){
			return $data[$indexName];
		}
		if(is_array($data)){
			foreach ($data as $value) {
				$output .= $this->findValueInArrayByIndex($value, $indexName, $separator);					
				$output = $output . $separator;
			}
		}
		return $output;
	}

	function AcceptPageBreak(){
			return true;
	}



	function medTable($medicamentos,$idoneidades, $interacciones){
			$break = $this->GetY();
			if($break >= 230)
				$this->AddPage();
			/*Header Conciliacion*/
			$this->setFillColor(188,189,192);
			$this->setTextColor(0,0,0);
			$this->SetFont('Arial','',11);
			$this->Cell(190,5,'Conciliación',0,1,'C',true);
            /*user and date*/
            $this->setFillColor(255,255,255);
			$this->SetFont('Arial','B',12);
			$this->Cell(20,8,'Usuario: ');
			$this->SetFont('Arial','',11);
			if(isset($medicamentos[0]["nombreQuimico"])&&isset($medicamentos[0]["apellidoQuimico"]))
				$this->Cell(89,8,$medicamentos[0]["nombreQuimico"]." ".$medicamentos[0]["apellidoQuimico"],0,"L");	
			else
				$this->Cell(89,8,"Sin asignar",0,"L");
			$this->SetFont('Arial','B',9);
			$this->Cell(38,8,'Fecha de captura: ');
			$this->SetFont('Arial','I',11);
			if(isset($medicamentos[0]["capturaDatosFarma"]))
				$this->Cell(150,8,substr($medicamentos[0]["capturaDatosFarma"],0,16),0,"L");	
			else
				$this->Cell(150,8," - ",0,"L");

			$this->Ln();
			/*end of use rand date*/
			$this->SetFont('Arial','',6);
			$this->setFillColor(188,189,192);
			$this->setTextColor(0,0,0);
			$this->Cell(40,5,'PRINCIPIO',0,0,'C',true);
			$this->setFillColor(255, 255, 255);
			$this->Cell(20,5,'CONC.',0,0,'C',true);
			$this->setFillColor(188,189,192);
			$this->Cell(20,5,'PRESENT.',0,0,'C',true);	
			$this->setFillColor(255, 255, 255);
			$this->Cell(20,5,'VIA',0,0,'C',true);
			$this->setFillColor(188,189,192);
			$this->Cell(30,5,'FRECUENCIA',0,0,'C',true);
			$this->setFillColor(255, 255, 255);
			$this->Cell(20,5,'H. INICIO',0,0,'C',true);
			$this->setFillColor(188,189,192);
			$this->Cell(40,5,'NOTAS',0,0,'C',true);			
			$this->Ln();
			$this->Line($this->GetX(), $this->GetY(), $this->GetX() + 190, $this->GetY());			
			$this->SetFont('Arial','',6);
			$this->setTextColor(0,0,0);
			if(isset($medicamentos) ) {
				foreach($medicamentos as $item){
					$tmpB2 = $this->GetY();
					if($tmpB2>240){
						$this->AddPage();
					}
					if(!isset($item['idoneidades'])){
						if(!isset($tempX))
							$tempX = $this->GetX();
						$tempY = $this->GetY();
						$maxY = $tempY;
						$maxTmp = $tempY;
						$rws = 5;
						$this->SetFont('Arial','',7);
						if(isset($item["nombrePrincipio"])){
							$tmpHeight = $this->calculateHeight($item["nombrePrincipio"], 40);
							$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
							$this->MultiCell(40, 5,$item["nombrePrincipio"],0,'L',false);
						}							
					    else
					    	$this->MultiCell(40,5,'-',1,'C',false);
					    $this->SetXY($tempX+40,$tempY);
					    $this->SetFont('Arial','',6);
						if(isset($item["concentracionDatosFarma"])){
							$tmpHeight = $this->calculateHeight($item["concentracionDatosFarma"], 20);
							$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
							$concentracion = $item["concentracionDatosFarma"];
							$abreviatura = "-";
							if(isset($item["abreviaturaUnidad"])){
								$tmpHeight = $this->calculateHeight($item["abreviaturaUnidad"], 20);
								$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
								$abreviatura = $item["abreviaturaUnidad"];
							}										
							$this->MultiCell(20,5,$concentracion." ".$abreviatura,0,'C',false);	
						}							
						else
					    	$this->MultiCell(20,5,'-',0,'C',false);						
						$this->SetXY($tempX+60,$tempY);
						if(isset($item["nombrePresentacion"])){
							$tmpHeight = $this->calculateHeight($item["nombrePresentacion"], 20);
							$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
							$this->MultiCell(20,5,html_entity_decode($item["nombrePresentacion"]),0,'C',false);
						}							
						else
					    	$this->MultiCell(20,5,'-',0,'C',false);						
						$this->SetXY($tempX+80,$tempY);
						$maxTmp = $this->getY();
						if(isset($item["nombreVia"])){
							$tmpHeight = $this->calculateHeight($item["nombreVia"], 20);
							$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
							$this->MultiCell(20,5,html_entity_decode($item["nombreVia"]),0,'C',false);
						}														
						else
					    	$this->MultiCell(20,5,'-',0,'C',false);						
						$this->SetXY($tempX+100,$tempY);
						if(isset($item["nombreFrecuencia"])){
							$tmpHeight = $this->calculateHeight($item["nombreFrecuencia"], 30);
							$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
							$this->MultiCell(30,5,html_entity_decode($item["nombreFrecuencia"]),0,'C',false);
						}							
						else
					    	$this->MultiCell(30,5,'-',0,'C',false);						
						$this->SetXY($tempX+130,$tempY);
						if(isset($item["inicioDatosFarma"])){
							$tmpHeight = $this->calculateHeight($item["inicioDatosFarma"], 20);
							$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
							$this->MultiCell(20,5,$item["inicioDatosFarma"],0,'C',false);
						}							
						else
					    	$this->MultiCell(20,5,'-',0,'C',false);						
						$maxTmp = $this->getY();						
						$this->SetXY($tempX+150,$tempY);
						if(isset($item["notaDatosFarma"])){
							$tmpHeight = $this->calculateHeight($item["notaDatosFarma"], 40);
							$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
							$this->MultiCell(40,5,$item["notaDatosFarma"],0,'L',false); 
						} 
						else{
							$this->MultiCell(40,5,' - ',0,'C',false);
						} 
						$this->Line($tempX, $tempY  + $rws , $tempX + 190, $tempY + $rws);						
					    $maxTmp = $this->getY() + $rws;
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX,$maxY);
						$this->Ln();
					}
				}
			}	
			if(count($idoneidades) > 0){
				foreach($idoneidades as $item){
					if(count($item) > 0){
						$this->ln();
						$tmpB = $this->GetY();
						if($tmpB>230){
							$this->AddPage();
						}
						$this->SetFont('Arial','B',8);
						$this->setTextColor(255,255,255);
						$this->Cell(190,5,'Evaluación de Idoneidad',0,1,'C',true);
						/*user and date*/
						$this->setTextColor(0,0,0);
						$this->SetFont('Arial','B',12);
						$this->Cell(20,8,'Usuario: ');
						$this->SetFont('Arial','',11);
						if(isset($medicamentos[0]["nombreQuimico"])&&isset($medicamentos[0]["apellidoQuimico"]))
							$this->Cell(89,8,$medicamentos[0]["nombreQuimico"]." ".$medicamentos[0]["apellidoQuimico"],0,"L");	
						else
							$this->Cell(89,8,"Sin asignar",0,"L");
						$this->SetFont('Arial','B',9);
						$this->Cell(38,8,'Fecha de captura: ');
						$this->SetFont('Arial','I',11);
						if(isset($item[0]["fechaIdoneidad"]))
							$this->Cell(150,8,substr($item[0]["fechaIdoneidad"],0,16),0,"L");	
						else
							$this->Cell(150,8," - ",0,"L");
						$this->ln();
						/*END user and date*/
						$this->setFillColor(188,189,192);
						$this->SetFont('Arial','',7);
						$tempX = $this->GetX();
						$tempY = $this->GetY();
						$maxY = $tempY;
						$maxTmp = $tempY;
						$this->MultiCell(60,5,'PRINCIPIO',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+60,$tempY);
						$this->setFillColor(255,255,255);
						$this->MultiCell(70,5,'RESULTADO DE IDONEIDAD ',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+130,$tempY);
						$this->setFillColor(188,189,192);
						$this->MultiCell(60,5,'EVENTO ADVERSO ',0,'C',true);
						$this->Line($tempX, $tempY + 5, $tempX + 190, $tempY + 5);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 				
						$lastY = $this->idoTables($item);
						$currentX = $this->GetX();
						$this->setFillColor(188,189,192);
						$this->SetFont('Arial','',7);
						$this->MultiCell(30,5,'RECOMENDACION:',0,0,'C',false);
						$this->SetXY($currentX+30,$lastY);
						$this->MultiCell(160,5,$item[0]["notaIdoneidad"],0,'L',false);
						$this->Line($tempX, $lastY + 5, $tempX + 190, $lastY + 5);

					}						
				}
			}
			if(count($interacciones) > 0){
				foreach($interacciones as $item){
					if(count($item) > 0){
						$this->ln();
						$tmpB = $this->GetY();
						if($tmpB>230){
							$this->AddPage();
						}
						$this->setFillColor(188,189,192);
						$this->SetFont('Arial','B',8);
						$this->setTextColor(255,255,255);
						$this->Cell(190,5,'Evaluación de Interacciones',0,1,'C',true);
						//user and date
						$this->setTextColor(0,0,0);
						$this->SetFont('Arial','B',12);
						$this->Cell(20,8,'Usuario: ');
						$this->SetFont('Arial','',11);
						if(isset($medicamentos[0]["nombreQuimico"])&&isset($medicamentos[0]["apellidoQuimico"]))
							$this->Cell(89,8,$medicamentos[0]["nombreQuimico"]." ".$medicamentos[0]["apellidoQuimico"],0,"L");	
						else
							$this->Cell(89,8,"Sin asignar",0,"L");
						$this->SetFont('Arial','B',9);
						$this->Cell(38,8,'Fecha de captura: ');
						$this->SetFont('Arial','I',11);
						if(isset($item[0]["fechaCapturaInteraccion"]))
							$this->Cell(150,8,substr($item[0]["fechaCapturaInteraccion"],0,16),0,"L");	
						else
							$this->Cell(150,8," - ",0,"L");
						$this->ln();
						//END user and date
						$this->setFillColor(188,189,192);
						$this->SetFont('Arial','',7);
						$tempX = $this->GetX();
						$tempY = $this->GetY();
						$maxY = $tempY;
						$maxTmp = $tempY;
						$this->MultiCell(10,5,'NO.',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+10,$tempY);
						$this->setFillColor(255,255,255);
						$this->MultiCell(25,5,'FECHA ',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+35,$tempY);
						$this->setFillColor(188,189,192);
						$this->MultiCell(30,5,'GRADO INTERAC. ',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+65,$tempY);
						$this->setFillColor(255,255,255);
						$this->MultiCell(25,5,'TIPO',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+90,$tempY);
						$this->setFillColor(188,189,192);
						$this->MultiCell(40,5,'DESCRIPCION',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+130,$tempY);
						$this->setFillColor(255,255,255);
						$this->MultiCell(25,5,'EFECTOS',0,'C',true);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 
						$this->SetXY($tempX+155,$tempY);
						$this->setFillColor(188,189,192);
						$this->MultiCell(35,5,'SUGERENCIA',0,'C',true);
						$this->Line($tempX, $tempY + 5, $tempX + 190, $tempY + 5);
						$maxTmp = $this->getY();
						if($maxTmp>$maxY)
							$maxY = $maxTmp; 				
						$lastY = $this->interaTables($item);

					}
				

			}
		}			
	}
	
	function calculateHeight($string, $width){
		$cellDefaultWidth = 40;		
		$defaultCharCapacity = 15;
		$cellDefaultHeight = 5;
		$stringSize = strlen($string);
		$charsPerLine = (($width * $defaultCharCapacity) / $cellDefaultWidth);
		$numOfRowsNeded = $stringSize / $charsPerLine;
		$height = ceil($numOfRowsNeded) * $cellDefaultHeight;

		return $height;
	}

	function findHeight($nombre){
			$size = strlen($nombre);
		if($size>25)
			if($size>50)
				$height = 15;
			else
				$height = 10;
		else
			$height = 5;

		return $height;
	}
	function getSuitabilityState(){

	}
	function findHeightI($nombre){
		$size = strlen($nombre);
		if($size>46)
			$height = 15;
		else if($size>23)
			$height = 10;
		else
			$height = 5;
		return $height;
	}	

	function idoTables($tabla){
		$maxY = $this->GetY();
			foreach($tabla as $item){
				$rws = 5;
				$this->SetFont('Arial','',7);
				$this->setTextColor(0,0,0);				
				$tempX = $this->GetX();
				$tempY = $this->GetY();
				$maxY = $tempY;
				$maxTmp = $tempY;
				$this->SetFont('Arial','',6);				
				if(isset($item["nombrePrincipio"])){
					$tmpHeight = $this->calculateHeight($item["nombrePrincipio"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(60,5,$item["nombrePrincipio"],0,'L',false);
				}else{
					$this->MultiCell(60,5,"-",0,'L',false);
				}				
				$this->SetFont('Arial','',7);
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 
				$this->SetXY($tempX+60,$tempY);
				if(isset($item["resultado"])){
					$tmpHeight = $this->calculateHeight($item["resultado"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(70,5,$item["resultado"],0,'C',false);	
				}else{
					$this->MultiCell(70,5," - ",0,'C',false);	
				}				
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 
				$this->SetXY($tempX+130,$tempY);
				if(isset($item["evento"]) && $item["evento"] != ''){
					$tmpHeight = $this->calculateHeight($item["evento"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(60,5,$item["evento"],0,'C',false);
				}else{
					$this->MultiCell(60,5," - ",0,'C',false);
				}				
				$maxTmp = $this->getY() + $rws - 5;
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 				
				$this->Line($tempX,$maxY,$tempX+190,$maxY);
				$this->SetXY($tempX,$maxY);
			}	
				return $maxY;						
	}

	function interaTables($tabla){
		$i = 0;
		$maxY = $this->GetY();
			foreach($tabla as $item){
				$rws = 5;
				$i++;
				$this->SetFont('Arial','',7);
				$this->setTextColor(0,0,0);				
				$tempX = $this->GetX();
				$tempY = $this->GetY();
				$maxY = $tempY;
				$maxTmp = $tempY;
				$this->SetFont('Arial','',7);				
				$this->MultiCell(10,5, $i,0,'L',false); //value corresponding to NO.
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 
				$this->SetXY($tempX+10,$tempY);
				if(isset($item["fechaCapturaInteraccion"])){
					$tmpHeight = $this->calculateHeight($item["fechaCapturaInteraccion"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(25,5,$item["fechaCapturaInteraccion"],0,'C',false);
				}else{
					$this->MultiCell(25,5,"-",0,'C',false);
				}				
				$this->SetFont('Arial','',7);
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 
				$this->SetXY($tempX+35,$tempY);
				//Grado interacion
				if(isset($item["grado"])){
					$tmpHeight = $this->calculateHeight($item["grado"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(30,5,$item["grado"],0,'C',false);	
				}else{
					$this->MultiCell(30,5," - ",0,'C',false);	
				}				
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 
				$this->SetXY($tempX+65,$tempY);
				if(isset($item["tipo"])){
					$tmpHeight = $this->calculateHeight($item["tipo"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(25,5,$item["tipo"],0,'C',false);
				}else{
					$this->MultiCell(25,5," - ",0,'C',false);
				}
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 
				$this->SetXY($tempX+90,$tempY);
				if(isset($item["descripcion"])){
					$tmpHeight = $this->calculateHeight($item["descripcion"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(40,5,$item["descripcion"],0,'L',false);
				}else{
					$this->MultiCell(40,5," - ",0,'C',false);
				}					
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 		
				$this->SetXY($tempX+130,$tempY);
				if(isset($item["detallesInteraccion"])){ //Efectos
					$tmpHeight = $this->calculateHeight($item["detallesInteraccion"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(25,5,$item["detallesInteraccion"],0,'C',false);
				}else{
					$this->MultiCell(25,5," - ",0,'C',false);
				}					
				$maxTmp = $this->getY();
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 		
				$this->SetXY($tempX+155,$tempY);
				if(isset($item["sugerenciaInteraccion"])){ //Sugerencia
					$tmpHeight = $this->calculateHeight($item["sugerenciaInteraccion"], 40);
					$rws = ($tmpHeight > $rws) ? $tmpHeight : $rws;
					$this->MultiCell(35,5,$item["sugerenciaInteraccion"],0,'L',false);
				}else{
					$this->MultiCell(35,5," - ",0,'C',false);
				}		
				$maxTmp = $this->getY() + $rws - 5;
				if($maxTmp>$maxY)
					$maxY = $maxTmp; 				
				$this->Line($tempX,$maxY,$tempX+190,$maxY);
				$this->SetXY($tempX,$maxY);
			}	
				return $maxY;						
	}

	function reportsHandler($medicamentos, $reportType){
		if($reportType == 1 || $reportType == 3){
			if(count($medicamentos > 1)){
				$tmpHelp = $medicamentos[0];
				unset($medicamentos);
				$medicamentos[0] = $tmpHelp;
			}
		}		
		foreach($medicamentos as $item){
			$idoneidades = array();
			$interacciones = array();
			if(isset($item['idoneidades'])){
				$idoneidades = $item['idoneidades'];	
			}
			if(isset($item['interacciones'])){
				$interacciones = array($item['interacciones']);	
			}
			unset($item['idoneidades']);
			unset($item['interacciones']);
			$this->medTable($item,$idoneidades, $interacciones);
			$this->ln();
			$this->ln();
		}
	}


	function firma(){
		$this->SetFont('Arial','',9);
		$yFirma = 265;
		$this->Line(40,$yFirma,155,$yFirma);
		$this->SetXY(70,$yFirma+2);
		$this->Cell(50,8,'Nombre y firma de paciente o familiar ');
	}
		
}

?>




