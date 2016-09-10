angular
    .module('app')
    .controller('MIAController',MIAController);
        

MIAController.$inject = ['$http','$scope','$uibModalInstance','info'];
	

function MIAController($http,$scope,$uibModalInstance,info){
	  $scope.interaccion = info;
	  if($scope.interaccion.tipoInteraccion == 1 )
	  	$scope.titulo = "Reporte de interaccion Medicamento - Alimento";
	  else
	  	$scope.titulo = "Reporte de interaccion Medicamento - Medicamento";

	  $scope.ok = function () {
	    $uibModalInstance.close($scope.interaccion);
	  };

	  $scope.cancel = function () {
	    $uibModalInstance.dismiss('cancel');
  	  };


}