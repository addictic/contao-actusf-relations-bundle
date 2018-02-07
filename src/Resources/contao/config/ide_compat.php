<?php

/**
 * This file is part of the ActuSF Relations bundle designed for Contao 4.
 *
 * Copyright (c) 2016-2018 Addictic
 * @package    Config
 * @link       https://www.addictic.fr
 * @author     Vianney CHANOUX <vchanoux@addictic.fr>
 * @author     Quentin GIRAUD <qgiraud@addictic.fr>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */


// This file is not used in Contao. Its only purpose is to make PHP IDEs like
// Eclipse, Zend Studio or PHPStorm realize the class origins, since the dynamic
// class aliasing we are using is a bit too complex for them to understand.

namespace {
    \define('TL_ROOT', __DIR__ . '/../../../../../');
    \define('TL_ASSETS_URL', 'http://localhost/');
    \define('TL_FILES_URL', 'http://localhost/');
}

namespace {
    class TableLookupActuSFRelationsWizard extends \Contao\TableLookupActuSFRelationsWizard {}
}
