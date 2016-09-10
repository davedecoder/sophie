angular
    .module('app')
    .service('options',optionService);
        

optionService.$inject = ['$http','$location','$q','$cookies','config'];
	

function optionService($http,$location,$q,$cookies,config){
	var	url = config.readyService();
	var service = {
        ready: ready,
        getPrescripcion:getPrescripcion
    };

    return service;
    //getUsers Logic
	    function getUsers(){
	    	return $http.get('conciliacion/tets.js').then(getUserSuccess,getError)
	    }

	    function getUserSuccess(response){
	    	if(response.data.permisos.config == true)
		    	if(response.data.permisos.admin == true ){
		    		return 'admin';
		    	}
	    		else{
	    			user.key = response.data.api_key;
	    			user.hospital = response.data.nombreHospital;
	    			user.nombre = response.data.nombreQuimico;
	    			user.permisos = response.data.permisos;
					$cookies.putObject('user', user);
					$http.defaults.headers.common = {
						'Content-Type' :  "application/x-www-form-urlencoded",
						'Auth': response.data.api_key
					}
	    			//alert('listo') //return user;
	    		}	
	    	else{console.log('cant config')}
	    }

	// getCookies logic
		function returnCookies(){
			if(user.key != undefined ){
				return user;
			}
			else
				return 'error';			
		}


		function getKey(){
			if( $cookies.getObject("user")==undefined ){
				console.log('undefined')
			}
		}	

		function getUrl(){
			return url;
		}

	function getPrescripcion(){
		return $http.get(url+'opMedicine').then(presSuccess,getError);
	}

	function presSuccess(response){
		var ops = response.data.body;
		ops.TipoMedicamento = [{idTipoMedicamento:1, nombreTipoMedicamento:'AH'},{idTipoMedicamento:2, nombreTipoMedicamento:'ARH'},{idTipoMedicamento:3, nombreTipoMedicamento:'CH'}, {idTipoMedicamento:4, nombreTipoMedicamento:'CR'}, {idTipoMedicamento:5, nombreTipoMedicamento:'H'}, {idTipoMedicamento:6, nombreTipoMedicamento:'QX'}];
		ops.TipoPrescripcion = [{idTipoPrescripcion:1, nombreTipoPrescripcion:'Primera Conciliación'},{idTipoPrescripcion:2, nombreTipoPrescripcion:'Nueva Conciliación'},{idTipoPrescripcion:3, nombreTipoPrescripcion:'Traslado de Área'},{idTipoPrescripcion:4, nombreTipoPrescripcion:'Egreso de Paciente'},{idTipoPrescripcion:5, nombreTipoPrescripcion:'Reingreso de Paciente'}];		
		return ops;
	}

    function getError(data){
    	console.log('Error');
    }

    function ready(){

    }

}