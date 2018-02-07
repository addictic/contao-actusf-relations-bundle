<?php

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
