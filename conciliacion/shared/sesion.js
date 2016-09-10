angular
    .module('app')
    .service('sesion',sesionService);
        

sesionService.$inject = ['$http','$location','$q','$cookies','config'];
	

function sesionService($http,$location,$q,$cookies,config){
	var sesion = {
	    url = config.readyService();
		user : false
	}
	var service = {
        start: start,
        crete:create,
        check:check

    };

    return service;			
	
    function httpHeader(user) {
    	$http.defaults.headers.common = {
    		'Content-Type' :  "application/x-www-form-urlencoded",
    		'Auth': user.key
    	
    	}
    	console.log('headerDone');
    }

    function start(){

    }

    function create(){

    }



    function check(){
    	sesion.user = $cookies.getObject('user');
    	if(sesion.user != false){
    		console.log('ya existia')
    		httpHeader(sesion.user);
    		return sesion;
    	}else{
    		console.log('no esta');
    		return false;
    	}
    }
    
    function setsesion(option, value) {    	
    	if(option != undefined && value != undefined)
    		return $cookies.put(option, value);
    	else
    		return false;
    }

}