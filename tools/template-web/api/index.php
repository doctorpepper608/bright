<?php

br()->importLib('RESTBinder');
br()->importDataSource('users');

spl_autoload_register(function($className) {

  if (preg_match('#DataSource$#', $className)) {
    $fileName = dirname(__DIR__).'/datasources/'.$className.'.php';
    if (file_exists($fileName)) {
      require_once($fileName);
    }
  }

});

$rest = new BrRESTBinder();
$rest
  ->route(new BrRESTUsersBinder(new BrDataSourceUsers()))
  ->route( '/api/some'
         , 'SomeDataSource'
         , array( 'security'       => 'login'
                , 'allowEmptyFilter' => true
                )
         )
;
