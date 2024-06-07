<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Service setup
 */
abstract class ServiceRef{

    const TYPE_DEFAULT = 'default';
    /**
     * @var Service[]
     */
    private static $_services = array(
        //
    );
    /**
     * @var string
     */
    private $_type = self::TYPE_DEFAULT;
                
    private $_settings = array();
    /**
     * @param array $settings
     */
    protected function __construct( array $settings = array() ) {
        $this->import( $settings );
    }
    /**
     * @param array $settings
     * @return \CODERS\Framework\Service
     */
    protected function import( array $settings = array()){
        foreach ($settings as $var => $val ){
            if( $var === 'type' ){
                $this->_type = $val;
            }
            elseif( isset($this->_settings[$var])){
                $this->_settings[$var]  = $val;
            }
        }
        return $this;        
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return array_key_exists($name, $this->_settings) ? $this->_settings[$name] : '';
    }

    /**
     * @param \CODERS\Framework\Service $service
     * @return boolean
     */
    private static final function __register( Service $service ){
        self::$_services[ ] = $service ;
        return true;
    }
    /**
     * 
     * @param string $setting
     * @param mixed $value
     * @return \CODERS\Framework\Service
     */
    protected final function define( $setting , $value = '' ){
        if( !array_key_exists($setting,$this->_settings)){
            $this->_settings[$setting]  =$value;
        }
        return $this;
    }

    /**
     * Ejecuta el servicio
     * @return bool Resultado de la ejecución del servicio
     */
    public function dispatch( array $data = array() ){
        
        return TRUE;
        
    }
    /**
     * @return String
     */
    public function type(){
        return $this->_type;
    }
    /**
     * @param array $route
     * @return string
     */
    private static final function __importClass( array $route ){
        $namespace = \CodersApp::Class( $route );
        return count($namespace) > 1 ?
                    sprintf('\CODERS\%s\%sService', $namespace[0], $namespace[1] ) :
                    sprintf('\CODERS\Framework\Services\%s', $namespace[0] );
    }
    /**
     * @param array $route
     * @return String|PAth
     */
    private static final function __importPath( array $route ){
        return count($route) > 1 ?
                    sprintf('%s/components/services/%s.php', \CodersApp::path($route[0]), $route[1]) :
                    sprintf('%s/components/services/%s.php', \CodersApp::path(), $route[0]);
    }
    /**
     * @param string $service
     * @param array $setup 
     * @return \CODERS\Framework\Service | boolean
     */
    public static final function create( $service, array $setup = array() ){
        $namespace = explode('.', $service);
        $path = self::__importPath($namespace);
        if(file_exists($path)){
            require_once $path;
            $class = self::__importClass($namespace);
            if(class_exists($class) && is_subclass_of($class, self::class)){
                $svc = new $class( $setup );
                if( self::__register($svc) ){
                    return $svc;
                }
            }
        }
        return false;
    }
    /**
     * @param string $type
     * @return number
     */
    public static final function count( $type ){
        return count(self::filter($type));
    }
   /**
     * @param string $type
     * @return \CODERS\Framework\Service[]
     */
    public static final function filter( $type ){
        $output = array();
        foreach( self::$_services as $svc ){
            if( $svc->type() === $type ){
                $output[] = $svc;
            }
        }
        return $output;
    }
    /**
     * @param string $type
     * @param array $data
     * @return int
     */
    public static final function run( $type , array $data = array( )){
        $count = 0;
        foreach( self::filter($type) as $svc ){
            if( $svc->dispatch( $data ) ){
                $count++;
            }
        }
        return $count;
    }
}