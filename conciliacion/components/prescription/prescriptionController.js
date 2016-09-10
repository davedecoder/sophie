angular
    .module('app')
    .controller('prescriptionController',prescriptionController);
        
prescriptionController.$inject = ['$scope','Login','prescriptionService','options','$timeout','navbarService'];

function prescriptionController($scope,Login,prescriptionService,options,$timeout,navbarService){
	$scope.nav = navbarService;
	$scope.nav.usuario = navbarService.getUsers();


	$scope.hoy = new Date();
	$scope.flag = false;
	var prescription;
	$scope.vm = {
		patient:{},
		render:true,
		medicamentos:{},
		opciones:[],
		prescripcion:[],
		continuar:continuar,
		addMedicamento:addMedicamento,
		setPreview:setPreview,
		captureDate: new Date(),
		remove:remove,
		checkStatus:checkStatus,
		checkRender:checkRender,
		buttonState:buttonState,
		buttonValue:buttonValue,
		checkPrescription:checkPrescription,
		cancel:cancel,
		checkRender2:checkRender2
	}
	
	//start();
	$scope.$on("$locationChangeStart", checkChange);
	
	function checkChange(event, next, current){
		prescriptionService.moving(event);
	}
	
	prescriptionService.ready().then(start,error);

	function start(response){
		$scope.vm.patient = response;
		preloadMeds(response.medicamentos[0]);	
		prescriptionService.getActives().then(activesSuccess,error);
	}

	function preloadMeds(those){
		$scope.vm.prescripcion = those;
		for (var i in those){
			$scope.vm.prescripcion[i].state = 'item-warning';
		}

	}


	function activesSuccess(response){
		$scope.vm.medicamentos = response;
		options.getPrescripcion().then(prescripcionSuccess,error);
	}

	function prescripcionSuccess(response){
		$scope.vm.opciones = response;
		//$scope.tipoPrescripcion = $scope.vm.patient.idTipoPrescripcion;
		selectType($scope.vm.patient.idTipoPrescripcion);		
	}
	
	function selectType(idTipo){
		for(var i in $scope.vm.opciones.TipoPrescripcion)
			if($scope.vm.opciones.TipoPrescripcion[i].idTipoPrescripcion == idTipo)
				$scope.tipoPrescripcion = angular.copy($scope.vm.opciones.TipoPrescripcion[i]);

	}

	function continuar(){
		console.log('click')
		if ($scope.vm.prescripcion.length > 0)
			prescriptionService.post($scope.vm.prescripcion, $scope.vm.captureDate, $scope.tipoPrescripcion.idTipoPrescripcion).then(error, error);
		else
			prescriptionService.goProfile($scope.vm.patient.idPaciente);
	}	

	function cancel(){
		prescriptionService.goProfile($scope.vm.patient.idPaciente);
	}



	//add medicamento
	
	
	function addMedicamento(thisOne){
		if(thisOne.nombrePrincipio != null){
			var principio =  angular.copy(thisOne);
			principio.state = 'item-danger';
			principio.cronicoDatosFarma = 0;
			principio.prescritoDatosFarma = 0;
			principio.numeroAplicacionDatosFarma = 1;
			$scope.vm.prescripcion.push(principio);
			$scope.vm.render = false;
			$timeout(function () {
				$scope.vm.render = true;
			},-1);
		}
	}


	function remove(indx){
		var tempList = angular.copy($scope.vm.prescripcion);
		for(var i in tempList)
			if(angular.equals(tempList[i],indx))
				var idd = i;

		tempList.splice(idd,1);
		$scope.vm.prescripcion = angular.copy(tempList)
	}

	function setPreview(thisOne,indx){
		$scope.onPreview = thisOne;
		//console.log($scope.onPreview)
		$scope.onPreview.indx = indx;
	}

	function continueSuccess(){

	}

	function error(response){
		if (response != undefined)//Si es undefined significa que no hubo error
			console.log(response);
	}


	function checkPrescription(){
		var tempPrescription = angular.copy($scope.vm.prescripcion);
		for(var i in $scope.vm.prescripcion){
			tempPrescription[i] = checkStatus($scope.vm.prescripcion[i])
		}
		$scope.vm.render = false;
		$timeout(function () {
				$scope.vm.render = true;
			},100);

	}


		function checkStatus(thisOne){
			if(angular.isDefined(thisOne.idTipoMedicamento)  &&
				angular.isDefined(thisOne.idUnidad) &&
				angular.isDefined(thisOne.concentracionDatosFarma) &&
				angular.isDefined(thisOne.idPresentacion) &&
				angular.isDefined(thisOne.idVia) &&
				angular.isDefined(thisOne.idFrecuencia) &&
				angular.isDefined(thisOne.inicioDatosFarma)
			)
				if(thisOne.concentracionDatosFarma != "" && thisOne.inicioDatosFarma !="")
					if(angular.isString(thisOne.concentracionDatosFarma) &&
						angular.isNumber(parseInt(thisOne.idUnidad))  &&
						angular.isNumber(parseInt(thisOne.idPresentacion)) &&
						angular.isNumber(parseInt(thisOne.idVia))  &&
						angular.isNumber(parseInt(thisOne.idFrecuencia)) &&
						angular.isString(thisOne.inicioDatosFarma)  &&
						angular.isNumber(parseInt(thisOne.idTipoMedicamento))
						)
						thisOne.state = 'item-success';
					else
						thisOne.state = 'item-danger';
				else 
					thisOne.state = 'item-danger';
			else 
					thisOne.state = 'item-danger';

			return thisOne;		
		}

	function checkRender(thisOne){
		$scope.vm.render = false;
		var state = thisOne.state;
		var tmpMed = checkStatus(thisOne);
		if(state != tmpMed.state){
			for(var i in $scope.vm.prescripcion){
				if($scope.vm.prescripcion[i].idPrincipio == thisOne.idPrincipio){
					$scope.vm.prescripcion[i] = angular.copy(tmpMed);
				}
			}
		}
		$timeout(function () {
				$scope.vm.render = true;
			},500);
	}

		function checkRender2(){
		var state = $scope.onPreview.state;
		var tmpMed = checkStatus($scope.onPreview);
		if(state != tmpMed.state){
			for(var i in $scope.vm.prescripcion){
				if($scope.vm.prescripcion[i].idPrincipio == $scope.onPreview.idPrincipio){
					$scope.vm.prescripcion[i] = angular.copy(tmpMed);
				}
			}
		}

	}


	function buttonState(thisOne){
		if(thisOne == 1)
			return 'btn-primary';
		else
			return 'btn-danger'
	}

	function buttonValue(thisOne){
		if(thisOne == 0)
			return 1;
		else 
			return 0;
	}



}
