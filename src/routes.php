<?php

Route::filter('crudauth', function()
        {
            if (Request::ajax())
            {
                if (Auth::guest())
                {
                    App::abort(403);
                }
            }

            if (Auth::guest())
                return Redirect::to('/admin/login');
        });
        
Route::when('db/*', 'crudauth');
Route::when('dbapi/*', 'crudauth');
Route::when('dbupload/*', 'crudauth');
//Route::when('dbinstall/*', 'crudauth');

Route::controller('db', 'DbController');
Route::controller('dbapi', 'DbApiController');
//Route::controller('dbinstall', 'DbInstallController');
Route::controller('dbupload', 'DbUploadController');

?>
