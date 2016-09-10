
angular
    .module('app')
    .controller('profileController',profileController);
        
profileController.$inject = ['$scope','Login','profileService','navbarService'];

function profileController($scope,Login,profileService,navbarService){
	$scope.nav = navbarService;
	$scope.nav.usuario = navbarService.getUsers();

	var interaccionM={
			receta:'',
			idDC:0,
			medicamentos:[],
			tipoInteraccion:2,
			categorizacionInteraccion:1
		}

	var	interaccionA={
			receta:'',
			idDC:0,
			medicamentos:[],
			tipoInteraccion:1,
			categorizacionInteraccion:1
		}



	$scope.labels = {
		currentEvaluation:'',
		loading:true,
		loadError:'Cargando datos',
		noInterno : false
	}
	$scope.vm = {
		evalIdoneidad:false,
		notaIdoneidad:'',
		postInteraccion:postInteraccion,
		alertas:{},
		patientAlert:patientAlert,
		interaccionM:angular.copy(interaccionM),
		interaccionA:angular.copy(interaccionA),
		cancelIdoneidad:cancelIdoneidad,
		saveIdoneidad:saveIdoneidad,
		estados:{},
		patient:{},
		prescription:[],
		alergy:[],
		checkOut:{},
		history:{},
		nullString:nullString,
		traslado:traslado,
		egreso:egreso,
		nuevaConciliacion:nuevaConciliacion,
		reingreso:reingreso,
		evaluar:evaluar,
		check:check,
		testHistorial:testHistorial,
		setOpen:setOpen, // funcion para abrir el panel de historial
		printReceta:printReceta,
		printHistorial:printHistorial,
		reportTest:reportTest,
		newRam:newRam,
        editCD:editCD,
        editPD:editPD

	}


	$scope.optionLabels = [];
		    		$scope.optionLabels.push("¿Indicado?");
		    		$scope.optionLabels.push("¿Duplicidad?");
		    		$scope.optionLabels.push("¿Adecuado a patologia?");
		    		$scope.optionLabels.push("¿Adecuado a peso, talla y edad? ");
		    		$scope.optionLabels.push("¿Alergia?");
		    		$scope.optionLabels.push("¿Interaccion Med-Med?");
		    		$scope.optionLabels.push("¿Interaccion Med-Alim? ");
		    		$scope.optionLabels.push("¿Insuficiencia renal o hepática? ");   
		    		$scope.statesLabels = [];
		    		$scope.statesLabels.push("OK = NO HAY ACCIONES A SEGUIR");
		    		$scope.statesLabels.push("IF = ES POSIBLE CONTINUAR LA MINISTRACION DE MEDICAMENTOS PERO SE DEBE HACER INTERVENCION FARMACEUTICA CON EL MEDICO QUE PRESCRIBE LO ANTES POSIBLE Y SOLICITAR AL CUIDADOR QUE SE MONITORIZE EL PACIENTE CON LA RECOMENDACIÓN DEL FARMACEUTICO.  ");
		    		$scope.statesLabels.push("X  = SE DEBE DETENER MINISTRACIÓN DE MEDICAMENTO, HACER INTERVENCION FARMACEUTICA CON EL MEDICO TRATANTE Y MINISTRAR HASTA AUTORIZACIÓN MEDICA.");
					
	profileService.find().then(userInit,error);		
	
	function check(thisOne){
		return profileService.change(thisOne);
	}
	
	function newRam() {
		profileService.modal_RAM($scope.vm.patient.prescription[0]);
	}

	function evaluar(receta){
		var tmp = profileService.evaluar(receta);
		$scope.vm.estados = tmp;
	}

	function cancelIdoneidad(){
		$scope.vm.evaluar($scope.vm.patient.prescription[0]);
		$scope.vm.evalIdoneidad = false;
	}


	function saveIdoneidad(){

		for (var i in $scope.vm.estados){
			$scope.vm.patient.prescription[0][i].notaIdoneidad = $scope.vm.notaIdoneidad;
			$scope.vm.patient.prescription[0][i].I1DatosFarma = $scope.vm.estados[i].i1.value;
			$scope.vm.patient.prescription[0][i].I2DatosFarma = $scope.vm.estados[i].i2.value;
			$scope.vm.patient.prescription[0][i].I3DatosFarma = $scope.vm.estados[i].i3.value;
			$scope.vm.patient.prescription[0][i].I4DatosFarma = $scope.vm.estados[i].i4.value;
			$scope.vm.patient.prescription[0][i].I5DatosFarma = $scope.vm.estados[i].i5.value;
			$scope.vm.patient.prescription[0][i].I6DatosFarma = $scope.vm.estados[i].i6.value;
			$scope.vm.patient.prescription[0][i].I7DatosFarma = $scope.vm.estados[i].i7.value; 
			$scope.vm.patient.prescription[0][i].I8DatosFarma = $scope.vm.estados[i].i8.value;
			if($scope.vm.estados[i].i7.value != 'ok'){
				$scope.vm.interaccionA.medicamentos.push($scope.vm.patient.prescription[0][i])
			}
			if($scope.vm.estados[i].i6.value != 'ok'){
				$scope.vm.interaccionM.medicamentos.push($scope.vm.patient.prescription[0][i])
			}
		}

		profileService.postIdoneidad($scope.vm.patient.prescription[0]).then(postIdoneidadSuccess,error);
	}

		function postIdoneidadSuccess(response){
			if($scope.vm.interaccionM.medicamentos.length >= 2){
				$scope.vm.interaccionM.receta = $scope.vm.patient.prescription[0][0].numeroRecetaDatosFarma;
				$scope.vm.interaccionM.idDC = $scope.vm.patient.clinical.idDatosClinicos;
				var tmpM = angular.copy($scope.vm.interaccionM);
				$scope.vm.interaccionM = angular.copy(interaccionM);
				$scope.vm.interaccionM.medicamentos = new Array();
				profileService.modal_MIA(tmpM);
			}
			else{
				$scope.vm.interaccionM = angular.copy(interaccionM);
				$scope.vm.interaccionM.medicamentos = new Array();
			}

			if($scope.vm.interaccionA.medicamentos.length > 0){
				$scope.vm.interaccionA.receta = $scope.vm.patient.prescription[0][0].numeroRecetaDatosFarma;
				$scope.vm.interaccionA.idDC = $scope.vm.patient.clinical.idDatosClinicos;
				var tmpA = angular.copy($scope.vm.interaccionA);
				$scope.vm.interaccionA = interaccionA;
				$scope.vm.interaccionA.medicamentos = new Array();
				profileService.modal_MIA(tmpA);
			}			
			reloadContent();
			profileService.history($scope.vm.patient.idPaciente).then(historySuccess,error);
			$scope.vm.evalIdoneidad = false;
		}

	function postInteraccion(interaccion){
		//interaccion.receta = $scope.vm.patient.prescription[0][0].numeroRecetaDatosFarma;
		//interaccion.idDC = $scope.vm.patient.clinical.idDatosClinicos;
		//profileService.modal_MIA(interaccion);
		//profileService.postInteraccion(interaccion).then(postInteraccionSuccess,error);
	}
	function postInteraccionSuccess(response){
		console.log(response);
	}

	$scope.closeAlert = function(alerta) {
		profileService.acceptAlert(alerta).then(patientAlertSuccess);		
	}
		
	function userInit(response){
		$scope.labels.loading = false;

		$scope.vm.patient = response;
		if($scope.vm.patient.idEstatusPaciente == 2)
			$scope.labels.noInterno = true;
		reloadContent();
		profileService.history($scope.vm.patient.idPaciente).then(historySuccess,error);
		
	}

	function patientAlert(){
		profileService.patientAlerts().then(patientAlertSuccess,error);
	}

	function patientAlertSuccess(response){
		$scope.vm.alertas.idoneidad = response['2'];
		$scope.vm.alertas.conciliacion = response['1'];
		$scope.vm.alertas.interacciones = warningClassify(response['interaccion']);
		$scope.vm.alertas.vistas = response['all'];
	}

function setIntMed(medTemp){
	var nString = '';
	for(var i in medTemp){
		nString = nString+medTemp[i].nombrePrincipio;
		if(i < medTemp.length)
			nString = nString+', ';
		else
			nString = nString+' ';
	}
    nString = nString+'causaron interacción';
    return nString;
}


    function warningClassify(all){
        var interacciones = [];
        
        for(var i in all){
            if(all[i].tipoInteraccion){
            	var flag = med_med = false;
                for(var j in all[i].medicamentos){
					var tmp = all[i];
					tmp.medicamento = all[i].medicamentos[j];
	                if(all[i].categorizacionInteraccion == 1)
	                    tmp.grado = 'menor';
	                if(all[i].categorizacionInteraccion == 2)
	                    tmp.grado= 'moderada';
	                if(all[i].categorizacionInteraccion == 3)
	                    tmp.grado= 'mayor';
	                if(all[i].tipoInteraccion == 1){
	                    tmp.tipo = 'Alim. - Med.';
	                    tmp.descripcion = ''+tmp.medicamento.nombrePrincipio+' y '+tmp.alimentoInteraccion+' causaron interacción';
	                    interacciones.push(tmp)
	                }
	                else{
	                	if(med_med == false){
	                		med_med = true;
	                		tmp.tipo = 'Med. - Med.';
	                   		tmp.descripcion = setIntMed(all[i].medicamentos);
	                    	interacciones.push(tmp)
	                	}
	                }
                }
            }
        }
        return interacciones;
    }




	function historySuccess(response){
		$scope.vm.patient.history = response;
		reloadContent();
	}

	function nullString(object){

		if(object == null || undefined)
			return 'no definido';
		else
			return object;
	}



	function setOpen(thisOne){
		$scope.vm.history = thisOne;

	}

	function getCurrentEvaluation(){
		return profileService.stringEvaluacion($scope.vm.patient.prescription[0]);
	}


	function reloadContent(){
		if($scope.vm.patient.prescription != null){
			$scope.labels.currentEvaluation = getCurrentEvaluation();
			if($scope.labels.currentEvaluation.value != 0)
				$scope.vm.evaluar($scope.vm.patient.prescription[0]);

			profileService.patientAlerts().then(patientAlertSuccess,error);
		}
	}


	function testHistorial(){
		profileService.getHistory();
	}

	//testHistorial();

	function reingreso() {
		profileService.reingreso($scope.vm.patient.idPaciente);
	}
	
	function nuevaConciliacion() {
		profileService.nuevaConciliacion($scope.vm.patient.idPaciente);
	}
	
	function egreso() {
		profileService.egreso($scope.vm.patient.idPaciente);
	}
	
	function traslado() {
		profileService.traslado($scope.vm.patient.idPaciente);
	}


    function printReceta(){
    	profileService.reportTest($scope.vm.patient.idPaciente,1);
        //profileService.printPrescription();

    }

    function printHistorial(){
        profileService.printHistory();
    }

    function reportTest(){
    	profileService.reportTest($scope.vm.patient.idPaciente,2);
    }



        function editCD(data){
        	console.log(data)
        	profileService.editCD(data.clinical);
        }
        function editPD(data){
        	profileService.editPD(data)
        }



	function error(response){
		$scope.labels.loading = true;
		$scope.labels.loadError = "Error al cargar los datos";
		console.log(response);
	}



}
