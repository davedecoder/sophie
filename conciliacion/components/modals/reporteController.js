angular
    .module('app')
    .controller('reporteController',reporteController);
        

reporteController.$inject = ['$http','$scope','$uibModalInstance','doc'];
	

function reporteController($http,$scope,$uibModalInstance,doc){
		$scope.pdf = doc;


	  $scope.ok = function () {
	    $uibModalInstance.close($scope.interaccion);
	  };

	  $scope.cancel = function () {
	    $uibModalInstance.dismiss('cancel');
  	  };


}