<?php defined('ABSPATH') or die;
/* * *****************************************************************************
 * Plugin Name: Coders MVC
 * Plugin URI: https://coderstheme.org
 * Description: Model View Controller Framework support for Coders App Endpoints
 * Version: 0.1
 * Author: Coder01
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_mvc
 * Domain Path: lang
 * Class: CodersMVC
 * 
 * @author Coder01 <coder01@mnkcoder.com>
 * **************************************************************************** */

/**
 * 
 */
abstract class CodersMVC extends CodersApp {
    /**
     * @param string $root
     */
    protected function __construct($root) {
        
        $this->__preload();
        
        parent::__construct($root);
        
    }
    /**
     * @return CodersMVC
     */
    private final function __preload(){
        $deps = array('content','view','request','response');
        foreach($deps as $class){
            require_once sprintf('%s/classes/%s.php',__DIR__,$class);
        }
        return $this;
    }
    
}





