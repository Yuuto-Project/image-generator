<?php

/** @var Laravel\Lumen\Routing\Router $router */

$router->post('/dialog', ['uses' => 'ImageGenerationController@dialog']);
