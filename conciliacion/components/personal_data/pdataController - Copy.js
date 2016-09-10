angular
    .module('app')
    .controller('pdataController',pdataController);

pdataController.$inject = ['$scope','pdataService'];

function pdataController($scope,pdataService){

	$scope.datosClinicos = false;
	$scope.vm = {
		postUser:postPatient,
		postCD:postCD,
		services:[],
		paciente:{},
		addAlergy:addAlergy
	}
	
	//pdataService.ready().then(gettingData,error);	
	
	pdataService.checkForUser().then(gettingData,error);	



	function gettingData(response) {
		$scope.personal_data.nacimientoPaciente = new Date();
		$scope.vm.alergias = response.alergias;
		$scope.vm.services = response.servicios;
		if (response.paciente.idPaciente != undefined)
		{
			$scope.datosClinicos = true;
			$scope.personal_data = response.paciente;
			if (response.clinicos != undefined)			
				$scope.paciente = response.clinicos;
			else
				$scope.paciente.ingresoDatosClinicos = new Date();
		}
		else
			$scope.paciente.ingresoDatosClinicos = new Date();
		console.log($scope.paciente)							
	}
	
	function postCDSuccess(response) {
		if (response == undefined)
			console.log("Error");
	}
	

	function addAlergy(thisOne){
		if(thisOne.nombreAlergia != null){
			pdataService.putAlergy(thisOne,$scope.personal_data.idPaciente).then(handleAlergy,error)
		}
		else{
			pdataService.createAlergy(thisOne).then(handleAlergy,error);
		}
	}

		function handleAlergy(response){
			$scope.personal_data.alergias.push(response)
		}

	function postCD(cd) {
		pdataService.postCD(cd).then(postCDSuccess,error);
	}
	
	function postPatient(user){	
		pdataService.postPatient(user).then(postPatientSuccess,error);					
	}
	function postPatientSuccess(response){
		if (response != undefined)
		{
			$scope.datosClinicos = true;
			if (response.idDatosClinicos != undefined){
				$scope.paciente = response;
				$scope.personal_data = response;
				$scope.personal_data.alergias = [];
			}
		}
		else
			console.log("error");
	}
	function error(response){
		console.log(response);
	}

}