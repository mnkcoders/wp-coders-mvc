<?php namespace CODERS\MVC;

/**
 * Description of request
 *
 * @author coder1
 */
class Request {
    
    private function __construct( ) {
        
        
    }
    
    public function get( $input , $default = '' ) {
       
        return $default;
    }
    /**
     * @return string
     */
    public function action() {
        return $this->get('action','default');
    }
    
    
    public static final function create(){
        
        return new Request();
    }
    
}
