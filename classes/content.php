<?php namespace Coders\Mvc;
/**
 * Description of Model
 *
 * @author coder1
 */
abstract class Content{
    
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
     * @return Content
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
     * @return Content
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
     * @return Content
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
     * @return Content
     */
    public function setError($name,$error = '') {
        if($this->has($name)){
            $this->_data[$name]['error'] = $error;
        }
        return $this;
    }
    /**
     * @return Content
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
     * @return Content
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
        
        if(class_exists($class) && is_subclass_of($class, Content::class, true)){
            return new $class();
        }
        else{
            //error
        }
        
        return NULL;
    }
}





