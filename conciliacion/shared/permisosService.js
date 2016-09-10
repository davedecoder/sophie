angular
    .module('app')
    .service('permisos',permisosService);

permisosService.$inject = ['$http','$cookies','config'];

function permisosService($http,$cookies,config){
    var url = config.readyService();
	var promise;
	var service = {
        ready: ready
    };

    return service;
    //

    function ready(){
    	
    }
	
}