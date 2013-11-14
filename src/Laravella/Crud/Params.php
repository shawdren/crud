<?php namespace Laravella\Crud;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use \Table;

/**
 * 
 * Used to pass a consistent set of data to views and prevent "$variable not found" errors.
 * 
 */
class Params extends CrudSeeder {

    public $action = "";
    public $tableMeta = null;
    public $tables = null;
    public $dataA = array();
    public $paginated = null;
    public $primaryTables = array();
    public $prefix = "";
    public $page = null;
    public $contents = array();
    public $assets = null;
    public $view = null;
    public $frontend = false;
    public $skin = null;
    public $selects = array();
    public $log = array();
    public $status = "success";
    public $slug = "";
    public $displayType = "text/html";
    public $displayTypes = array();
    public $widgetTypes = array();
    public $menu = array();
    public $user = null;

    /**
     * $slug pageslug
     * 
     * @param type $slug
     */
    public static function bySlug($frontend, $slug, $view) {
        $params = new Params($frontend, null, null, $view);
        $pageA = Table::asArray('contents', array('slug'=>$slug));
        
        $params->contents = $pageA;
        $params->view = $view;
        return $params->asArray();
    }
    
    /**
     * 
     * Used to pass a consistent set of data to views and prevent "$variable not found" errors.
     * 
     * 
     * @param type $status Is this a used for frontend or backend
     * @param type $status Wether the action succeeded or not.  See log for further details.
     * @param type $action the action that controller is performing. See _db_actions.name 
     * @param type $tableMeta The table's meta data. As generated by Laravella\Crud\Table::getTableMeta()
     * @param type $tables Is an array of Table objects. Actual data.
     * @param type $pageSize. The size of the pagination.
     * @param type $primaryTables A list of records with primary keys related to this table's via foreign keys.
     * @param type $prefix Used to prepend the href on the primary key
     * @param type $view An entry in _db_views
     */
    public function __construct($frontend = false, $status='success', $message='', $log=array(), 
            $view = null, $action = "", $tableMeta = null, 
            $tableActionViews = null, $prefix = "", $selects = null, $displayType = "", $dataA = array(), 
            $tables = array(), $paginated = array(), $primaryTables = array())
    {
        $this->user = Auth::user();
        $this->status = $status;
        $this->message = $message;
        $this->action = $action;
        $this->tableMeta = $tableMeta;
        if (is_object($view))
        {
            $this->pageSize = $view->page_size;
        }
        else
        {
            $this->pageSize = 10;
        }
        $this->prefix = $prefix;
        $this->page = $tableActionViews;  //single, called by first()
        $this->view = $view;
        $this->frontend = $frontend;

        $skins = Options::getSkin();
        $this->skin = array();
        
        if ($frontend) {
            $this->skin['name'] = $skins['name'];
            $this->skin['fullname'] = $skins['frontend'];
        } else {
            $this->skin['name'] = $skins['adminName'];
            $this->skin['fullname'] = $skins['admin'];
        }
        
        $this->selects = $selects;
        $this->displayType = $displayType;
        $this->log = $log;
        //potentially null
        $this->paginated = $paginated;
        $this->tables = $tables;
        $this->primaryTables = $primaryTables;
        $this->assets = $this->__getAssets();
        $this->dataA = $dataA;
        $this->displayTypes = $this->__getDisplayTypes();
        $this->widgetTypes = $this->__getWidgetTypes();

        if (Auth::check())
        {
            $userId = Auth::user()->id;
            $this->menu = $this->getMenu($userId);
        }
    }

    /**
     * 
     */
    private function __getAssets() {
        $assetsA = array();

        $assetType = "default";
        
        $pot = Options::get('skin').".dbview";
        
        if (isset($this->page) && is_object($this->page))
        {
            $pot = $this->page->view_name;
        }
        
        $assets = DB::table('_db_page_assets as pa')
        ->join('_db_option_types as pot', 'pot.id', '=', 'pa.page_type_id')
        ->join('_db_option_types as aot', 'aot.id', '=', 'pa.asset_type_id')
        ->join('_db_assets as a', 'a.asset_type_id', '=', 'pa.asset_type_id')
        ->join('_db_pages as p', 'p.page_type_id', '=', 'pa.page_type_id')
        ->select('pa.id', 'pa.page_type_id', 'pa.asset_type_id', 'a.id', 
        'p.id', 'aot.name', 'pot.name', 'a.url', 'a.vendor', 'a.type', 'a.version', 
        'a.position', 'p.action_id', 'p.view_id', 'p.object_id', 'p.page_size', 
                'p.title', 'p.slug')
        /*->where('pot.name', $pot)*/  //TODO : had to comment this out because upload isn't properly linked to assets and pages
        ->where('p.slug', '_db_actions_getselect')
        ->get();

//        $q = \Laravella\Crud\DbGopher::getLastQuery();
//        echo var_dump($q);
        
        foreach($assets as $asset) {
            $assetsA[] = array('url'=>$asset->type."/".$asset->url, 'type'=>$asset->type, 'position'=>$asset->position);
        }

//        echo var_dump($assetsA);
//        die; 
        
        return $assetsA;
    }
    
