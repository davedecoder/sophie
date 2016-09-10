angular
    .module('app')
    .service('grapic',graphicsService);

graphicsService.$inject = ['$http', 'config'];

function graphicsService($http, config) {
	var url = config.ready();
	var data = {};


	var respuesta = {};
	var service = {
		predefined : predefinedGraphics
	}
	return service;
	
	function error(response) {
		console.log("error");
		console.log(response);
	}
	
	function predefinedWithoutOperation(response){
		var aux = response.data.body.estadisticas[0]
		if (aux[0] != undefined)
			for (var i in aux)
			{
				aux[i].total = aux[i].resultado.total;
				delete aux[i].resultado;
			}
		else
			aux = aux.resultado.resultado;
		return aux;
	}

	
	function predefinedAge(response) {
		if (response.data.body.estadisticas[0].resultado.resultado.total != undefined)	
		{
			var aux = {
				total: response.data.body.estadisticas[0].resultado.resultado.total,
				edad: response.data.body.estadisticas[0].resultado.edad
			};
			if (respuesta.edad == undefined)
				respuesta.edad = [aux];
			else
				respuesta.edad.push(aux);
			if (respuesta.edad.length < 3)
			{
				switch (respuesta.edad.length) {
					case 1: data.edad = [16,65];
							break;
					case 2: data.edad = [66,200];
							break;
				}
				return $http.post(url+"statistics/", data).then(predefinedAge, error);	
			}
		}
		return respuesta.edad;
	}
	
	function predefinedDesviations(response) {
		if (response.data.body.estadisticas[0].resultado.resultado.TotalI1 != undefined)	
		{
			if (respuesta.aux == undefined)
			{
				respuesta.aux = response.data.body.estadisticas[0].resultado.resultado;
				data.propiedad = 2;
				return $http.post(url+"statistics/", data).then(predefinedDesviations, error);	
			}else
			{
				respuesta.propiedades = {};
				var total = 0, aux = response.data.body.estadisticas[0].resultado.resultado;
				total = parseInt(respuesta.aux.TotalI1) + parseInt(respuesta.aux.TotalI2) + parseInt(respuesta.aux.TotalI3) + parseInt(respuesta.aux.TotalI4) + parseInt(respuesta.aux.TotalI5) + parseInt(respuesta.aux.TotalI6) + parseInt(respuesta.aux.TotalI7) + parseInt(respuesta.aux.TotalI8);
				total +=  parseInt(aux.TotalI1) + parseInt(aux.TotalI2) + parseInt(aux.TotalI3) + parseInt(aux.TotalI4) + parseInt(aux.TotalI5) + parseInt(aux.TotalI6) + parseInt(aux.TotalI7) + parseInt(aux.TotalI8);
				respuesta.propiedades.TotalI1 = ((parseInt(respuesta.aux.TotalI1) + parseInt(aux.TotalI1)) * 100) / total; 
				respuesta.propiedades.TotalI2 = ((parseInt(respuesta.aux.TotalI2) + parseInt(aux.TotalI2)) * 100) / total; 
				respuesta.propiedades.TotalI3 = ((parseInt(respuesta.aux.TotalI3) + parseInt(aux.TotalI3)) * 100) / total; 
				respuesta.propiedades.TotalI4 = ((parseInt(respuesta.aux.TotalI4) + parseInt(aux.TotalI4)) * 100) / total; 
				respuesta.propiedades.TotalI5 = ((parseInt(respuesta.aux.TotalI5) + parseInt(aux.TotalI5)) * 100) / total; 
				respuesta.propiedades.TotalI6 = ((parseInt(respuesta.aux.TotalI6) + parseInt(aux.TotalI6)) * 100) / total; 
				respuesta.propiedades.TotalI7 = ((parseInt(respuesta.aux.TotalI6) + parseInt(aux.TotalI7)) * 100) / total; 
				respuesta.propiedades.TotalI8 = ((parseInt(respuesta.aux.TotalI7) + parseInt(aux.TotalI8)) * 100) / total; 
				delete respuesta.aux;
				respuesta.total = total;
				var aux = [
					{Propiedad:'Indicado', total: respuesta.propiedades.TotalI1 },
					{Propiedad:'Duplicado', total: respuesta.propiedades.TotalI2 },
					{Propiedad:'Adecuado a patología', total: respuesta.propiedades.TotalI3 },
					{Propiedad:'Adecuado a peso, talla y edad', total: respuesta.propiedades.TotalI4 },
					{Propiedad:'Alérgico', total: respuesta.propiedades.TotalI5 },
					{Propiedad:'Interacción Alimento - Medicamento', total: respuesta.propiedades.TotalI6 },
					{Propiedad:'Interacción Medicamento - Medicamento', total: respuesta.propiedades.TotalI7 },
					{Propiedad:'Insuficiencia renal o hepática', total: respuesta.propiedades.TotalI8 }
				];				
				return aux;	
			}
		}		
	}
	
	function predefinedWeight(response) {
		if (response.data.body.estadisticas[0].resultado.resultado.total != undefined)	
		{
			var aux = {
				total: response.data.body.estadisticas[0].resultado.resultado.total,
				peso: response.data.body.estadisticas[0].resultado.peso
			};
			if (respuesta.peso == undefined)
			{
				respuesta.peso = [aux];
				data.peso = [18.5,24.9];
				return $http.post(url+"statistics/", data).then(predefinedWeight, error);	
			}
			else
			{
				switch (respuesta.peso.length) {					
					case 1: respuesta.peso.push(aux);
							data.peso = [25,29.9];
							return $http.post(url+"statistics/", data).then(predefinedWeight, error);	
					case 2: respuesta.peso.push(aux);
							data.peso = [30,10000];
							return $http.post(url+"statistics/", data).then(predefinedWeight, error);	
					case 3: respuesta.peso.push(aux);					
							return respuesta.peso;	
				}
			}
		}
	}
	
	function predefinedGraphics(option, fecha) {
		data = {};
		data.fecha = [];
		if (fecha != undefined)
		{
			var aux =fecha.inicio,			
			anio = aux.getFullYear(),
			mes = ((1 + aux.getMonth()).toString().length == 1) ? "0"+(1+aux.getMonth()): 1 + aux.getMonth(),
			dia = (aux.getDate().toString().length == 1) ? "0"+aux.getDate() : aux.getDate();	
			if (fecha.inicio == fecha.final)	
				data.fecha[0] = anio + "-" + mes + "-" + dia;
			else
			{
				data.fecha[0] = anio + "-" + mes + "-" + dia;
				var aux2 = fecha.final,
				anio2 = aux2.getFullYear(),
				mes2 = ((1 + aux2.getMonth()).toString().length == 1) ? "0"+(1+aux2.getMonth()): 1 + aux2.getMonth(),
				dia2 = (aux2.getDate().toString().length == 1) ? "0"+aux2.getDate() : aux2.getDate();		
				data.fecha[1] = anio2 + "-" + mes2 + "-" + dia2;
			}
		}
		else
		{
			var aux = new Date();
			anio = aux.getFullYear(),
			mes = ((1 + aux.getMonth()).toString().length == 1) ? "0"+(1+aux.getMonth()): 1 + aux.getMonth(),
			dia = (aux.getDate().toString().length == 1) ? "0"+aux.getDate() : aux.getDate();	
			data.fecha[0] = anio + "-" + mes + "-" + "01";
			if (dia > 1)
				data.fecha[1] = anio + "-" + mes + "-" + dia;
		}
		switch (option) {		

			//Grafica por sexo
			case 1: data.tipo = 3;										
					data.opSexo = 1;					
					return $http.post(url+"statistics/", data).then(predefinedWithoutOperation, error);
			
			//Grafica por áreas de atencion
			case 2: data.tipo = 3;
					data.opArea = 1;					
					return $http.post(url+"statistics/", data).then(predefinedWithoutOperation, error);	

			//Grafica por edad promedio					
			case 3: data.tipo = 3;
					data.edad = [1,15];					
					return $http.post(url+"statistics/", data).then(predefinedAge, error);	
									
			//Grafica por desviaciones en la valoración
			case 4: data.tipo = 1;
					data.propiedad = 1;					
					return $http.post(url+"statistics/", data).then(predefinedDesviations, error);
			
			//Grafica por peso 
			case 5: data.tipo = 3;
					data.peso = [1,18.49];					
					return $http.post(url+"statistics/", data).then(predefinedWeight, error);
					
			//Grafica por tipo de medicamento
			case 6: data.tipo = 2;
					data.opTipoMedicamento = 1;					
					return $http.post(url+"statistics/", data).then(predefinedWithoutOperation, error);	
			
			//Grafica medicamentos con incidencia
			case 7: data.tipo = 1;
					data.incidencia = 1;					
					return $http.post(url+"statistics/", data).then(predefinedWithoutOperation, error);	
		}
	}


	function chart_test(){

	}
}