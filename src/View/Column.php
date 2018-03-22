<?php
namespace Table\View;

use Cake\View\Helper;
use Cake\Collection\Collection;
use Cake\View\StringTemplateTrait;
use Cake\Core\InstanceConfigTrait;
use Cake\View\HelpersRegistry;

class Column 
{

    use StringTemplateTrait;
    use InstanceConfigTrait;
    
    protected $_field = '';
    
    protected $_path = '';
    
    protected $_title = '';
    
    protected $_modelName = '';
    
    protected $_type = 'string';
    
    protected $_sort = '';
    
    protected $_defaultConfig = [
        'templates' => [
            'cell' => '<td>{{value}}</td>',
            'header' => '<th>{{title}}</th>',
        ]
    ];
    
    protected $_headerTagOptions = [];
    
    protected $_tagOptions = [];
    
    public function __construct()
    {
        $this->templates($this->_defaultConfig['templates']);
       
    }


    public function setTitle($title) {
        $this->_title = $title;
    }
    
    public function setPath($path) {
        $this->_path = $path;
        if($path)
        {
            if (strpos($path,'.') !== false)
            {
                list($modelName, $field) = explode('.', $field); 
                $modelName = Inflector::Camelize($modelName);
                $model = Inflector::Pluralize($modelName); 
                $this->_modelName = $model;
                $this->_field = $field;
                $this->_sort = $model.".".$field;
            }
            else
            {
                $this->_field = $path;
                $this->_sort = $path;
            }
        }
    }

    public function header()
    {
//         $this->Paginator = \Cake\Core\ObjectRegistry::load('Cake\View\Helper\PaginatorHelper');
//        $title = $this->Paginator->sort($this->_title);
//        return $this->formatTemplate('header', ['title' => $title]);
    }
    
    public function render($row)
    {
        return $this->format('cell', ['value' => 'test']);
    }
    
    
    
    
}