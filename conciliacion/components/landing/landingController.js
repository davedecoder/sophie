angular
    .module('app')
    .controller('landingController',landingController);
        
landingController.$inject = ['$scope','landingService'];

function landingController($scope,landingService){
 	var vm = this;

 	
	activate();




	function activate(){
		landingService.getUsers();
	}

	
}
