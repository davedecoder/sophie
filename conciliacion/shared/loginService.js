angular
    .module('app')
    .service('Login',loginService);
        

loginService.$inject = ['$http','$location','$q','$cookies','config'];
	

function loginService($http,$location,$q,$cookies,config){
	var	url = config.readyService();
	var user = {};
	var promise;
	var service = {
        getUsers: getUsers,
        returnCookies:returnCookies,
        getKey:getKey,
        deleteCookies,deleteCookies,
        ready: ready
    };

    return service;
    //getUsers Logic
	    function getUsers(){
	    	return $http.get('conciliacion/tets.js').then(getUserSuccess,getError)
	    }

	    function getUserSuccess(response){
	    	var tmp = response.data[0];
	    	if(tmp.permisos.login == true)
		    	if(tmp.permisos.admin == true ){
		    		return 'admin';
		    	}
	    		else{
	    			user.key = tmp.api_key;
	    			user.hospital = tmp.nombreHospital;
	    			user.nombre = tmp.nombreQuimico;
	    			user.permisos = tmp.permisos;
					$cookies.putObject('user', user);
					$http.defaults.headers.common = {
						'Content-Type' :  "application/x-www-form-urlencoded",
						'Auth': tmp.api_key
					}
	    			//alert('listo') //return user;
	    		}	
	    	else{console.log('cant login')}
	    }

	// getCookies logic
		function returnCookies(){
			if(user.key != undefined ){
				return user;
			}
			else
				return 'error';			
		}

		function deleteCookies(){
				$cookieStore.remove('key');
				$cookieStore.remove('hospital');
				$cookieStore.remove('nombre');
				$cookieStore.remove('user');
		}


		function getKey(){
			//var temp;
			//if( $cookieStore.get("key"))
		}	

    function getError(data){
    	console.log('Error');
    }

    function ready(){

    }

}