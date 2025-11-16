<?php
// Front controller untuk halaman keranjang
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/controllers/ShopController.php';

$controller = new ShopController();
$data = $controller->cart();
extract($data);
include __DIR__ . '/views/cart.view.php';

