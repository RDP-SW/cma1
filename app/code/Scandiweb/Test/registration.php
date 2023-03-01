<?php
/**
 * @category Scandiweb
 * @package Scandiweb_Test
 * @author Rizki Prasetyo <info@scandiweb.com>
 * @copyright Copyright (c) 2023 Scandiweb, Ltd (http://scandiweb.com)
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Scandiweb_Test',
    __DIR__
);
