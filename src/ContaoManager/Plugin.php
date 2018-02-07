<?php

/**
 * This file is part of the ActuSF Relations bundle designed for Contao 4.
 *
 * Copyright (c) 2016-2018 Addictic
 * @package    ContaoManager
 * @link       https://www.addictic.fr
 * @author     Vianney CHANOUX <vchanoux@addictic.fr>
 * @author     Quentin GIRAUD <qgiraud@addictic.fr>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Addictic\ContaoActuSFRelationsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\ContaoTableLookupWizard\Terminal42ContaoTableLookupWizard;
use Addictic\ContaoActuSFRelationsBundle\AddicticContaoActuSFRelationsBundle;


class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(AddicticContaoActuSFRelationsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class, Terminal42ContaoTableLookupWizard::class])
        ];
    }
}
