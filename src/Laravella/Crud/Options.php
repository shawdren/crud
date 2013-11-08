<?php

namespace Laravella\Crud;

use \DB;

/**
 * Description of Options
 *
 * @author Victor
 */
class Options {

    public static function get($name, $type=null)
    {
        $setting = '';
        $option = DB::table('_db_options as o');
        if (!is_null($type) && !empty($type)) {
                $option = $option->join('_db_option_types as ot', 'ot.id', '=', 'o.option_type_id')
                ->where('ot.name', $type);
        }
        $option = $option->where('o.name', $name)
        ->select('o.value')
        ->first();
        if (is_object($option))
        {
            $setting = $option->value;
        }
        return $setting;
    }

    /**
     * 
     * @return type
     */
    public static function getSkin() {
        $skinFront = Options::get('skin', 'frontend');
        $skinAdmin = Options::get('skin', 'admin');
        $skinA = explode('::', $skinFront);
        $adminSkinA = explode('::', $skinAdmin);
        $skinName = $skinA[1];
        $adminSkinName = $adminSkinA[1];
        
        $skin = array('admin'=>$skinAdmin, 
            'frontend'=>$skinFront,
            'name'=>$skinName,
            'adminName'=>$adminSkinName);        
        
        return $skin;
    }
    
    public static function getTypes($name)
    {
        $options = new Options();
        return $options->__getTypes($name);
    }

    /**
     * Get type tree
     * 
     * @param type $name
     */
    private function __getTypes($name)
    {
        if (empty($name))
        {
            return null;
        }
        $typeA = array();

        $options = DB::table('_db_option_types as pot')
                ->join('_db_option_types as cot', 'cot.parent_id', '=', 'pot.id')
                ->select('cot.name as cname', 'pot.name as pname')
                ->where('pot.name', '=', $name)
                ->get();

        if (is_array($options))
        {
            foreach ($options as $option)
            {
                $val = $this->getTypes($option->cname);
                if (!empty($val))
                {
                    $typeA[$option->cname] = $val;
                }
                else
                {
                    $typeA[$option->cname] = $option->cname;
                }
            }
        }

        return $typeA;
    }

    /**
     * 
     * @param type $types
     * @return type
     */
    private function __getValues($types)
    {
        $values = array();
        foreach ($types as $name => $type)
        {
            if (is_array($type) && count($type > 0))
            {
                $values[$name] = $this->__getValues($type);
            }
            else
            {
                $values[$name] = $this->getByType($name);
            }
        }
        return $values;
    }

    /**
     * 
     * @param type $types
     * @return type
     */
    private static function getValues($types)
    {
        $options = new Options();
        return $options->__getValues($types);
    }

    /**
     * 
     * @param type $name
     * @return type
     */
    public static function getByType($name) {
        $options = DB::table('_db_options as o')
                ->join('_db_option_types as ot', 'ot.id', '=', 'o.option_type_id')
                ->select('ot.name as type', 'o.name as option', 'o.value as value')
                ->where('ot.name', '=', $name)
                ->get();
        
        $values = array();
        foreach($options as $option) {
            $values[$option->option] = $option->value;
        }
        
        return $values;
        
    }
    
    /**
     * Get all options of a certain type
     * 
     * @param type $name
     * @return type
     */
    public static function getType($name)
    {

        $types = static::getTypes($name);

        $values = static::getValues($types);

        return $values;
    }

}

?>
