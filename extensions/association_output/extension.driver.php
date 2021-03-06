<?php

class extension_association_output extends Extension
{
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/frontend/',
                'delegate' => 'DataSourcePreExecute',
                'callback' => 'setOutputParameters'
            ),
            array(
                'page' => '/frontend/',
                'delegate' => 'DataSourcePostExecute',
                'callback' => 'appendAssociatedEntries'
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'AdminPagePreGenerate',
                'callback' => 'buildEditor'
            ),
            array(
                'page' => '/blueprints/datasources/',
                'delegate' => 'DatasourcePreCreate',
                'callback' => 'saveDataSource'
            ),
            array(
                'page' => '/blueprints/datasources/',
                'delegate' => 'DatasourcePreEdit',
                'callback' => 'saveDataSource'
            )
        );
    }

/*-------------------------------------------------------------------------
    Editor:
-------------------------------------------------------------------------*/

    /**
     * Build interface to select association output to Data Source editor.
     *
     * @param mixed $context
     *  Delegate context including page object
     */
    public function buildEditor($context)
    {
        $callback = Symphony::Engine()->getPageCallback();

        if ($callback['driver'] == 'blueprintsdatasources' && !empty($callback['context'])) {
            Administration::instance()->Page->addScriptToHead(URL . '/extensions/association_output/assets/associationoutput.datasources.js');

            // Get existing associations
            if ($callback['context'][0] === 'edit') {
                $name = $callback['context'][1];
                $datasource = DatasourceManager::create($name);
                $settings = $datasource->dsParamINCLUDEDASSOCIATIONS;
                $settings['section_id'] = $datasource->getSource();
            }

            // Build interface
            $wrapper = $context['oPage']->Contents->getChildByName('form', 0);

            $fieldset = new XMLElement('fieldset');
            $fieldset->setAttribute('class', 'settings association-output');
            $fieldset->setAttribute('data-context', 'sections');
            $fieldset->appendChild(new XMLElement('legend', __('Associated Content')));

            // Build options
            $options = array();
            $sections = SectionManager::fetch();
            foreach ($sections as $section) {
                $section_id = $section->get('id');
                $section_handle = $section->get('handle');
                $associations = SectionManager::fetchParentAssociations($section_id);

                if (!empty($associations)) {
                    foreach ($associations as $association) {
                        $options[] = $this->buildElementOptions($association, $settings, $section_id);
                    }
                }
            }

            // Append field selection
            $label = Widget::Label(__('Included Associations'));
            $select = Widget::Select('fields[includedassociations][]', $options, array('multiple' => 'multiple'));
            $label->appendChild($select);

            $fieldset->appendChild($label);
            $wrapper->appendChild($fieldset);
        }
    }

    /**
     * Build element options.
     *
     * @param array $association
     *  Association data
     * @param array  $settings
     *  Data Source settings
     * @param number $section_id
     *  Section ID
     * @return array
     *  Element options
     */
    private function buildElementOptions($association, $settings, $section_id)
    {
        $elements = array();
        $label = FieldManager::fetchHandleFromID($association['child_section_field_id']);
        $fields = FieldManager::fetch(null, $association['parent_section_id']);

        foreach ($fields as $field) {
            $name = $field->get('element_name');
            $value = $association['parent_section_id'] . '|#|' . $association['parent_section_field_id']  . '|#|' . $label . '|#|' . $name;
            $selected = false;

            if ($section_id == $settings['section_id'] && isset($settings[$label])) {
                if (in_array($name, $settings[$label]['elements'])) {
                    $selected = true;
                }
            }

            $elements[] = array($value, $selected, $name);
        }

        return array(
            'label' => $label,
            'data-label' => $section_id,
            'options' => $elements
        );
    }

    /**
     * Save included associations.
     *
     * @param mixed $context
     *  Delegate context including string contents of the data souce PHP file
     */
    public function saveDataSource($context)
    {
        $contents = $context['contents'];
        $elements = $_POST['fields']['includedassociations'];

        if (isset($elements)) {

            // Prepare associations
            $associations = array();
            foreach ($elements as $element) {
                $this->buildAssociationSettings($associations, $element);
            }

            // Prepare variable string
            $included = $this->formatAssociationSettings($associations);

            // Store included associations
            $contents = str_replace(
                "<!-- INCLUDED ELEMENTS -->",
                "<!-- INCLUDED ELEMENTS -->\n    public \$dsParamINCLUDEDASSOCIATIONS = $included;",
                $contents
            );

            $context['contents'] = $contents;
        }
    }

    /**
     * Build associative array with association settings based on element values.
     *
     * @param array $associations
     *  Association settings
     * @param string $element
     *  Included element values
     */
    private function buildAssociationSettings(&$associations, $element)
    {
        list($section_id, $field_id, $field_handle, $elements) = explode('|#|', $element);
        $associations[$field_handle]['section_id'] = $section_id;
        $associations[$field_handle]['field_id'] = $field_id;
        $associations[$field_handle]['elements'][] = $elements;
    }

    /**
     * Format association settings
     *
     * @param array $settings
     *  Association settings
     * @return string
     *  Settings formatted as string
     */
    private function formatAssociationSettings($settings)
    {
        $string = var_export($settings, true);
        $string = str_replace("  ", "    ", $string);
        $string = str_replace("array (", "array(", $string);
        $string = str_replace(" => \n    array", " => array", $string);
        $string = str_replace(" => \n        array", " => array", $string);
        $string = preg_replace("/\d+ => /", "", $string);
        $string = preg_replace("/,(\n)( +)?\)/", "$1$2)", $string);
        $string = str_replace("\n    ", "\n        ", $string);
        $string = preg_replace("/\)$/", "    )", $string);

        return $string;
    }

