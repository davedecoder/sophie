angular
    .module('app')
    .service('cdataService',cdataService);

cdataService.$inject = ['$http','$q','permisos', 'config', 'tracker', '$location'];

function cdataService($http,$q,permisos,config,tracker,$location){
	var promise;
    var url;
    var clinicalData = {
    	servicios : {},
    	paciente : {}
    };
	var service = {
        postClinical: postClinical,
        ready: ready
    };



    return service;
    

    function ready(){
    	url = config.ready();
    	if (url == undefined)
    	{
    		$location.path("login");	
    		var sync = $q.defer();
    		return sync.promise;
    	}
    	else{		
			var aux = tracker.getType();
			clinicalData.paciente.idPaciente = aux.id;
			clinicalData.paciente.tipoPrescripcion = aux.typePrescription;
			if (clinicalData.paciente.idPaciente == undefined || clinicalData.paciente.tipoPrescripcion == undefined)
				$location.path("/home");
			else
				return $http.get(url+'services').then(getServicesSuccess, error);
		}
    }



    
    // 
    function postClinical(user){  
		var aux;
    	if (user.ingresoDatosClinicos != undefined)
    	{  	
	    	var tm = (user.ingresoDatosClinicos.toLocaleTimeString().length == 8) ? user.ingresoDatosClinicos.toLocaleTimeString() : "0"+user.ingresoDatosClinicos.toLocaleTimeString(); 
	    	var anio = user.ingresoDatosClinicos.getFullYear(); 
	    	var mes = ((1 + user.ingresoDatosClinicos.getMonth()).toString().length == 1) ? "0"+(1+user.ingresoDatosClinicos.getMonth()): 1 + user.ingresoDatosClinicos.getMonth();
	    	var dia = (user.ingresoDatosClinicos.getDate().toString().length == 1) ? "0"+user.ingresoDatosClinicos.getDate() : user.ingresoDatosClinicos.getDate(); 	
	    	aux = anio + "-" + mes + "-" + dia + " " + tm;
	    }	    
        var data = {
            ingresoDatosClinicos: (aux != undefined) ? aux : undefined,
            idServicio: user.Servicio.idServicio,
            pesoDatosClinicos: (user.pesoDatosClinicos != undefined) ? user.pesoDatosClinicos : undefined,
            tallaDatosClinicos: (user.tallaDatosClinicos != undefined) ? user.tallaDatosClinicos : undefined,
            identificadorPacienteDatosClinicos: (user.identificadorDatosClinicos != undefined) ? user.identificadorDatosClinicos: undefined,
            camaDatosClinicos: (user.camaDatosClinicos != undefined) ? user.camaDatosClinicos : undefined,
            motivoDatosClinicos: (user.motivoDatosClinicos != undefined) ? user.motivoDatosClinicos : undefined,
            diagnosticoDatosClinicos: (user.diagnosticoDatosClinicos != undefined) ? user.diagnosticoDatosClinicos : undefined,
            doctorDatosClinicos: (user.doctorDatosClinicos != undefined) ? user.doctorDatosClinicos : undefined,            
            observacionDatosClinicos: (user.observacionDatosClinicos != undefined) ? user.observacionDatosClinicos : undefined          
        }
        return $http.post(url+'clinicalData/'+user.idPaciente,data).then(postClinicalSuccess,error);
    }

    function postClinicalSuccess(response){
        if (response.data.body["clinical data"].idDatosClinicos != undefined)
        	$location.path("/new/prescription");        
        else
        	return false;
    }
	
	function getPersonalDataSuccess(response) {
		var aux = clinicalData.paciente.tipoPrescripcion;
		clinicalData.paciente = response.data.body.patient;
		clinicalData.paciente.tipoPrescripcion = aux;
		return $http.get(url+"patient/"+clinicalData.paciente.idPaciente+"/lastClinicalData").then(getClinicalSuccess, error);
	}
	
	function getClinicalSuccess(response){
		if (response.data.body['clinical data'].idEstatusPaciente != undefined)
		{
			if (clinicalData.paciente.tipoPrescripcion == 1)
			{
				clinicalData.paciente.tipoPrescripcion = 5;
				tracker.set("prescription",5);//Reingreso
			}
			var hel = response.data.body['clinical data'];
			clinicalData.paciente.idEstatusPaciente = hel.idEstatusPaciente;
			if (hel.idEstatusPaciente == 1 && hel.ingresoDatosClinicos != undefined)
				clinicalData.paciente.ingresoDatosClinicos = new Date(hel.ingresoDatosClinicos.split(" ")[0]);	
			if(hel.identificadorPacienteDatosClinicos != undefined)
				clinicalData.paciente.identificadorDatosClinicos = hel.identificadorPacienteDatosClinicos;
			if(hel.pesoDatosClinicos != undefined) 
				clinicalData.paciente.pesoDatosClinicos = parseInt(hel.pesoDatosClinicos);
			if(hel.tallaDatosClinicos != undefined) 
				clinicalData.paciente.tallaDatosClinicos = parseInt(hel.tallaDatosClinicos);						
		}
		return clinicalData;
	}
	
	function getServicesSuccess(response) {
		clinicalData.servicios = response.data.body;
		return $http.get(url+"patient/"+clinicalData.paciente.idPaciente).then(getPersonalDataSuccess, error);
	}
	
    function error(response){
        console.log(response);
        return clinicalData;
    }


	
}