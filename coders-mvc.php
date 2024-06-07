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

add_action( 'register_coders_extensions', function(){
    CodersApp::registerExtension(__DIR__);
});
/**
 * Description of request
 *
 * @author coder1
 */
class CodersRequest {
    
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
        
        return new CodersRequest();
    }
    
}
/**
 * Description of CodersResponse
 *
 * @author coder1
 */
abstract class CodersResponse {
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
     * @return \CODERS\MVC\CodersResponse
     */
    public static final function create(){
        
        return null;
    }
    
}
/**
 * Description of Model
 *
 * @author coder1
 */
abstract class CoderContent{
    
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_DATE = 'date';
    const TYPE_DATE_TIME = 'datetime';
    const TYPE_EMAIL = 'textarea';
    const TYPE_NUMBER = 'number';
    const TYPE_FLOAT = 'float';
    const TYPE_CURRENCY = 'currency';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_INVALID = 'invalid';
    
    private $_data = array(
        //add here content dictionary
    );
    
    /**
     * @param array $input
     */
    private function __construct( array $input = array( ) ) {
        
        $this->import($input);
    }
    /**
     * @return string
     */
    public function __toString() {
        return strval($this);
    }
    /**
     * @param string $name
     * @param array $args
     */
    public function __call($name,$args) {
        switch(TRUE){
            case preg_match('/^list_/', $name):
                return $this->list($name);
            case preg_match('/^is_/', $name):
                return $this->is($name);
            case preg_match('/^has_/', $name):
                return $this->has($name);
            case preg_match('/^error_/', $name):
                return $this->error($name);
            case preg_match('/^type_/', $name):
                return $this->type($name);
            default:
                return $this->value($name,'');
        }
        return '';
    }
    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name,$value) {
        $this->set($name, $value);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->value($name,'');
    }
    /**
     * @param string $name
     * @param mixed $value
     * @return Model
     */
    public function set($name,$value = '') {
        
        switch( $this->type($name)){
            case self::TYPE_INVALID:
                //invaid type or undefined
                return $this;
            case self::TYPE_DATE:
                if( $this->matchDate($value)){
                    $this->_data[$name]['value'] = $value;
                }
                else{
                    $this->setError($name,'Invalid Date Format');
                }
                break;
            case self::TYPE_DATE_TIME:
                if( $this->matchDateTime($value)){
                    $this->_data[$name]['value'] = $value;
                }
                else{
                    $this->setError($name,'Invalid Date-Time Format');
                }
                break;
            case self::TYPE_EMAIL:
                if( $this->matchEmail($value)){
                    $this->_data[$name]['value'] = $value;
                }
                else{
                    $this->setError($name,'Invalid Email Format');
                }
                break;
            case self::TYPE_CHECKBOX:
                $this->_data[$name]['value'] = intval($value) > 0 || boolval($value);
                break;
            case self::TYPE_CURRENCY:
            case self::TYPE_FLOAT:
                $this->_data[$name]['value'] = floatval($value);
                break;
            case self::TYPE_NUMBER:
                $this->_data[$name]['value'] = intval($value);
                break;
            case self::TYPE_TEXTAREA:
            case self::TYPE_TEXT:
            default:
                $this->_data[$name]['value'] = $value;
                break;
        }
        return $this->touch($name);
    }
    /**
     * @param string $name
     * @return array
     */
    public function list($name) {
        $call = 'list' . ucfirst($name);
        return method_exists($this, $call) ? $this->$call() : array();
    }
    /**
     * @param string $name
     * @return bool
     */
    public function has($name, $att = '' ) {
        
        if( isset($this->_data[$name]) ){
            return strlen($att) > 0 ? isset($this->_data[$name][$att]) : TRUE;
        }
        
        $call = 'has' . ucfirst($name);
        return method_exists($this, $call) ? $this->$call() : FALSE;
    }
    /**
     * @param string $name
     * @return bool
     */
    public function is($name) {
        $call = 'is' . ucfirst($name);
        return method_exists($this, $call) ? $this->$call() : false;
    }
    /**
     * @param string $name
     * @return bool
     */
    public function updated($name) {
        return $this->has($name,'updated') && $this->_data[$name]['updataed'];
    }
    /**
     * @param string $name
     * @return CoderContent
     */
    public function touch($name) {
        $this->_data[$name]['updated'] = TRUE;
        return $this;
    }
    /**
     * @param string $name
     * @return string
     */
    public function type($name) {
        return $this->has($name) ? $this->_data[$name]['type'] : self::TYPE_INVALID;
    }
    /**
     * @param string $name
     * @return string
     */
    public function error($name) {
        return $this->has($name,'error') ? $this->_data[$name]['error'] : '';
    }
    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function value($name , $default = '' ) {
        return $this->has($name) ? $this->_data['value'] : $default;
    }
    
    /**
     * @param string $name
     * @param String $type
     * @param array $attributes
     * @return CoderContent
     */
    protected function register($name,$type = self::TYPE_TEXT,array $attributes = array()) {
        if(!$this->has($name)){
            $this->_data[$name] = array(
                'type' => $type,
            );
            $this->set($name, isset($attributes['value']) ? $attributes['value'] : '' );
        }
        return $this;
    }
    /**
     * 
     * @param array $input
     * @return CoderContent
     */
    public function import( array $input ){
        
        foreach( $input as $var => $val ){
            $this->set($var, $val);
        }
        
        return $this;
    }
    /**
     * @return array
     */
    public function listAttributes() {
        return array_keys($this->_data);
    }
    /**
     * @return bool
     */
    public function hasErrors() {
        $errors = 0;
        foreach( $this->listAttributes() as  $att ){
            $errors += strlen( $this->error($att)) > 0 ? 1 : 0;
        }
        return $errors > 0;
    }
    /**
     * @return bool
     */
    public function isUpdated() {
        $updated = 0;
        foreach($this->listAttributes() as $att ){
            $updated += $this->updated($att) ? 1 : 0;
        }
        return $updated > 0;
    }
    /**
     * @param string $name
     * @param string $error
     * @return CoderContent
     */
    public function setError($name,$error = '') {
        if($this->has($name)){
            $this->_data[$name]['error'] = $error;
        }
        return $this;
    }
    /**
     * @return CoderContent
     */
    public function reset() {
       
        return $this;
    }
    /**
     * @param string $email
     * @return boolean
     */
    public static function matchEmail($email) {
        return preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
    }
    /**
     * @param string $date
     * @return boolean
     */
    public static function matchDate($date) {
        return preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $date);
    }
    /**
     * @param string $dateTime
     * @return boolean
     */
    public static function matchDateTime($dateTime) {
        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateTime);
    }
    /**
     * @param string $model
     * @param array $data
     * @return CoderContent
     */
    public static final function create( $model , array $data = array() ){
        
        $root = explode('.', $model);
        $path = sprintf('%s/%s/models/%s.php',WP_PLUGIN_DIR,$root[0],$root[1]);
        $class = sprintf('%sModel', ucfirst($root[1]));
        
        if(file_exists($path)){
            require_once $path;
        }
        else{
            //error
        }
        
        if(class_exists($class) && is_subclass_of($class, CoderContent::class, true)){
            return new $class();
        }
        else{
            //error
        }
        
        return NULL;
    }
}
/**
 * Description of CoderView
 *
 * @author coder1
 */
