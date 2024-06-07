<?php namespace Coders\Mvc;
/**
 * Description of request
 *
 * @author coder1
 */
class Request {
    
    private function __construct( ) {
        
        
    }
    
    
    public static final function create(){
        
        return new Request();
    }
    
}