/*-------------------------------------------------------------------------
    Output Parameters:
-------------------------------------------------------------------------*/

    /**
     * Dynamically set output parameters used to fetch associated entries
     *
     * @param mixed $context
     *  Delegate context including page object
     */
    public function setOutputParameters($context)
    {
        $datasource = $context['datasource'];
        $associations = $datasource->dsParamINCLUDEDASSOCIATIONS;

        if (!empty($associations)) {

            // Create output parameters, if necessary
            if (!is_array($datasource->dsParamPARAMOUTPUT)) {
                $datasource->dsParamPARAMOUTPUT = array();
            }

            // Add missing output parameters
            $fields = array_keys($associations);
            $datasource->dsParamPARAMOUTPUT = array_merge($datasource->dsParamPARAMOUTPUT, $fields);
        }
    }

    /**
     * Unset dynamically added output parameters after use
     *
     * @param mixed $context
     *  Delegate context including page object
     */
    public function unsetOutputParameters(&$context)
    {
        $datasource = $context['datasource'];
        $associations = $datasource->dsParamINCLUDEDASSOCIATIONS;

        if (!empty($associations)) {

            // Get original settings
            $handle = substr(get_class($datasource), 10);
            $original = DatasourceManager::create($handle);

            // Extract dynamic output parameters
            $original_parameters = (array) $original->dsParamPARAMOUTPUT;
            $current_parameters = (array) $datasource->dsParamPARAMOUTPUT;
            $dynamic_parameters = array_diff($current_parameters, $original_parameters);

            // Remove dynamic output parameters
            $name = $original->dsParamROOTELEMENT;
            $this->removeOutputParameters($context['param_pool'], $name, $dynamic_parameters);
        }
    }

    /**
     * Remove output parameters
     *
     * @param array $param_pool
     *  Parameter pool data
     * @param string $name
     *  Data Source name
     * @param array $parameters
     *  Parameters that should be removed
     */
    private function removeOutputParameters(&$param_pool, $name, $parameters)
    {
        foreach ($parameters as $parameter) {
            $handle = 'ds-' . $name . '.' . $parameter;
            unset($param_pool[$handle]);
        }
    }

