angular
    .module('app')
    .controller('homeController',homeController);

homeController.$inject = ['$scope','homeService','navbarService'];

function homeController($scope,homeService,navbarService){
	$scope.nav = navbarService;
	$scope.nav.usuario = navbarService.getUsers();
	$scope.vm = {
		pacientes : [],
		paciente : [],
		alertas:[],
		//preview:preview,
		toProfile:toProfile,
		grafica:grafica,
		getAlerts:getAlerts,
		altas:[],
		newPaciente:newPaciente,
		imprimir:imprimir,
		graficar: render
	};
	var aux = new Date();
	var opcionHelper;
	$scope.fecha ={		
		final: aux,
		inicio: new Date(aux.getFullYear(),aux.getMonth(),1 ),
		options:{
				    formatYear: 'yy',
				    startingDay: 1
			    }
	} 

	$scope.opcionesGraficas = [{
		id:1,
		titulo:'Pacientes por genero',
		styleColor:'well-graficas'
	},{
		id:2,
		titulo:'Pacientes por area de atención',
		styleColor:'well-graficas'
	},{
		id:3,
		titulo:'Pacientes por rango de edad',
		styleColor:'well-graficas'
	},{
		id:4,
		titulo:'Desviaciones en valoración de idoneidad',
		styleColor:'well-graficas'
	},/*{
		id:5,
		titulo:'Peso promedio',
		styleColor:'well-graficas'
	},*/{
		id:6,
		titulo:'Tipos de medicamento',
		styleColor:'well-graficas'
	},{
		id:7,
		titulo:'Principios con mas incidencias',
		styleColor:'well-graficas'
	}];
	$scope.verTodo = false;
	$scope.estadisticas;

	homeService.ready().then(homeReady,error);

	function newPaciente() {
		homeService.newPaciente();
	}

//READY
	function homeReady(patients){
			$scope.vm.pacientes = angular.fromJson(patients.hospitalizados);
			$scope.vm.altas = angular.fromJson(patients.altas);
			//grafica();
	}

// alerts\
	function getAlerts(){
		homeService.alerts().then(alertsReady,error);
	} 


//alertsReady
	function alertsReady(response){
		var tmp = {};

		//tmp.conciliacion = response 
		$scope.vm.alertas = response;
		//chart.ready();
	}
/* PREVIEW
	function preview(paciente){
		homeService.preview(paciente).then(previewSuccess,error);
		$scope.vm.paciente = paciente;
	}

		function previewSuccess(response){
			$scope.vm.paciente = response;
		}
	*/

	function activateButton(button){
		angular.forEach($scope.opcionesGraficas,function(variable,key){
			if(variable.id == button.id)
				variable.styleColor = 'well-active';
			else 
				variable.styleColor = 'well-graficas'
		});
	}


	function grafica(opt){
		opcionHelper = opt;
		activateButton(opt);
		$scope.estadisticas = {
			render:true
		}
		$scope.chart = {};
		$scope.verTodo = false;
	}
	
	function render() {
		homeService.grafica(opcionHelper,$scope.fecha).then(renderTables,error);
	}



	function renderTables(response){
		$scope.estadisticas = response.tabla;
		$scope.estadisticas.render = true;
		$scope.verTodo = true;
		$scope.chart = response;
		console.log(response);
		//console.log($scope.estadisticas)
	}

	function imprimir(){
		return homeService.imprimir();
	}


/*

	function altas(){
		$scope.vm.altas = homeService.altas();
	}
*/
	function toProfile(idd) {
		var tmp = {
			idPaciente:idd
		}
		homeService.moveToProfile(tmp);			
	}

	function error(data){
		console.log(data);
	}



	}

