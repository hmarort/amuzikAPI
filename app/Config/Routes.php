<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->post('/login', 'UserController::login');        // User login
$routes->post('/register', 'UserController::saveUser');  // User registration
$routes->get('/info', 'UserController::userInfo');       // Get user by ID (changed to GET)
$routes->post('/update', 'UserController::saveUser');    // Update user
$routes->post('/delete', 'UserController::deleteUser');  // Delete user
$routes->post('/find', 'UserController::find');          // Find user by username

// Friend routes
$routes->post('/saveFriendship', 'FriendController::saveFriendship');
$routes->post('/deleteFriendship', 'FriendController::deleteFriendship');

// Token routes
$routes->post('/token', 'TokenController::insertToken');