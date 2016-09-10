angular
    .module('app')
    .controller('loginController',loginController);
        
loginController.$inject = ['$scope','Login','loginService'];

function loginController($scope,Login,loginService){
		
	$scope.vm ={
		logIn:logIn,
		ready:ready,
		emailError:false,
		passwordError:false
	}

	function logIn(user){
		$scope.vm.emailError = false;
		$scope.vm.passwordError = false;
		if(user.name != undefined && user.password != undefined)
			loginService.checkUser(user).then(userSuccess,showError);
		//delete(user)
	}
	function userSuccess(response){
		console.log(response)
		if(response.hasError){
			if(response.email){
				$scope.vm.emailError = true;
			}
			if(response.password)
				$scope.vm.passwordError = true;
		}
		//console.log(response)
	}
	function showError(response){

	}

	function ready(){

	}
	//Login.getUsers();
}
