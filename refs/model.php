<?php namespace CODERS\Framework;
 
defined('ABSPATH') or die;
/**
 * 
 */
abstract class ModelRef{
    
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_PASSWORD = 'password';
    const TYPE_NUMBER = 'number';
    const TYPE_FLOAT = 'float';
    const TYPE_CURRENCY = 'currency';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DROPDOWN = 'dropdown';
    const TYPE_OPTION = 'option';
    const TYPE_LIST = 'list';
    const TYPE_FILE = 'file';
    const TYPE_HIDDEN = 'hidden';
    //
    /**
     * @var array [endpoint , module ]
     */
    private $_module;
    /**
     * @var string
     */
    private $_dataSource = '';
    /**
     * @var array
     */
    private $_content = array(
        //define model data
    );
    /**
     * @param array $data
     */
    //protected function __construct( $route , array $data = array( ) ) {
    protected function __construct( array $data = array( ) ) {
        
        //register endpoint module
        $this->_module = $this->__module();
        //define default model name as table name
        $this->defineDataSource($this->module());
        
        if( count( $data ) ){
            //input data
            $this->import($data);
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return implode('.', $this->_module);
        //return $this->__class();
    }
    /**
     * @param string $name
     * @param mixede $value
     */
    public function __set($name, $value) {
        $this->change($name, $value,true);
    }
    /**
     * @return array
     */
    public function __serialize() {
        return $this->values();
    }
    /**
     * @param array $data
     */
    public function __unserialize(array $data ) {
        $this->import($data);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        // content_data => getContentData()
        $get = sprintf('get%s',preg_replace('/\s/', '',ucwords( preg_replace('/[\_\-]/', ' ', $name ) ) ) );
        return method_exists($this, $get) ? $this->$get() : $this->value($name);
    }
    /**
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        switch( TRUE ){
            case preg_match(  '/^error_/' , $name ):
                return $this->get(substr($name, 6 ), 'error','');
            case preg_match(  '/^value_/' , $name ):
                return $this->value(substr($name, 6 ));
            case preg_match(  '/^type_/' , $name ):
                return $this->get(substr($name, 5), 'type', self::TYPE_TEXT);
            case preg_match(  '/^label_/' , $name ):
                return $this->get(substr($name, 6 ), 'label', $name );
            case preg_match(  '/^is_/' , $name ):
                return $this->is(substr($name, 3));
            case preg_match('/^count_/', $name):
                return $this->count(substr($name, 6));
            case preg_match(  '/^list_/' , $name ):
                return $this->list(substr($name, 5));
            case preg_match('/^increase_/', $name):
                return $this->increase(substr($name, 9), count($arguments) && is_numeric($arguments[0]) ? $arguments[0] : 1 );
            case preg_match('/^decrease_/', $name):
                return $this->increase(substr($name, 9), count($arguments) && is_numeric($arguments[0]) ? -$arguments[0] : -1 );
            default:
                return $name;
        }
    }
    
    /**
     * Model Class Name
     * @param bool $full
     * @return string
     */
    protected static final function __class( $full = false ){
        
        if( $full ){
            return get_called_class();
        }

        $ns = explode('\\', get_called_class() );
        return $ns[ count($ns) - 1 ];
    }
    /**
     * @return string
     */
    protected static final function __model(){
        return strtolower( preg_replace('/Model$/', '', self::__class()));
    }
    protected function dataSource(){
        return $this->module();
    }
    
    /**
     * @param string $attribute
     * @param mixed $value
     * @return \CODERS\Framework\Model
     */
    protected final function __reset( $attribute , $value = '' ){
        foreach( $this->elements() as $element ){
            if( $this->has($element,$attribute)){
                $this->set($element, $attribute, $value);
            }
        }
        return $this;
    }

    
    /**
     * @return string
     */
    protected static function __ts( $format = true ){
        return $format ? date('Y-m-d H:i:s') : date('YmdHis');
    }
    /**
     * @param boolean $file
     * @return string|PATH
     */
    protected function __path( $file = false ){
        $ref = new \ReflectionClass(get_called_class());
        return preg_replace('/\\\\/', '/',  $file ? $ref->getFileName() : dirname( $ref->getFileName() ) );
    }
    /**
     * 
     */
    private function __module(){
        $path = $this->__path(true);
        $route = explode('/components/models/', $path);
        return count($route) > 1 ? array(
                substr($route[0], strrpos($route[0], '/')+1),
                preg_replace('/.php$/', '',  substr($route[1], strrpos($route[1], '/'))),
            ) : '';
    }
    /**
     * @param string $email
     * @return boolean
     */
    protected function matchEmail( $email ){
         return !preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email) ? FALSE : TRUE;
    }
    /**
     * @return string
     */
    public final function context(){
        return $this->module();
        //return count( $this->_module ) > 1 ? $this->_module[1] : $this->_module[0];
    }
    /**
     * @return \CODERS\Framework\Model
     */
    public function clone( ) {
        $modelClass = self::class;
        return new $modelClass( $this->__override($this->values( ) ) );
    }
    /**
     * @return string
     */
    public final function endpoint(){
        return $this->_module[0];
    }
    /**
     * @param bool $full
     * @return string
     */
    public final function module( $full = false ){
        return $full ?
                implode('.', $this->_module) :
                        (count($this->_module) > 1 ? $this->_module[1] : $this->_module[0]);
    }
    /**
     * @param string $element
     * @param string $type
     * @param array $attributes
     * @return \CODERS\Framework\Model
     */
    protected final function define( $element , $type = self::TYPE_TEXT , array $attributes = array( ) ){
        
        if( !array_key_exists($element, $this->_content)){
            $this->_content[$element] = array(
                'type' => $type,
            );
            switch($type){
                case self::TYPE_CHECKBOX:
                    $this->_content[$element]['value'] = FALSE;
                case self::TYPE_NUMBER:
                case self::TYPE_CURRENCY:
                case self::TYPE_FLOAT:
                    $this->_content[$element]['value'] = 0;
                    break;
                case self::TYPE_TEXT:
                case self::TYPE_TEXTAREA:
                    //others
                default;
                    $this->_content[$element]['value'] = '';
                    break;
            }
            foreach( $attributes as $att => $val ){
                switch( $att ){
                    case 'value':
                        //force value parsing
                        $this->change($element,$val);
                        break;
                    default:
                        $this->set($element, $att, $val);
                        break;
                }
            }
        }

        return $this;
    }
    /**
     * @param string $created
     * @param string $updated
     * @return \CODERS\Framework\Model
     */
    protected function defineTimeStamps( $created = 'date_created' , $updated = 'date_updated'){
        return $this->define($created,self::TYPE_DATETIME,array())
                ->define($updated,self::TYPE_DATETIME,array());
    }
    /**
     * @param string $ds
     * @return \CODERS\Framework\Model
     */
    protected function defineDataSource($ds) {
        if(strlen($this->_dataSource) === 0 ){
            $this->_dataSource = $ds;
        }
        return $this;
    }
    /**
     * @return string
     */
    protected function ds() {
        return $this->_dataSource;
    }
    /**
     * @param \CODERS\Framework\Model $source
     * @return \CODERS\Framework\Model
     */
    protected final function __copy(Model $source ){
        if( count( $this->_content) === 0 ){
            foreach( $source->_content as $element => $meta ){
                $this->_content[ $element ] = $meta;
            }
        }
        return $this;
    }
    /**
     * Define here the override rules to clone and copy a model
     * @param array $values
     * @return array
     */
    protected function __override( array $values ){
        //
        return $values;
    }
    /**
     * @param array $filters
     * @return \CODERS\Framework\Model
     */
    protected function reload(array $filters = array() ) {
        
        $data = $this->db()->select($this->ds(),'*',$filters);
    
        return count($data) ? $this->import($data) : $this;
    }
    /** 
     * @param string $element
     * @return boolean
     */
    public function required( $element ){
        return $this->get($element,'required',FALSE);
    }
    /**
     * @return boolean
     */
    public function validateAll(){
        foreach( $this->elements() as $element ){
            if( !$this->validate($element)){
                return FALSE;
            }
        }
        return TRUE;
    }
    /**
     * @return boolean
     */
    protected function validate( $element ){
        
        if( $this->required($element) ){
            $value = $this->value($element);
            switch( $this->type($element)){
                case self::TYPE_CHECKBOX:
                    return TRUE; //always true, as it holds FALSE vaule by default
                case self::TYPE_NUMBER:
                case self::TYPE_CURRENCY:
                case self::TYPE_FLOAT:
                    return FALSE !== $value; //check it's a number
                case self::TYPE_EMAIL:
                    //validate email
                    return $this->matchEmail($value);
                    //return preg_match( self::matchEmail() , $value) > 0;
                //case self::TYPE_TEXT:
                default:
                    $size = $this->get($element, 'size' , 1 );
                    if( FALSE !== $value && strlen($value) <= $size ){
                        return TRUE;
                    }
                    break;
            }
            return FALSE;
        }
        return TRUE;
    }
    /**
     * Combine elements from
     * @param Array $data
     * @return \CODERS\Framework\Model
     */
    public function import( array $data ){
        foreach( $data as $element => $value ){
            $this->change($element, $value);
        }
        return $this;
    }
    /**
     * @param string $element
     * @return boolean
     */
    /*public function exists( $element ){
        //return array_key_exists($element, $this->_dictionary);
        return $this->has($element);
    }*/
    /**
     * @param string $element
     * @param string $attribute
     * @return boolean
     */
    public final function has( $element , $attribute = '' ){
        
        if( array_key_exists($element, $this->_content) ){
            if(strlen($attribute) ){
                return array_key_exists( $attribute, $this->_content[$element]);
            }
            return TRUE;
        }

        $has = sprintf('has%s',preg_replace('/\s/', '',ucwords( preg_replace('/[\_\-]/', ' ', $element ) ) ) );
        return method_exists($this, $has) ? $this->$has() : false;
    }
    /**
     * @param string $element
     * @param string $attribute
     * @param mixed $default
     * @return mixed
     */
    protected function get( $element , $attribute , $default = FALSE ){
        if( $this->has($element, $attribute)){
            return $this->_content[$element][$attribute];
        }
        return $default;
    }
    /**
     * @param string $element
     * @return mixed
     */
    public function value( $element ){
        switch( $this->type($element)){
            case self::TYPE_CHECKBOX:
                return $this->get($element, 'value' );
            case self::TYPE_CURRENCY:
            case self::TYPE_FLOAT:
            case self::TYPE_NUMBER:
                return $this->get($element, 'value' , 0 );
            default:
                return $this->get($element, 'value', '');
        }
        //return $this->get($element, 'value', $default );
    }
    /**
     * @param string $name
     * @return number
     */
    public final function count( $name ){
        $count = 'count'. preg_replace('/\s/', '',ucwords( preg_replace('/[\_\-]/', ' ', $name ) ) );
        return method_exists($this, $count) ? $this->$count( ) : 0;
    }
    /**
     * @param string $name
     * @return boolean
     */
    public function is( $name ){
        $is = 'is'. preg_replace('/\s/', '',ucwords( preg_replace('/[\_\-]/', ' ', $name ) ) );
        return method_exists($this, $is) ? $this->$is( ) : false;
    }
    /**
     * @param string $name
     * @param int $amount
     * @return int
     */
    public function increase( $name , $amount = 1 ){
        if( $this->has($name) ){
            switch( $this->type($name)){
                case self::TYPE_FLOAT:
                case self::TYPE_CURRENCY:
                case self::TYPE_NUMBER:
                    $value = $this->value($name) + $amount;
                    $this->change($name, $value , true );
                    return $value;
            }
        }
        return 0;
    }

