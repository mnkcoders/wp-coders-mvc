<?php defined('ABSPATH') or die;
/**
 * 
 */
final class Uploader extends CoderProvider{

    /**
     * @var array
     */
    private $_files = array();

    /**
     * @param string $ array $setup = array( ) 
     */
    protected final function __construct( array $setup = array( ) ) {
        
        $this->register('path')
                ->register('ts',date('Y-m-d H:i:s'))
                ->register('seed',date('YmdHis'));
        
        parent::__construct($setup);
    }
    
    /**
     * @return array
     */
    public final function files() {
        return $this->_files;
    }

    /**
     * @param String $id
     * @return String
     */
    public final function path($id = '') {
        return strlen($id) ? $this->path . '/' . $id : $this->path;
    }

    /**
     * @param int $variation
     * @return string
     */
    private static final function generateId($variation = 0) {
        $ts = $this->seed;
        $seed = $variation > 0 ? sprintf('%s_%s', $ts, $variation) : $ts;
        return md5(uniqid($seed, true));
    }

    /**
     * @param string $upload
     * @return array
     */
    private static final function input($upload) {
        $files = array_key_exists($upload, $_FILES) ? $_FILES[$upload] : array();
        $output = array();
        if (count($files)) {
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $output[] = array(
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'type' => $files['type'][$i],
                        'error' => $files['error'][$i],
                    );
                }
            }
            else {
                $output[] = $files;
            }
        }
        return $output;
    }

    /**
     * @param string $from
     * @param string $to
     * @return int | bool
     */
    private static final function storage($from, $to) {
        $buffer = file_get_contents($from);
        if ($buffer !== FALSE) {
            if (file_put_contents($to, $buffer)) {
                return filesize($to);
            }
        }
        return FALSE;
    }

    /**
     * @param string $error
     * @return bool
     * @throws \Exception
     */
    private static final function validate($error) {
        switch ($error) {
            case UPLOAD_ERR_CANT_WRITE:
                throw new \Exception('UPLOAD_ERROR_READ_ONLY');
            case UPLOAD_ERR_EXTENSION:
                throw new \Exception('UPLOAD_ERROR_INVALID_EXTENSION');
            case UPLOAD_ERR_FORM_SIZE:
                throw new \Exception('UPLOAD_ERROR_SIZE_OVERFLOW');
            case UPLOAD_ERR_INI_SIZE:
                throw new \Exception('UPLOAD_ERROR_CFG_OVERFLOW');
            case UPLOAD_ERR_NO_FILE:
                throw new \Exception('UPLOAD_ERROR_NO_FILE');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new \Exception('UPLOAD_ERROR_INVALID_TMP_DIR');
            case UPLOAD_ERR_PARTIAL:
                throw new \Exception('UPLOAD_ERROR_INCOMPLETE');
            case UPLOAD_ERR_OK:
                return true;
        }
        return false;
    }

    /**
     * Upload handler
     * @param string $name
     * @return Uploader
     * @throws \Exception
     */
    public final function upload($name) {

        //$ts = $this->ts;
        $counter = 0;

        foreach (self::input($input) as $upload) {
            try {
                if ($this->validate($upload['error'])) {
                    $upload['id'] = $this->generateId(++$counter);
                    $upload['path'] = $this->path($upload['id']);
                    $upload['size'] = $this->storage($upload['tmp_name'], $upload['path']);
                    $upload['timestamp'] = $this->ts;
                    if ($upload['size'] !== FALSE) {
                        unlink($upload['tmp_name']);
                        unset($upload['tmp_name']);
                        $this->_files[$upload['id']] = $upload;
                    } else {
                        throw new \Exception(sprintf('Failed to read upload buffer %s', $upload['name']));
                    }
                }
            }
            catch (\Exception $ex) {
                //send notification
                die($ex->getMessage());
            }
        }
        return $this;
    }

    /**
     * @param string $path
     * @return \Uploader
     */
    public static final function create($path) {
        return new Uploader( array('path' => $path) );
    }
}



