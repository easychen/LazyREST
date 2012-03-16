<?php
$GLOBALS['config']['site_name'] = 'LazyRest';
$GLOBALS['config']['site_url'] = 'http://' . $_SERVER['HTTP_HOST'];

$GLOBALS['config']['token_table_name'] = 'user';
$GLOBALS['config']['token_account_field'] = 'account';
$GLOBALS['config']['token_password_field'] = 'password';



$GLOBALS['config']['mc_host'] = 'localhost';
$GLOBALS['config']['mc_port'] = 11211;

// if you use sae's smtp to send mail,add smtp info here
$GLOBALS['config']['smtp_account'] = 'yourmailbox@gmail.com';
$GLOBALS['config']['smtp_password'] = '******';
$GLOBALS['config']['smtp_server'] = null;
$GLOBALS['config']['smtp_port'] = null;

?>