    /**
     * @param string $name
     * @return array
     */
    public final function list( $name ){
        $list = 'list'. preg_replace('/_/', '', ucwords($name) );
        return method_exists($this, $list) ? $this->$list( ) : array();
    }
    /**
     * @param string $element
     * @return array
     */
    public function options( $element ){
        $list = $this->meta($element, 'options');
        return strlen($list) ? $this->list($list) : array();
    }
    /**
     * @param string $element
     * @param string $meta
     * @param mixed $default
     * @return mixed
     */
    public function meta( $element , $meta , $default = '' ){
        return $this->has($element) ? $this->get($element,$meta) : $default;
    }

    /**
     * @param string $element
     * @param string $attribute
     * @param mixed $value
     * @return \CODERS\Framework\Model
     */
    protected final function set( $element , $attribute , $value ){
        if(array_key_exists($element, $this->_content)){
            $this->_content[$element][ $attribute ] = $value;
        }
        return $this;
    }
    /**
     * @param string $element
     * @param mixed $value
     * @param boolean $update
     * @return \CODERS\Framework\Model
     */
    protected function change( $element , $value = FALSE , $update = FALSE ){
        $customSetter = sprintf('set%sValue',$element);
        if(method_exists($this, $customSetter)){
            //define a custom setter for a more extended behavior
            $this->$customSetter( $value );
            //$this->set($element, 'updated', true);
        }
        elseif( $this->has($element)){
            switch( $this->type($element)){
                case self::TYPE_CHECKBOX:
                    return $this->set($element,
                            'value',
                            is_bool($value) ? $value : FALSE )
                            ->set($element, 'updated', $update);
                case self::TYPE_CURRENCY:
                case self::TYPE_FLOAT:
                    return $this->set($element,'value',floatval($value))
                        ->set($element, 'updated', $update);
                case self::TYPE_NUMBER:
                    return $this->set($element,'value',intval($value))
                        ->set($element, 'updated', $update);
                default:
                    return $this->set($element,'value',strval($value))
                        ->set($element, 'updated', $update);
            }
        }
        return $this;
    }
    /**
     * @param string $element
     * @return string|boolean
     */
    public final function type( $element ){
        return $this->has($element) ? $this->_content[$element]['type'] : FALSE;
    }
    /**
     * @return array
     */
    protected final function dictionary(){ return $this->_content; }
    /**
     * @return array
     */
    public final function elements(){ return array_keys($this->_content); }
    /**
     * @return array
     */
    public final function values(){
        $output = array();
        foreach( $this->elements() as $element ){
            $output[$element] = $this->value( $element );
        }
        return $output;
    }
    /**
     * @return array
     */
    public final function updated(){
        $updated = array();
        foreach( $this->elements() as $element ){
            if( $this->get($element, 'updated', FALSE)){
                $updated[$element] = $this->value($element);
            }
        }
        return $updated;
    }
    /**
     * @return \CODERS\Framework\Query
     */
    protected final function db(){ return new Query($this->endpoint()); }
    /** 
     * @param array $filters
     * @return \CODERS\Framework\Model
     */
    public final function from( array $filters ){
        $data = $this->load($this->ds(),$filters);
        if( count($data )){
            $this->import($data[0]);
        }
        return $this;
    }