abstract class CoderView {
    
    const INPUT_FILE = 'file';
    const INPUT_HIDDEN = 'hidden';
    
    
    /**
     * 
     */
    private function __construct( ) {
        
        
    }
    /**
     * <html> content
     * @param string $tag
     * @param array $attributes
     * @param mixed $content
     * @return string|HTML
     */
    public static final function HTML( $tag, $attributes = array( ), $content = null ){
        if( isset( $attributes['class'])){
            $attributes['class'] = is_array($attributes['class']) ?
                    implode(' ', $attributes['class']) :
                    $attributes['class'];
        }
        $serialized = array();
        foreach( $attributes as $att => $val ){
            $serialized[] = sprintf('%s="%s"',$att,$val);
        }
        if(!is_null($content) ){
            if(is_object($content)){
                $content = strval($content);
            }
            elseif(is_array($content)){
                $content = implode(' ', $content);
            }
            return sprintf('<%s %s>%s</%s>' , $tag ,
                    implode(' ', $serialized) , strval( $content ) ,
                    $tag);
        }
        return sprintf('<%s %s />' , $tag , implode(' ', $serialized ) );
    }
    
    /**
     * @param string $name
     * @param string $action
     * @param string $type
     * @param Mixed $content
     * @return HTML | String
     */
    public static function form( $name , $action = '' , $type = self::FORM_TYPE_PLAIN , $content = null ){
        
        return self::html('form', array(
            'name' => $name,
            'action' => strlen($action) ? $action : filter_input(INPUT_SERVER, 'PHP_SELF'),
            'encType' => $type,
        ), $content);
    }


    public static final function create(){
        
        return new CoderView();
    }    
}
/**
 * 
 */
abstract class CoderService{
    
    protected function __construct() {
        
    }
    
    
    public static final function create( $service ){
        
        return null;
    }
}
/**
 * 
 */
abstract class CoderProvider{
    protected function __construct() {
        
    }
    
    public static final function create( $provider ){
        
        return null;
    }
}