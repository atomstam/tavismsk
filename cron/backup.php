<?php
require dirname(__DIR__) . '/config/bootstrap.php';
(new App\Services\BackupService())->create(null);
echo "backup ok\n";
