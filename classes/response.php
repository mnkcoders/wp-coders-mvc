<?php namespace Coders\Mvc;
/**
 * Description of Response
 *
 * @author coder1
 */
abstract class Response {
    /**
     * 
     */
    protected function __construct(  ) {
        
        
    }
    
    /**
     * 
     * @param Request $request
     * @return bool
     */
    public function action( Request $request ){
        
        $call = $request->action() . 'Action';
        
        return method_exists($this, $call) ? $this->$call( $request ) : $this->errorAction( $request );
    }
    
    /**
     * 
     */
    abstract function defaultAction(Request $request );

    /**
     * @param Request $request
     * @return bool
     */
    public function errorAction(Request $request ) {
        
        return FALSE;
    }
    /**
     * @param string $action
     * @return bool
     */
    public function can( $action ){
        $call = $action . 'Action';
        return method_exists($this, $call);
    }
    
    /**
     * @return \CODERS\MVC\Response
     */
    public static final function create(){
        
        return null;
    }
    
}



