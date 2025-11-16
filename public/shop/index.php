<?php
// Front controller untuk katalog shop
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/controllers/ShopController.php';

$controller = new ShopController();
$data = $controller->index();
extract($data);
include __DIR__ . '/views/index.view.php';