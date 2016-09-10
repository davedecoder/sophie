angular
    .module('app')
    .controller('cdataController',cdataController);

cdataController.$inject = ['$scope','cdataService'];

function cdataController($scope,cdataService){
		$scope.vm = {
			services:[],
			paciente:{},
			send:send,
			ready:ready
		};
		
		cdataService.ready().then(ready, error);
		
		function ready(response){
			$scope.vm.services = response.servicios;
			$scope.vm.paciente = response.paciente;			
		}

		function send(user){
			cdataService.postClinical(user).then(postSuccess,error);
		}

		function postSuccess(response){
			if (response != undefined)
				console.log("error");
		}

		function error(response){
			console.log(response);
		}

}