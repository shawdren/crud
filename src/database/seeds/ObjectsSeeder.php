<?php namespace Laravella\Crud;

use Laravella\Crud\Log;
use \Seeder;
use \DB;

/**
 * @deprecated Use _db_pages
 */
class SeedObjects extends CrudSeeder {
    
    public function run()
    {
        DB::table('_db_objects')->delete();
        
    }

}
?>