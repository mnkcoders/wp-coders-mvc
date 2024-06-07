<?php namespace Coders\Mvc;
/**
 * Description of request
 *
 * @author coder1
 */
class Request {
    
    const INPUT_GET = INPUT_GET;
    const INPUT_POST = INPUT_POST;
    const INPUT_REQUEST = 10;
    const INPUT_SERVER = INPUT_SERVER;
    const INPUT_SESSION = INPUT_SESSION;
    const INPUT_COOKIE = INPUT_COOKIE;
    
    private $_ts;
    private $_action;
    private $_module;
    
    private function __construct( $request ) {
        $root = explode('.', $request);
        $this->_module = $root[0];
        $this->_action = count($root) > 1 ? $root[1] : 'default';
        $this->_ts = time();
    }
    /**
     * @return string
     */
    public function __toString( ) {
        return sprintf('%s.%s (%s)',
                $this->_module,
                $this->_action,
                $this->_ts);
    }
    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        return $this->get($name,'');
    }
    /**
     * 
     * @param string $input
     * @param string $default
     * @return string
     */
    public function get( $input , $default = '' , $type = self::INPUT_REQUEST ) {

        switch( $type ){
            case self::INPUT_GET:
            case self::INPUT_POST:
            case self::INPUT_COOKIE:
            case self::INPUT_SERVER:
                $input = filter_input( $type, $input );
                return !is_null($input) ? $input : $default;
            case self::INPUT_REQUEST: default:
                return $this->get($input,$default,self::INPUT_GET)
                    || $this->get($input,$default,self::INPUT_POST);
        }
        
        return $default;
    }
    /**
     * @param string $input
     * @return int
     */
    public function getInt($input) {
        return intval( $this->get($input, 0) );
    }
    /**
     * @param string $input
     * @param string $separator
     * @return array
     */
    public function getArray($input,$separator = ',') {
        return explode($separator, $this->get($input));
    }
    /**
     * @param bool $fullRoute
     * @return string
     */
    public function action($fullRoute = false) {
        return $fullRoute ? sprintf('%s.%s',$this->_module,$this->_action) : $this->_action;
    }
    /**
     * @return string
     */
    public function module(){
        return $this->_module;
    }
    /**
     * @return string
     */
    public function endpoint(){
        return CodersApp::instance()->endPoint();
    }
    /**
     * @param string $name
     * @param mixed $value
     * @param int $time
     * @return bool
     */
    public final function setCookie( $name, $value = null, $time = 10 ){
        $elapsed = time() + ( $time  * 60);
        $cookie = preg_replace('/\-/', '_', $this->endpoint()) . '_' . $name;
        return setcookie( $cookie, $value, $elapsed);
    }
    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public final function cookie( $name , $default = '' ){
        $cookie = preg_replace('/\-/', '_', $this->endpoint()) . '_' . $name;
        return $this->get($cookie,$default,self::INPUT_COOKIE);
    }
    /**
     * @return int WP User ID
     */
    public final function uid(){ return get_current_user_id(); }
    /**
     * @return string
     */
    public final function sid(){ return wp_get_session_token(); }
    /**
     * @return string|NULL Dirección remota del cliente
     */
    public final function remote(){
        return $this->get('REMOTE_ADDR','', self::INPUT_SERVER);
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
            $arguments['page'] = $this->module();
            $arguments['action'] = $this->action();
            $url .=  'admin.php';
        }
        else{
            $route = array();
            if( $this->action() !== 'default' ){
                $route[] = $this->module();
                $route[] = $this->action();
            }
            elseif($this->module() !== 'main' ){
                $route[] = $this->module();
            }
            $url .= sprintf( '/%s/%s/' , $this->endpoint() , implode('-' , $route ) );
        }
        
        return self::url(  $url , $arguments );
    }
    
    /**
     * @param string $url
     * @param array $args
     * @return String|URL
     */
    public static final function url(  $url = '' , array $args = array() ){

        if(strlen($url) === '' ){
            $url = get_site_url();
        }
        
        $serialized = array();
        foreach( $args as $var => $val ){
            $serialized[] = sprintf('%s=%s',$var,$val);
        }
        
        return count( $serialized ) ? $url . '?' . implode('&', $serialized ) : $url;
    }
    
    /**
     * @param int $type
     * @return array
     */
    public static function input($type) {

        switch( $type ){
            case self::INPUT_REQUEST:
                return array_merge(
                        self::input(self::INPUT_GET),
                        self::input(self::INPUT_POST));
            case self::INPUT_GET:
            case self::INPUT_POST:
                $input = filter_input_array($type);
                return !is_null($input) ? $input : array();
            default:
                //hide other imputs atm
                return array();
        }
    }
    
    public static final function create(){
        
        return new Request();
    }
    
}/**








