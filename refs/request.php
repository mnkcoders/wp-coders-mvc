<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Request and Response manager
 */
class RequestRef{
    
    const CONTEXT = 'public';
    const ACTION = 'default';
    
    const INPUT_GET = INPUT_GET;
    const INPUT_POST = INPUT_POST;
    const INPUT_SERVER = INPUT_SERVER;
    const INPUT_COOKIE = INPUT_COOKIE;
    const INPUT_SESSION = INPUT_SESSION;
    const INPUT_REQUEST = 10;

    private $_endpoint = '';
    private $_context = self::CONTEXT;
    private $_action = self::ACTION;
    private $_ts;

    /**
     * @param string $endpoint
     * @param string $request
     */
    protected function __construct( $endpoint , $request = '' ) {

        $this->_ts = time();
        $this->_endpoint = $endpoint;
        
        $route = explode('.', strlen($request) ? $request : '' );

        if( strlen($route[0]) ){
            $this->_context = $route[0];
        }
        if( count($route) > 1 ){
            $this->_action =  $route[1];
        }
        elseif(is_admin()){
            $this->_action = $this->get('action', self::ACTION, INPUT_GET);
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return sprintf('[%s] %s.%s',
                get_class($this),
                $this->_ts ,
                $this->endPoint() ,
                $this->action( TRUE ) );
    }
    /**
     * Obtiene un valor de la Request
     * @param string $name
     * @return string
     */
    public final function __get($name) {
        return $this->get($name,'');
    }
    /**
     * Importa un parámetro del evento
     * @param string $input
     * @param mixed $default
     * @return mixed
     */
    public final function get( $input, $default = FALSE , $type = INPUT_REQUEST ){
        
        switch( $type ){
            case self::INPUT_GET:
            case self::INPUT_POST:
            case self::INPUT_COOKIE:
            case self::INPUT_SERVER:
                $input = filter_input( $type, $input );
                return !is_null($input) ? $input : $default;
            case self::INPUT_REQUEST: default:
                $get = $this->get($input,FALSE,self::INPUT_GET);
                return $get !== FALSE ? $get : $this->get($input,FALSE,self::INPUT_POST);
        }
    }
    /**
     * Retorna un valor numérico
     * @param string $property
     * @param int $default
     * @return int
     */
    public final function getInt( $property, $default = 0 ){
        return intval( $this->get($property, $default ) );
    }
    /**
     * @param string $property Propiedad a extraer
     * @param string $separator Separador de los valores serializados
     * @return array
     */
    public final function getArray( $property, $separator = ',' ){
        return explode($separator, $this->get($property, ''));
    }
    /**
     * @param int $type
     * @return array
     */
    public final function input( $type = self::INPUT_REQUEST ){
        
        switch( $type ){
            case self::INPUT_REQUEST:
                return array_merge(self::__INPUT(self::INPUT_GET),self::__INPUT(self::INPUT_POST));
            case self::INPUT_GET:
            case self::INPUT_POST:
                return self::__INPUT( $type );
            default:
                //hide other imputs atm
                return array();
        }
    }
    /**
     * @param string $name
     * @param mixed $value
     * @param int $time
     * @return bool
     */
    public final function setCookie( $name, $value = null, $time = 10 ){
        $elapsed = time() + ( $time  * 60);
        $cookie = preg_replace('/\-/', '_', $this->endPoint()) . '_' . $name;
        return setcookie( $cookie, $value,$elapsed);
    }
    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public final function cookie( $name , $default = '' ){
        $cookie = preg_replace('/\-/', '_', $this->endPoint()) . '_' . $name;
        return $this->get($cookie,$default,self::INPUT_COOKIE);
    }
    /**
     * @return int WP User ID
     */
    public final function UID(){ return get_current_user_id(); }
    /**
     * @return string
     */
    public final function SID(){ return wp_get_session_token(); }
    /**
     * @return string|NULL Dirección remota del cliente
     */
    public static final function remoteAddress(){
        
        return filter_input(self::INPUT_SERVER, 'REMOTE_ADDR');
    }
    /**
     * @return string Event Type
     */
    public final function action( $full = FALSE ){
        
        return $full ?
                $this->_context . '.' . $this->_action :
                $this->_action;
    }
    /**
     * @param bool $cc Camel Case
     * @return String
     */
    public final function context( $cc = FALSE ){ return $cc ? ucfirst($this->_context) : $this->_context; }
    /**
     * @param bool $cc Camel Case
     * @return String
     */
    public final function endPoint( $cc = FALSE ){
        return $cc ?
                preg_replace('/ /','', ucwords(preg_replace('/-/',' ', $this->_endpoint ))) :
                $this->_endpoint;
    }
    /**
     * @param array $arguments
     * @param boolean $is_admin
     * @return string|URL
     */
    public function getUrl( ){

        $arguments = array();
        
        $is_admin = is_admin();
        $url = $is_admin ? admin_url() : get_site_url();
        
        if( $is_admin ){
            // admin-page = endpoint-controller
            $arguments['page'] = $this->endPoint() .'-'. $this->context();
            $arguments['action'] = $this->action();
            $url .=  'admin.php';
        }
        else{
            $route = array();
            if( $this->action() !== self::ACTION ){
                $route[] = $this->context();
                $route[] = $this->action();
            }
            elseif($this->context() !== self::CONTEXT ){
                $route[] = $this->context();
            }
            $url .= sprintf( '/%s/%s/' , $this->endPoint() , implode('-' , $route ) );
        }
        
        return self::URL(  $url , $arguments );
    }
    /**
     * @param string $action
     * @param array $arguments
     * @param boolean $admin
     * @return string
     */
    public static final function createLink( $action , array $arguments = array() , $admin = false ){
        
        $route = is_array($action) ? $action : explode('.', $action);
        
        if(strlen($route[0]) === 0 ){
            $route[0] = \CodersApp::instance()->endPoint();
        }

        $endpoint_url = $admin ?
                admin_url():
                get_site_url() . '/' . $route[0];
        
        if($admin){
            $endpoint_url .= 'admin.php';
            $page = array('page' => count($route) > 1 && $route[1] !== 'admin' ? $route[0] . '-'. $route[1] : $route[0] );
            if(count($route) > 2){
                $page['action'] = $route[2];
            }
            $arguments = $page + $arguments;
        }
        elseif(count($route) > 1 ){
            $act = array_slice($route, 1);
            $endpoint_url .= '/' . implode('-', $act);
        }
        //var_dump($endpoint_url);
        return self::URL($endpoint_url,$arguments);
    }
    /**
     * @param string $url
     * @param array $params
     * @return String|URL
     */
    public static final function URL(  $url = '' , array $params = array() ){

        $serialized = array();
        if(strlen($url) === '' ){
            $url = get_site_url();
        }
        foreach( $params as $var => $val ){
            $serialized[] = sprintf('%s=%s',$var,$val);
        }
        
        return count( $serialized ) ? $url . '?' . implode('&', $serialized ) : $url;
    }
    /**
     * @param int $input
     * @return array
     */
    private static final function __INPUT( $input = self::INPUT_GET ){
        $vars = filter_input_array($input);
        return !is_null($vars) ? $vars : array();
    }
    /**
     * @param string $endpoint
     * @param string $route
     * @return \CODERS\Framework\Request
     */
    public static final function import( $endpoint , $route = '' ){
        return new Request( $endpoint , $route );
    }
    /**
     * @param string $endpoint
     * @param string $route
     * @return \CODERS\Framework\Request
     */
    public static final function ajax( $endpoint , $route = 'default' ){
        return new Request( $endpoint , 'ajax.'.$route );
    }
    /**
     * @param string $route
     * @param string $endpoint (target endpoint, self by default)
     * @return \CODERS\Framework\Request
     */
    public final function redirect( $route  = '' , $endpoint = '' ){
        
        return (new Request(strlen($endpoint) ? $endpoint : $this->endPoint() , $route ))->route();
    }
    /**
     * @return \CODERS\Framework\Response 
     */
    public final function route( ){
        
        if( !is_admin() && $this->context() === 'admin' ){
            return false;
        }
        
        return Response::create($this, is_admin());
    }
}
/**
 * 
 */
class Response extends Request{
    /**
     * @var array
     */
    private $_components = array(
        'strings'
    );    
    /**
     * @param string $endpoint
     * @param string $request
     */
    protected function __construct($endpoint, $request = '') {
        
        parent::__construct($endpoint, $request);
        //import all endpoint required components by request
        $this->preload();
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return bool
     */
    public final function __call($name, $arguments) {
        
        return $this->response($name, count($arguments) ? $arguments : array());
    }
    /**
     * @param boolean $file
     * @return string |PATH
     */
    protected function __path( $file = false ){
        $ref = new \ReflectionClass(get_called_class());
        return preg_replace('/\\\\/', '/',  $file ? $ref->getFileName() : dirname( $ref->getFileName() ) );
    }
    /**
     * Preload all core and instance components
     * @return \CODERS\Framework\Request
     */
    protected final function preload( ){
        foreach( $this->_components as $component ){
            if(strlen($component)){
                $route = explode('.', $component);
                $path = '';
                switch( count( $route ) ){
                    case 3: //load endpoint custom component
                        $path = sprintf('%s/components/%s/%s.php',
                                \CodersApp::path( strlen($route[0]) > 0 ? $route[0] : $this->endPoint() ),
                                $route[1], $route[2]);
                        break;
                    case 2: //load framework component
                        $path = sprintf('%s/components/%s/%s.php', \CodersApp::path(), $route[0],$route[1]);
                        break;
                    case 1: //load framework classes
                        $path = sprintf('%s/classes/%s.php', \CodersApp::path(), $route[0]);
                        break;
                }
                if( strlen($path) && file_exists($path)){
                    require_once $path;
                }
                else{
                    \CodersApp::notify( sprintf('Invalid component path [%s]',$path),'error');
                }
            }
        }
        $this->_components = array();
        return $this;
    }
    /**
     * @return string
     */
    public function request(){
        return $this->endPoint() . '.' . $this->action(true);
    }
    /**
     * @return string
     */
    protected function module(){
        $path = explode('/',  $this->__path(true));
        return explode('.',  $path[count($path)-1] ) [0];
    }
    /**
     * @param string $component
     * @return \CODERS\Framework\Request
     */
    protected function require( $component ){
        
        if( !in_array( $component ,$this->_components ) ){
                $this->_components[ ] = $component;
        }
        
        return $this;
    }
    /**
     * 
     * @param \CODERS\Framework\Request $request
     * @param boolean $admin
     * @return \CODERS\Framework\Response
     */
    protected static final function create( Request $request , $admin = false ){

        $path = self::__contextPath( $request->endPoint(), $request->context() , $admin);
        $class = self::__contextClass( $request->endPoint(), $request->context(), $admin );
        if(file_exists($path)){
            require_once $path;
            if(class_exists($class) && is_subclass_of($class, Response::class)){
                return new $class( $request->endPoint() ,$request->action(true));
            }
            elseif (\CodersApp::debug()) {
                \CodersApp::notify(sprintf('Invalid Response [%s]', $class), 'error');
            }
        }
        elseif (\CodersApp::debug()) {
            \CodersApp::notify(sprintf('Invalid context path %s', $path), 'error');
        }
        return new Response($request->endPoint(),$request->context());
    }
    /**
     * @param string $endpoint
     * @param string $context
     * @param boolean $admin
     * @return String
     */
    private static final function __contextPath( $endpoint , $context , $admin = false ){
        $path = \CodersApp::path($endpoint);
        $route = $admin && $context !== 'admin' ? 'admin-' . $context : $context;
        return sprintf('%s/components/controllers/%s.php',$path,$route );
    }
    /**
     * @param string $endpoint
     * @param string $context
     * @param boolean $admin
     * @return String
     */
    private static final function __contextClass( $endpoint , $context , $admin = false ){
        $namespace = preg_replace('/ /','', ucwords(preg_replace('/-/',' ', $endpoint )));
        $controller = $admin && $context !== 'admin' ? ucfirst($context) . 'Admin' : ucfirst($context);
        return sprintf('\CODERS\%s\%sController', $namespace,$controller );
    }
    /**
     * @param string $provider
     * @param array $data
     * @return \CODERS\Framework\Provider
     */
    protected final function importProvider( $provider , array $data = array( ) ){
        return  class_exists('\CODERS\Framework\Provider') ?
                \CODERS\Framework\Provider::create(preg_replace('/^\./', $this->endPoint() . '.' , $provider), $data) :
                        null;
    }
    /**
     * @param string $model
     * @param array $data
     * @return \CODERS\Framework\Model | boolean
     */
    protected final function importModel( $model , array $data = array() ){
        return  class_exists('\CODERS\Framework\Model') ?
                \CODERS\Framework\Model::create( sprintf('%s.%s',$this->endPoint(),$model) , $data ) :
                        null;
    }
    /**
     * @param string $view
     * @return \CODERS\Framework\View | boolean
     */
    protected final function importView( $view ){
        return  class_exists('\CODERS\Framework\View') ?
            \CODERS\Framework\View::create( sprintf('%s.%s',
                    $this->endPoint(), $view ) ) :
                            null;
    }
    /**
     * @param array $args
     * @return boolean
     */
    protected function error_action( array $args = array( ) ){
        var_dump($args);
        return FALSE;
    }
    /**
     * @param array $args
     * @return boolean
     */
    protected function default_action( array $args = array() ){
        
        //printf(strval($this));
        var_dump($args);

        return TRUE;
    }
    /**
     * Write the response/output
     * @return boolean
     */
    public function response( $action = '' ){
        
        if( strlen($action) === 0 ){
            $action = $this->action();
        }
        
        $call = sprintf('%s_action',$action);
        
        return method_exists($this, $call) ?
                $this->$call( $this->input() ) : 
                $this->error_action( $this->input() );
    }
}