    /**
     * @param array $route
     * @return string
     */
    private static final function __importClass( array $route ){
        return count($route) > 1 ?
                    sprintf('\CODERS\%s\%sModel',
                            \CodersApp::Class($route[0]),
                            \CodersApp::Class($route[1])) :
                    sprintf('\CODERS\Framework\Models\%s',
                            \CodersApp::Class($route[0]));
    }
    /**
     * @param array $route
     * @return String|PAth
     */
    private static final function __importPath( array $route ){
        return count($route) > 1 ?
                    sprintf('%s/components/models/%s.php',
                            \CodersApp::path($route[0]),
                            $route[1]) :
                    sprintf('%s/components/models/%s.php',
                            \CodersApp::path(),
                            $route[0]);
    }
    /**
     * @param string $model
     * @return string
     * @throws \Exception
     */
    private static final function __preload( $model ){
        $route = explode('.', $model);
        $class = self::__importClass($route);
        try{
            if(!class_exists($class)){
                $path = self::__importPath($route);
                if(file_exists($path)){
                    require_once $path;
                }
                else{
                    throw new \Exception(sprintf('Invalid path [%s]',$path) );
                }
            }
            if( class_exists($class) && is_subclass_of($class, self::class) ){
                return $class;
            }
            else{
                throw new \Exception(sprintf('Invalid Model [%s]',$class) );
            }
        }
        catch (\Exception $ex) {
            \CodersApp::notify($ex->getMessage());
        }
        return '';
    }

