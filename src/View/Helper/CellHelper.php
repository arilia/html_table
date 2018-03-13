<?php
namespace Table\View\Helper;

use Cake\View\Helper;
use Cake\Collection\Collection;
use Cake\ORM\TableRegistry;
use Cake\View\StringTemplateTrait;
use Cake\Utility\Inflector;


class CellHelper extends Helper
{
    
    use StringTemplateTrait;
    
    public $helpers = ['Html', 'Paginator', 'Time', 'Number', 'Form', 'Js', 'Attachments.Attachment'];
    
    public $paginate = true;
    
    protected $_true_text = 'Sì';
    
    protected $_type = '';
    
    protected $_false_text = 'No';
    
    protected $_td_options = [];
    
    protected $_defaultConfig = [
        'templates' => [
            'default' => '{{value}}',
            'icon' => '<i class="fa-fw fa {{value}}"></i>',
        ],
    ];
    
    public function header($key, $column, $paginate = true)
    {
        
        $header = '<th>';
        $title = $key;
        $sort = '';
        if(is_string($column))
            $field = $column;
        if(is_array($column))
        {
            $field = '';
            if(isset($column['title']))
                $title = $column['title'];
            if(isset($column['field']))
            {
                $field = $column['field'];
                
            }
            
            if(isset($column['sort']))
                $sort = $column['sort'];
            
        }

        if($title)
        {
            if (strpos($field,'.') !== false)
            {
                list($model, $field) = explode('.', $field); 
                $model = Inflector::Camelize($model);
                $model = Inflector::Pluralize($model); 
                $field = $model.".".$field;
            }
            if($field && $paginate  && $sort !== false)
            {
                $options = ['escape' => false];
                
                if(isset($column['default_direction']) && in_array($column['default_direction'], ['asc', 'desc', 'ASC', 'DESC']))
                    $options = ['direction' => $column['default_direction']];
                    
                $header .= $this->Paginator->sort($sort? $sort : $field, $title, $options);
            }
            else
                $header .= $title;
        }
        $header .= '</th>';
        return $header;
    }
    
    public function XLSHeader($key, $column)
    {
        $header = $key;
        if(is_array($column) && isset($column['title']))
        {
            $header = $column['title'];
        }
        return $header;
    }
    
    
    function XLSCell($row, $cell, $column)
    {
        
        $cell->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL);
        $cell->getStyle()->getAlignment()->setWrapText(true);
        $cell->getStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
        if(is_string($column))
            $path = $column;
        if(is_array($column))
        {
            $path = 'id';
            if(isset($column['field']))
                $path = $column['field'];
            
        }
        
        list($value, $entity, $step) = $this->_traverse($row, $path);

        $type = '';
        if(isset($column['type']) && ! empty($column['type']))
        {
            $type = $column['type'];
        }
        elseif(is_object($entity))
        {
            try {
                    $className =  $entity->source();
                    $entityTable = TableRegistry::get($className);
                    $type = $entityTable->schema()->baseColumnType($step);
            }
            catch(\Exception $e)
            {
                $type = '';
            }
        }
        
