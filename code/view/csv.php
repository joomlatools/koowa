<?php
/**
 * Kodekit - http://timble.net/kodekit
 *
 * @copyright   Copyright (C) 2007 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     MPL v2.0 <https://www.mozilla.org/en-US/MPL/2.0>
 * @link        https://github.com/timble/kodekit for the canonical source repository
 */

namespace Kodekit\Library;

/**
 * Csv View
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\View
 */
class ViewCsv extends ViewAbstract
{
    /**
     * Character used for quoting
     *
     * @var string
     */
    public $quote = '"';

    /**
     * Character used for separating fields
     *
     * @var string
     */
    public $separator = ',';

    /**
     * End of line
     *
     * @var string
     */
    public $eol = "\n";

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   ObjectConfig $config Configuration options
     * @return  void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $config->append(array(
            'behaviors'   => array('routable'),
            'disposition' => 'inline',
            'quote'       => '"',
            'separator'   => ',',
            'eol'         => "\n"
        ));

        parent::_initialize($config);
    }

    /**
     * Return the views output
     *
     * @param ViewContext  $context A view context object
     * @return string   The output of the view
     */
    protected function _actionRender(ViewContext $context)
    {
        $rows    = '';
        $columns = array();

        // Get the columns
        foreach($context->entity as $entity)
        {
            $data    = $entity->toArray();
            $columns = array_merge($columns + array_flip(array_keys($data)));
        }

        // Empty the column values
        foreach($columns as $key => $value) {
            $columns[$key] = '';
        }

        //Create the rows
        foreach($context->entity as $entity)
        {
            $data = $entity->toArray();
            $data = array_merge($columns, $data);

            $rows .= $this->_arrayToString(array_values($data)).$this->eol;
        }

        // Create the header
        $header = $this->_arrayToString(array_keys($columns)).$this->eol;

        return $header.$rows;
    }

    /**
     * Render
     *
     * @param   array   $data Value
     * @return  boolean
     */
    protected function _arrayToString($data)
    {
        $fields = array();
        foreach($data as $value)
        {
            //Cast objects to string
            if(is_object($value))
            {
                if(method_exists($value, '__toString')) {
                    $value = (string) $value;
                } else {
                    $value = null;
                }
            }

            //Implode array's
            if(is_array($value))
            {
                if(is_numeric(key($value))) {
                    $value = implode(',',$value);
                } else {
                    $value = json_encode($value);
                }
            }

             // Escape the quote character within the field (e.g. " becomes "")
            if ($this->_quoteValue($value))
            {
                $quoted_value = str_replace($this->quote, $this->quote.$this->quote, $value);
                $fields[] 	  = $this->quote . $quoted_value . $this->quote;
            }
            else $fields[] = $value;
        }

        return  implode($this->separator, $fields);
    }

    /**
     * Check if the value should be quoted
     *
     * @param   string  $value Value
     * @return  boolean
     */
    protected function _quoteValue($value)
    {
        if(is_numeric($value)) {
            return false;
        }

        if(strpos($value, $this->separator) !== false) { // Separator is present in field
            return true;
        }

        if(strpos($value, $this->quote) !== false) { // Quote character is present in field
            return true;
        }

        if (strpos($value, "\n") !== false || strpos($value, "\r") !== false ) { // Newline is present in field
            return true;
        }

        if(substr($value, 0, 1) == " " || substr($value, -1) == " ") {  // Space found at beginning or end of field value
            return true;
        }

        return false;
    }
}
