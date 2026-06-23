<?php
require_once __DIR__ . '/init.php';
$portal = $_GET['portal'] ?? null;
(new AuthController())->login(is_string($portal) ? $portal : null);
