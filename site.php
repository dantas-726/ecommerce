<?php 

use \Hcode\PageAdmin;


$app->get('/admin', function() {
	$page = new Hcode\PageAdmin();

	$page->setTpl("index");

});