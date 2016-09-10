angular
    .module('app')
    .service('prescriptionService',prescriptionService);
        

prescriptionService.$inject = ['$http','$location','$q', 'config', 'tracker'];

function prescriptionService($http,$location,$q,config,tracker){
    var url, last = false;
    var patient = {};
	var service = {
        getActives:getActives,
        ready:ready,
        post:postPrescription,
        goProfile:goProfile            
    };

    return service;
    
    function goProfile(patientID) {
        tracker.toProfile(patientID);
    }

    function ready(){
        url = config.ready();
        if (url == undefined)
        {
            $location.path("/login");                
        }else{
            var aux = tracker.findPatient2();
            if(aux.patient == false)
                $location.path("/home");
            return $http.get(url+'patient/'+aux.patient).then(findSuccess,getError);
            //return $http.get(url+"patient/"+patient.id+"/lastPrescription/").then(getPrescriptionSuccess, getError);

        }
    }

        function findSuccess(response){  
                var auxTmp = tracker.findPatient2();
                patient = response.data.body.patient;
                patient.idTipoPrescripcion = auxTmp.type;
                return $http.get(url+'patient/'+patient.idPaciente+'/lastClinicalData').then(patientSuccess,getError);//homeService.preview(user).then(alergies,getError);         
        }
            function patientSuccess(response){
                patient.clinical = response.data.body['clinical data'];
                return $http.get(url+'patient/'+patient.clinical.idPaciente+'/lastPrescription/').then(clinicalSuccess,getError);
            }
                function clinicalSuccess(response){
                    if (response.data.body.ultimaPrescripcion != undefined)
                        patient.medicamentos = response.data.body.ultimaPrescripcion;
                    else
                        patient.medicamentos = [[]];
                    return $http.get(url+'alergy/'+patient.idPaciente).then(alergiesSuccess,getError);
                }
                    function alergiesSuccess(response){
                        patient.alergy = response.data.body['alergies'];
                        return patient;
                    }



	function getPrescriptionSuccess(response) {
		if (response.data.body.ultimaPrescripcion != undefined)
			patient.medicamentos = response.data.body.ultimaPrescripcion;
		else
			patient.medicamentos = [[]];
		return patient;
	}


// Actives
    function getActives(){
        return $http.get(url+'medicine').then(activesSuccess,getError);
    }

        function activesSuccess(response){
            return response.data.body;
        }
    

    function postPrescription(prescription, fecha, tipo) {
        var aux = {}, pre = [];
        var hora =(fecha.toLocaleTimeString().length == 8) ? fecha.toLocaleTimeString() : "0"+fecha.toLocaleTimeString();
        var anio = fecha.getFullYear(); 
        var mes = ((1 + fecha.getMonth()).toString().length == 1) ? "0"+(1+fecha.getMonth()): 1 + fecha.getMonth();
        var dia = (fecha.getDate().toString().length == 1) ? "0"+fecha.getDate() : fecha.getDate();     
        var fecha1 = anio + "-" + mes + "-" + dia + " " + hora;             
        last = (tipo == 4) ? true : false; 
        for (i in prescription)
        {
            aux.capturaDatosFarma = fecha1;
            aux.idTipoMedicamento = (prescription[i].idTipoMedicamento != undefined) ? parseInt(prescription[i].idTipoMedicamento) : undefined; 
            aux.idTipoPrescripcion = parseInt(tipo);
            aux.idUnidad = (prescription[i].idUnidad != undefined) ? parseInt(prescription[i].idUnidad) : (prescription[i].abreviaturaUnidad != undefined) ? parseInt(prescription[i].abreviaturaUnidad.idUnidad) : undefined;
            aux.idFrecuencia = (prescription[i].idFrecuencia != undefined) ? parseInt(prescription[i].idFrecuencia) : (prescription[i].nombreFrecuencia != undefined) ? parseInt(prescription[i].nombreFrecuencia.idFrecuencia) : undefined;
            aux.idPresentacion = (prescription[i].idPresentacion != undefined) ? parseInt(prescription[i].idPresentacion) : undefined;
            aux.idPrincipio = (prescription[i].idPrincipio != undefined) ? parseInt(prescription[i].idPrincipio) : undefined;
            aux.idVia = (prescription[i].idVia != undefined) ? parseInt(prescription[i].idVia) : undefined;
            aux.concentracionDatosFarma = (prescription[i].concentracionDatosFarma != undefined && prescription[i].concentracionDatosFarma != '') ? prescription[i].concentracionDatosFarma : undefined;
            aux.cronicoDatosFarma = (prescription[i].cronicoDatosFarma != undefined) ? Boolean(parseInt(prescription[i].cronicoDatosFarma)) : false;
            aux.prescritoDatosFarma = (prescription[i].prescritoDatosFarma != undefined) ? Boolean(prescription[i].prescritoDatosFarma): undefined;     
            aux.inicioDatosFarma = (prescription[i].inicioDatosFarma != undefined && prescription[i].inicioDatosFarma != '') ? prescription[i].inicioDatosFarma : undefined;
            aux.notaDatosFarma = (prescription[i].notaDatosFarma != undefined) ? prescription[i].notaDatosFarma : undefined;
            aux.numeroAplicacionDatosFarma = 1;
            pre.push(angular.copy(aux));
            
        }
        var aux2 = tracker.findPatient2();
        return $http.post(url+'meds/'+aux2.patient, {medicamentos:pre}).then(postPrescriptionSuccess,getError);
    }






    function postPrescriptionSuccess(response) {
    	if (!response.data.error)
    	{
            var aux2 = tracker.findPatient2();
    		if (last)
    		{
    			$http.put(url+'patient/'+aux2.patient+'/exit/');
    			last = false;
    		}
            goProfile(aux2.patient);
    	}
    	else
    		return response;
    }
    


    function getError(data){
    	console.log('Error');
    }

}