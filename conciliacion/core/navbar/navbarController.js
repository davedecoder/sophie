angular
    .module('app')
    .controller('navbarController',navbarController);
        
navbarController.$inject = ['$scope','permisos','Login','navbarService'];

function navbarController($scope,permisos,Login,navbarService){
	$scope.vm = {
		cerrarSesion:cerrarSesion,
		goHome: goHome,
		usuario : {},
		goRams : goRams
	}



	$scope.vm.usuario = navbarService.getUsers();

	function goHome() {
		navbarService.goHome();
	}
	
	function cerrarSesion(){
		navbarService.logOut();
	}
	
	function goRams() {
		$('input[name=k]').val(navbarService.getKey());
		if ($('input[name=k]').val() != '')
		{
			$('#newSS').submit();
			$('input[name=k]').val('');
		}
	}
	//Login.getUsers();

}
