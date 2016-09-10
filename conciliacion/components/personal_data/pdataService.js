angular
    .module('app')
    .service('pdataService',pdataService);

pdataService.$inject = ['$http','$q','permisos', 'config', 'tracker', '$location'];

function pdataService($http,$q,permisos, config, tracker, $location){
    var url,
    pType,
    clinicalData = {
    	paciente:{}, // no inicializado
        message:'' // no inicializado
    };
	var promise;
	var service = {
        postPatient:postPatient,
        postCD : postClinical,
        ready: ready,
        checkForUser:checkForUser,
        createAlergy:createAlergy,
        putAlergy:putAlergy,
        moving:moving
    };

    return service;
    
    function moving(event) {
    	config.moving(event);
    }
    
    function postClinical(user){  
		var aux;
    	if (user.ingresoDatosClinicos != undefined)
    	{  	
    		var tm = user.ingresoDatosClinicos.toLocaleTimeString().split(" ")[0]; 
	    	tm = (tm.toString().length == 8) ? tm : "0"+tm; 
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
        return $http.post(url+'clinicalData/'+clinicalData.paciente.idPaciente,data).then(postClinicalSuccess,error);
    }

    function postClinicalSuccess(response){
        if (response.data.body["clinical data"].idDatosClinicos != undefined)
        {
        	config.allowMoving();
        	$location.path("/new/prescription");        
        }
        else
        	return false;
    }
    
    // 
    function postPatient(user){
    	var anio = user.nacimientoPaciente.getFullYear(); 
    	var mes = ((1 + user.nacimientoPaciente.getMonth()).toString().length == 1) ? "0"+(1+user.nacimientoPaciente.getMonth()): 1 + user.nacimientoPaciente.getMonth();
    	var dia = (user.nacimientoPaciente.getDate().toString().length == 1) ? "0"+user.nacimientoPaciente.getDate() : user.nacimientoPaciente.getDate();
        var data = {
            nombrePaciente:String(user.nombrePaciente),
            apellidoPaternoPaciente:" ", 
            nacimientoPaciente:anio+"-"+mes+"-"+dia,  
            sexoPaciente:user.sexoPaciente
        }   
        return $http.post(url+'patient',data).then(postSuccess,error);
    }

    function postSuccess(response){
        if (response.data.body.patient.idPaciente != undefined)
        {
            var patientId = response.data.body.patient.idPaciente;
            tracker.newPatientClinical(patientId);
            //tracker.set("patient", response.data.body.patient.idPaciente);
            //tracker.set("prescription", 1);
            //clinicalData.paciente = {};
            //clinicalData.paciente.idPaciente = response.data.body.patient.idPaciente;
            //clinicalData.paciente.tipoPrescripcion = 1;
            
            // ESTA LLAMADA ES PARA CHECAR QUE SEA REINGRESO NO INTENCIONADO O NO.
            return $http.get(url+"patient/"+patientId+"/lastClinicalData").then(checkReingreso,error);
        }
        else
            return false;
    }

    function checkReingreso(response){
        if (response.data.body['clinical data'].idEstatusPaciente != undefined){
        	if (response.data.body['clinical data'].idEstatusPaciente == 2)
        		response.data.body['clinical data'].ingresoDatosClinicos = new Date();
            tracker.reIngreso2(response.data.body['clinical data'].idPaciente)
            return getPatient();
        }else{
            return getPatient();
        }
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
            clinicalData.clinicos = {};
            clinicalData.clinicos.idEstatusPaciente = hel.idEstatusPaciente;
            if (hel.idEstatusPaciente == 1 && hel.ingresoDatosClinicos != undefined)
                clinicalData.clinicos.ingresoDatosClinicos = new Date(hel.ingresoDatosClinicos.split(" ")[0]);  
            if(hel.identificadorPacienteDatosClinicos != undefined)
                clinicalData.clinicos.identificadorDatosClinicos = hel.identificadorPacienteDatosClinicos;
            if(hel.pesoDatosClinicos != undefined) 
                clinicalData.clinicos.pesoDatosClinicos = parseInt(hel.pesoDatosClinicos);
            if(hel.tallaDatosClinicos != undefined) 
                clinicalData.clinicos.tallaDatosClinicos = parseInt(hel.tallaDatosClinicos);                        
        }
        return clinicalData;
    }
    
	



    function ready(){
        url = config.ready();
        if (url == undefined)
        {
            $location.path("login");    
            var sync = $q.defer();
            return sync.promise;
        }
        else
            return $http.get(url+'services').then(getServicesSuccess, error);
    }

        function getServicesSuccess(response) {
            var aux = tracker.getType();
            if (aux.id != undefined)
            {
                clinicalData.paciente.idPaciente = aux.id;
                clinicalData.paciente.tipoPrescripcion = aux.typePrescription;
                clinicalData.servicios = response.data.body;
                return $http.get(url+"patient/"+clinicalData.paciente.idPaciente).then(getPersonalDataSuccess, error);  
            }
            else
            {
                clinicalData.servicios = response.data.body;
                return clinicalData;
            }
        }

        	function getPersonalDataSuccess(response) {
        		var aux = clinicalData.paciente.tipoPrescripcion;
        		clinicalData.paciente = response.data.body.patient;
        		clinicalData.paciente.nacimientoPaciente = new Date(clinicalData.paciente.nacimientoPaciente);
        		clinicalData.paciente.tipoPrescripcion = aux;
        		return $http.get(url+"patient/"+clinicalData.paciente.idPaciente+"/lastClinicalData").then(getClinicalSuccess, error);
        	}



    function error(response){
    	console.log("error");
        console.log(response);
        return clinicalData;
    }
    


    function checkForUser(){
        url = config.ready();
        config.setBlock();
        var aux = tracker.findPatient2();
        if(aux.type == 0 ){
            console.log('Error de Traker, no se esta registrando correctamente la accion')
            $location.path("/home");
        }
        else{
            if(aux.type == 1 && aux.patient == false){           //NUEVO PACIENTE
                    clinicalData.paciente = false;  // no hay datos precargados de nadie  
                    clinicalData.message = aux.text;
                    return getServices();
            }if(aux.type == 1 && aux.patient != false){   // la pagina se recargo despues de ingresar a un cristiano SIN INSERTAR DATO CLINICO
                    return getPatient(aux.patient);
            }else if(aux.type == 3){ //ES UN TRASLADO DE AREA, HAY QUE PRECARGAR DATOS.
                return getPatient(aux.patient);
            }else if(aux.type == 5){
                return getPatient(aux.patient);  //RE INGRESO detectado
            }else
                console.log('averiguar porque no aplica')
        }
    }


    function getPatient(){
        var aux = tracker.findPatient2();
        return $http.get(url+"patient/"+aux.patient).then(patientSuccess, error);
    }

    function patientSuccess(response){
        var aux = tracker.findPatient2();
        clinicalData.paciente = response.data.body.patient;
        clinicalData.paciente.nacimientoPaciente = new Date(clinicalData.paciente.nacimientoPaciente);
        clinicalData.paciente.tipoPrescripcion = aux.type;
        clinicalData.message = aux.text;
        return $http.get(url+"alergy/"+clinicalData.paciente.idPaciente).then(checkAllergies, error);
    }

    function checkAllergies(response){
        clinicalData.paciente.alergias = response.data.body['alergies'];
        return $http.get(url+"patient/"+clinicalData.paciente.idPaciente+"/lastClinicalData").then(checkClinical, error);
    }

    function checkClinical(response){
        if (response.data.body['clinical data'].idEstatusPaciente != undefined)
        {
            var hel = response.data.body['clinical data'];
            clinicalData.clinicos = {};
            clinicalData.clinicos.idEstatusPaciente = hel.idEstatusPaciente;
            if (hel.idEstatusPaciente == 1 && hel.ingresoDatosClinicos != undefined)
                clinicalData.clinicos.ingresoDatosClinicos = new Date(hel.ingresoDatosClinicos.split(" ")[0]);  
            if(hel.identificadorPacienteDatosClinicos != undefined)
                clinicalData.clinicos.identificadorDatosClinicos = hel.identificadorPacienteDatosClinicos;
            if(hel.pesoDatosClinicos != undefined) 
                clinicalData.clinicos.pesoDatosClinicos = parseInt(hel.pesoDatosClinicos);
            if(hel.tallaDatosClinicos != undefined) 
                clinicalData.clinicos.tallaDatosClinicos = parseInt(hel.tallaDatosClinicos);                        
        }
        return getServices();

    }



    function getServices(){
        return $http.get(url+'services').then(getAllergies, error);
    }

    function getAllergies (response){
        clinicalData.servicios = response.data.body;
        return $http.get(url+'alergy').then(returnData,error);
    }

        function returnData(response){
            clinicalData.alergias = response.data.body['alergies'];
            return clinicalData;
        }
    

    function reloadAllergies(){

    }

    function putAlergy(thisOne){
        var data = {
            alergias:[thisOne]
        }
        var aux = tracker.findPatient2();
        return $http.post(url+'alergy/'+aux.patient,data).then(alergyAsigned,error);
    }
        function alergyAsigned(response){
            console.log(response)
            return response.data.body[0];
        }

    function createAlergy(name){
        var data = {
            nombreAlergia:name
        }
        return $http.post(url+'alergy',data).then(alergyCreated,error);
    }

    function alergyCreated(response){
        console.log(response);
        return putAlergy(response.data.body[0]);
    }
	
}