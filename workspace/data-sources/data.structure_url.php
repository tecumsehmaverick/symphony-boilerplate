<?php

require_once TOOLKIT . '/class.datasource.php';

class datasourcestructure_url extends SectionDatasource
{
    public $dsParamROOTELEMENT = 'structure-url';
    public $dsParamORDER = 'asc';
    public $dsParamPAGINATERESULTS = 'yes';
    public $dsParamLIMIT = '1';
    public $dsParamSTARTPAGE = '1';
    public $dsParamREDIRECTONEMPTY = 'no';
    public $dsParamPARAMOUTPUT = array(
        'system:id',
        'slug',
        'matrix'
        );
    public $dsParamSORT = 'order';
    public $dsParamHTMLENCODE = 'yes';
    public $dsParamASSOCIATEDENTRYCOUNTS = 'no';
    public $dsParamCACHE = '0';

    public $dsParamFILTERS = array(
        '4' => '{$current-path:home}',
        '10' => 'yes',
    );

    public $dsParamINCLUDEDELEMENTS = array(
        'path',
        'title',
        'slug',
        'parent',
        'matrix'
    );
    
    public $dsParamINCLUDEDASSOCIATIONS = array(
        'matrix' => array(
            'section_id' => '4',
            'field_id' => '14',
            'elements' => array(
                'title',
                'text',
                'image',
                'quote',
                'quote-cite',
                'aside',
                'marketing'
            )
        )
    );

    public function __construct($env = null, $process_params = true)
    {
        parent::__construct($env, $process_params);
        $this->_dependencies = array();
    }

    public function about()
    {
        return array(
            'name' => 'Structure: URL',
            'author' => array(
                'name' => 'Jonathan Simcoe',
                'website' => 'http://symphony.dev',
                'email' => 'jonathan@simko.io'),
            'version' => 'Symphony 2.5.0beta1',
            'release-date' => '2014-07-17T23:31:51+00:00'
        );
    }

    public function getSource()
    {
        return '2';
    }

    public function allowEditorToParse()
    {
        return true;
    }

    public function execute(array &$param_pool = null)
    {
        $result = new XMLElement($this->dsParamROOTELEMENT);

        try{
            $result = parent::execute($param_pool);
        } catch (FrontendPageNotFoundException $e) {
            // Work around. This ensures the 404 page is displayed and
            // is not picked up by the default catch() statement below
            FrontendPageNotFoundExceptionHandler::render($e);
        } catch (Exception $e) {
            $result->appendChild(new XMLElement('error', $e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile()));
            return $result;
        }

        if ($this->_force_empty_result) {
            $result = $this->emptyXMLSet();
        }

        if ($this->_negate_result) {
            $result = $this->negateXMLSet();
        }

        return $result;
    }
}