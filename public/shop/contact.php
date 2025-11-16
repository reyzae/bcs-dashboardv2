<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/controllers/ShopController.php';

$controller = new ShopController();
$data = $controller->contact();
extract($data);
include __DIR__ . '/views/contact.view.php';