    /**
     * @param string $model
     * @param array $data
     * @return \CODERS\Framework\Model | boolean
     * @throws \Exception
     */
    public static final function create($model, $data = array()) {
        
        $class = self::__preload($model);

        return strlen($class) ? new $class( $data ) : null;
    }
    /**
     * @param string $source
     * @param array $filters
     * @return \CODERS\Framework\Model[]
     */
    public static function fill( $source , array $filters = array() ){

        $output = array();
        $class = self::__preload($source);

        if(strlen($class)){
            foreach( self::load($source,$filters) as $row ){
                //var_dump($data);
                $output[] = new $class( $row );
            }
        }
        return $output;
    }
    /**
     * @param string $source
     * @param array $collection
     * @return \CODERS\Framework\Model[]
     */
    public static function browse( $source , array $collection ){
        $output = array();
        $class = self::__preload($source);
        if (strlen($class)) {
            foreach ($collection as $row) {
                $output[] = new $class($row);
            }
        }
        return $output;
    }

    /**
     * @param string $source
     * @param string $id
     * @param string $index
     * @return \CODERS\Framework\Model
     */
    public static final function select( $source , $id , $index = 'id' ){
        $found = self::fill($source , array( $index => $id ) );
        return count($found) ? $found[0] : null;
    } 
    /**
     * @param string $source
     * @param array $filters
     * @param callable $callback
     * @return array
     */
    public static function load( $source = '' , array $filters = array() ){
        $module = explode('.', $source);
        if( count( $module ) > 1 ){
            $db = new Query($module[0]);
            return $db->select( $module[1], '*' , $filters );
        }
        return array();
    }
    /**
     * @param string $model
     * @return \CODERS\Framework\Model
     */
    public static function empty($model) {
        return self::create($model);
    }
}

