<?php
// Front controller untuk halaman checkout
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/controllers/ShopController.php';

$controller = new ShopController();
$data = $controller->checkout();
extract($data);
include __DIR__ . '/views/checkout.view.php';