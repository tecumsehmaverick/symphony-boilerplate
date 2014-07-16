<?php

require_once TOOLKIT . '/class.datasource.php';

class datasourcestructure_all extends SectionDatasource
{
    public $dsParamROOTELEMENT = 'structure-all';
    public $dsParamORDER = 'asc';
    public $dsParamPAGINATERESULTS = 'no';
    public $dsParamLIMIT = '20';
    public $dsParamSTARTPAGE = '1';
    public $dsParamREDIRECTONEMPTY = 'no';
    public $dsParamSORT = 'order';
    public $dsParamASSOCIATEDENTRYCOUNTS = 'no';
    public $dsParamCACHE = '0';

    public $dsParamFILTERS = array(
        '10' => 'yes',
    );

    public $dsParamINCLUDEDELEMENTS = array(
        'slug',
        'banner'
    );
    
    public $dsParamINCLUDEDASSOCIATIONS = array(
        'banner' => array(
            'section_id' => '1',
            'field_id' => '3',
            'elements' => array(
                'caption',
                'position',
                'image'
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
            'name' => 'Structure: All',
            'author' => array(
                'name' => 'Jonathan Simcoe',
                'website' => 'http://symphony.dev',
                'email' => 'jdsimcoe@gmail.com'),
            'version' => 'Symphony 2.5.0beta1',
            'release-date' => '2014-07-16T18:22:48+00:00'
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