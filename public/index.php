<?php
/**
 * Front Controller - TAVIS
 * Traffic Awareness Vaccine Intelligent System (ระบบวัคซีนจราจร)
 * สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม
 */

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\Router;

$router = new Router();
require BASE_PATH . '/routes/web.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($uri, $method);
