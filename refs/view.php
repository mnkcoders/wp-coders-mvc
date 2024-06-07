<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

//use CODERS\Framework\Dictionary;
/**
 * 
 */
abstract class ViewRef{
    const INPUT_FILE = 'file';
    const INPUT_HIDDEN = 'hidden';

    /**
     * @var \CODERS\Framework\Model
     */
    private $_model = NULL;
    private $_layout = 'default';
    private $_module;
    /**
     * @var \CODERS\Framework\Strings
     */
    private $_strings = null;
    
    private $_activeForm = '';
    /**
     * @var array
     */
    private $_inbox = array();

    /**
     * @var URL
     */
    const GOOGLE_FONTS_URL = 'https://fonts.googleapis.com/css?family';
    

    /**
     * @var array Scripts del componente
     */
    private $_scripts = array();
    /**
     * @var array Estilos del componente
     */
    //private $_styles = array();
    /**
     * @var array Links del componente
     */
    private $_links = array();
    /**
     * @var array Metas adicionales del componente
     */
    private $_metas = array();
    /**
     * @var array List all body classes here
     */
    private $_classes = array('coders-framework');
    /**
     * @param string $route
     */
    protected function __construct( ) {

        //$this->_module = is_array($route) ? $route : explode('.', $route);
        $this->_module = $this->__extractModule();
    }
    /**
     * @return \CODERS\Framework\View
     */
    private final function preloadStrings(){
        if( is_null($this->_strings) && class_exists('\CODERS\Framework\Strings')){
            $this->_strings = \CODERS\Framework\Strings::create($this->endPoint());
        }
        return $this;
    }
    /**
     * @return String
     */
    public function __toString() {
        return $this->endpoint(TRUE);
    }
    /**
     * @return boolean
     */
    protected final function __debug(){ return \CodersApp::debug(); }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        switch( true ){
            case preg_match('/^get_/', $name):
                $get = preg_replace('/\_/', '', $name );
                return method_exists($this, $get) ? $this->$get()  : '';
            case preg_match('/^form_/', $name):
                return $this->__formElement(substr($name, 5));
        }
        return $this->hasModel() ? $this->_model->$name : '';
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        switch( true ){
            case $name === 'dump':
                return $this->hasModel() ? get_class($this->_model) : '';
            case $name === 'debug':
                return $this->__debug();
            case $name === 'open_form':
                return $this->openForm( $arguments );
            case $name === 'close_form':
                return $this->closeForm();
            case $name === 'html':
                return count($arguments) > 1 ? Renderer::html(
                            $arguments[0],
                            is_array($arguments[1]) ? $arguments[1] : array(),
                            count($arguments) > 2 ? $arguments[2] : null ) :
                            '<!-- empty html -->';
            case $name === 'action' :
                if( count( $arguments) ){
                    return $this->__action(
                                $arguments[0],
                                count($arguments) > 1 && is_array($arguments[1]) ? $arguments[1] : array(),
                                count($arguments) > 2 && is_bool($arguments[2]) ? $arguments[2] : is_admin() );
                }
                return sprintf('<!-- action id required -->');

            case preg_match('/^action_/', $name):
                $action = preg_replace('/[\-\_]/','.',substr($name, 7));
                return $this->__action(
                            $action,
                            count($arguments) && is_array($arguments[0]) ? $arguments[0] : array(),
                            count($arguments) > 1 && is_bool($arguments[1]) ? $arguments[1] : is_admin() );
            case preg_match('/^submit_/', $name):
                $action = substr($name, 7);
                $content = count($arguments) && is_string($arguments[0])? $arguments[0] : $this->__( $name);
                $attributes = count($arguments) > 1 ? $arguments[1] : $arguments[0];
                return $this->__submit($action,$content, is_array( $attributes) ? $attributes : array() );
            case preg_match('/^button_/', $name):
                $action = substr($name, 7);
                $content = count($arguments) && is_string($arguments[0])? $arguments[0] : $this->__( $name);
                $attributes = count($arguments) > 1 ? $arguments[1] : $arguments[0];
                return $this->__button($action,$content, is_array( $attributes) ? $attributes : array() );
            
            case preg_match('/^is_/', $name):
                return $this->hasModel() ? $this->_model->is(substr($name, 3)) : false;
            case preg_match('/^has_/', $name):
                return $this->hasModel() ? $this->_model->has(substr($name, 4)) : false;
            case preg_match('/^count_/', $name):
                return $this->hasModel() ? $this->_model->count(substr($name, 6)) : false;
            case preg_match('/^input_/', $name):
                $type = substr($name, 6);
                return $this->__input(
                        count($arguments) ? $arguments[0] : '_' . $type,
                        $type,
                        count($arguments) > 1 ? $arguments[1] : array());
            case preg_match(  '/^list_/' , $name ):
                return $this->__list(substr($name, 5));
            case preg_match(  '/^value_/' , $name ):
                return $this->value(substr($name, 6));
            case preg_match(  '/^attribute_/' , $name ):
                return $this->has($name) ? $this->_model->get(substr($name, 10)) : '';

            case preg_match(  '/^display_/' , $name ):
                return $this->__display(substr($name, 8),$arguments);
            case preg_match(  '/^label_/' , $name ):
                return $this->label(substr($name, 6));
            case $name === 'label':
                return count($arguments) ? $this->label($arguments[0]) : '';
        }
        return $this->hasModel() ? $this->model()->$name( $arguments ) : '';
    }
    /**
     * @param string $name
     * @param string $content
     * @param array $args
     * @return String
     */
    protected function __submit( $name , $content = '' , array $args = array()){

        $args['class'] = array_key_exists('class',$args) ?
            $args['class'] :
                'button ' . preg_replace('/\./', '-', $name);
        
        return Renderer::submit($name, $this->__(is_string($content) ? $content : 'submit_'.$name) , $args );
    }
    /**
     * @param string $name
     * @param string $content
     * @param array $args
     * @return String
     */
    protected function __button( $name , $content = '', array $args = array()){
        
        $args['class'] = array_key_exists('class',$args) ?
            $args['class'] :
                'button ' . preg_replace('/\./', '-', $name);

        return Renderer::button($name, $this->__( is_string($content) ? $content : 'button_'.$name), $args );
    }
    /**
     * @param string $action
     * @param array $params
     * @param bool $admin
     * @return String
     */
    protected function __action( $action , $params = array() , $admin = false ){

        if(strlen($action) && $action !== '.' ){
            $route = explode('.', $action);

            if( count($route) < 2 ){
                $action = strlen($route[0]) ?
                        sprintf('.%s.%s', $this->context(), $route[0]) :
                        '.' . $this->context();
            }
            elseif( count( $route) < 3 && strlen($route[0]) ){
                $action = sprintf('.%s.%s',
                        strlen($route[0]) ? $route[0] : $this->context(),
                        $route[1]);                
            }
        }
        else{
            $action = '.' . $this->context();
        }

        return Request::createLink($action, $params , $admin );
    }
    /**
     * @return string
     */
    protected function endpoint( $full = FALSE ){
        return $full ? implode('.', $this->_module) : $this->_module[0];
    }
    /**
     * @return string
     */
    public function context(){
        return implode('.', $this->_module);
    }
    /**
     * @param bool $full
     * @return string
     */
    public function module( $full = false ){
        return $full ? 
                implode('.', $this->_module):
                $this->_module[ count($this->_module) - 1 ] ;
        //$path = explode('/',  $this->__path(true));
        //return $path[count($path)-1] ;
    }
    /**
     * @return \CODERS\Framework\Model
     */
    protected function model(){
        return $this->_model;
    }
    /**
     * @param string $name
     * @param string $type
     * @param array $atts
     * @return string|HTML
     */
    protected function __input( $name , $type , array $atts = array()){
        
        $value = isset($atts['value']) ? $atts['value'] : '';
        
        $options = isset( $atts['options'] ) ? $this->__list($atts['options']) : array();
        
        switch( $type ){
            case self::INPUT_FILE:
                return Renderer::inputFile($name, $atts);
            case self::INPUT_HIDDEN:
                return Renderer::inputHidden($name, $value);
            case Model::TYPE_DROPDOWN:
                return Renderer::inputDropDown( $name, $options, $value, $atts);
            case Model::TYPE_LIST:
                return Renderer::inputList( $name, $options, $value, $atts );
            case Model::TYPE_OPTION:
                return Renderer::inputOptionList( $name, $options, $value, $atts);
            case Model::TYPE_CHECKBOX:
                $value = isset($atts['value']) && is_bool($atts['value']) ? $atts['value'] : false;
                return Renderer::inputCheckBox($name, $value, 1, $atts);
            case Model::TYPE_FLOAT:
            case Model::TYPE_NUMBER:
                return Renderer::inputNumber($name, 0, $atts);
            case Model::TYPE_DATE:
            case Model::TYPE_DATETIME:
                return Renderer::inputDate( $name, $value, $atts );
            case Model::TYPE_EMAIL:
                return Renderer::inputEmail( $name, $value, $atts);
            //case Model::TYPE_TELEPHONE:
            //    return Renderer::inputTelephone(
            //            $name, $this->value($name),
            //            array('class' => 'form-input'));
            case Model::TYPE_PASSWORD:
                return Renderer::inputPassword( $name, $atts );
            case Model::TYPE_TEXTAREA:
                return Renderer::inputTextArea($name, '', $atts);
            case Model::TYPE_TEXT:
                return Renderer::inputText($name, '', $atts);
        }
        return sprintf('<!-- invalid input type %s -->',$type);
    }

    /**
     * List to override and add custom inputs
     * @param string $name
     * @param string $type
     * @return String|HTML
     */
    private function __formElement( $name ){
        
        if(strlen($type) === 0 ){
            $type = $this->type($name);
        }
        
        $atts = array(
            'value' => $this->value($name),
        );
        
        switch( $type ){
            case Model::TYPE_DROPDOWN:
            case Model::TYPE_LIST:
            case Model::TYPE_OPTION:
                $atts['options'] = $this->hasModel() ? $this->_model->meta($name, 'options' , $name) : $name;
                break;
        }
        
        return $this->__input($name, $type, $atts);
    }
    /**
     * @param string|array $class
     * @return \CODERS\Framework\View
     */
    protected function addBodyClass( $class ){
        if( !is_array($class)){
            $class = explode(' ', $class);
        }
        $this->_classes = array_merge($this->_classes,$class);
        return $this;
    }
    /**
     * @param array $content
     * @return \CODERS\Framework\View
     */
    protected function addMeta( array $content ){
        $this->_metas[] = $content;
        return $this;
    }
    /**
     * @param string $asset
     * @return String|URL
     */
    protected function contentUrl( $asset ){
        //$path = explode('/wp-content/plugins/',  $this->__path() )[1];
        return sprintf('%s/contents/%s',
                plugins_url( $this->endpoint() ),
                $asset);
    }
    /**
     * @param string $link_id
     * @param string $url
     * @param string $type
     * @return \CODERS\Framework\View
     */
    protected function addLink( $link_id , $url , $type , array $atts = array()){
        if( !array_key_exists($link_id, $this->_links)){
            $atts['id'] = $link_id;
            $atts['type'] = $type;
            $atts['href'] = $url;
            $this->_links[$link_id] = $atts;
        }
        return $this;
    }
    /**
     * @param string $style_id
     * @param string $url
     * @param string $type text/css default
     * @return \CODERS\Framework\View
     */
    protected function addStyle( $style_id , $url , $type = 'text/css'){
        return $this->addLink( $style_id, $url, $type, array('rel'=>'stylesheet'));
    }
    /**
     * @param string $script_id
     * @param string $url
     * @return \CODERS\Framework\View
     */
    protected function addScript( $script_id , $url = '', $dependencies = '' , array $localized = array() ){
        if( !array_key_exists($script_id, $this->_scripts)){
            $atts = array(
                'id' => $script_id,
                'src' => $url,
                'type' => 'text/javascript', //parameter this?
            );
            if(strlen($dependencies)){
                $atts['deps'] = $dependencies;
            }
            if( count( $localized )){
                $atts['localized'] = $localized;
            }
            $this->_scripts[$script_id] = $atts;
        }
        return $this;
    }
    /**
     * @param array $args name action method enctype
     * @return String
     */
    protected function openForm( array $args ){
        if( count($args) && $this->_activeForm  === '' ){
            $name = $args[0];
            $admin = count($args) > 4 ? is_bool( $args[4] ) && $args[4] : is_admin();
            $action = count($args) > 1 ?
                    Request::createLink($args[1] , array() , $admin) :
                    filter_input(INPUT_SERVER, 'PHP_SELF' );
            $method = count($args) > 2 ? $args[2] : 'post';
            $type = count($args) > 3 && strlen($args[3]) ? $args[3] : Renderer::FORM_TYPE_ENCODED;
            
            $this->_activeForm = $name;
            return sprintf('<!-- opening form [%s] --><form name="%s" method="%s" action="%s" encType="%s">',
                    $name, $name, $method, $action, $type);
        } 
        return sprintf('<!-- form [%s] is open -->',$this->_activeForm);
    }
    /**
     * @return string
     */
    protected function closeForm(){
        if( strlen($this->_activeForm)){
            $form = $this->_activeForm;
            $this->_activeForm = '';
            return sprintf('</form><!-- form [%s] closed -->',$form);
        }
        return '<!-- no active form to close -->';
    }
    /**
     * @param string $name
     * @return string
     */
    protected function value( $name ){
        return $this->has($name) ? $this->_model->$name : '';
    }
    /**
     * @param string $name
     * @param string $meta
     * @return string
     */
    protected function meta( $name , $meta ){

        return $this->has($name) ?
                $this->_model->meta($name, $meta ):
                sprintf('<!-- DATA %s NOT FOUND -->',$name);
    }
    /**
     * @param string $name
     * @return number
     */
    protected final function __count( $name ){
        $count = sprintf('count%s', preg_replace('/\s/', '', ucwords( preg_replace('/[\_\-]/', ' ', $name))));
        return method_exists($this, $count) ? $this->$count() : $this->hasModel() ? $this->_model->count($name) : 0;
    }
    /**
     * @param string $list
     * @return array
     */
    protected function __list( $list ){
        
        $call = 'list'. preg_replace('/_/', '', ucwords($list) );
        
        if(method_exists($this, $call)){
            return $this->$call( );
        }
        
        return $this->hasModel() ? $this->_model->list($list) : array();
    }
    /**
     * @param string $name
     * @return string
     */
    protected function label( $name ){
        return $this->__($name);
    }
    /**
     * @param string $key
     * @return string
     */
    protected function __( $key ){
        return !is_null($this->_strings) ? $this->_strings->__( $key ) : $key;
    }
    /**
     * @param string $element
     * @return boolean
     */
    public function has( $element ){
        return $this->hasModel() && $this->_model->has($element);
    }
    /**
     * @return boolean
     */
    public function hasModel(){
        return !is_null($this->_model);
    }
    /**
     * @param string $name
     * @return string
     */
    public function type( $name ){
        return $this->has($name) ? $this->_model->type($name) : '';
    }

        
    /**
     * @param \CODERS\Framework\Model $model
     * @return \CODERS\Framework\View
     */
    public function setModel( Model $model = null ){
        if(!is_null($model) && is_null($this->_model)){
            $this->_model = $model;
        }
        return $this;
    }
    /**
     * @param string $layout
     * @return \CODERS\Framework\View
     */
    public function setLayout( $layout ){
        $this->_layout = $layout;
        return $this;
    }
    /**
     * @param array $input
     * @return \CODERS\Framework\View
     */
    public function importMessages( $input ){
        if(is_array($input)){
            foreach( $input as $msg){
                $this->_inbox[] = $msg;
            }
        }
        elseif(is_string($input)){
            $this->_inbox[] = $input;
        }
        return $this;
    }
    /**
     * 
     */
    private function __extractModule(){
        $path = $this->__path();
        $view = preg_replace('/.php$/', '',  substr($path, strrpos($path, '/') + 1 ) );
        $route = explode('/components/views/', $path);
        $base = count($route) > 1 ?
                substr($route[0], strrpos($route[0], '/') + 1) :
                'coders-framework';
        return array( $base , $view );
    }
    /**
     * @return string |PATH
     */
    protected function __path(){
        $ref = new \ReflectionClass(get_called_class());
        return preg_replace('/\\\\/', '/',  dirname( $ref->getFileName() ) );
    }
    /**
     * @param string $view
     * @param string $type
     * @return string
     */
    protected function __display( $view ){
        
        $path = sprintf('%s/html/%s.php',$this->__path(),$view);
        
        if(file_exists($path)){
            require $path;
        }
        elseif(\CodersApp::debug()){
            //printf('<!-- html: %s.%s not found -->',$this->endpoint(), $view);
            printf('<p class="info">html layout not found [%s]</p>',$path);
        }
        else{
            printf('<!-- html: %s not found -->',$path);
        }
    }
    /**
     * @return string
     */
    protected function getAdminPageTitle(){
        return get_admin_page_title();
    }
    /**
     * Setup all view contents
     * @return \CODERS\Framework\View
     */
    protected function prepare(){
        
        $this->addMeta( array( 'charset'=> get_bloginfo('charset')));
        $this->addMeta(array('name'=>'viewport','content' => 'width=device-width, initial-scale=1.0'));
        $this->addBodyClass(preg_replace('/\./', '-', implode(' ', $this->_module)));
        
        $metas = $this->_metas;
        $links = $this->_links;
        $scripts = $this->_scripts;

        add_action('wp_head', function() use( $metas, $links ) {
            foreach ($metas as $content) {
                print \CODERS\Framework\Renderer::html('meta', $content);
            }
            foreach ($links as $link_id => $content) {
                $content['id'] = $link_id;
                print \CODERS\Framework\Renderer::html('link', $content);
            }
        });
        add_action( 'wp_enqueue_scripts' , function() use( $scripts ) {
            foreach ($scripts as $script_id => $content) {
                if(strlen($content['src'])) {
                    wp_enqueue_script(
                            $script_id, $content['src'],
                            strlen($content['deps']) ? explode(' ', $content['deps']) : array(),
                            false, TRUE);
                }
                else{
                    wp_enqueue_script( $script_id );
                }
                if( isset( $content['localized'])){
                    wp_localize_script($script_id,
                            preg_replace('/\s/', '', ucwords(preg_replace('/[\-_]/', ' ', $script_id ) )  ),
                            $content['localized']);
                }
            }
        });
        
        return $this;
    }
    /**
     * @return \CODERS\Framework\View
     */
    protected function showAdminMessages(){
        foreach( $this->_inbox as $msg ){
            printf('<div class="notice is-dimissible"><p>%s</p></div>',$msg );
        }
        return $this;
    }
    /**
     * Start the rendering
     * @return \CODERS\Framework\View
     */
    protected function renderHeader( ){
        if(is_admin()){
            $this->showAdminMessages();
            printf('<!-- [%s] opener --><div class="wrap"><h1>%s</h1>', $this->endpoint(true), $this->getAdminPageTitle());
        }
        else{
            printf('<html %s ><head>',get_language_attributes());
            wp_head();
            printf('</head><body class="%s">', implode(' ',  get_body_class(implode(' ', $this->_classes))));
        }
        return $this;
    }
    /**
     * Render the application content
     * @return \CODERS\Framework\View
     */
    protected function renderContent(){
        
        printf('<div class="container %s %s">',$this->endpoint(),$this->_layout);
        
        if( $this->__debug() && !$this->hasModel()){
            printf('<!--No model set for view [%s] -->',$this->endpoint(true));
        }
        
        //override here
        $this->__display($this->_layout);
        
        print('</div>');
        
        return $this;
    }
    /**
     * Stop and complete the rendering
     * @return \CODERS\Framework\View
     */
    protected function renderFooter(){
        if(is_admin()){
            //finalize the admin view setup
            printf('</div><!-- [%s] closer -->',$this->endpoint(true) );
        }
        else{
            wp_footer();
            print '</body></html>';            
        }
        return $this;
    }

        /**
     * @return \CODERS\Framework\View
     */
    public function show(){

        $this->preloadStrings()
                ->prepare()
                ->renderHeader()
                ->renderContent()
                ->renderFooter();

        return $this;
    }
    
    /**
     * @param array $route
     * @return string
     */
    private static final function __modClass( array $route ){
        //$route = explode('.', $route);
        return count($route) > 1 ?
                    sprintf('\CODERS\%s\%sView',
                            \CodersApp::Class($route[0]),
                            \CodersApp::Class($route[1])) :
                    sprintf('\CODERS\Framework\Views\%s',
                            \CodersApp::Class($route[0]));
    }
    /**
     * @param array $route
     * @return String|PAth
     */
    private static final function __modPath( array $route ){
        return count($route) > 1 ?
                    sprintf('%s/components/views/%s/view.php',
                            \CodersApp::path($route[0]),
                            $route[1]) :
                    sprintf('%s/components/views/%s/view.php',
                            \CodersApp::path(),
                            $route[0]);
    }
    /**
     * @param string $route
     * @return boolean|\CODERS\Framework\Views\Renderer
     */
    public static final function create( $route ){
        
        //$package = self::package($model);
        $namespace = explode('.', $route);
        $path = self::__modPath($namespace);
        $class = self::__modClass($namespace);
        try{
            if(file_exists($path)){
                require_once $path;
                if(class_exists($class) && is_subclass_of($class, self::class)){
                    return new $class( $route );
                }
                else{
                    throw new \Exception(sprintf('Invalid View [%s]',$class) );
                }
            }
            else{
                throw new \Exception(sprintf('Invalid path [%s]',$path) );
            }
        }
        catch (\Exception $ex) {
            \CodersApp::notify($ex->getMessage());
        }
        
        return null;
    }
}
/**
 * 
 */
