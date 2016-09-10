angular
    .module('app')
    .service('tracker',trakerService);
        

trakerService.$inject = ['$http','$location','$q','$cookies'];
	

function trakerService($http,$location,$q,$cookies){
	var patient = {};
	var service = {
        ready: ready,
        findPatient:findPatient,
        remove:removeTracker,
        set: setTracker,
        getType: getType
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
		console.log(option)
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

}