<?php

/**
 * This file is part of the ActuSF Relations bundle designed for Contao 4.
 *
 * Copyright (c) 2016-2018 Addictic
 * @package    Widgets
 * @link       https://www.addictic.fr
 * @author     Vianney CHANOUX <vchanoux@addictic.fr>
 * @author     Quentin GIRAUD <qgiraud@addictic.fr>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Addictic\ContaoActuSFRelationsBundle;

use Terminal42\ContaoTableLookupWizard\TableLookupWizard;


class TableLookupActuSFRelationsWizard extends TableLookupWizard
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

        $GLOBALS['TL_CSS'][] = 'bundles/terminal42contaotablelookupwizard/tablelookup.min.css';

        // Vianney : Si c'est '1' au chargement de la page, alors tablelinks.min.js est en 404 (testing by class Input get)
        if (!$blnNoAjax) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/terminal42contaotablelookupwizard/tablelookup.min.js';
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

        $objTemplate                  = new \BackendTemplate('be_widget_tablelookupactusfrelationswizard');
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

    /**
     * Renders the table body
     * @return  string
     */
    public function getBody()
    {
        $objTemplate = new \BackendTemplate('be_widget_tablelookupactusfrelationswizard_content');
        $arrResults  = array();
        $blnQuery    = true;

        if ($this->blnIsAjaxRequest && !\Input::get('keywords')) {
            $blnQuery = false;
        }

        if ($blnQuery) {
            $arrResults = $this->getResults();

            \Haste\Generator\RowClass::withKey('rowClass')
                ->addCustom('row')
                ->addCount('row_')
                ->addFirstLast('row_')
                ->addEvenOdd('row_')
                ->applyTo($arrResults);
        }

        if (!empty($arrResults)) {
            $objTemplate->hasResults = true;
        }

        // Determine the results message based on keywords availability
        if (strlen(\Input::get('keywords'))) {
            $noResultsMessage = sprintf($GLOBALS['TL_LANG']['MSC']['tlwNoResults'], \Input::get('keywords'));
        } else {
            $noResultsMessage = $GLOBALS['TL_LANG']['MSC']['tlwNoValue'];
        }

        $objTemplate->results          = $arrResults;
        $objTemplate->colspan          = count($this->arrListFields) + 1 + (int)$this->blnEnableSorting;
        $objTemplate->noResultsMessage = $noResultsMessage;
        $objTemplate->fieldType        = $this->fieldType;
        $objTemplate->isAjax           = $this->blnIsAjaxRequest;
        $objTemplate->strId            = $this->strId;
        $objTemplate->enableSorting    = $this->blnEnableSorting;
        $objTemplate->dragHandleIcon   = 'system/themes/' . \Backend::getTheme() . '/images/drag.gif';

        return $objTemplate->parse();
    }

    /**
     * Get the results
     *
     * @return array
     */
    protected function getResults()
    {
        $arrResults = array();

        if ($this->arrWhereProcedure && !empty($this->arrWhereProcedure)) {
            $objResults = \Database::getInstance()
                ->prepare(implode(' ', $this->arrQueryProcedure))
                ->execute($this->arrQueryValues);

            while ($objResults->next()) {
                $arrRow                         = $objResults->row();
                $strKey                         = $arrRow[$this->foreignTable . '_id'];
                $arrResults[$strKey]['rowId']   = $arrRow[$this->foreignTable . '_rel'] . '__' . $arrRow[$this->foreignTable . '_id'] . '__' . $arrRow[$this->foreignTable . '_title'] . '__' . $arrRow[$this->foreignTable . '_type'];
                $arrResults[$strKey]['rawData'] = $arrRow;
                $arrResults[$strKey]['table']   = $this->typeRelation;

                // Mark checked if not ajax call
                if (!$this->blnIsAjaxRequest) {
                    $arrResults[$strKey]['isChecked'] = $this->optionChecked($arrRow[$this->foreignTable . '_id'],
                        $this->varValue);
                }

                foreach ($this->arrListFields as $strField) {
                    list($strTable, $strColumn) = explode('.', $strField);
                    $strFieldKey                                        = str_replace('.', '_', $strField);
                    $arrResults[$strKey]['formattedData'][$strFieldKey] = \Haste\Util\Format::dcaValue($strTable,
                        $strColumn, $arrRow[$strFieldKey]);
                }
            }
        }

        return $arrResults;
    }

    /**
     * @param bool $otherTable
     * @return array
     */
    protected function getArrSelects($otherTable = false)
    {
        if (!$otherTable) {
            $arrSelects = array($this->foreignTable . '.id AS ' . $this->foreignTable . '_id');

            foreach ($this->arrListFields as $strField) {
                $arrSelects[] = $strField . ' AS ' . str_replace('.', '_', $strField);
            }
            // Cas personalisés
        } else {
            $arrSelects = array($otherTable . '.id AS ' . $otherTable . '_id');

            foreach ($this->arrListFields as $strField) {
                $arrField = explode(".", $strField);
                if ($arrField[1] != 'type') {
                    $arrField[0] = $otherTable;
                    $strField    = implode(".", $arrField);

                    $arrSelects[] = $strField . ' AS ' . str_replace('.', '_', $strField);
                }
            }
        }
        return $arrSelects;
    }

    /**
     * Prepares the SELECT statement
     */
    protected function prepareSelect()
    {
        $arrSelects = $this->getArrSelects();

        // Build SQL statement
        $this->arrQueryProcedure[] = 'SELECT ' . implode(', ', $arrSelects);
        if ($this->typeRelation) {
            $this->arrQueryProcedure[] = 'FROM tl_asf_relations';
        } else {
            $this->arrQueryProcedure[] = 'FROM ' . $this->foreignTable;
        }
    }

    /**
     * Prepares the WHERE statement
     */
    protected function prepareWhere()
    {
        $arrKeywords = trimsplit(' ', \Input::get('keywords'));
        $varData     = \Input::get($this->strName);

        // Handle keywords
        foreach ($arrKeywords as $strKeyword) {
            if (!$strKeyword) {
                continue;
            }

            $this->arrWhereProcedure[] = '(' . implode(' LIKE ? OR ', $this->arrSearchFields) . ' LIKE ?)';
            $this->arrWhereValues      = array_merge($this->arrWhereValues,
                array_fill(0, count($this->arrSearchFields), '%' . $strKeyword . '%'));
        }

        // Filter those that have already been chosen
        if ($this->fieldType == 'checkbox' && is_array($varData) && !empty($varData)) {
            $this->arrWhereProcedure[] = $this->foreignTable . '.id NOT IN (' . implode(',', $varData) . ')';
        } elseif ($this->fieldType == 'radio' && $varData != '') {
            $this->arrWhereProcedure[] = "{$this->foreignTable}.id!='$varData'";
        }

        // If custom WHERE is set, add it to the statement
        if ($this->sqlWhere) {
            $this->arrWhereProcedure[] = $this->sqlWhere;
        }

        if (!empty($this->arrWhereProcedure)) {
            $strWhere = implode(' OR ', $this->arrWhereProcedure);

            $this->arrQueryProcedure[] = 'WHERE ' . $strWhere;
            $this->arrQueryValues      = array_merge($this->arrQueryValues, $this->arrWhereValues);
        }
    }

    /**
     * @param bool $arrResults
     * @return $arr
     */
    protected function getResultsForHeadlines($arrResults = false)
    {
        $arrMergeResult   = array();
        $arrResultsEvents = array();
        if ($arrResults && is_array($arrResults)) {

            $arrSelects = $this->getArrSelects('tl_asf_events');

            // Build SQL
            $query_event = 'SELECT ' . implode(', ', $arrSelects) . ' FROM tl_asf_events WHERE published = 1';

            $objResultsEvents = \Database::getInstance()
                ->prepare($query_event)
                ->execute();

            while ($objResultsEvents->next()) {
                $tableRef = 'tl_asf_events';
                $arrRow   = $objResultsEvents->row();
                $strKey   = $arrRow[$this->foreignTable . '_id'];

                $arrResultsEvents[$strKey]['rowId']                        = $tableRef . '__' . $arrRow[$tableRef . '_id'] . '__' . $arrRow[$tableRef . '_title'] . '__événement';
                $arrResultsEvents[$strKey]['rawData']                      = $arrRow;
                $arrResultsEvents[$strKey]['rawData'][$tableRef . '_type'] = "événement";
                $arrResultsEvents[$strKey]['table']                        = 'events';

                // Mark checked if not ajax call
                if (!$this->blnIsAjaxRequest) {
                    $arrResultsEvents[$strKey]['isChecked'] = $this->optionChecked($arrRow[$tableRef . '_id'],
                        $this->varValue);
                }

                foreach ($this->arrListFields as $strField) {
                    $strField = $this->setGoodTable($strField, $tableRef);
                    list($strTable, $strColumn) = explode('.', $strField);
                    $strFieldKey = str_replace('.', '_', $strField);
                    if ($strColumn == 'type') {
                        $arrRow[$strFieldKey] = 'Evenement';
                    }
                    $arrResultsEvents[$strKey]['formattedData'][$strFieldKey] = \Haste\Util\Format::dcaValue($strTable,
                        $strColumn, $arrRow[$strFieldKey]);
                }
            }

            $arrMergeResult = array_merge($arrResults, $arrResultsEvents);
        }

        return $arrMergeResult;
    }

    /**
     * Overwrite this origine strField
     *
     * @param $strField
     * @return string
     */
    protected function setGoodTable($strField, $strNewTable)
    {
        $arrField    = explode('.', $strField);
        $arrField[0] = $strNewTable;
        return $arrField[0] . "." . $arrField[1];
    }
}