class Renderer{
    
    const FORM_TYPE_DATA = 'multipart/form-data';
    const FORM_TYPE_ENCODED = 'application/x-www-form-urlencoded';
    const FORM_TYPE_PLAIN = 'text/plain';
    
    /**
     * @param string $string
     * @return string
     */
    public static final function __( $string ){
        return \CodersApp::__($string);
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

    /**
     * @param string $tag
     * @param mixed $attributes
     * @param mixed $content
     * @return String|HTML HTML output
     */
    public static function html( $tag, $attributes = array( ), $content = null ){
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
     * <meta />
     * @param array $attributes
     * @return HTML
     */
    public static function meta( array $attributes ){
        
        foreach( $attributes as $attribute => $value ){
            if(is_array($value)){
                $valueInput = array();
                foreach( $value as $valueVar => $valueVal ){
                    $valueInput[] = sprintf('%s=%s',$valueVar,$valueVal);
                }
                $attributes[] = sprintf('%s="%s"',$attribute, implode(', ', $valueInput) );
            }
            else{
                $attributes[] = sprintf('%s="%s"',$attribute,$value);
            }
        }
        
        return self::html('meta', $attributes );
    }
    /**
     * <link />
     * @param URL $url
     * @param string $type
     * @param array $attributes
     * @return HTML
     */
    public static final function link( $url , $type , array $attributes = array( ) ){
        $attributes[ 'href' ] = $url;
        $attributes[ 'type' ] = $type;
        return self::html( 'link', $attributes );
    }
    /**
     * <a href />
     * @param type $url
     * @param type $label
     * @param array $atts
     * @return HTML
     */
    public static function action( $url , $label , array $atts = array( ) ){
        
        $atts['href'] = $url;
        
        if( !isset($atts['target'])){
            $atts['target'] = '_self';
        }
        
        return self::html('a', $atts, $label);
    }
    /**
     * <button type="submit"></button>
     * @param string $name
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static final function submit( $name, $content = '' , array $atts = array()){
        
        $atts['type'] = 'submit';
        if(!array_key_exists('value', $atts)){
            $atts['value'] = $name;
        }
        
        return self::button($name , $content, $atts);
    }
    /**
     * <button></button>
     * @param string $name
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static function button( $name , $content = '' , array $atts = array( ) ){
        
        $atts['name'] = $name;
        
        if( !array_key_exists('type', $atts)){
            $atts['type'] = 'button';
        }
        
        return self::html('button', $atts, strlen($content) ? $content : self::__($name));
    }
    /**
     * <ul></ul>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    public static function listUnsorted( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::html('li', array('class'=>$itemClass) , $item ) :
                    self::html('li', array(), $item ) ;
        }
        
        return self::html( 'ul' , $atts ,  $collection );
    }
    /**
     * <ol></ol>
     * @param array $content
     * @param array $atts
     * @param mixed $itemClass
     * @return HTML
     */
    public static function listSorted( array $content , array $atts , $itemClass = '' ){
        
        $collection = array();
        
        foreach( $content as  $item ){
            $collection[] = !empty($itemClass) ?
                    self::html('li', array('class'=>$itemClass) , $item ) :
                    self::html('li', array(), $item ) ;
        }
        
        return self::html( 'ol' , $atts ,  $collection );
    }
    /**
     * <span></span>
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static final function span( $content , $atts = array( ) ){
        return self::html('span', $atts , $content );
    }
    /**
     * <img src />
     * @param string/URL $src
     * @param array $atts
     * @return HTML
     */
    public static final function image( $src , array $atts = array( ) ){
        
        $atts['src'] = $src;
        
        return self::html('img', $atts);
    }
    /**
     * <label></label>
     * @param string $input
     * @param string $text
     * @param mixed $class
     * @return HTML
     */
    public static function label( $text , array $atts = array() ){

        return self::html('label', $atts, $text);
    }
    /**
     * <span class="price" />
     * @param string $name
     * @param int $value
     * @param string $coin
     * @return HTML
     */
    public static function price( $name, $value = 0.0, $coin = '&eur', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        return self::html('span',
                $atts ,
                $value . self::html('span', array('class'=>'coin'), $coin));
    }
    /**
     * <input type="number" />
     * @param String $name
     * @param int $value
     * @param array $atts
     * @return HTML
     */
    public static function inputNumber( $name, $value = 0, array $atts = array() ){
        
        if( !isset($atts['min'])){ $atts['min'] = 0; }

        if( !isset($atts['step'])){ $atts['step'] = 1; }
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        $atts['name'] = $name;
        
        $atts['value'] = $value;
        
        $atts['type'] = 'number';
        
        return self::html('input', $atts);
    }
    /**
     * <textarea></textarea>
     * @param string $name
     * @param string $value
     * @param array $atts
     */
    public static function inputTextArea( $name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['placeholder'] = array_key_exists('placeholder', $atts)? $atts['placeholder'] : '';

        return self::html('textarea', $atts, $value);
    }
    /**
     * <input type="text" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputText($name, $value = '', array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['placeholder'] = array_key_exists('placeholder', $atts)? $atts['placeholder'] : '';
        $atts['type'] = 'text';
        
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="password" />
     * @param string $name
     * @param array $atts
     * @return HTML
     */
    public static function inputPassword( $name, array $atts = array() ){
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['type'] = 'password';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="search" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputSearch( $name, $value = '' , array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'search';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="date" />
     * Versión con jQuery UI
     * <input type="text" class="hasDatepicker" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputDate($name, $value = '', array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'date';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="tel" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputTelephone($name, $value = null, array $atts = array() ){

        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'tel';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="email" />
     * @param string $name
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputEmail($name, $value = '', array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'email';
        return self::html( 'input' , $atts );
    }
    /**
     * <input type="checkbox" />
     * @param string $name
     * @param boolean $checked
     * @param array $atts
     * @return HTML
     */
    public static function inputCheckBox( $name, $checked = false , $value = 1, array $atts = array() ){
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = 'checkbox';
        if($checked){ $atts['checked'] = 1; }
        return self::html( 'input' , $atts );
    }
    /**
     * Lista de opciones <input type="radio" />
     * @param String $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputOptionList( $name, array $options, $value = null, array $atts = array( ) ){


        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name );
        
        $radioItems = array();
        
        $baseAtts = array( 'type' => 'radio' , 'name' => $name );
        
        if( isset($atts['disabled']) ){
            $baseAtts['disabled'] = 'disabled';
            unset($atts['disabled']);
        }
        
        foreach( $options as $option => $label){
            
            $optionAtts = array_merge($baseAtts,array('value'=>$option));
            
            if( !is_null($value) && $option == $value ){
                $optionAtts['checked'] = 'checked';
            }
            
            $radioItems[ ] = self::html(
                    'li',
                    array(),
                    self::html( 'input', $optionAtts, $label) );
        }
        
        return self::html('ul', $atts, implode('</li><li>',  $radioItems));
    }
    /**
     * <select size="5" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputList($name, array $options, $value = null, array $atts = array() ){

        if( !isset($atts['id']) ){
            preg_replace('/-/', '_',  $name );
        }
        
        if( !isset($atts['size'])){
            $atts['size'] = 5;
        }
        
        $atts['id'] = 'id_'.$name;
        
        $atts['name'] = $name;
        
        $items = array();
        
        if( isset($atts['placeholder'])){
            $items[''] = sprintf('- %s -', $atts['placeholder'] );
            unset($atts['placeholder']);
        }

        foreach( $options as $option => $label ){
            $items[] = self::html('option', $option == $value ?
                    array('value'=> $option,'selected'=>'true') :
                    array('value'=>$option),
                $label);
        }
        
        return self::html('select', $atts, $items );
    }
    /**
     * <select size="1" />
     * @param string $name
     * @param array $options
     * @param string $value
     * @param array $atts
     * @return HTML
     */
    public static function inputDropDown($name, array $options, $value = null, array $atts = array() ){
        
        $atts['size'] = 1;
        
        return self::inputList( $name , $options, $value, $atts);
    }
    /**
     * <input type="hidden" />
     * @param string $name
     * @param string $value
     * @return HTML
     */
    public static function inputHidden( $name, $value ){
        
        return self::html('input', array(
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ));
    }
    /**
     * <input type="file" />
     * @param string $name
     * @return HTML
     */
    public static function inputFile( $name , array $atts = array( ) ){
        
        $max_filesize = 'MAX_FILE_SIZE';
        
        $atts['id'] = 'id_' . preg_replace('/-/', '_', preg_replace('/\[.*\]/','',$name));
        $atts['name'] = $name;
        $atts['type'] = 'file';
        
        $file_size =  pow(1024, 2);
        
        if( isset($atts[$max_filesize]) ){
            $file_size = $atts[$max_filesize];
            unset($atts[$max_filesize]);
        }
        
        return self::inputHidden( $max_filesize, $file_size ) . self::html('input', $atts );
    }
    /**
     * <button type="*" />
     * @param string $name
     * @param string $value
     * @param string $content
     * @param array $atts
     * @return HTML
     */
    public static function inputButton( $name, $value , $content, array $atts = array( ) ){
        
        $atts['value'] = $value;
        $atts['id'] = 'id_' . preg_replace('/-/', '_',  $name ) . '_' . $value;
        $atts['name'] = $name;
        if( !isset($atts['type'])){
            $atts['type'] = 'button';
        }
        return self::html('button', $atts, $content);
    }
    /**
     * <button type="submit" />
     * @param string $name
     * @param string $value
     * @param string $label
     * @param array $atts
     * @return HTML
     */
    public static function inputSubmit( $name , $value , $label , array $atts = array( ) ){
        
        return self::inputButton($name,
                $value,
                $label,
                array_merge( $atts , array( 'type'=>'submit' ) ));
    }
}


