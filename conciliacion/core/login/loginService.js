angular
    .module('app')
    .service('loginService',loginService);
        

loginService.$inject = ['$http','$location','$q','config'];

function loginService($http,$location,$q,config){
    var tryer = {};
	var promise;
	var service = {
        checkUser:checkUser,
        ready: ready
    };

    return service;


    function checkUser(thisOne){
        tryer = thisOne
        return $http.get('conciliacion/tets.js').then(validate,getError)
    }

    function validate(response){
        var user = response.data;
        var error = {hasError:false};
        for(var i in user){
            if(user[i].loginQuimico == tryer.name){
                    error.email=false;
                    if(user[i].contraseñaQuimico == tryer.password){
                        tryer = {};
                        var aux = {
                            data:user[i]
                        };              
                        var res = config.pushUser(aux);
                        if (res == "user")
                            $location.path("/home");                 
                    }else{
                        error.hasError= true;
                        error.password = true;
                    }
                }else{
                    if(error.email != false ){
                        error.hasError = true;
                        error.email = true;
                    }
                }
            }
        return error;    
    }

    function getUserSuccess(response){
       var user = response.data;
       for(var i in user){
            if(user[i].loginQuimico == tryer.name && user[i].contraseñaQuimico == tryer.password)
            {
            	tryer = {};
            	var aux = {
            		data:user[i]
            	};            	
				var res = config.pushUser(aux);
				if (res == "user")
					$location.path("/home");
            	return;
            }
       }


    }

    function getError(data){
    	console.log('Error');
    }
    function ready(){

    }

}