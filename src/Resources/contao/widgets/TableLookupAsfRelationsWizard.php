<?php

/**
 * Extension for Contao Open Source CMS
 *
 * Copyright (C) 2013 - 2015 terminal42 gmbh
 *
 * @package    TableLookupWizard
 * @link       http://www.terminal42.ch
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao;

class TableLookupAsfRelationsWizard extends \TableLookupWizard
{

    /**
     * Template
     * @var string
     */
    protected $hasArticles = false;

    /**
     * Résultat enregistré dans la page
     * @var array
     */
    protected $result = array();

    /**
     * Type of Relations for Actusf
     * @var mixed (bool or string tl_asf_*)
     */
    protected $typeRelation = false;


}