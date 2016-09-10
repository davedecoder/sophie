angular
    .module('app')
    .service('idoneidad',idoneidadService);
        

idoneidadService.$inject = ['$http','$location','$q','$cookies','config'];
	

function idoneidadService($http,$location,$q,$cookies,config){
	var	url = config.readyService();
    var estados = {
        actual:{},
        nuevo:{}
    }
    var ResponseIdoneidades = [];
    var responses = {};
    responses.xState = 
        {
            value:'x',
            mensaje:'generic',
            state:"btn-danger",
            icon:'fa fa-close'  
        };

    responses.okState = {
            value:'ok',
            mensaje:'generic',
            state:"btn-success",
            icon:'fa fa-check '        
    };

    responses.ifState = {
            value:'if',
            mensaje:'generic',
            state:"btn-warning ",
            icon:'fa fa-eye'        
    };


var txtIdoneidades =  {
    1:{
        'if':'indicado bajo monitorización',
        'x':'no indicado'
    },
    2:{
        'if':'duplicidad bajo monitorización',
        'x':'riesgo de duplicidad'
    },
    3:{
        'if':'adecuado a patología bajo monitorización',
        'x':'no adecuado a patología'
    },
    4:{
        'if':'adecuado a peso, talla y edad bajo monitorización',
        'x':'no adecuado a peso, talla y edad'
    },
    5:{
        'if':'monitorizar por posible alergia',
        'x':'paciente alérgico'
    },
    6:{
        'if':'provocó una interacción con otro medicamento',
        'x':'provocó una interacción con otro medicamento'
    },
    7:{
        'if':'provocó una interacción con un alimento',
        'x':'provocó una interacción con un alimento'
    },
    8:{
        'if':'provocó insuficiencia renal/hepatica',
        'x':'provocó insuficiencia  renal/hepatica'
    }
}

	var service = {
        ready: ready,
        quickEvaluation:quickEvaluation,
        option:option,
        evaluar:evaluar,
        nextState:nextState,
        postInteraccion:postInteraccion,
        postIdoneidad:postIdoneidad,
        stringEvaluacion:stringEvaluacion,
        textForState:textForState,
        clasifyInteraction:clasifyInteraction
    };

    return service;
	
    function validateMedMed(receta){
        var meds = [];
        for (var i in receta){
            if(receta[i].I6DatosFarma != 'ok')
                meds.push(receta[i]);
        }
        if(meds.length < 2)
            for(var i in receta){
                receta[i].I6DatosFarma = 'ok';
            }
        return receta;
    }

    function postIdoneidad(receta){
        receta = validateMedMed(receta);
        return $http.post(url+"suitability/prescriptionNumber/"+receta[0].numeroRecetaDatosFarma+"/clinicalData/"+receta[0].idDatosClinicos,data={medicamentos:receta}).then(idoneidadSuccess,getError);
    }

    function idoneidadSuccess(response){
        return response.data.body;
    }



    function postInteraccion(data){
        return $http.post(url+'interaction/clinicalData/'+data.idDC+'/prescriptionNumber/'+data.receta,data).then(successInteraccion,getError);
    }
        function successInteraccion(response){
            return response.data.body;
        }

    function evaluar(receta){
        var values = [];
        for(var i in receta)
            values[i] = medicamento(receta[i]);

        return values;
    }

    function medicamento(thisOne){
        var temp = {
            i1:option(thisOne.I1DatosFarma),
            i2:option(thisOne.I2DatosFarma),
            i3:option(thisOne.I3DatosFarma),
            i4:option(thisOne.I4DatosFarma),
            i5:option(thisOne.I5DatosFarma),
            i6:option(thisOne.I6DatosFarma),
            i7:option(thisOne.I7DatosFarma),
            i8:option(thisOne.I8DatosFarma),
            estado:quickEvaluation(thisOne),
            nombrePrincipio:thisOne.nombrePrincipio
        }
        return temp;
    }

    function nextState(thisOption){
                    if(thisOption == "ok")
                        return responses.ifState;
                    if(thisOption == "if")
                        return responses.xState;
                    if(thisOption == "x")
                        return responses.okState;  
    }


    function option(option){
        if ((option == null) || (option == 'ok') )
            return responses.okState;
        if(option == 'if')
            return responses.ifState;
        if(option == 'x')
            return responses.xState;
    }



                    function state(prescription){

                    

                    }

                    function CheckFullArrayState(arraySlot){
                        var state = false;
                            if (arraySlot.I1DatosFarma != null
                            && arraySlot.I2DatosFarma != null
                            && arraySlot.I3DatosFarma != null
                            && arraySlot.I4DatosFarma != null
                            && arraySlot.I5DatosFarma != null
                            && arraySlot.I6DatosFarma != null
                            && arraySlot.I7DatosFarma != null
                            && arraySlot.I8DatosFarma != null)
                                state = true;
                        return state;
                    };

                    function CheckRedState(arraySlot){
                        var state = false;
                        if (arraySlot.I1DatosFarma == "x"
                        || arraySlot.I2DatosFarma == "x"
                        || arraySlot.I3DatosFarma == "x"
                        || arraySlot.I4DatosFarma == "x"
                        || arraySlot.I5DatosFarma == "x"
                        || arraySlot.I6DatosFarma == "x"
                        || arraySlot.I7DatosFarma == "x"
                        || arraySlot.I8DatosFarma == "x")
                            state = true;
                        return state;                   
                    }

                    function CheckIfState(arraySlot){
                        var state = false;
                        if (arraySlot.I1DatosFarma == "if"
                        || arraySlot.I2DatosFarma == "if"
                        || arraySlot.I3DatosFarma == "if"
                        || arraySlot.I4DatosFarma == "if"
                        || arraySlot.I5DatosFarma == "if"
                        || arraySlot.I6DatosFarma == "if"
                        || arraySlot.I7DatosFarma == "if"
                        || arraySlot.I8DatosFarma == "if")
                            state = true;
                        return state;                   
                    }

    function quickEvaluation(prescription){
        if (prescription.I1DatosFarma == null){ // no hay evaluacion
                return 'sin evaluación';
        }else if(CheckRedState(prescription) == true)
            return 'no ideoneo';
        else if(CheckIfState(prescription) == true)
            return 'requiere monitorización';
        else return 'ideonea';

    }



	    function userSuccess(response){
	    	console.log('set cookie here')
	    	return response.data.body['patient'];
	    }


	function remove(){
		console.log('delete here');
	}

    function getError(data){
    	return 'error';
    	console.log('idoneidad error');
    }

    function stringEvaluacion(receta){
        //console.log(receta)
        var defaul = {
            value:0,
            label:'Prescripción sin evaluación de ideoneidad',
            style:'text-or'
        }

        var ideonea = {
            value:1,
            label:'Prescripción ideonea',
            style:'text-gr'
        }

        var monitorizar = {
            value:2,
            label:'Prescripción con monitorización',
            style:'text-or'
        }

        var noideaonea = {
            value:3,
            label:'Prescripción no ideonea',
            style:'text-rd'
        }

        var state = {value:4};
        for(var i in receta){
            if (receta[i].I1DatosFarma == null) // no hay evaluacion
                   state = defaul;
            else if(state.value != 3 && state.value != 0){   //si no ha encontrado error o default todabia
                if(CheckRedState(receta[i]) == true )  // checa por errores
                    state = noideaonea;
                else if(state.value != 2){    // si no encontro errores, checa si ya hay monitorizacion reportada
                    if(CheckIfState(receta[i]) == true)    //si no hay reporte de monitorizacion, checa que este medicamento no tenga
                        state = monitorizar;
                    else //si no tiene estado de monitorizacion, el medicamento es correcto.
                        state = ideonea;
                }

            }
        }
        return state;
    }



    function createObject(thisMed,thisID){
        var newObject = thisMed;
            newObject.fecha = thisMed.fechaIdoneidad;
            newObject.principio = thisMed.nombrePrincipio;
            newObject.usuario = thisMed.nombreQuimico+' '+thisMed.apellidoQuimico;
            newObject.idError = thisID;
        
        if(thisID == 0){
            newObject.resultado = 'Ideoneo';
            newObject.evento = '' ;
        }else if(thisID == 1){
            if(thisMed.I1DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[1].if;
            }else if(thisMed.I1DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[1].x;
            }
        }else if(thisID == 2){
            if(thisMed.I2DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[2].if;
            }else if(thisMed.I2DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[2].x;
            }
        }else if(thisID == 3){
            if(thisMed.I3DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[3].if;
            }else if(thisMed.I3DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[3].x;
            }
        }else if(thisID == 4){
            if(thisMed.I4DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[4].if;
            }else if(thisMed.I4DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[4].x;
            }
        }else if(thisID == 5){
            if(thisMed.I5DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[5].if;
            }else if(thisMed.I5DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[5].x;
            }
        }else if(thisID == 6){
            if(thisMed.I6DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[6].if;
            }else if(thisMed.I6DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[6].x;
            }
        }else if(thisID == 7){
            if(thisMed.I7DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[7].if;
            }else if(thisMed.I7DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[7].x;
            }
        }else if(thisID == 8){
            if(thisMed.I8DatosFarma == 'if'){
                newObject.resultado = 'Requiere monitorización';
                newObject.evento = txtIdoneidades[8].if;
            }else if(thisMed.I8DatosFarma == 'x'){
                newObject.resultado = 'No Ideoneo';
                newObject.evento = txtIdoneidades[8].x;
            }
        }
        ResponseIdoneidades.push(newObject);
    }

    function checkDiferences(onThis){
        var diferente = false;
        if(onThis.I1DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,1);
        }
        if(onThis.I2DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,2);
        }
        if(onThis.I3DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,3);
        }
        if(onThis.I4DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,4);
        }
        if(onThis.I5DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,5);
        }
        if(onThis.I6DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,6);
        }
        if(onThis.I7DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,7);
        }
        if(onThis.I8DatosFarma != 'ok'){
            diferente = true;
            createObject(onThis,8);
        }
        if(diferente == false)
            createObject(onThis,0);
    }



    function textForState(receta){
        ResponseIdoneidades = [];
        for(var i in receta){
            checkDiferences(receta[i]);
        }
        return ResponseIdoneidades;
    }


function clasifyInteraction(those){
    var interacciones = [];
    var flag = med_med = false;
    for(var i in those){
            var tmp = those[i];
            //tmp.medicamento =those[i].medicamentos[j];
            if(those[i].categorizacionInteraccion == 1)
                tmp.grado = 'menor';
            if(those[i].categorizacionInteraccion == 2)
                tmp.grado= 'moderada';
            if(those[i].categorizacionInteraccion == 3)
                tmp.grado= 'mayor';
            if(those[i].tipoInteraccion == 1){
                tmp.tipo = 'Alim. - Med.';
                tmp.descripcion = ''+tmp.nombrePrincipio+' y '+tmp.alimentoInteraccion+' causaron interacción';
                interacciones.push(tmp)
            }
            else{
                if(med_med == false){
                    med_med = true;
                    tmp.tipo = 'Med. - Med.';
                    tmp.descripcion = setIntMed(those);
                    interacciones.push(tmp)
                }
            }
    }
    return interacciones;
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


    function ready(){

    }

}