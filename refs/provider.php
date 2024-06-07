<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

abstract class ProviderRef{
    
    private $_content = array(
        //
    );
    
    protected function __construct( array $data = array( ) ) {
        
        //
        $this->__import($data);
    }
    /**
     * @param string $id
     * @return string
     */
    protected static function generateId( $id = 0 ){
        return md5( uniqid( date( 'YmdHis' ) . $id , true ) );
    }
    
    /**
     * @param string $setting
     * @param mixed $value
     * @return \CODERS\Framework\Provider
     */
    protected function set( $setting , $value = '' ){
        $this->_content[$setting] = $value;
        return $this;
    }
    /**
     * @param string $name
     * @return boolean
     */
    protected function has( $name ){
        return array_key_exists($name, $this->_content);
    }

    /**
     * @return array
     */
    public final function elements(){
        return array_keys($this->_content);
    }
    /**
     * @return array
     */
    public final function values(){
        return $this->_content;
    }
    /**
     * @param array $data
     */
    private final function __import( array $data = array()){
        foreach( $data as $var => $val ){
            if($this->has($var)){
                $this->set($var, $val);
                //$this->_elements[$var] = $val;
            }
        }
    }

    /**
     * @return string
     */
    public function __toString() {
        return get_class( $this );
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return array_key_exists($name, $this->_content) ? $this->_content[$name] : '';
    }
    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        if( $this->has($name)){
            $this->set($name, $value);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        $call = sprintf('call%s', ucfirst($name));
        return method_exists($this, $call) ? $this->$call($arguments) : false;
    }
    
    /**
     * @param boolean admin
     * @return String
     */
    protected static final function __contextPath( $provider ){
        $path = explode('.', $provider);
        $root = count($path) > 1 ? \CodersApp::path($path[0]) : \CodersApp::path();
        $pr = count($path) > 1 ? $path[1] : $path[0];
        return sprintf('%s/components/providers/%s.php', $root , $pr);
    }
    /**
     * @return String
     */
    protected static final function __contextClass( $provider ){
        
        $namespace = explode('.', $provider);
        
        if( count( $namespace) > 1 ){
            return sprintf('\CODERS\%s\Providers\%s',
                    ucwords($namespace[0]),
                    ucwords($namespace[1]));
        }
        
        return sprintf('\CODERS\Framework\Providers\%s', ucwords($namespace[0] ) );
    }
    /**
     * @param string $provider
     * @param array $data
     * @return \CODERS\Framework\Provider
     */
    public static final function create( $provider , array $data = array()){
        
        $class = self::__contextClass($provider);
        //var_dump($class);
        
        if( !class_exists($class)){
            $path = self::__contextPath($provider);
            //var_dump($path);
            if(file_exists($path)){
                require_once $path;
            }
        }
        
        return class_exists($class) ? new $class( $data ) : null;
    }
}


