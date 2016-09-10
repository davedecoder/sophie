angular
    .module('app')
    .service('config',configService);
        

configService.$inject = ['$http','$location','$q','$cookies'];
	

function configService($http,$location,$q,$cookies){
	var	url ='http://localhost/nubaAPI/API/v1/';
	var user = {};
	var service = {
        getUsers: getUsers,
        returnCookies:returnCookies,
        getKey:getKey,
        deleteCookies,deleteCookies,
        pushUser:getUserSuccess,
        ready: ready
    };

    return service;
    
    function configureHeader() {
    	$http.defaults.headers.common = {
    		'Content-Type' :  "application/x-www-form-urlencoded",
    		'Auth': user.key
    	}
    }
    
    //getUsers Logic    
    function getUsers(){
    	return $http.get('conciliacion/tets.js').then(getUserSuccess,getError)
    }

    function getUserSuccess(response){
    	//Revisar para que no se haga siempre la inserción de la cookie si ya existe
    	if(response.data.permisos.config == true)
	    	if(response.data.permisos.admin == true ){
	    		return 'admin';
	    	}
    		else{
    			user = {};
    			user.key = response.data.api_key;
    			user.hospital = response.data.nombreHospital;
    			user.nombre = response.data.nombreQuimico +' '+ response.data.apellidoPaternoQuimico;
    			user.permisos = response.data.permisos;
				$cookies.putObject('user', user);
				configureHeader();					
    			//alert('listo') //return user;
    			return "user";
    		}	
    	else{
    		console.log('cant config');
    		return "error";
    	}
    }
    
	function deleteCookies() {
		//Falta esta implementación 
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

    function getError(data){
    	console.log('Error');
    }

    function ready(){
		if (user.key == undefined)
		{
			user = $cookies.getObject('user');
			if (user == undefined)
			{
				$location.path("login");
			}
			configureHeader();
		}
		return url;
    }

}