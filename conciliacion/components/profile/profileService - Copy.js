angular
    .module('app')
    .service('profileService',profileService);
        

profileService.$inject = ['$http','$route','$location','$q','tracker','homeService', '$location', 'config','idoneidad','historico','$uibModal','alerts'];

function profileService($http,$route,$location,$q,tracker,homeService, $location, config,idoneidad,historico,$uibModal,alerts){
    var url;
    var user = {};
    var report = {};
	var promise;
	var service = {
        find: find,
        history:history,
        //alergies:alergies,
        ready: ready,
        move: move,
        traslado:traslado,
        reingreso:reingreso,
        egreso:egreso,
        nuevaConciliacion:nuevaConciliacion,
        getState:getState,
        evaluar:evaluar,
        change:change,
        patientAlerts:patientAlerts,
        postIdoneidad:postIdoneidad,
        postInteraccion:postInteraccion,
        stringEvaluacion:stringEvaluacion,
        getHistory:getHistory,
        modal_MIA:modal_MIA,
        acceptAlert:acceptAlert,
        getTipoPrescripcion:tipoPrescripcion,
        printPrescription:printPrescription,
        printHistory:printHistory,
        idoneidadString:idoneidadString,
        reportTest:reportTest,
        modal_RAM:modal_RAM,
        editCD:editCD,
        editPD:editPD
    };

    return service;

    function move(route, track) {
        console.log('error, hay que des habilitr esta')
    	tracker.set('prescription', track);
    	$location.path(route);
    }
	
	function acceptAlert(alerta) {
		return alerts.accept(alerta).then(patientAlerts);
	}    

    function patientAlerts(){    	
        var aux = tracker.findPatient2();
        return $http.get(url+'warnings/'+aux.patient).then(alertSuccess,getError);
    }

    function alertSuccess(response){
        var tmpAlertas = response.data.body['alertas'];
       // tmpAlertas.interacciones 
        //console.log(response.data.body['alertas'])
        return tmpAlertas;
    }

    function postIdoneidad(reseta){
        return idoneidad.postIdoneidad(reseta).then(postIdoneidadSuccess,getError);
    }
        function postIdoneidadSuccess(response){
            return response;
        }

    function postInteraccion(reseta){
        console.log(reseta)
        return idoneidad.postInteraccion(reseta).then(postInteraccionSuccess,getError);
    }
        function postInteraccionSuccess(response){
           // return reloadContent();
            return true;
        }


    function find(){
        url = config.ready();
        if (url == undefined)
        {
            $location.path("/login");                
        }else{
        	alerts.ready();
            var aux = tracker.findPatient2();
            if(aux.patient == false)
                $location.path("/home");
            return $http.get(url+'patient/'+aux.patient).then(findSuccess,getError);
        }
    }

        function findSuccess(response){  
             	user = response.data.body.patient;
        		return $http.get(url+'patient/'+user.idPaciente+'/lastClinicalData').then(patientSuccess,getError);//homeService.preview(user).then(alergies,getError);        	
        }
            function patientSuccess(response){
                user.clinical = response.data.body['clinical data'];
                return $http.get(url+'patient/'+user.clinical.idPaciente+'/lastPrescription/').then(clinicalSuccess,getError);
            }
                function clinicalSuccess(response){
                    user.prescription = response.data.body['ultimaPrescripcion'];
                    return $http.get(url+'alergy/'+user.idPaciente).then(alergiesSuccess,getError);
                }
                    function alergiesSuccess(response){
                        user.alergy = response.data.body['alergies'];
                        if(user.prescription != null)
                            user.prescription[0] = tipoNames(user.prescription[0]);                            
                        else
                            user.prescription = false
                        return user;
                    }


    function getState(state){
        return idoneidad.option(state);
    }

    function change(thisOne){
        return idoneidad.nextState(thisOne);
    }


    function evaluar(receta){
        return idoneidad.evaluar(receta);
    }


    function history(id){
        return $http.get(url+'patient/'+id+'/historyReport').then(historySuccess,getError);
    }

        function historySuccess(response){
            var tmpHistory = [];
            var temp = response.data.body['medicamentos'];

            for(var i in temp){
                var element = {}

                element.interacciones = angular.copy(temp[i].interacciones);
                element.idoneidades = angular.copy(temp[i].idoneidades);
                delete(temp[i].idoneidades)
                delete(temp[i].interacciones)
                element.medicamentos =  tipoPrescripcion(temp[i]);
                element.idoneidades = idoneidadString(element.idoneidades,element.medicamentos);
                tmpHistory.push(element);
            }
            return tmpHistory;
        }

    function getError(data){
        return data;
    	console.log('Error in profileService');
    }

    function ready(){

    }

    function stringEvaluacion(receta){
            return idoneidad.stringEvaluacion(receta);
    }
	
	function tipoPrescripcion(receta){
		var TipoPrescripcion = [{idTipoPrescripcion:1, nombreTipoPrescripcion:'Primera Conciliación'},{idTipoPrescripcion:2, nombreTipoPrescripcion:'Nueva Conciliación'},{idTipoPrescripcion:3, nombreTipoPrescripcion:'Traslado de Área'},{idTipoPrescripcion:4, nombreTipoPrescripcion:'Egreso de Paciente'},{idTipoPrescripcion:5, nombreTipoPrescripcion:'Reingreso de Paciente'}];		
		for (var i in receta)
		    for (var j in TipoPrescripcion)
		        if(receta[i].idTipoPrescripcion == TipoPrescripcion[j].idTipoPrescripcion)
		            receta[i].idTipoPrescripcion = TipoPrescripcion[j].nombreTipoPrescripcion;
		return receta;
	}

    function tipoNames(receta){
        var TipoMedicamento = [{idTipoMedicamento:1, nombreTipoMedicamento:'AH'},{idTipoMedicamento:2, nombreTipoMedicamento:'ARH'},{idTipoMedicamento:3, nombreTipoMedicamento:'CH'}, {idTipoMedicamento:4, nombreTipoMedicamento:'CR'}, {idTipoMedicamento:5, nombreTipoMedicamento:'H'}, {idTipoMedicamento:6, nombreTipoMedicamento:'QX'}];
        for (var i in receta)
            for (var j in TipoMedicamento)
                if(receta[i].idTipoMedicamento == TipoMedicamento[j].idTipoMedicamento)
                    receta[i].idTipoMedicamento = TipoMedicamento[j].nombreTipoMedicamento;
        return receta;
    }


    function getHistory(){
        var aux = tracker.findPatient2();
        historico.find(aux.patient);
    }

    function reingreso(patientID) {
        tracker.reIngreso(patientID);
    }
    
    function nuevaConciliacion(patientID) {
        tracker.nuevaConciliacion(patientID);
    }
    
    function egreso(patientID) {
        tracker.egreso(patientID);
    }
    
    function traslado(patientID) {
        tracker.traslado(patientID);
    }

    function printPrescription(){
            var aux = tracker.findPatient2();
            $http.get(url+"patient/"+aux.patient+"/firstConciliation/").then(hospitalData,getError);
    }


        function hospitalData(response){
            report = response.data.body['primera conciliacion'];
            var aux = tracker.findPatient2();
            $http.get(url+'patient/'+aux.patient+'/historyReport').then(ReportReady,getError);

        }

            function ReportReady(response){
                console.log(response)
                var tmpArr = [];
                tmpArr.push(response.data.body['medicamentos'][0])
                report.medicamentos = tmpArr;
                report.tipoReporte = 1;
                report.hospital = "";
                console.log(report)
                /*Para que jale necesito los datos del hospital asi oj.hospital = array()*/
                $http.post("pdf/PDFCreator.php",{"sentencia":report}).then(reportModal,getError);
            }


    function printHistory(){
            var aux = tracker.findPatient2();
            $http.get(url+"patient/"+aux.patient+"/firstConciliation/").then(historyData,getError);
    }

        function historyData(response){
                report = response.data.body['primera conciliacion'];
                report.hospital = "";
                var aux = tracker.findPatient2();
                $http.get(url+'patient/'+aux.patient+'/historyReport').then(historyReady,getError);        
        }
            function historyReady(response){
                console.log(response);
                report.medicamentos = response.data.body['medicamentos'];
                $http.post("pdf/doc/Historico.php",{"sentencia":report}).then(reportModal,getError);
            }


    function reportTest(id,tipo){
        report.tipo=tipo;
        report.hospital = "";
        $http.get(url+'patient/'+id+'/historyReport').then(reportTest2,getError);
       // 
    }
        function reportTest2(response){
            var tmpMed = [];
            var temp = response.data.body['medicamentos'];
            for(var i in temp){
                var element = angular.copy(temp[i]);
                element.idoneidades = idoneidadString(temp[i].idoneidades,temp[i].medicamentos);
                element.interacciones = idoneidad.clasifyInteraction(temp[i].interacciones);
                tmpMed.push(element);
            }

            var object = response.data.body;
            object.medicamentos = tmpMed;
            //object.hospital=report.hospital;
            /*Cambia esta linea no estoy seguro si deberia ir aqui*/ 
            object.tipoReporte = report.tipo;
            $http.post("pdf/PDFCreator.php",{"sentencia":object}).then(reportModal,getError);
        }



/*
            var tmpHistory = [];
            var temp = response.data.body['medicamentos'];

            for(var i in temp){
                var element = {}

                element.interacciones = angular.copy(temp[i].interacciones);
                element.idoneidades = angular.copy(temp[i].idoneidades);
                delete(temp[i].idoneidades)
                delete(temp[i].interacciones)
                element.medicamentos =  tipoPrescripcion(temp[i]);
                element.idoneidades = idoneidadString(element.idoneidades,element.medicamentos);
                tmpHistory.push(element);
            }
            return tmpHistory;


*/







    function reportModal(doc){
        var modal = $uibModal.open({
            animation:true,
            backdrop:'static',
            templateUrl:'conciliacion/templates/modal_Reporte.html',
            controller:'reporteController',
            size:'lg',
            keyboard:false,
            resolve:{
                doc: function(){ 
                    return doc.data;
                }
            }
        });        
    }
    
    function modal_RAM(receta) {
    	var modal = $uibModal.open({
    		animation:true,
    		backdrop:'static',
    	    templateUrl: 'conciliacion/templates/createRam.html',
    	    controller: createRamCtrl,
    	    size: 'md',
    	    keyboard:false,
    	    resolve: {
    	        object: function () {
    	        	return receta;
    	 		}
    	      }              
    		});
    	
    	function createRamCtrl($scope,$uibModalInstance,object) 
    	{
    		$scope.a="";
    		$scope.meds = object;
    		$scope.listaElementos = [];
    		$scope.k = config.returnCookies().key;
    		
    		$scope.add = function(med)
    		{
    			delete($scope.a);
    			$scope.listaElementos.push(med);
    		};
    		
    		$scope.deleteElement = function() 
    		{
    			$scope.listaElementos = [];					
    		};
    		
    		$scope.cancel = function() 
    		{
    			$uibModalInstance.close();
    		};												
    	};
    }

    function modal_MIA(info){
        var modal = $uibModal.open({
            animation:true,
            backdrop:'static',
            templateUrl:'conciliacion/templates/modal_MIA.html',
            controller:'MIAController',
            size:'md',
            keyboard:false,
            resolve:{
                info: function(){ 
                    return info;
                }
            }
        });


        modal.result.then(function(response ){
            postInteraccion(response);
        });

    }

    function idoneidadString(array){
        var newLayout = [];
        for(var i in array){
            var tmp = idoneidad.textForState(array[i])
            newLayout.push(tmp);
        }
        return newLayout;
    }

    function intHistObjt(those){
        var ints = [];
            for(var i in those){

            }
    }

        function editCD(tmp){
            var object = angular.copy(tmp);
            var modal = $uibModal.open({
                animation:true,
                templateUrl:'conciliacion/templates/editCD.html',
                controller:editCdCtrl,
                size:'md',
                keyboard:false,
                resolve:{
                    object: function(){ 
                        return object;
                    }
                }
            });


            modal.result.then(function(response ){
                if(response)
                    putCD(response);
            });

        }

        function editCdCtrl($scope,$uibModalInstance,$location,object, $filter) {
                var aux1 = String(object.ingresoDatosClinicos),
                aux = aux1.search(" "), 
                fecha = aux1.slice(0, aux); 
                $scope.paciente = object;
                $scope.paciente.identificadorPacienteDatosClinicos = (object.identificadorPacienteDatosClinicos != undefined) ? object.identificadorPacienteDatosClinicos : undefined;
                $scope.paciente.ingresoDatosClinicos = fecha;
                $scope.paciente.tallaDatosClinicos = (object.tallaDatosClinicos != undefined) ? parseInt(object.tallaDatosClinicos) : undefined;
                $scope.paciente.pesoDatosClinicos = (object.pesoDatosClinicos != undefined) ? parseInt(object.pesoDatosClinicos) : undefined;
                $scope.paciente.camaDatosClinicos = (object.camaDatosClinicos != undefined) ? object.camaDatosClinicos : undefined;
                    //Global.getServicios().then(function(data) {
                   //     $scope.Servicios = data;
                  //      for (var i=0; i<data.length; i++)
                 //           if (object.idServicio == data[i].idServicio)
                  //          {
                  //              $scope.paciente.Servicio = data[i];
                   //             break;
                     //       }
                    //    });


              $scope.ok = function(){
                   $uibModalInstance.close($scope.paciente);
                }   

              $scope.cancel = function () {
                $uibModalInstance.close();
              };            
        };


        function putCD(user){   
            var data = {
                    pesoDatosClinicos: (user.pesoDatosClinicos != undefined) ? user.pesoDatosClinicos : undefined,
                    tallaDatosClinicos: (user.tallaDatosClinicos != undefined) ? user.tallaDatosClinicos : undefined,
                    identificadorPacienteDatosClinicos: (user.identificadorPacienteDatosClinicos != undefined) ? user.identificadorPacienteDatosClinicos: undefined,
                    camaDatosClinicos: (user.camaDatosClinicos != undefined) ? user.camaDatosClinicos : undefined,
                    motivoDatosClinicos: (user.motivoDatosClinicos != undefined) ? user.motivoDatosClinicos : undefined,
                    diagnosticoDatosClinicos: (user.diagnosticoDatosClinicos != undefined) ? user.diagnosticoDatosClinicos : undefined,
                    doctorDatosClinicos: (user.doctorDatosClinicos != undefined) ? user.doctorDatosClinicos : undefined,            
                    observacionDatosClinicos: (user.observacionDatosClinicos != undefined) ? user.observacionDatosClinicos : undefined
                }  
                console.log(data) 
                $http.put(url+"clinicalData/"+user.idDatosClinicos,{data:data}).then(function(response){
                    console.log(response)
                    $route.reload(); 
                })
                
        }


        function editPD(tmp){
            var object = angular.copy(tmp);
            var modal = $uibModal.open({
                animation:true,
                templateUrl:'conciliacion/templates/editPD.html',
                controller:editPDCtrl,
                size:'md',
                keyboard:false,
                resolve:{
                    object: function(){ 
                        return object;
                    }
                }
            });


            modal.result.then(function(response ){
                if(response)
                    putPD(response);
            });
        }      
        function editPDCtrl($scope,$uibModalInstance,$location,object, $filter) {
                $scope.paciente = object;
                $scope.paciente.nombrePaciente = (object.nombrePaciente != undefined) ? object.nombrePaciente: undefined;
                $scope.paciente.nacimientoPaciente = object.nacimientoPaciente;
                $scope.paciente.apellidoPaternoPaciente = (object.apellidoPaternoPaciente != undefined) ? object.apellidoPaternoPaciente : undefined;
                $scope.paciente.apellidoMaternoPaciente = (object.apellidoMaternoPaciente != undefined) ? object.apellidoMaternoPaciente : undefined;
                $scope.paciente.sexoPaciente = (object.sexoPaciente != undefined) ? object.sexoPaciente : undefined;
                  //  Global.getServicios().then(function(data){
                     //   $scope.Servicios = data;
                   // });

              $scope.ok = function(){

                $uibModalInstance.close( $scope.paciente);
                //Global.updatePD($scope.paciente).then(function(respuesta){
                  //  $modalInstance.close(respuesta);
               // });
              };

              $scope.cancel = function () {
                $uibModalInstance.close();
              };            
        }


        function putPD(user){
                console.log(user);
                var data = {
                        nombrePaciente:user.nombrePaciente,
                        apellidoPaternoPaciente:user.apellidoPaternoPaciente,
                        apellidoMaternoPaciente:user.apellidoMaternoPaciente, 
                        nacimientoPaciente:user.nacimientoPaciente, 
                        sexoPaciente:user.sexoPaciente
                    } 
                console.log(data)
                $http.put(url+"patient/"+user.idPaciente).then(function(response){
                    console.log(response);
                    $route.reload();
                });
        }
         



}



