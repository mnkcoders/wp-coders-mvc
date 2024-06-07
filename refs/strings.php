<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

final class StringsRef{
    /**
     * @var array
     */
    private $_strings = array(
        //drop in here all strings
    );
    /**
     * @var string
     */
    private $_locale = '';
    
    /**
     * @param string $endpoint
     * @param string $locale
     */
    private final function __construct( $endpoint , $locale = '' ) {

        $path = self::__path($endpoint,$locale);
        $content = self::__load($path);
        $this->__import($content);
        $this->_locale = $locale;        
    }
    
    public final function __get($name) {
        return $this->__($name);
    }
    /**
     * @param string $string
     * @param string $default
     * @return string
     */
    public final function __( $string ){
        return array_key_exists($string, $this->_strings) ? $this->_strings[$string] : $string;
    }
    /**
     * @param string $endpoint
     * @param string $locale
     * @return string
     */
    private static final function __path($endpoint, $locale = ''){
        if(strlen($locale) === 0 ){
            $locale = get_locale();
        }        
        return sprintf('%s/strings/strings_%s.cfg',\CodersApp::path($endpoint),$locale);
    }
    /**
     * @param string $endpoint
     * @return boolean
     */
    private static final function __exists( $endpoint , $locale = '' ){
        return file_exists(self::__path($endpoint,$locale));
    }
    /**
     * @param string $path
     * @return string
     */
    private static final function __load( $path ){
        try{
            $handle = fopen($path, "r");
            $content = fread($handle, filesize($path));
            fclose($handle);
            return $content;
            
        }
        catch (\Exception $ex) {
            \CodersApp::notify('error loading strings ' . $ex->getMessage());
        }
        return '';
    }
    /**
     * @param string $content
     */
    private final function __import( $content ){
        if(strlen($content)){
            foreach( explode("\n", $content) as $line ){
                $string = explode('=', trim($line));
                if( count($string) > 1 && strlen($string[0]) > 1){
                    $this->_strings[trim( $string[0] ) ] = trim($string[1]);
                }
            }
        }
    }

    /**
     * @param string $endpoint
     * @param string $locale
     * @return \CODERS\Framework\Strings
     */
    public static final function create( $endpoint , $locale = '' ){
        if( self::__exists($endpoint,$locale)){
            return new Strings( $endpoint , $locale );
        }
        return null;
    }
}



