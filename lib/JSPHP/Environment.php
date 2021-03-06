<?php
class JSPHP_Environment {
    /**
     * @var bool
     */
    protected $componentsInitialized = false;
    
    /**
     * @var JSPHP_IParser
     */
    public $parser;
    
    /**
     * @var JSPHP_ICompiler
     */
    public $compiler;
    
    /**
     * @var JSPHP_Runtime
     */
    public $runtime;
    
    /**
     * @var string
     */
    public $currentFile;
    
    function resetEnvironment() {
        $this->runtime = null;
        $this->componentsInitialized = false;
    }
    
    /**
 	* Save the dinosaurs
 	*/
   function __construct($bCompat = true){
	   	if($bCompat){
			require_once 'Helper/Compat.php';
	   	}
   }
   
    
    /**
     * Load default components if they have not been supplied.
     */
    function initComponents() {
        if ($this->componentsInitialized) {
            return;
        }
        $this->componentsInitialized = true;
        
        if (!$this->parser) {
            require_once 'JSPHP/Parser.php';
            $this->parser = new JSPHP_Parser();
        }
        if (!$this->compiler) {
            require_once 'JSPHP/Compiler.php';
            $this->compiler = new JSPHP_Compiler();
        }
        if (!$this->runtime) {
            require_once 'JSPHP/Runtime.php';
            $this->runtime = new JSPHP_Runtime();
            $this->runtime->environment = $this;
        }
        if (!$this->runtime->vm) {
            require_once 'JSPHP/VM.php';
            $this->runtime->vm = new JSPHP_VM($this->runtime);
        }
    }
    
    /**
     * Load a file and run its code.
     * @param string $path
     * @return JSPHP_Runtime_Object
     */
    function runFile($path) {
        if (!$this->componentsInitialized) {
            $this->initComponents();
        }
        
        $parentFile = $this->currentFile;
        if ($parentFile) {
            $path = $this->absolutePath($path, dirname($parentFile));
        } else {
            $path = realpath($path);
        }
        
        if (!$path || !file_exists($path)) {
            throw new Exception("Can't read file {$path}");
        }
        $data = file_get_contents($path);
        
        $blockCacheK = md5($data);
        if (!$block = $this->cacheGetOpCodeBlock($blockCacheK)) {
            $tree = $this->parser->parseJS($data);
            $ops = $this->compiler->compile($tree);
            $block = $this->runtime->vm->loadOpCode($ops, $path);
            $this->cacheSetOpCodeBlock($blockCacheK, $block);
        }
        $this->currentFile = $path;
        try {
            $out = $this->runtime->vm->runBlockAsModule($block);
            $this->currentFile = $parentFile;
        } catch (Exception $e) {
            $this->currentFile = $parentFile;
            throw $e;
        }
        return $out;
    }
    
    function cacheGetOpCodeBlock($k) {
        if ($this->runtime && $this->runtime->vm
            && ($block = $this->runtime->vm->cacheGetOpCodeBlock($k))) {
            return $block;
        }
    }
    
    function cacheSetOpCodeBlock($k, JSPHP_VM_OpCodeBlock $block) {
        if ($this->runtime && $this->runtime->vm) {
            $this->runtime->vm->cacheSetOpCodeBlock($k, $block);
        }
    }
    
    protected function absolutePath($path, $dir = null) {
        if ($absolutePath = realpath($path)) {
            return $absolutePath;
        }
        return realpath($dir . DIRECTORY_SEPARATOR . $path);
    }
}
