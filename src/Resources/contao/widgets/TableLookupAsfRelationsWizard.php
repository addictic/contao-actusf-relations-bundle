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

    /**
     * Generate the widget and return it as string
     * @return  string
     */
    public function generate()
    {
        $blnNoAjax = \Input::get('noajax');

        $this->blnIsAjaxRequest = \Input::get('tableLookupWizard') == $this->strId;

        // Ensure search and list fields have correct aliases
        $this->ensureColumnAliases($this->arrSearchFields);;
        $this->ensureColumnAliases($this->arrListFields);

        // Ajax call
        if ($this->blnIsAjaxRequest) {
            // Clean buffer
            while (ob_end_clean()) {
            }

            $this->prepareSelect();
            $this->prepareJoins();
            $this->prepareWhere();
            $this->prepareOrderBy();
            $this->prepareGroupBy();

            $strBuffer = $this->getBody();
            $response  = new \Haste\Http\Response\JsonResponse(
                array
                (
                    'content' => $strBuffer,
                    'token'   => REQUEST_TOKEN,
                )
            );

            $response->send();
        }

        $GLOBALS['TL_CSS'][] = 'system/modules/tablelookupwizard/assets/tablelookup.min.css';

        // Vianney : Si c'est '1' au chargement de la page, alors tablelinks.min.js est en 404 (testing by class Input get)
        if (!$blnNoAjax) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/tablelookupwizard/assets/tablelookup.min.js';
        }

        // Si il y a des relations, le fonctionnement est différent
        if ($this->typeRelation) {
            $table    = "tl_asf_" . $this->typeRelation;
            $strField = $this->strName;
            $intId    = $this->arrConfiguration['currentRecord']; // Numéro de le ligne dans la table enregistré

            // On charge les relations
            $objLinks = \Database::getInstance()->prepare("SELECT `" . $strField . "`FROM `" . $table . "` WHERE id=?")->execute($intId);

            if ($objLinks->numRows != 0 && !empty(deserialize($objLinks->links, true))) {
                $arrIds             = deserialize($objLinks->links, true);
                $this->blnHasValues = true;
            } else {
                $arrIds             = array(0);
                $this->blnHasValues = false;
            }
        } else {
            // Sinon on est dans une relation normal
            $arrIds = deserialize($this->varValue, true);

            if ($arrIds[0] == '') {
                $arrIds = array(0);
            } else {
                $this->blnHasValues = true;
            }
        }

        // Add preselect to WHERE statement
        if ($this->typeRelation && $arrIds[0] != 0 && !$this->blnIsAjaxRequest) {
            $this->result[] = $arrIds;

            foreach ($arrIds as $ids) {
                $this->arrWhereProcedure[] = '(tl_asf_relations.id =  "' . $ids['id'] . '" AND tl_asf_relations.rel ="' . $ids['rel'] . '")';
            }
        }

        $this->prepareSelect();
        $this->prepareJoins();
        $this->prepareWhere();

        $objTemplate                  = new \BackendTemplate('be_widget_tablelookupwizard');
        $objTemplate->noAjax          = $blnNoAjax;
        $objTemplate->strId           = $this->strId;
        $objTemplate->fieldType       = $this->fieldType;
        $objTemplate->fallbackEnabled = $this->blnEnableFallback;
        $objTemplate->noAjaxUrl       = $this->addToUrl('noajax=1');
        $objTemplate->listFields      = $this->arrListFields;
        $objTemplate->colspan         = count($this->arrListFields) + (int)$this->blnEnableSorting;
        $objTemplate->searchLabel     = $this->searchLabel == '' ? $GLOBALS['TL_LANG']['MSC']['searchLabel'] : $this->searchLabel;
        $objTemplate->columnLabels    = $this->getColumnLabels();
        $objTemplate->hasValues       = $this->blnHasValues;
        $objTemplate->enableSorting   = $this->blnEnableSorting;
        $objTemplate->body            = $this->getBody();

        return $objTemplate->parse();
    }
}