angular
    .module('app')
    .controller('pdataController',pdataController);

pdataController.$inject = ['$scope','pdataService','navbarService'];

function pdataController($scope,pdataService,navbarService){
	$scope.nav = navbarService;
	$scope.nav.usuario = navbarService.getUsers();
	$scope.datosClinicos = false;
	$scope.personal_data = {};
	$scope.vm = {
		postUser:postPatient,
		postCD:postCD,
		services:[],
		paciente:{},
		addAlergy:addAlergy,
		nameError:false
	}
	
	//pdataService.ready().then(gettingData,error);	
	
	$scope.$on("$locationChangeStart", checkChange);
	
	function checkChange(event, next, current){
		console.log(next);
		pdataService.moving(event);
	}
	
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
	}
	
	function postCDSuccess(response) {
		if (response != undefined)
			console.log("Error");
	}
	

	function addAlergy(thisOne){
		if(thisOne.nombreAlergia != null){
			pdataService.putAlergy(thisOne).then(handleAlergy,error)
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
		$scope.vm.nameError = false;
		var tmp = user.nombrePaciente;
		for(var i = 0; i<tmp.length ; i++){
			if(tmp[i] != ' ')
				if( !isNaN(tmp[i]) )
					 $scope.vm.nameError = true;
		}
		if($scope.vm.nameError == false)
			pdataService.postPatient(user).then(postPatientSuccess,error);					
	}

	function postPatientSuccess(response){
		if (response != undefined)
		{
			$scope.datosClinicos = true;
			$scope.personal_data = response.paciente;
			if (response.clinicos != undefined)
				console.log('here')
				$scope.paciente.clinicos = response.clinicos;
		}
		else
			console.log("error");
	}
	function error(response){
		console.log(response);
	}

}