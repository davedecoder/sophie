angular
    .module('app')
    .service('historico',historicoService);
        

historicoService.$inject = ['$http','$cookies','config'];
	

function historicoService($http,$cookies,config){
    var url = config.readyService();
    var TipoPrescripcion = [{idTipoPrescripcion:1, nombreTipoPrescripcion:'Primera Conciliación'},{idTipoPrescripcion:2, nombreTipoPrescripcion:'Nueva Conciliación'},{idTipoPrescripcion:3, nombreTipoPrescripcion:'Traslado de Área'},{idTipoPrescripcion:4, nombreTipoPrescripcion:'Egreso de Paciente'},{idTipoPrescripcion:5, nombreTipoPrescripcion:'Reingreso de Paciente'}];
    var TipoMedicamento = [{idTipoMedicamento:1, nombreTipoMedicamento:'AH'},{idTipoMedicamento:2, nombreTipoMedicamento:'ARH'},{idTipoMedicamento:3, nombreTipoMedicamento:'CH'}, {idTipoMedicamento:4, nombreTipoMedicamento:'CR'}, {idTipoMedicamento:5, nombreTipoMedicamento:'H'}, {idTipoMedicamento:6, nombreTipoMedicamento:'QX'}];

    var patient= {
        prescriptions:[],
        alerts:[],
        data:{}
    };
	var service = {
        start: start,
        find:find,
        todos:todos
    };

    return service;
	
    function start(){
       // url = config.ready();
    }

    function find(thisDude){
        patient.id = thisDude;
        $http.get(url+'patient/'+thisDude+'/historyReport').then(historyDude,getError);
    }

        function historyDude(response){
            patient.prescriptions = response.data.body['medicamentos'];
            patient.prescriptions = tiposPrescripcion(patient.prescriptions);
            patient.prescriptions = tiposMedicamentos(patient.prescriptions);
            $http.get(url+'warnings/'+patient.id).then(alertSuccess,getError);
            
        }

        function alertSuccess(response){
            patient.alerts = response.data.body['alertas'];
            console.log(patient);
        }




        function mergeHistory(){
            
        }

        function tiposPrescripcion(receta){
            for (var i in receta)
                for(var k in receta[i])
                    for (var j in TipoPrescripcion)
                        if(receta[i][k].idTipoPrescripcion == TipoPrescripcion[j].idTipoPrescripcion)
                            receta[i][k].idTipoPrescripcion = TipoPrescripcion[j].nombreTipoPrescripcion;
            return receta;            
        }

        function tiposMedicamentos(receta){
            for (var i in receta)
                for(var k in receta[i])
                    for (var j in TipoMedicamento)
                        if(receta[i][k].idTipoMedicamento == TipoMedicamento[j].idTipoMedicamento)
                            receta[i][k].idTipoMedicamento = TipoMedicamento[j].nombreTipoMedicamento;
            return receta;
        }

    function todos(){

    }




    function getError(response){
        console.log('error en historial')
        console.log(response)
    }

}