        switch ($type)
        {
            case 'boolean':
                if ($value)
                    $value = true;
                break;
            case 'date':
                if(!empty($value))
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( $value->getTimestamp() );
                $cell->getStyle()->getNumberFormat()->setFormatCode('dd.mm.yyyy');
                break;
            case 'datetime':
                if(!empty($value))
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( $value->getTimestamp() );
                $cell->getStyle()->getNumberFormat()->setFormatCode('dd.mm.yyyy');
                break;
            case 'currency':
                $cell->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                break;
            default:
                $value = strip_tags($value);
                break;
        }
        if(isset($column['xls_style']) && ! empty($column['xls_style']))
        {
            $cell->getStyle()->applyFromArray($column['xls_style']);
        }
        $cell->setValue($value);
    }
    
    public function cell($row, $column, $is_action = false)
    {
        if(isset($column['hidden']) && $column['hidden'])
            return '';
        $this->_td_options = [];
        $renderer = 'render';
        if(isset($column['renderer']) && is_string($column['renderer']))
        {
            $renderer = $column['renderer'];
        }
        $value = $this->{$renderer}($row, $column);
        
        if($is_action)
            return $value;
        else
            return $this->Html->tag('td', $value, $this->_td_options);
        
    }
    
    public function render($row, $column)
    {
        
        
        if(isset($column['element']))
        {
            $ret = $this->_View->element($column['element'], [
                'data' => $row
            ]);
            return $ret;
        }
        
        $template = 'default';
        $url = false;
        if(is_string($column))
            $path = $column;
        if(is_array($column))
        {
            $path = 'id';
            if(isset($column['field']))
                $path = $column['field'];
            if(isset($column['url']) && is_array($column['url']))
            {
                
                foreach($column['url'] as $key => $value)
                {
                    if($key === 'data')
                    {
                        foreach($value as $data_key => $data_value)
                        {
                            if($data_value && isset($row->{$data_value}))
                                $url['?'][$data_key] = $row->{$data_value};
                            else
                                $url['?'][$data_key] = $data_value;
                        }
                    }
                    else if(in_array($key, ['controller', 'action', 'prefix', 'plugin'], true))
                    {
                        $url[$key] = $value; 
                    }
                    else
                    {
                        list($param, $entity, $step) = $this->_traverse($row, $value);
                        if($param)
                            $url[] = $param;
                        else
                            $url[] = $value;
                    }
                }
                
            }
        }
        

        list($value, $entity, $step) = $this->_traverse($row, $path);

        $type = '';
        if(isset($column['type']) && ! empty($column['type']))
        {
            $type = $column['type'];
        }
        elseif(is_object($entity))
        {
            try{
                    $className =  $entity->source();
                    $entityTable = TableRegistry::get($className);
                    $type = $entityTable->schema()->baseColumnType($step);
            }
            catch(\Exception $e)
            {
                $type = '';
            }
        }
        
        $st = '';
        switch($type)
        {
            case 'boolean':
                $this->_td_options['class'] = 'text-center'; 
                if($value)
                {
                    $st =  isset($column['true_text']) ? $column['true_text'] : $this->_true_text ;
                }
                else
                {
                    $st =  isset($column['false_text']) ? $column['false_text'] : $this->_false_text ;
                }
            break;
            case 'date':
                if($value && is_object($value))
                {
                    $st = $value->format('d.m.Y');
                }
            break;
            case 'datetime':
                if($value)
                {
                    $st = $value->format('d.m.Y \a\l\l\e H:i:s');
                }
            break;
            case 'currency':
                if($value != 0)
                    $st = $this->Number->currency($value);
                $this->_td_options['class'] = " currency";
            break;
            case 'string_translate':
                $st = __($value);
            break;
            case 'integer':
                $st = $value;
            break;
            case 'icon':
//                $template = 'icon';
                $st = '<i class="fa-fw fa '.$value.'"></i>';
            break;
            case 'lookup':
                
                if(isset($column['lookup']) && is_array($column['lookup']))
                {
                    $lookup = $column['lookup'];
                    $st = $value;
                    if(isset($lookup[$value]))
                        $st = $lookup[$value];
                    
                }
            break;
            default: $st = $value;
        }
        
        $value = $st;
        if(isset($column['icon']))
            $value = '<i class="fa-fw fa '.$column['icon'].'"></i>';
        if(isset($column['text']))
            $value = $column['text'];
        if(!$value && isset($column['empty_string']))
            $value = $column['empty_string'];
        if($url)
        {
            
            $options = ['escape' => false];
            if(isset($column['linkClass']))
                    $options['class'] = $column['linkClass'];
//                debug($options);
            if(isset($column['confirm']))
                $options['confirm'] = $column['confirm'];
           
            if(isset($column['linkTitle']))
            {
                list($title, $entity, $step) = $this->_traverse($row, $column['linkTitle']);
                if(!$title)
                    $title = $column['linkTitle'];
                $options['title'] = $title;
            }
            if(isset($column['postlink']))
                $value = $this->Form->postLink($value, $url, $options);
            elseif(isset($column['ajaxlink']))
            {
                if(isset($column['update']))
                    $options['update'] = $column['update'];
                if(isset($column['complete']))
                    $options['complete'] = $column['complete'];
                if(isset($column['data']))
                    $options['data'] = $column['data'];
                if(isset($column['dataType']))
                    $options['dataType'] = $column['dataType'];
                if(isset($column['ajaxType']))
                    $options['type'] = $column['ajaxType'];
                
                $value = $this->Js->link($value, $url, $options);
            }
            else
                $value = $this->Html->link($value, $url, $options);
        }
 
       
        if(isset($column['options']) && !empty($column['options'])) 
        {
            if(isset($column['options']['class']) && isset($this->_td_options['class']))
                $column['options']['class'] .= ' '.$this->_td_options['class'];
            $this->_td_options = $column['options'];
        }
        
            $cell = $this->Html->tag('td', $value, $this->_td_options);
        
        return $value;
        
    }
    
    
    public function getType()
    {
        return $this->_type;
    }
    
    public function setType($type)
    {
        $this->_type =$type;
    }
    
    protected function _traverse($item, $path)
    {
        $value = '';
        $path = explode('.', $path);
        
        foreach($path as $step)
        {
            if(!empty($item))
            {
                $entity = $item;
                if(is_array($item))
                {
                    $item = '';
                    if(isset($entity[$step]))
                        $item = $entity[$step];
                    
                }
                elseif(is_object($item))
                {
                    $item = $entity->{$step};
                }
            }
            
        }
        if(!empty($item) || $item === 0)
        {
            
            $value = $item;
        }
        return [$value, $entity, $step];
    }
}


?>
