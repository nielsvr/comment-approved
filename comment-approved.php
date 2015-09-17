<?php
/*
Plugin Name: Comment Approved
Plugin URI: https://nielsvr.com
Description: Notify a user when their comment is approved

Version: 1.4.3
Requires at least: 3.0

Author: Niels van Renselaar
Author URI: https://nielsvr.com

Text Domain: comment-approved
Domain Path: /languages/

*/

include dirname( __FILE__ ) . '/classes/main.php';

CommentApproved::instance();
