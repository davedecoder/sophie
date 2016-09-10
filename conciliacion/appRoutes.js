
angular
    .module('app').config(['$routeProvider','$locationProvider', function ($routeProvider,$locationProvider) {
        $routeProvider.when('/landing', {templateUrl: 'conciliacion/components/landing/landing.html',controller:'landingController'});
        $routeProvider.when('/login', {templateUrl: 'conciliacion/core/login/login.html',controller:'loginController'});
        $routeProvider.when('/home', {templateUrl: 'conciliacion/components/home/home.html',controller:'homeController'});
        $routeProvider.when('/profile/', {templateUrl: 'conciliacion/components/profile/profile.html',controller:'profileController'});
        $routeProvider.when('/new/patient', {templateUrl: 'conciliacion/components/personal_data/personal_data.html',controller:'pdataController'});        
        $routeProvider.when('/new/prescription', {templateUrl: 'conciliacion/components/prescription/prescription.html',controller:'prescriptionController'});
        $routeProvider.otherwise({ redirectTo: '/login', caseInsensitiveMatch: true });
        $locationProvider.html5Mode(false);


  }]);
