<?php
namespace Table\View\Helper;

use Cake\View\Helper;
use Cake\Collection\Collection;
use Cake\View\StringTemplateTrait;
use Cake\View\HelperRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


class TableHelper extends Helper
{
    
    use StringTemplateTrait;
     
    public $helpers = ['Html', 'Paginator', 'Session', 'Form', 'Table.Cell'];
    
    protected $_id = '';
    
    public $addedRow = "";
    
    public $addedRowId = false;
    
    private $_spreadsheet;
    
    
    protected $_defaultConfig = [
        'stackedActions' => false,
        'xlsButton' => true,
        'starsButton' => false,
        'pdfButton'=> true,
        'paginate' => true,
        'id' => 'table_1',
        'checkboxes' => false,
        'columnChooser' => false,
        'totRow' => false,
        'checkKey' => 'id',
        'prepend' => '',
        'ajaxPaginate' => false
    ];
    
    protected $_default_actions = [
        'edit' => [
            'title' => '',
            'field' => 'id',
            'icon' => 'fa-pencil',
            'url' => [
                'action' => 'edit',
                'id',
            ],
            'linkTitle' => 'Modifica'
        ],
        'delete' => [
            'postlink' => true,
            'title' => '',
            'field' => 'id',
            'icon' => 'fa-trash-o',
            'url' => [
                'action' => 'delete',
                'id'
            ],
            'confirm' => "Sei sicuro di voler procedere con l'operazione?",
            'linkTitle' => 'Cancella'
        ],
        'admin_delete' => [
            'postlink' => true,
            'title' => '',
            'field' => 'id',
            'icon' => 'fa-trash-o',
            'url' => [
                'action' => 'delete',
                'id',
                'prefix' => 'admin'
            ],
            'confirm' => "Sei sicuro di voler procedere con l'operazione?",
            'linkTitle' => 'Cancella'
        ],
        'view' => [
            'title' => '',
            'field' => 'id',
            'icon' => 'fa-search',
            'url' => [
                'action' => 'view',
                'id'
            ],
            'linkTitle' => 'Mostra'
        ],
    ];
    
    
    // comment test test
    
    public function initialize(array $config) {
        
    }
    
    public function table($data, $columns, $actions = [], $options = [])
    {
        $this->Paginator->templates([
            'sortAsc' => '<a class="asc" href="{{url}}">{{text}}&nbsp;<i class="fa fa-caret-up"></i></a>',
            'sortDesc' => '<a class="desc" href="{{url}}">{{text}}&nbsp;<i class="fa fa-caret-down"></i></a>',
            'number' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
            'nextActive' => '<li class="next page-item"><a class="page-link" rel="next" href="{{url}}">{{text}}</a></li>',
            'nextDisabled' => '<li class="page-item next disabled"><a class="page-link" href="" onclick="return false;">{{text}}</a></li>',
            'prevActive' => '<li class="page-item prev"><a class="page-link" rel="prev" href="{{url}}">{{text}}</a></li>',
            'prevDisabled' => '<li class="page-item prev disabled"><a href="" class="page-link" onclick="return false;">{{text}}</a></li>',
            'counterRange' => '{{start}} - {{end}} of {{count}}',
            'counterPages' => '{{page}} of {{pages}}',
            'first' => '<li class="page-item first"><a class="page-link" href="{{url}}">{{text}}</a></li>',
            'last' => '<li class="page-item last"><a class="page-link" href="{{url}}">{{text}}</a></li>',
            'current' => '<li class="page-item active"><a class="page-link" href="">{{text}}</a></li>',
            'ellipsis' => '<li class="page-item ellipsis"><a class="page-link">&hellip;</a></li>',
            
        
        ]);
        
        if($this->_View->layout == 'xls_table')
        {
            $this->_spreadsheet = new Spreadsheet();
            $this->__prepareXls($data, $columns, $actions = [], $options = []);
            return;
        }
        $this->_id = $this->config('id');
        if($this->request->query('table_id') && $this->_id != $this->request->query('table_id'))
        {
            return;
        }
        if($this->config('paginate'))
        {
            //$this->Paginator->config('options.url.table_layout', '');
            $this->Paginator->config('options.url.?.table_layout', null);
                $this->Paginator->config('options.url.?.table_id', null);
//            $this->Paginator->templates([
//                'ellipsis' => '<li class="ellipsis"><a href="#"><strong>...</strong></a></li>'
//            ]);
        }
        $this->config($options);
        
        $this->__javascript();
        
        
        
        $return  = '';
        

        if($this->config('paginate'))
            $return .= $this->__pageNavigator($columns);
        $return .= '<table class="table  table-striped table-hover " id="table_'.$this->_id.'">';
        $table_content = $this->config('prepend');
        
        $table_content  .= '<thead>';
        $table_content .= $this->__headers($columns, $actions);
        $table_content .= '</thead>';
        $table_content .= '<tbody>';
        $table_content .= $this->__rows($data, $columns, $actions);
        if(!$this->addedRowId)
            $table_content .= $this->addedRow;
        $table_content .= '</tbody>';
        
        $return .= $table_content;
        $return .= '</table>';
        $return .= $this->__pageNumbers($this->config('paginate'));
        $this->_View->assign('table_content', $table_content);
        $this->_View->assign('paginated_table', $return);
        $return = '<div class="table_container" id ="'.$this->_id.'">'.$return;
        $return .= '</div>';
        
        return $return;
    }
    
