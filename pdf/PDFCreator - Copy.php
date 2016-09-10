<?php


class PDFCreator{
	private $Roboto;
	function __construct(){
        //require_once dirname(__FILE__) . '/tfpdf.php'; 
        require_once dirname(__FILE__) . '/Historico.php'; 

	}


	function createPatientHistory($patientObj, $dir){

		$pdf = new Historico();//				
		$pdf->AddFont('Arial', 'I', 'ariali.ttf', true); //
		$pdf->AddFont('Arial', 'B', 'ariblk.ttf', true);
		$pdf->AddFont('Arial', '', 'arial.ttf', true);				
		/*Datos hospital*/
		$defaultHospital = array("logo" => "assets/img/logo/nuba.png", "nuba" => "assets/img/logo/nuba.png", "calleHospital" => "calleHospital", "numeroExteriorHospital" => "7", "coloniaHospital" => "colonia hospital",  "telefonoHospital" => "223232" );
		if(!isset($patientObj["hospital"])){
			$patientObj["hospital"] = $defaultHospital;			
		}
		if(!isset($patientObj["nuba"])){
			$patientObj["nuba"] = "assets/img/logo/nuba.png";
		}
		$global = $patientObj["hospital"];
		array_push($global, $patientObj["nuba"]);
		$pdf->Globalicer($global);
		$pdf->AddPage();
		$pdf->SetFont('Arial','',16);
		/*tipo Reporte*/ 	
		$reportType = 2;
		$titulo = "Historico";
		if(isset($patientObj["tipoReporte"])){
			/*Config*/
			switch ($patientObj["tipoReporte"]) {
						case 1:
							$titulo = "Prescripción Actual";
							$reportType = $patientObj["tipoReporte"];				
							break;
						case 2:
							$titulo = "Historial";
							$reportType = $patientObj["tipoReporte"];
							break;
						case 3:
							$titulo = "Perfil de Egreso";
							$reportType = $patientObj["tipoReporte"];
							break;
						default:
							break;
					}		
		}else{
			$titulo = "Historico";
			$reportType = 2;
		}		
		$pdf->MultiCell(0,8,$titulo,0,'C');		
		//var_dump($patientObj);
		$pdf->patientData($patientObj);
		$pdf->ln();		
		if(isset($patientObj['medicamentos']) && (count($patientObj['medicamentos']) > 0)){
			$pdf->reportsHandler($patientObj['medicamentos'], $reportType);	
		}else{
			$pdf->ln();			
			$pdf->MultiCell(190,5, " No hay Prescripciones ",0,'C');			
		}
		$pdf->firma();
		$i = 1; 
		if ($handle = opendir($dir)) {
		    while (($file = readdir($handle)) !== false){
		        //if (!in_array($file, array('.', '..')) && !is_dir($dir.$file)) 
		            $i++;
		    }
		}
		$content = $pdf->Output($dir."pdf$i.pdf","F");
		return $dir . "pdf$i.pdf";
		/*
		$filename = "C:/wamp/www/nubaAPI/API/include/pdf/tmp/history/test.pdf";
		$pdf->Output($filename, 'F');		
		
		$i = 1; 
		$dir = 'C:/wamp/www/nubaAPI/API/include/pdf/tmp/history';
		if ($handle = opendir($dir)) {
		    while (($file = readdir($handle)) !== false){
		        if (!in_array($file, array('.', '..')) && !is_dir($dir.$file)) 
		            $i++;
		    }
		}
		$content = $pdf->Output("C:/wamp/www/nubaAPI/API/include/pdf/tmp/history/pdf$i.pdf","F");
		echo "C:/wamp/www/nubaAPI/API/include/pdf/tmp/history/pdf$i.pdf";
		*/
	}
}

$sentencia = json_decode(file_get_contents('php://input'), true);
$data = $sentencia["sentencia"];
$pdf = new PDFCreator();  
$dir = 'tmp/history/';
$output = $pdf->createPatientHistory($data, $dir);
echo "pdf/" . $output;

?>