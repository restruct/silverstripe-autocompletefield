<?php

namespace Restruct\SS\AutoComplete;

//use PhpOffice\PhpWord\Element\Object;

class TextField extends \TextField {

    private static $allowed_actions = array(
        'Suggest'
    );

    /**
     * Name of the class this field searches.
     *
     * @var string
     */
    private $sourceClass;

    /**
     * The field to use for searching for suggestions
     *
     * @var string
     */
    protected $searchFields = array('Title');

    /**
     * The field to use as value/save to database
     *
     * @var string
     */
    protected $displayField = 'Title';

    /**
     * Limit the amount of suggestions
     *
     * @var int
     */
    private $limit = 20;

    /**
     * Configuration for the JS component ()
     *
     * @var string
     */
    protected $js_config = array(

    );

    /**
     * @return string
     */
    function Type() {
        return 'autocomplete-text text';
    }

    /**
     * Set the class from which to get Autocomplete suggestions
     *
     * @param string $className The name of the source class
     *
     * @return static
     */
    public function setSourceClass($className) {
        $this->sourceClass = $className;

        return $this;
    }

    /**
     * Get the class which is used for Autocomplete suggestions
     *
     * @return string The name of the source class.
     */
    public function getSourceClass() {
        return $this->sourceClass;
    }

    /**
     * Set the field to search for Autocomplete suggestions
     *
     * @param string/array $fields The name of the source field.
     *
     * @return static
     */
    public function setSearchFields($fields) {
        $this->searchFields = is_array($fields) ? $fields : array($fields);

        return $this;
    }

    public function setSearchField($field){
        return $this->setSearchFields($field);
    }

    /**
     * Get the field which is used for Autocomplete suggestions
     *
     * @return array|string
     */
    public function getSearchFields() {
        return $this->searchFields;
    }


    /**
     * Set the field to show as suggestion/value
     *
     * @param string $field
     *
     * @return static
     */
    public function setDisplayField($field) {
        $this->displayField = $field;

        return $this;
    }

    /**
     * Get the field to show as suggestion/value
     *
     * @return string The name of the field.
     */
    public function getDisplayField() {
        return $this->displayField;
    }

    /**
     * @param int $limit
     *
     * @return static
     */
    public function setLimit($limit) {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }


    /**
     * Set the config for the JS component
     *
     * @param array $js_conf
     *
     * @return $this
     */
    public function setJSConfig($js_conf) {
        if(!is_array($js_conf)) {
            return user_error("setJSConfig needs array", E_USER_ERROR);
        }
        // basic namespache config options: data-jsconf-[option]
        foreach($js_conf as $key => $val){
            $this->js_config[$key] = $val;
        }

        return $this;
    }

    /**
     * Get the config for the JS component
     *
     * @return array JS config
     */
    public function getJSConfig() {
        return $this->js_config;
    }


    /**
     * @return null|string
     */
    protected function determineSourceClass() {
        if($sourceClass = $this->sourceClass) {
            return $sourceClass;
        }

        $form = $this->getForm();

        if(!$form) {
            return null;
        }

        $record = $form->getRecord();

        if(!$record) {
            return null;
        }

        return $record->ClassName;
    }

    /**
     * @return array
     */
    function getAttributes() {
        return array_merge(
            parent::getAttributes(),
            array(
                'data-jsconfig' => json_encode(
                    array_merge(
                        array(
                            'serviceUrl' => parse_url($this->Link(), PHP_URL_PATH) . '/Suggest',
                            'autoSelectFirst' => 'true',
                        ),
                        $this->getJSConfig() // overrides previous array if same keys
                    )
                )
            )
        );
    }

    public static function setRequirements(){

        \Requirements::javascript(AUTOCOMPLETE_TEXTFIELD_DIR . '/bower_components/devbridge-autocomplete/dist/jquery.autocomplete.js');

        $init = <<<JS
        
// switched to entwine for dynamic initiation
(function($) {
    $.entwine(function($) {
        $('input.autocomplete-text').entwine({
            onmatch: function() {
                this._super();
                
                $(this).devbridgeAutocomplete( $(this).data('jsconfig') );
                $(this).devbridgeAutocomplete().setOptions({'onSelect':function(){ $(this).trigger('change'); }});
                $(this).devbridgeAutocomplete().setOptions({'autoFocus':true});
            }
        });
    });
})(jQuery);
        
// $('input.autocomplete-text').each(function(){
//     // console.log($(this).data('jsconfig'));
//     // $(this).autocomplete( $(this).data('jsconfig') );
//     $(this).devbridgeAutocomplete( $(this).data('jsconfig') );
//     $(this).devbridgeAutocomplete().setOptions({'onSelect':function(){ $(this).trigger('change'); }});
// });

JS;
        \Requirements::customScript($init, 'AutocompleteTextfieldInit');
    }

    function Field($properties = array()) {

        // init script for this field
        self::setRequirements();

        return parent::Field($properties);
    }

    /**
     * Handle a request for an Autocomplete list.
     *
     * @param SS_HTTPRequest $request The request to handle.
     *
     * @return string A JSON list of items for Autocomplete.
     */
    public function Suggest(\SS_HTTPRequest $request) {
        // Find class to search within
        $sourceClass = $this->determineSourceClass();

        if(!$sourceClass) {
            return;
        }

        // input
        $q = $request->getVar('query');
        $limit = $this->getLimit();

        $filters = array();

        foreach(preg_split('/[\s,]+/', $q) as $keyword) {
            foreach($this->getSearchFields() as $searchField) {
                $filters["{$searchField}:PartialMatch"] = $keyword;
            }
        }

        // Generate query
        $suggestions = \DataList::create($sourceClass)
            ->filterAny($filters)
            ->sort($this->displayField)
            ->limit($limit);

        // generate items from result
        $resultlist = array();
        foreach($suggestions as $suggestion) {
            $resultlist[] = $suggestion->{$this->displayField};
        }

        $return = new \stdClass();
        $return->suggestions = array_values( array_unique($resultlist) );

        // the response body
        return json_encode($return);
    }

}