    private function __getXLSHeaders($columns)
    {
        $col_num = 1;
        foreach ($columns as $key => $col)
        {
            
            $header = $this->Cell->XLSHeader($key, $col);
            $styleArray = [
                'font' => ['bold' => true,], 
                'borders' => [
                    'bottom' => ['borderstyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
                ], 
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
                    'color' => ['argb' => 'FFA0FF0A0']
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'wrap' => true
                    ], 
            ];
            $colWidth = 20;
            if(isset($col['xls_col_width']) && ! empty($col['xls_col_width']))
            {
                $colWidth = $col['xls_col_width'];
            }
            $this->_spreadsheet->getActiveSheet()->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_num))->setWidth($colWidth);
            $this->_spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col_num, 1)->applyFromArray($styleArray);
            $this->_spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col_num, 1, $header);
            $col_num++;
        }
        return $col_num-1;
    }
    
    
    private function __getXLSRows($data, $columns)
    {
        $rowNum = 1;
        foreach ($data as  $row)
        {
            $rowNum++;
            $colNum = 1;
            foreach ($columns as $col)
            {
                
                $PHPEcellCell = $this->_spreadsheet->getActiveSheet()->getCellByColumnAndRow($colNum, $rowNum);
                $this->Cell->XLSCell($row, $PHPEcellCell, $col);
                $colNum++;
            }
            
        }
        return $rowNum;
        
    }
    private function __prepareXls($data, $columns, $actions = [], $options = [])
    {
        
        
        
        
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder( new \PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder() );
        $this->_spreadsheet->getProperties()->setCreator("AOUI VR - Servizio Ingegneria Clinica");
        $this->_spreadsheet->setActiveSheetIndex(0);
        $this->_spreadsheet->getDefaultStyle()->getFont()->setName('Arial'); 
        $this->_spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $col_num = $this->__getXLSHeaders($columns);
        $row_num = $this->__getXLSRows($data, $columns);
        $this->_spreadsheet->getActiveSheet()->freezePane('B2');
        ob_start();
        $objWriter = new Xls($this->_spreadsheet);
        $objWriter->save('php://output');
        $xls_table_content = ob_get_clean();

        $this->_View->assign('xls_table_content', $xls_table_content);
            
    }
    
    
    private function __javascript()
    {
        $query = $this->request->query;
        if(isset($query['checkAll']))
            unset($query['checkAll']);
        if(isset($query['checked']))
            unset($query['checked']);
        $url = [
            'table_layout' => 'table',
            '_ext' => 'table',
            'table_id' => $this->_id,
            '?' => $query,
            
        ];
        
//        $url = [];
        $url = $this->Paginator->generateUrl($url, null, ['escape' => false]);
//        debug($url);
        /**
        $url = htmlspecialchars_decode($url);
//        debug($url);
        $url = Router::parse($url);
//        debug($url);
        unset($url['_matchedRoute']);
        if(isset($url['pass']))
        {
            $url = array_merge($url, $url['pass']);
            unset($url['pass']);
        }
//        
        $url['table_layout'] = 'table';
        $url['_ext'] = 'table';
        $url['table_id'] = $this->_id;
        
        if(isset($url['?']['checkAll']))
            unset($url['?']['checkAll']);
        if(isset($url['?']['checked']))
            unset($url['?']['checked']);

        $url = Router::url($url); */
//        debug($url);
        $this->Html->scriptStart(['block' => true]);
        ?>
            table_<?= $this->_id ?> = function() {
                return {
                    update: function() { 
                        $("#refresh_icon").addClass("fa-spin");
                        $.ajax(
                            {
                                dataType:"html", 
                                success:function (data, textStatus) {
                                    $("#<?= $this->_id ?>").html(data);
                                }, 
                                url:"<?= $url ?>"
                            }
                        );
                    },
                    sendAndUpdate: function() { 
                        $("#refresh_icon").addClass("fa-spin");
                        $.ajax(
                            {
                                dataType: "html", 
                                data: $("#table_<?= $this->_id ?>").closest("form").serialize(),
                                success: function (data, textStatus) {
                                    $("#<?= $this->_id ?>").html(data);
                                }, 
                                type: "get",
                                url: "<?= $url ?>"
                            }
                        );
                    },
                    doAction: function(link) { 
                        console.log(link);
                        $("#refresh_icon").addClass("fa-spin");
                        $.ajax(
                            {
                                dataType:"html", 
                                success:function (data, textStatus) {
                                    $("#<?= $this->_id ?>").html(data);
                                }, 
                                url:link + "<?='&table_layout=table&_ext=table&table_id='.$this->_id ?>"
                                
                            }
                        );
                    }
                    
                }
            }(); 
            
            $(document).ready(function () {
            
                
            
                $("#table_helper_refresh").bind(
                    "click", function (event) 
                    {
                        
                        table_<?= $this->_id ?>.update();
                        return false;
                });
                
                $(".record_checkbox").bind(
                    "change", function (event)    
                    {
                    if(event.target.checked)
                        $( event.target ).closest("tr").addClass("table-info");
                    else
                        $( event.target ).closest("tr").removeClass("table-info");
                });
                    
                $("#checkallcheckbox_<?=$this->_id?>").bind(
                    "click", function (event)    
                    {
                    
                        $('.record_checkbox').prop('checked' , event.target.checked);
                        $('.record_checkbox').trigger('change');
                        return true;
                });
                
                $("#paginator_next_<?=$this->_id?>").bind(
                    "click", function (event)    
                    {
                    
                        table_<?=$this->_id?>.doAction(event.originalEvent.srcElement.attributes.href.value);
                        return false;
                });
                
            });
        <?php
        
        
        
        $this->Html->scriptEnd();
    }
    
    private function __headers($columns, $actions)
    {
        $return = '';
        $return = "<tr>";
        
        if($this->config('checkboxes'))
        {
            $return .= '<th>'.$this->Form->checkbox("checkAll.checkAll",  ['class'=>'record_checkbox_check_all', 'id' => 'checkallcheckbox_'.$this->_id]).'</th>';
        }
        
        foreach ($columns as $key => $col)
        {
            if(isset($col['hidden']) && $col['hidden'])
            {
                $return .= '';
            }
            else
            {
                $return .= $this->Cell->header($key, $col, $this->config('paginate'));
            }
        }
        if(!empty($actions))
        {
            $return .= "<th>Azioni</th>";
        }
        $return .= "</tr>";
        return $return;
    }
    
    private function __rows($data, $columns, $actions)
    {
        $return = '';
        
        $checkKey = $this->config('checkKey');
        foreach ($data as $rowCounter => $row)
        {
            if($this->addedRow && $this->addedRowId)
            {
                $row_id = $row->id;
                if($row_id == $this->addedRowId)
                {
                    $return .= $this->addedRow;
                    continue;
                }
            }
            $class = '';
            $chechCell= '';
            if($rowCounter === $this->config('totRow'))
            {
                    $class = 'tot';
            }
            if($this->config('checkboxes'))
            {
                
                if($rowCounter === $this->config('totRow'))
                {
                    $chechCell .= '<td></td>';
                }
                else
                {
                    $check_id = $row->{$checkKey};

                    $checked  = false; // $this->checkDefault;
                    if (isset($this->request->query['checked'][$check_id]))
                        $checked = $this->request->query['checked'][$check_id];
                    if($checked) 
                        $class = "table-info";
                    $chechCell = '<td>'.$this->Form->checkbox("checked.$check_id",  [ 'class'=>'record_checkbox', 'hiddenField' => false,  'checked' => $checked]).'</td>';
                }

                
            }
            $return .= "<tr class='$class'>".$chechCell;
            foreach ($columns as $key => $col)
            {
                
                $return .= $this->Cell->cell($row, $col);
                
            }
            if(!empty($actions))
            {
                if($this->config('stackedActions')){
                    $return .= '<td class = "text-nowrap text-center">';
                    $return .= '<div class="btn-group-vertical ">';
                }
                else
                {
                    $return .= '<td class = "text-nowrap text-right">';
                    $return .= '<div class="btn-group">';
                }
                foreach ($actions as $key => $col)
                {
                    
                    if(is_string($col) && !empty($this->_default_actions[$col]) )
                        $col = $this->_default_actions[$col];
                        $col['linkClass'] = 'btn btn-outline-primary border-0 p-1';
                    $return .= $this->Cell->cell($row, $col, true);

                }
                $return .= '</div>';
                $return .= '</td>';
            }
            $return .= "</tr>";
        }
        return $return;
    }
    
    private function __pageNumbers($paginate)
    {
        $st = ""; 
        if($paginate)
        {   
            
            $this->Paginator->config('options.url.table_layout', '');
            $st .= "<div class='text-center'>";
            $st .= "<ul class = 'pagination  justify-content-center' >";
            $st .= $this->Paginator->prev('&laquo;', ['escape' => false]);
            $st .= $this->Paginator->numbers(['separator' => '', 'tag' => 'li' , 'currentClass'=>'active', 'currentTag'=>'a', 'first' => 'Prima',  'last' => 'Ultima']);
            $st .= $this->Paginator->next('&raquo;', ['escape' => false]);
            $st .= "</ul>";
            $st .= "</div>";
        }
        return $st;
    }
    
    private function __columnChooser($columns)
    {
        $st = '';
        $st .= '<div class="btn-group" role="group">';
        $st .= '<a class="btn btn-secondary  dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-table"></i>
            </a>';
        $st .= "<ul class='dropdown-menu' style='float: left'>";


        foreach($columns as $key => $column)
        {
             $checked = !(isset($column['hidden']) && $column['hidden']);
            if(isset($column['title']))
                $column = $column['title'];
            else
                $column = $key;
            if($column)
            {
                $input = $this->Form->checkbox('column_$column', ['checked' => $checked]);
                $st .= '<li><a>'.$input.'&nbsp;'.$column."</a></li>";
            }
        }
        $st .= "</ul>";
        $st .= "</div>";   
        return $st;
    }
    
    private function __pageNavigator($columns)
    {
        $st = "";
        
       
        
        $this->Paginator->config('options.url.table_layout', '');
        $url = $this->Paginator->config('options.url');
        
        if($this->config('beforePagination'))
            $st .= '<div class="pull-left">'.$this->config('beforePagination').'</div>';


        // SCELTA COLONNE
        
//        if($this->config('columnChooser')) 
//            $st .= $this->__columnChooser($columns);   
        
        
        // NUMERO RECORD PER PAGINA
        
        $params = $this->Paginator->params();
        $st .= '<div class="btn-toolbar justify-content-between">';
        $st .= '<div class="dropdown pull-left" >';
        $st .= '<button id="btnGroupDrop1" type="button" class="btn btn-light  dropdown-toggle" data-toggle="dropdown">
            '.$params['perPage'].' 
            </button>';
        $st .= "<div class='dropdown-menu'>";
        $st .= '<h6 class="dropdown-header">Righe per pagina</h6>';
        foreach([20, 50, 100, 1000] as $i)
        {
            $url_per_page = $this->Paginator->generateUrl(['limit' => $i, 'page' => null], null, ['escape' => false]);
            if(isset($params['perPage']) && $params['perPage'] == $i)
                $st .= "<a class='dropdown-item active'>$i</a>";
            else
                $st .=  $this->Html->link($i, $url_per_page, ['class'=>'dropdown-item']);
        }
        $st .= "</div>";
        $st .= "</div>";

        
        
        // ICONCINE
        $st .= "<div class='button-group pull-right' >";
        if($this->config('pdfButton'))
        {
            $img_pdf = '<i class="far fa-file-pdf" style="color:darkred"> </i>';
            $url_pdf = $this->Paginator->generateUrl(['table_layout' => 'pdf'], null, ['escape' => false]);
            $st .= $this->Html->link($img_pdf, $url_pdf, ['escape' => false, 'target' => 'alt', 'class'=>"btn btn-light "]);

        }
        if($this->config('xlsButton'))
        {
            $img_xls = '<i class="far fa-file-excel" style="color:darkgreen"> </i>';
            $url_xls = $this->Paginator->generateUrl(['table_layout' => 'xls'], null, ['escape' => false]);
            $st .= $this->Html->link($img_xls, $url_xls, ['escape' => false, 'class'=>"btn btn-light "]);

        }
        if($this->config('starsButton'))
        {
            $img_star = '<i class="far fa-star" style="color:darkgreen"> </i>';
            debug(Router::url());
            debug($this->request);
            $url_star = ['controller' => 'Favorites', 'action' => 'add', 'address' => $this->request->here()];
            $st .= $this->Html->link($img_star, $url_star, ['escape' => false, 'class'=>"btn btn-light "]);

        }
        

        if(true)
        {
            $img_reload = '<i class="fas fa-sync" id="refresh_icon"> </i>';
            $url_reload = $url;
            $st .= $this->Html->link($img_reload, $url_reload, ['escape' => false, 'id' => 'table_helper_refresh', 'class'=>"btn btn-light"]);

        }
        $st .= "</div>";
        $st .= '</div>';
        // NAVIGAZIONE
        $st .= "<ul class='pagination  justify-content-center' >"; 
        $st .= $this->Paginator->prev('&laquo;', ['escape' => false]);
        $st .= '<li class="page-item"><a class="page-link">';
        $st .= $this->Paginator->counter('Record da {{start}} a {{end}} di {{count}}'); 
        $st .= "</a></li>";
        $st .= $this->Paginator->next('&raquo;', ['escape' => false]);
        $st .= "</ul>";
        return $st;
    }
    
    public function backLink($action, $controller = null)
    {
        $back_url = $this->request->referer();
        $link = $this->Html->link(
            '<i class="fa fa-arrow-left"></i>',
            $back_url, 
            ['class' => 'button btn btn-secondary', 'escape' => false]
        );
        return $link;
    }
}


?>
