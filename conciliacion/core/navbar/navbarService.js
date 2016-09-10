angular
    .module('app')
    .service('navbarService', navbarService );
        

navbarService.$inject = ['$http','$location','$q','$cookies'];

function navbarService($http,$location,$q,$cookies){

	var promise;
	var service = {
        getUsers: getUsers,
        logOut:logOut,
        ready: ready,
        goHome:goHome,
        getKey:getKey
    };

    return service;
    
    function getKey() {
    	var aux = $cookies.getObject('user');
    	if (aux != undefined)
    		return aux.key;
    	else
    		return '';
    }

    function logOut(){
        console.log('cerrando')
        $cookies.remove('user');
        $location.path("/inicio");     

    }
    
    function goHome() {
    	$cookies.remove('patient');
    	$cookies.remove('prescription');
    	$location.path("/home");
    }


    //
    function getUsers(){
    	return $cookies.getObject('user');
    }


    function getError(data){
    	console.log(data);
    }

    function ready(){

    }

}