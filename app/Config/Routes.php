<?php

use CodeIgniter\Router\RouteCollection;

/**
 * PT ANTAM Tbk — Smart Learning Portal Routes
 * 
 * @var RouteCollection $routes
 */

// --------------------------------------------------------------------
// Public Authentication Routes
// --------------------------------------------------------------------
$routes->get('/', 'AuthController::index');
$routes->get('login', 'AuthController::index');
$routes->post('login', 'AuthController::doLogin');
$routes->post('verify-2fa', 'AuthController::verify2FA');
$routes->post('resend-otp', 'AuthController::resendOtp');
$routes->get('cancel-2fa', 'AuthController::cancel2FA');
$routes->post('register', 'AuthController::register');
$routes->get('logout', 'AuthController::logout');
$routes->get('logout.php', 'AuthController::logout');
$routes->match(['GET', 'POST'], 'test-mail', 'MailTestController::index');
$routes->match(['GET', 'POST'], 'test_mail.php', 'MailTestController::index');

// --------------------------------------------------------------------
// Authenticated Application Routes (Protected with AuthFilter)
// --------------------------------------------------------------------
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('home', 'HomeController::index');
    $routes->get('dashboard', 'DashboardController::index');
    
    // Learning Bank / Catalog
    $routes->get('learning-bank', 'LearningBankController::index');
    $routes->get('learning_bank', 'LearningBankController::index');

    // My Learning Track
    $routes->get('my-learning', 'MyLearningController::index');
    $routes->get('my_learning', 'MyLearningController::index');

    // Notifications & Alarm Center
    $routes->get('notifikasi', 'NotificationController::index');

    // Admin Console
    $routes->get('admin-panel', 'AdminPanelController::index');
    $routes->get('admin_panel', 'AdminPanelController::index');

    // Reports & Compliance
    $routes->get('reports', 'ReportsController::index');

    // User Settings & Preferences
    $routes->get('settings', 'SettingsController::index');

    // Central API Endpoint for AJAX actions
    $routes->match(['GET', 'POST'], 'api', 'ApiController::index');
    $routes->match(['GET', 'POST'], 'api.php', 'ApiController::index');

    // Legacy app.php?page=... URL compatibility
    $routes->get('app.php', static function () {
        $page = service('request')->getGet('page') ?? 'home';
        $target = match($page) {
            'dashboard'     => 'dashboard',
            'learning_bank' => 'learning-bank',
            'my_learning'   => 'my-learning',
            'notifikasi'    => 'notifikasi',
            'admin_panel'   => 'admin-panel',
            'reports'       => 'reports',
            'settings'      => 'settings',
            default         => 'home',
        };
        return redirect()->to($target);
    });
});
