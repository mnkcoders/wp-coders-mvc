<?php namespace CODERS\MVC;

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
     * @return \CODERS\MVC\Response
     */
    public static final function create(){
        
        return null;
    }
    
}




