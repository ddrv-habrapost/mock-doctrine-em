<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

return ConsoleRunner::createHelperSet($entityManager);

