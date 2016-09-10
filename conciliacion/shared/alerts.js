angular
	.module("app")
	.service('alerts', alertService);

alertService.$inject = ['$http','config'];

function alertService($http, config) {
	var url;
	var service = {
		all:all,
		personal:personal,
		accept:accept,
		ready:ready
	};
	
	return service
	
	function ready() {
		url = config.ready();
	}
	
	function all() {
		return $http.get().then();
	}
	
	function personal(paciente) {
		return $http.get().then();	
	}
	
	function accept(alerta) {
		alerta.idAccion = 1;
		alerta.idAlerta = parseInt(alerta.idAlerta);
		return $http.put(url+'warnings/'+alerta.idAlerta,{alertas:[alerta]}).then(response,error);
	}
	
	function response(response) {
		return response.data;
	}
	
	function error(response) {
		console.log("error");
		console.log(response);
	}
}