angular
    .module('app')
    .service('chart',d3Service);

d3Service.$inject = ['$http','grapic'];

function d3Service($http, grapic) {
  var graficas = {};
  var data;
  var chart ;



  var configuracion = {};
	var service = {
		ready:ready,
		ready2:ready2,
		imprimir:imprimir
	}
	return service;
	

	function ready(){
    	return grapic.predefined(1).then(secondChart,error);
	}
	function secondChart(response){
		crea(response,'chart1');
		graficas.primera = response;
	 	return grapic.predefined(2).then(thirdChart,error);
	}

	function thirdChart(response){
		crea(response,'chart2');
	  	graficas.segunda = response;
	  	return grapic.predefined(3).then(fourthChart,error);		
	}
	
	function fourthChart(response){
		crea(response,'chart3');
	  	graficas.tercera = response;
	  	return grapic.predefined(4).then(fifthChart,error);		
	}
	
	function fifthChart(response){
		crea(response,'chart4');
	  	graficas.cuarta = response;
	  	return grapic.predefined(5).then(sixthChart,error);		
	}
	
	function sixthChart(response){
		crea(response,'chart5');
	  	graficas.quinta = response;
	  	return grapic.predefined(6).then(seventhChart,error);		
	}
	
	function seventhChart(response){
		crea(response,'chart6');
	  	graficas.sexta = response;
	  	return grapic.predefined(7).then(finalChart,error);		
	}
	
	function finalChart(response){
		crea(response,'chart7');
		graficas.septima = response;
		return graficas;
	}
	

function ready2(objeto, fechas){
		configuracion = objeto.titulo;
    	return grapic.predefined(objeto.id, fechas).then(testChart,error);
	}

  function testChart(those){
  	var tmpThose = angular.copy(those),
  	grafica = {data:{dataset:[]}, options:{}},
	keyName = Object.keys(those['0']),
  	lnombre;
  	if(keyName['0']=='total')
  		lnombre = keyName['1']
  	else
  		lnombre = keyName['0']
  	angular.forEach(tmpThose,function(variable,key){
  			variable.nombre = variable[lnombre];
  	});
      var count = 1;
      for(var key in tmpThose)
      {
        grafica.data.dataset.push({x: String(count), total: parseInt(tmpThose[key].total)});
        count++;
      }

     grafica.options = {
     	series:[{
     		label: configuracion,
     		axis: "y",
     		dataset: "dataset",
     		key: "total",
     		color: '#1f77b4',
     		type: ['column'],
     		id: 'mySeries0'
     	}],
     	axes: {	x: {key: "x"}}
      };
      grafica.tabla = tmpThose;      
      return grafica;
  }




function imprimir(){
	//var divTest = document.getElementById('test');
	var uri = chart.getImageURI();
		//console.log(uri)
		window.open(uri);
      return chart.getImageURI(uri);
}








  function crea(those,nameTable){
  	//console.log(nameTable)
      var array = [
      [{label:'Desviacion',id:'Desviacion'},
      {label:'Promedio',id:'Promedio',type:'number'}]];
      var count = 1;
      //console.log(those)
  //    for(var i = 0; i < those.length ; i++){
  	  var tempData = those;
  	  //console.log(tempData)
      for(var key in tempData)
      {
        var value = [String(count),tempData[key].total];
        array.push(value);
        count++;
      }
	 //   }
     // console.log(array)

      var options = {
        width: 500,
        height: 250,
        legend: { position: 'top', maxLines: 3 }
      };
      
  }



  function error(response){

  }


}