/**
 * WPDB Query Handler
 */
final class Query {
    
    private $_endpoint;
    
    /**
     * 
     * @param type $endpoint
     */
    public final function __construct( $endpoint ) {
        $this->_endpoint = $endpoint;
    }
    /**
     * @global \wpdb $wpdb
     * @return \wpdb
     */
    private static final function db(){
        global $wpdb;
        return $wpdb;
    }
    /**
     * 
     * @return \CODERS\Framework\Query
     */
    private final function checkErrors(){
        $last_error = $this->db()->last_error;
        if( strlen( $last_error ) ){
            \CodersApp::notify($last_error,'error', is_admin(),true);
        }
        return $this;
    }
    /**
     * @global string $table_prefix
     * @return string
     */
    public final function prefix(){
        global $table_prefix;
        return $table_prefix . preg_replace('/\-/', '_',  $this->_endpoint );
    }
    /**
     * @param string $table
     * @param bool $quote
     * @return string
     */
    public final function table( $table , $quote = false ){
        return $quote ?
                sprintf('`%s_%s`',$this->prefix(),$table):
                sprintf('%s_%s',$this->prefix(),$table);
    }
    
    /**
     * @param array $filters
     * @param string $join
     * @return array
     */
    private final function where( array $filters , $join = 'AND' ) {
        
        $where = array();

        foreach ($filters as $var => $val) {
            switch (TRUE) {
                case is_string($val):
                    $where[] = sprintf("`%s`='%s'", $var, $val);
                    break;
                case is_object($val):
                    $where[] = sprintf("`%s`='%s'", $var, $val->toString());
                    break;
                case is_array($val):
                    $where[] = sprintf("`%s` IN ('%s')", $var, implode("','", $val));
                    break;
                default:
                    $where[] = sprintf('`%s`=%s', $var, $val);
                    break;
            }
        }

        return implode($join, $where);
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $filters
     * @param string $index
     * @return array
     */
    public final function select( $table, $columns = '*', array $filters = array() , $index = '' ) {
        
        $select = array();
        
        switch( TRUE ){
            case is_array($columns):
                $select[] = count($columns) ?
                    sprintf("SELECT %s"  , implode(',', $columns) ) :
                    "SELECT *";
                break;
            case is_string($columns) && strlen($columns):
                $select[] = sprintf("SELECT %s"  , $columns );
                break;
            default:
                $select[] = "SELECT *";
                break;
        }
        
        $select[] = sprintf("FROM `%s`", $this->table($table) );
        
        if (count($filters)) {
            $select[] = " WHERE " . $this->where($filters );
        }
        
        return $this->query( implode(' ', $select) , $index );
    }
    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    public final function insert( $table , array $data ){
        
        $db = self::db();
        
        $columns = array_keys($data);

        $values = array();
        
        foreach( $data as $val ){
            if(is_array($val)){
                //listas
                $values[] = sprintf("'%s'",  implode(',', $val));
            }
            elseif(is_numeric($val)){
                //numerico
                $values[] = $val;
            }
            else{
                //texto
                $values[] = sprintf("'%s'",$val);
            }
        }
        
        $sql_insert = sprintf('INSERT INTO `%s` (%s) VALUES (%s)',
                $this->table($table),
                sprintf('`%s`', implode('`,`', $columns)),
                implode(',', $values));
        
        $result = $db->query($sql_insert);
        $this->checkErrors();
        
        return FALSE !== $result ? $result : 0;
    }
    /**
     * @param string $table
     * @param array $data
     * @param array $filters
     * @return int
     */
    public final function update( $table , array $data , array $filters ){

        $db = self::db();

        $values = array();
        
        foreach( $data as $field => $content ){
            
            if(is_numeric( $content)){
                $value = $content;
            }
            elseif(is_array($content)){
                $value = implode(',',$content);
            }
            else{
                $value = sprintf("'%s'",$content);
            }
            
            $values[] .= sprintf("`%s`=%s",$field,$value);
        }
        
        $sql_update = sprintf( "UPDATE %s SET %s WHERE %s",
                $this->table($table),
                implode(',', $values),
                $this->where($filters));
        
        $result = $db->query($sql_update);
        $this->checkErrors();
        
        return FALSE !== $result ? $result : 0;
    }
    /**
     * @param string $table
     * @param array $filters
     * @return int
     */
    public final function delete( $table, array $filters ){
               
        $db = self::db();

        $result = $db->delete($this->table($table), $filters);
        
        $this->checkErrors();
        
        return FALSE !== $result ? $result : 0;
    }
    /**
     * @param string $SQL_QUERY
     * @param string $index
     * @return array
     */
    public final function query( $SQL_QUERY , $index = '' ){
        
        $db = self::db();

        $result = $db->get_results($SQL_QUERY, ARRAY_A );

        $this->checkErrors();

        if( !is_null($result) && count( $result ) ){
            
            if( strlen($index) ){
                $output = array();
                foreach( $result as $row ){
                    if( isset( $row[$index])){
                        $output[ $row[ $index ] ] = $row;
                    }
                }
                return $output;
            }

            return $result;
        }
        return array();
    }
    /**
     * @param string $table
     * @return boolean
     */
    private final function __tableExists( $table ){
        $parsed = $this->table($table);
        //$this->db()->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
        $query = $this->db()->prepare( 'SHOW TABLES LIKE %s',$parsed );
        $result = $this->db()->get_var($query);
        return !is_null($result) && $result === $parsed;
    }
    /**
     * @param string $table
     * @param array $data
     * @return array
     */
    private final function __updateTable( $table , array $data ){
        
        return array();
        
        $elements = array();
        
        foreach( $data as $column => $def ){
            if( is_array($def)){
                $type = array_key_exists('type', $def) ? $def['type'] : 'text';
                $size = array_key_exists('size', $def) ? $def['size'] : 8;
                $index = array_key_exists('index', $def) && $def['index'];
                $required = array_key_exists('required', $def) && $def['required'];
                $default = array_key_exists('default', $def) ? $def['default'] : '';
                $content = array( '`' . $column . '`' );

                if(strlen($default)){ $content[] = $default === 'CURRENT_TIMESTAMP' ? sprintf("DEFAULT %s",$default) : sprintf("DEFAULT '%s'",$default); }
                if( $required ){ $content[] = 'NOT NULL'; }
                if( $index ){ $content[] = 'PRIMARY KEY'; }
                $elements[] = implode(' ', $content);
            }
            elseif(is_string ($def)){
                $elements[] = $def;
            }
        }
        
        return $elements;
    }
    /**
     * @param string $table
     * @param array $data
     * @return array
     */
    private final function __createTable( $table , array $data ){

        $elements = array();
        foreach( $data as $column => $def ){
            if( is_array($def)){
                $type = array_key_exists('type', $def) ? $def['type'] : 'text';
                $size = array_key_exists('size', $def) ? $def['size'] : 8;
                $index = array_key_exists('index', $def) && $def['index'];
                $required = $index || array_key_exists('required', $def) && $def['required'];
                $default = array_key_exists('default', $def) ? $def['default'] : '';
                $content = array( '`' . $column . '`' );
                switch( $type ){
                    case 'incremental':
                        $content[] = sprintf('INT(%s) AUTO_INCREMENT',$size);
                        break;
                    case 'number':
                        $content[] = sprintf('INT(%s)',$size);
                        break;
                    case 'date_time':
                        $content[] = sprintf('DATETIME');
                        break;
                    case 'date':
                        $content[] = sprintf('DATE');
                        break;
                    case 'time':
                        $content[] = sprintf('TIME');
                        break;
                    case 'timestamp':
                        $content[] = sprintf('TIMESTAMP');
                        break;
                    case 'longtext':
                        $content[] = 'LONGTEXT';
                        break;
                    case 'text':
                    default:
                        $content[] = sprintf('VARCHAR(%s)',$size);
                        break;
                }
                if(strlen($default)){ $content[] = $default === 'CURRENT_TIMESTAMP' ? sprintf("DEFAULT %s",$default) : sprintf("DEFAULT '%s'",$default); }
                if( $required ){ $content[] = 'NOT NULL'; }
                if( $index ){ $content[] = 'PRIMARY KEY'; }
                $elements[] = implode(' ', $content);
            }
            elseif(is_string ($def)){
                $elements[] = $def;
            }
        }
        return $elements;
    }

    /**
     * @param array $schema TABLE type[text,number,incremental] size[8], index[false] required[false]
     * @return boolean
     */
    public final function __install( array $schema = array()){
        
        require_once( sprintf( '%swp-admin/includes/upgrade.php' , ABSPATH ) );

        $sql = array();
        
        foreach( $schema as $table => $data ){

            if( $this->__tableExists($table)){
                \CodersApp::notify(sprintf('%s already exists',$this->table($table)),'warning',true,true);
            }
            else{
                $create = $this->__createTable($table, $data);
                $sql[] = sprintf('CREATE TABLE %s ( %s ) %s',
                        $this->table($table,true),
                        implode(' , ',$create),
                        $this->db()->get_charset_collate());
                $this->__createTable($table, $data);
            }
        }
        
        if( count( $sql ) ){
            //run each sql
            $counter = 0;
            foreach( $sql as $query ){
                $result = dbDelta($query);
                \CodersApp::notify(json_encode($result));
                //print($query);
                //var_dump($result);
                $counter++;
            }
            return $counter === count( $sql );
        }
        
        return false;
    }
    /**
     * @param array $tables
     * @return boolean
     */
    public final function __uninstall( array $tables){
        $remove = array();
        foreach( $tables as $table ){
            $remove[] = $this->table($table);
        }
        $sql = sprintf('DROP TABLE IF EXISTS %s', implode(' ', $remove));
        $result = $this->db()->query($sql);
        if( $result ){
            \CodersApp::notify('tables removed ' . implode(', ', $remove));
        }
        return $result;
    }
}


