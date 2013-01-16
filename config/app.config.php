<?php
$GLOBALS['config']['site_name'] = 'LazyRest';
$GLOBALS['config']['site_domain'] = $_SERVER['HTTP_HOST'];
$GLOBALS['config']['site_url'] = 'http://' . $_SERVER['HTTP_HOST'];

$GLOBALS['config']['token_table_name'] = 'user';
$GLOBALS['config']['token_account_field'] = 'account';
$GLOBALS['config']['token_password_field'] = 'password';