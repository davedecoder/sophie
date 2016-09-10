angular
    .module('app')
    .service('landingService',landingService);
        
landingService.$inject = ['$http','$location','$q','Login'];

function landingService($http,$location,$q,Login){

	var promise;
	var service = {
        getUsers: getUsers,
        ready: ready
    };

    return service;
    //
    function getUsers(){
    	return Login.getUsers().then(getUserSuccess,getError);
    }

    function getUserSuccess(data){
    	console.log('Redirected');
    }

    function getError(data){
    	console.log('Error');
    }

    function ready(){

    }

}