    /**
     * get all entries from _db_display_types
     * This determines under which conditions a field will be displayed
     * 
     * @return type
     */
    private function __getDisplayTypes() {
        $displayTypes = DB::table('_db_display_types')->get();
        $dtA = array();
        foreach($displayTypes as $displayType) {
            $dtA[$displayType->id] = $displayType->name;
        }
        return $dtA;
    }
    
    /**
     * get all entries from _db_widget_types
     * This determines how a field will be displayed, what it will look like
     * 
     * @return type
     */
    private function __getWidgetTypes() {
        $widgetTypes = DB::table('_db_widget_types')->get();
        $dtA = array();
        foreach($widgetTypes as $widgetType) {
            $dtA[$widgetType->id] = $widgetType->name;
        }
        return $dtA;
    }
    
    /**
     * 
     * @param type $userId
     * @return type
     */
    public static function getUserMenu ($userId = null) {
        if ($userId == null) {
            $userId = Auth::user()->id;
        }
        
        $menus = DB::table('users as u')->join('usergroups as ug', 'u.usergroup_id', '=', 'ug.id')
                ->join('_db_menu_permissions as mp', 'mp.usergroup_id', '=', 'ug.id')
                ->join('_db_menus as m', 'm.id', '=', 'mp.menu_id')
                ->join('_db_menus as m2', 'm2.parent_id', '=', 'm.id')
                ->where('u.id', '=', $userId)
                ->select('u.username', 'ug.group', 
                        'm.id', 'm.icon_class', 'm.label', 'm.href', 'm.parent_id', 
                        'm2.id as m2_id', 'm2.icon_class as m2_icon_class', 'm2.label as m2_label', 
                        'm2.href as m2_href', 'm2.parent_id as m2_parent_id')->get();


        
        $menuA = array();
        foreach($menus as $menu) {
            if (!isset($menuA[$menu->label])) {
                $menuA[$menu->label] = array();
            }
            $menuA[$menu->label][] = array('username'=>$menu->username, 'group'=>$menu->group, 
                'id'=>$menu->id, 'icon_class'=>$menu->icon_class, 'label'=>$menu->label, 
                'href'=>$menu->href, 'parent_id'=>$menu->parent_id, 'm2_id'=>$menu->m2_id, 
                'm2_icon_class'=>$menu->m2_icon_class, 'm2_label'=>$menu->m2_label, 
                'm2_href'=>$menu->m2_href, 'm2_parent_id'=>$menu->m2_parent_id);
        }
        return $menuA;
    }
    
    /**
     * Build a menu array from _db_menus
     * 
     * @param type $userId
     * @return type
     */
    public function getMenu($userId=null)
    {
        return static::getUserMenu($userId);
    }

    /**
     * Instantiate a Params object to use for Editing
     * 
     * @param type $status
     * @param type $message
     * @param type $log
     * @param type $view
     * @param type $action
     * @param type $tableMeta
     * @param type $tableActionViews
     * @param type $prefix
     * @param type $selects
     * @param type $tables
     * @param type $paginated
     * @param type $primaryTables
     * @return \Laravella\Crud\Params
     */
    public static function forEdit($status = "success", $message = "", $log = array(), $view = null, $action = "", $tableMeta = null, $tableActionViews = null, $prefix = "", $selects = null, $displayType = "text/html", $tables = null, $paginated = null, $primaryTables = null)
    {
        $params = new Params(false, $status, $message, $log);
        return $params;
    }

    /*
     * meta
     * data
     * name
     * pagesize
     * selects
     */
    public function asArray()
    {
        $returnA = array("action" => $this->action,
            "meta" => $this->tableMeta['fields_array'],
            "tableName" => $this->tableMeta['table']['name'],
            "prefix" => $this->prefix,
            "pageSize" => $this->pageSize,
            "view" => $this->view,
            "frontend" => $this->frontend,
            "skin" => $this->skin,
            "slug" => $this->slug,
            "selects" => $this->selects,
            "contents" => $this->contents,
            "log" => array(), //too big
            "status" => $this->status,
            "message" => $this->message,
            "pkName" => $this->tableMeta['table']['pk_name'],
            "displayType" => $this->displayType,
            "tables" => $this->tables,
            "data" => $this->paginated,
            "dataA" => $this->dataA,
            "pkTables" => $this->primaryTables,
            "menu" => $this->menu,
            "assets" => $this->assets,
            "displayTypes" => $this->displayTypes,
            "widgetTypes" => $this->widgetTypes,
            "user" => $this->user
        ); //$this->tables[$tableName]['tableMetaData']['table']['pk_name']);

        if (isset($this->page) && is_object($this->page))
        {
            $returnA["title"] = $this->page->title;
        }
        else
        {
            $returnA["title"] = "";
        }

        if (Options::get('debug')){
            $returnA['params'] = json_encode($returnA);
        }

        return $returnA;
    }

}

?>
