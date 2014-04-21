<?php

$loader = require 'vendor/autoload.php';

$loader->add('Manubo\\Tests\\', __DIR__);
$loader->add('Manubo\\Twig\\', realpath(__DIR__.'/../src'));

