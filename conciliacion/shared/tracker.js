angular
    .module('app')
    .service('tracker',trakerService);
        

trakerService.$inject = ['$http','$location','$q','$cookies'];
	

function trakerService($http,$location,$q,$cookies){
	var flag = {
		type:0, // no inicializado
		patient:{}, // no inicializado
		text:'' // no inicializado
	}



	var patient = {};
	var service = {
        ready: ready,
        findPatient:findPatient,
        remove:removeTracker,
        set: setTracker,
        getType: getType,
        findPatient2:findPatient2,
        newPatient:newPatient,
        newPatientClinical:newPatientClinical,
        toProfile:toProfile,
        reIngreso:reIngreso,
        reIngreso2:reIngreso2,
        nuevaConciliacion:nuevaConciliacion,
        egreso:egreso,
        traslado:traslado
    };

    return service;			
	
    function findPatient(){
    	if (patient.id == undefined)
    		patient.id = $cookies.get("patient");    	
    	if (patient.id != undefined)
    		return patient;	    	
	    else {
			return "false";
	    }
    }
	
	function getType(){
		if (patient.id == undefined || patient.typePrescription == undefined)
		{
			patient.id = $cookies.get("patient");
			patient.typePrescription = $cookies.get("prescription");
		}	
		if (patient.id != undefined && patient.typePrescription != undefined)		
			return patient;
		else
			return {};
	}

	function removeTracker(option){
		if (option == 'patient')
			patient = {};
		if(option != undefined)
			return $cookies.remove(option);
		else
			return false;		
	}

    function ready(){

    }
    
    function setTracker(option, value) {
    	if (option == "patient")
    		patient.id = value;
    	if (option == "prescription")  
			patient.typePrescription = value;    		
    	if(option != undefined && value != undefined)
    		return $cookies.put(option, value);
    	else
    		return false;
    }




	function deletePatient(){
		if($cookies.getObject('patient') != undefined){
			$cookies.remove('patient');
		}
	}


	function findPatient2(){
		if($cookies.getObject('patient') != undefined)
			flag = $cookies.getObject('patient');
		else
			flag.patient = false;
		return flag;
	}


	function toProfile(patientID){
		flag.type = 0; //no se maneja en el preview del perfil
		flag.text = 'Visita a perfil';
		flag.patient = patientID;
		deletePatient();
		$cookies.putObject("patient", flag);
		$location.path("/profile");	
	}




    function newPatient(){
    	flag.type=1;
    	flag.text = 'Ingreso de nuevo paciente';
    	deletePatient();
    	$location.path("/new/patient");
    }


    function newPatientClinical(patientID){
    	flag.type=1;
    	flag.patient = patientID;
    	flag.text = 'Datos clinicos de ingreso';
    	deletePatient();
    	$cookies.putObject("patient", flag);
    }


    function nuevaConciliacion(patientID){
    	flag.type=2;
    	flag.patient = patientID;
    	flag.text = 'Nueva prescripción';
    	deletePatient();
    	$cookies.putObject("patient", flag);
    	$location.path("/new/prescription");
    }



    function traslado(patientID){
    	flag.type=3;
    	flag.patient = patientID;
    	flag.text = 'Datos clinicos por traslado de area';
    	deletePatient();
    	$cookies.putObject("patient", flag);
    	$location.path("/new/patient");
    }


    function egreso(patientID){
    	flag.type=4;
    	flag.patient = patientID;
    	flag.text = 'Prescripción de egreso';
    	deletePatient();
    	$cookies.putObject("patient", flag);
    	$location.path("/new/prescription");
    }


    function reIngreso(patientID){
    	flag.type=5;
    	flag.patient = patientID;
    	flag.text = 'Datos clinicos de reingreso';
    	deletePatient();
    	$cookies.putObject("patient", flag);
    	$location.path("/new/patient");
    }
		
    function reIngreso2(patientID){ // no intencionado
    	flag.type=5;
    	flag.patient = patientID;
    	flag.text = 'Reingreso detectado';
    	deletePatient();
    	$cookies.putObject("patient", flag);
    }


}