/*-------------------------------------------------------------------------
    XML:
-------------------------------------------------------------------------*/

    /**
     * Append associated entries to the XML output
     */
    public function appendAssociatedEntries($context)
    {
        $datasource = $context['datasource'];
        $section_id = $datasource->getSource();
        $xml = $context['xml'];
        $parameters = $context['param_pool'];

        if (!empty($datasource->dsParamINCLUDEDASSOCIATIONS)) {
            foreach ($datasource->dsParamINCLUDEDASSOCIATIONS as $name => $settings) {
                $transcriptions = array();
                $entry_ids = null;

                if (!empty($parameters)) {
                    $entry_ids = array_unique($parameters['ds-' . $datasource->dsParamROOTELEMENT . '.' . $name]);
                }

                if (!empty($entry_ids)) {
                    if (!is_numeric($entry_ids[0])) {
                        $this->fetchEntryIdsByValues($entry_ids, $transcriptions, $settings['field_id']);
                    }

                    // Append associated entries
                    $associated_xml = $this->fetchAssociatedEntries($settings, $section_id, $entry_ids);
                    $associated_items = $this->groupAssociatedEntries($associated_xml);
                    $this->includeAssociatedEntries($xml, $associated_items, $name, $transcriptions);

                    // Clean up parameter pool
                    $this->unsetOutputParameters($context);
                }
            }
        }
    }

    /**
     * Fetch entry ids by value.
     *
     * @param array $entries
     *  Entry values
     * @param array $transcriptions
     *  Value/ID transcriptions
     * @param number $field_id
     *  Field ID
     */
    private function fetchEntryIdsByValues(&$entries, &$transcriptions, $field_id)
    {
        $value_list = "'" . implode($entries, "', '") . "'";
        $data = Symphony::Database()->fetch(
            sprintf(
                "SELECT `entry_id`, `handle`
                FROM sym_entries_data_%d
                WHERE `handle` IN (%s) or `value` IN (%s)",
                $field_id,
                $value_list,
                $value_list
            )
        );

        foreach ($data as $transcription) {
            $transcriptions[$transcription['handle']] = $transcription['entry_id'];
        }

        $entries = array_values($transcriptions);
    }

    /**
     * Fetch associated entries using a custom Data Source
     *
     * @param array $settings
     *  An array of field settings
     * @param array $entry_ids
     *  An array of associated entry ids
     * @return XMLElement
     */
    private function fetchAssociatedEntries($settings, $section_id, $entry_ids = array())
    {
        $datasource = DatasourceManager::create('associations', null, false);
        $datasource->dsParamSOURCE = $settings['section_id'];
        $datasource->dsParamFILTERS['system:id'] = implode($entry_ids, ', ');
        $datasource->dsParamINCLUDEDELEMENTS = $settings['elements'];

        return $datasource->execute();
    }

    /**
     * Group associated entries by id
     *
     * @param XMLELement $associated_xml
     *  The Data Source output
     * @return array
     */
    private function groupAssociatedEntries($associated_xml)
    {
        $associated_items = array();

        foreach ($associated_xml->getChildren() as $entry) {
            if ($entry->getName() === 'entry') {
                $associated_items[$entry->getAttribute('id')] = $entry->getChildren();
            }
        }

        return $associated_items;
    }

    /**
     * Attach associated entries to the existing Data Source output
     *
     * @param XMLElement $xml
     *  The existing XML
     * @param array $associated_items
     *  An array linking entry ids to XML entries
     * @param string $name
     *  The associative field name
     * @param array $transcriptions
     *  An array mapping field handles to entry ids
     */
    private function includeAssociatedEntries(&$xml, $associated_items, $name, $transcriptions)
    {
        $entries = $xml->getChildren();
        if (!empty($entries)) {
            foreach ($entries as $entry) {
                $fields = $entry->getChildren();

                if ($entry->getName() === 'entry' && !empty($fields)) {
                    foreach ($fields as $field) {
                        $items = $field->getChildren();

                        if ($field->getName() === $name && !empty($items)) {
                            foreach ($items as $item) {
                                $id = $item->getAttribute('id');

                                if (empty($id)) {
                                    $handle = $item->getAttribute('handle');
                                    $id = $transcriptions[$handle];
                                }

                                $association = $associated_items[$id];
                                if (!empty($association)) {
                                    $item->replaceValue('');
                                    $item->setChildren($associated_items[$id]);
                                    $item->setAttribute('id', $id);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
