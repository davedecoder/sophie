angular
    .module('app')
    .service('homeService',homeService);

homeService.$inject = ['$http','$q','permisos','Login', 'tracker', '$location', 'config','chart'];

function homeService($http,$q,permisos,Login, tracker, $location, config,chart){
	var promise;
    var url;
    var patients = {
        hospitalizados:0,   //no inicializados
        altas:0            //no inicializados
    }
	var service = {
        ready: ready,
        //preview:preview,
        alerts:alerts,
        moveToProfile:moveToProfile,
        grafica:grafica,
        altas:altas,
        newPaciente:newPaciente,
        imprimir:imprimir
    };



    return service;
    
    function newPaciente() {
    	tracker.newPatient();
    }

// ready
    function ready(){
        url = config.ready();
        return userSuccess();
    }


    function grafica(obj, fecha){
        //return chart.crea(num);
       // return chart.ready();			
       return chart.ready2(obj, fecha);
    }

    function imprimir(){
        return chart.imprimir();
    }

    function userSuccess(){
        return $http.get(url+'patient/all/hospitalized/').then(patientsSuccess,getError);
    } 

    function patientsSuccess(response){
            patients.hospitalizados = response.data.body;
        return altas();
    }
/*PREVIEW LOGIC
    function preview(patient){
        user = {};
        user = patient;
        return $http.get(url+'patient/'+patient.idPaciente+'/lastClinicalData').then(patientSuccess,getError);
    }
    function patientSuccess(response){
        user.clinical = response.data.body['clinical data'];
        return $http.get(url+'patient/'+response.data.body['clinical data'].idPaciente+'/lastPrescription/').then(clinicalSuccess,getError);
    }
    function clinicalSuccess(response){
        user.prescription = response.data.body['ultimaPrescripcion'];
        return user;
    }
*/

function altas(){
    var data ={
        estatus:2,
        fecha_1:0,
        edad_1:0,
        genero:0
    }
    return $http.post(url+'search',data).then(altasSuccess,getError);
}
function altasSuccess(response){
    patients.altas = response.data.body['search'];
    return patients;
}

// Alerts Logic
    function alerts(){
        return $http.get(url+'warnings').then(alertsSuccess,getError);
    }
    function alertsSuccess(response){
        var alertas = response.data.body['alertas'];
        alertas.all = alertas['1'].concat(alertas['2']);
        alertas.interacciones = warningClassify(alertas['3']);
        return alertas;
    }


    function warningClassify(all){
        var interacciones = [];
        for(var i in all){
            if(all[i].tipoInteraccion){
                var tmp = all[i];
                if(all[i].tipoInteraccion == 1)
                    tmp.nombreInteraccion = 'Alim. - Med.';
                else
                    tmp.nombreInteraccion = 'Med. - Med.';
                if(tmp.categorizacionInteraccion == 1)
                    tmp.nombreCategoria = 'menor';
                if(tmp.categorizacionInteraccion == 2)
                    tmp.nombreCategoria = 'moderada';
                if(tmp.categorizacionInteraccion == 3)
                    tmp.nombreCategoria = 'mayor';
                interacciones.push(tmp)
            }
        }
        return interacciones;
    }




//Error handler
    function getError(response){
        console.log(response)
    }
    
	function moveToProfile(paciente) {

        tracker.toProfile(paciente.idPaciente)
		//if (tracker.set("patient", paciente.idPaciente) != false)				
	}

}