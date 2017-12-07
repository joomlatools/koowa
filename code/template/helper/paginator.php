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
 * Paginator Template Helper
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Kodekit\Library\Template\Helper
 */
class TemplateHelperPaginator extends TemplateHelperSelect
{
    /**
     * Render item pagination
     *
     * @see     http://developer.yahoo.com/ypatterns/navigation/pagination/
     *
     * @param   array   $config An optional array with configuration options
     * @return  string  Html
     */
    public function pagination($config = array())
    {
        $config = new ModelPaginator($config);
        $config->append(array(
            'url'        => null,
            'total'      => 0,
            'display'    => 4,
            'offset'     => 0,
            'limit'      => 0,
            'attribs'    => array(),
            'show_limit' => true,
            'show_count' => true,
            'page_rows'  => array(10, 20, 50, 100)
        ))->append(array(
            'show_pages' => $config->count !== 1
        ));

        $translator = $this->getObject('translator');

        // Do not show pagination when $config->limit is lower then $config->total
        if($config->total > $config->limit)
        {
            $html = '';
            if($config->show_limit) {
                $html .= '<div class="pagination__limit">'.$translator->translate('Display NUM').' '.$this->limit($config).'</div>';
            }

            if($config->show_pages) {
                $html .=  $this->pages($config);
            }

            if($config->show_count) {
                $html .= '<div class="pagination__count"> '.$translator->translate('Page').' '.$config->current.' '.$translator->translate('of').' '.$config->count.'</div>';
            }
            return $html;
        }
        return false;
    }

    /**
     * Render a select box with limit values
     *
     * @param   array|ObjectConfig     $config An optional array with configuration options
     * @return  string  Html select box
     */
    public function limit($config = array())
    {
        $config = new ObjectConfigJson($config);
        $config->append(array(
            'limit'     => 0,
            'attribs' => array('class' => 'k-form-control'),
            'values'  => array(5, 10, 15, 20, 25, 30, 50, 100)
        ));

        $html     = '';
        $selected = 0;
        $options  = array();
        $values   = ObjectConfig::unbox($config->values);

        if ($config->limit && !in_array($config->limit, $values)) {
            $values[] = $config->limit;
            sort($values);
        }

        foreach($values as $value)
        {
            if($value == $config->limit) {
                $selected = $value;
            }

            $options[] = $this->option(array('label' => $value, 'value' => $value));
        }

        if ($config->limit == $config->total) {
            $options[] = $this->option(array('label' => $this->getObject('translator')->translate('All'), 'value' => 0));
        }

        $html .= $this->optionlist(array('options' => $options, 'name' => 'limit', 'attribs' => $config->attribs, 'selected' => $selected));
        return $html;
    }

    /**
     * Render a list of pages links
     *
     * @param   array   $config An optional array with configuration options
     * @return  string  Html
     */
    public function pages($config = array())
    {
        $config = new ModelPaginator($config);
        $config->append(array(
            'url'      => null,
            'total'    => 0,
            'display'  => 2,
            'offset'   => 0,
            'limit'    => 0,
            'show_limit' => true,
            'show_count' => false
        ))->append(array(
            'show_pages' => $config->count !== 1
        ));

        $html = '<div class="k-pagination">';

        if($config->offset) {
            $html .= $this->page($config->pages->prev, $config->url);
        }

        foreach($config->pages->offsets as $offset) {
            $html .= $this->page($offset, $config->url);
        }

        if($config->total > ($config->offset + $config->limit)) {
            $html .= $this->page($config->pages->next, $config->url);
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Render a page link
     *
     * @param   ObjectConfigInterface  $page The page data
     * @param   HttpUrlInterface       $url  The base url to create the link
     * @return  string  Html
     */
    public function page(ObjectConfigInterface $page, HttpUrlInterface $url)
    {
        $page->append(array(
            'title'   => '',
            'current' => false,
            'active'  => false,
            'offset'  => 0,
            'limit'   => 0,
            'rel'      => '',
            'attribs'  => array(),
        ));

        //Set the offset and limit
        $url->query['limit']  = $page->limit;
        $url->query['offset'] = $page->offset;

        $rel   = !empty($page->rel) ? 'rel="'.$page->rel.'"' : '';

        $html = '<li '.$this->buildAttributes($page->attribs).'>';
        $html .= '<a href="'.$url.'" '.$rel.'>'.$this->getObject('translator')->translate($page->title).'</a>';
        $html .= '</li>';

        return $html;
    }
}
