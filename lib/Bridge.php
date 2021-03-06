<?php
/**
* All bridge logic
* Note : adapter are store in other place
*/

interface BridgeInterface{
    public function collectData(array $param);
    public function getCacheDuration();
    public function loadMetadatas();
    public function getName();
    public function getURI();
}

abstract class BridgeAbstract implements BridgeInterface{

    protected $cache;
    protected $items = array();

	public $name = "Unnamed bridge";
	public $uri = "";
	public $description = 'No description provided';
	public $maintainer = 'No maintainer';
	public $useProxy = true;
	public $parameters = array();

    /**
    * Launch probative exception
    */
    protected function returnError($message, $code){
        throw new \HttpException($message, $code);
    }

    protected function returnClientError($message){
        $this->returnError($message, 400);
    }

    protected function returnServerError($message){
        $this->returnError($message, 500);
    }

    /**
    * Return datas stored in the bridge
    * @return mixed
    */
    public function getDatas(){
        return $this->items;
    }



    /**
    * Defined datas with parameters depending choose bridge
    * Note : you can define a cache before with "setCache"
    * @param array $param $_REQUEST, $_GET, $_POST, or array with bridge expected paramters
    */
    public function setDatas(array $param){
        if( !is_null($this->cache) ){
            $this->cache->prepare($param);
            $time = $this->cache->getTime();
        }
        else{
            $time = false; // No cache ? No time !
        }

        if( $time !== false && ( time() - $this->getCacheDuration() < $time ) ){ // Cache file has not expired. Serve it.
            $this->items = $this->cache->loadData();
        }
        else{
            $this->collectData($param);

            if( !is_null($this->cache) ){ // Cache defined ? We go to refresh is memory :D
                $this->cache->saveData($this->getDatas());
            }
        }
    }

    /**
    * Define default bridge name
    */
    public function getName(){
        return $this->name;
    }

    /**
    * Define default bridge URI
    */
    public function getURI(){
        return $this->uri;
    }

    /**
    * Define default duraction for cache
    */
    public function getCacheDuration(){
        return 3600;
    }

    /**
    * Defined cache object to use
    */
    public function setCache(\CacheAbstract $cache){
        $this->cache = $cache;

        return $this;
    }

    public function message($text) {
      if(!file_exists('DEBUG')){
        return;
      }
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
      $calling = $backtrace[2];
      $message = $calling["file"].":".$calling["line"]
        ." class ".get_class($this)."->".$calling["function"]
        ." - ".$text;
      error_log($message);
    }

    protected function getContents($url,$use_include_path=false,$context=null,$offset=0,$maxlen=null){
      $contextOptions = array(
        'http' => array(
          'user_agent'=>ini_get('user_agent')
        ),
      );

      if(defined('PROXY_URL') && $this->useProxy) {
        $contextOptions['http']['proxy'] = PROXY_URL;
        $contextOptions['http']['request_fulluri'] = true;

        if(is_null($context)){
          $context = stream_context_create($contextOptions);
        } else {
          $prevContext=$context;
          if(!stream_context_set_option($context,$contextOptions)){
            $context=$prevContext;
          };
        }
      }

      if(is_null($maxlen)){
        $content=@file_get_contents($url, $use_include_path, $context, $offset);
      }else{
        $content=@file_get_contents($url, $use_include_path, $context, $offset,$maxlen);
      }

      if($content===false){
        $this->message('Cant\'t download '.$url );
      }
      return $content;
    }

    protected function getSimpleHTMLDOM($url, $use_include_path = false, $context=null, $offset = 0, $maxLen=null, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT){
      $content=$this->getContents($url,$use_include_path,$context,$offset,$maxLen);
      return str_get_html($content,$lowercase,$forceTagsClosed,$target_charset,$stripRN,$defaultBRText,$defaultSpanText);
    }

}

/**
 * Extension of BridgeAbstract allowing caching of files downloaded over http files.
 * This is specially useful for sites from Gawker or Liberation networks, which allow pages excerpts top be viewed together on index, while full pages have to be downloaded
 * separately.
 * This class mainly provides a get_cached method which will will download the file from its remote location.
 * TODO allow file cache invalidation by touching files on access, and removing files/directories which have not been touched since ... a long time
 * After all, rss-bridge is not respaw, isn't it ?
 */
abstract class HttpCachingBridgeAbstract extends BridgeAbstract {

    /**
     * Maintain locally cached versions of pages to download to avoid multiple doiwnloads.
     * A file name is generated by replacing all "/" by "_", and the file is saved below this bridge cache
     * @param url url to cache
     * @return content of file as string
     */
    public function get_cached($url) {
        $simplified_url = str_replace(["http://", "https://", "?", "&", "="], ["", "", "/", "/", "/"], $url);
		// TODO build this from the variable given to Cache
		$pageCacheDir = __DIR__ . '/../cache/'."pages/";
        $filename =  $pageCacheDir.$simplified_url;
        if (substr($filename, -1) == '/') {
            $filename = $filename."index.html";
        }
        if(file_exists($filename)) {
//            $this->message("loading cached file from ".$filename." for page at url ".$url);
			// TODO touch file and its parent, and try to do neighbour deletion
            $this->refresh_in_cache($pageCacheDir, $filename);
            $content=file_get_contents($filename);
		} else {
//            $this->message("we have no local copy of ".$url." Downloading to ".$filename);
            $dir = substr($filename, 0, strrpos($filename, '/'));
            if(!is_dir($dir)) {
//				$this->message("creating directories for ".$dir);
                mkdir($dir, 0777, true);
            }
            $content=$this->getContents($url);
            if($content!==false){
              file_put_contents($filename,$content);
            }
        }
        return $content;
    }

     public function get_cached_time($url) {
        $simplified_url = str_replace(["http://", "https://", "?", "&", "="], ["", "", "/", "/", "/"], $url);
        // TODO build this from the variable given to Cache
        $pageCacheDir = __DIR__ . '/../cache/'."pages/";
        $filename =  $pageCacheDir.$simplified_url;
        if (substr($filename, -1) == '/') {
            $filename = $filename."index.html";
        }
        if(!file_exists($filename)) {
            $this->get_cached($url);
        }
        return filectime($filename);
    }

    private function refresh_in_cache($pageCacheDir, $filename) {
		$currentPath = $filename;
		while(!$pageCacheDir==$currentPath) {
			touch($currentPath);
			$currentPath = dirname($currentPath);
		}
    }

    public function remove_from_cache($url) {
        $simplified_url = str_replace(["http://", "https://", "?", "&", "="], ["", "", "/", "/", "/"], $url);
    	// TODO build this from the variable given to Cache
		$pageCacheDir = __DIR__ . '/../cache/'."pages/";
        $filename =  realpath($pageCacheDir.$simplified_url);
        $this->message("removing from cache \"".$filename."\" WELL, NOT REALLY");
        // filename is NO GOOD
//        unlink($filename);
    }

}

class Bridge{

    static protected $dirBridge;

    public function __construct(){
        throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
    }

	/**
	* Checks if a bridge is an instantiable bridge.
	* @param string $nameBridge name of the bridge that you want to use
	* @return true if it is an instantiable bridge, false otherwise.
	*/
	static public function isInstantiable($nameBridge) {

		$re = new ReflectionClass($nameBridge);
		return $re->IsInstantiable();

	}


    /**
    * Create a new bridge object
    * @param string $nameBridge Defined bridge name you want use
    * @return Bridge object dedicated
    */
    static public function create($nameBridge){
        if( !preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameBridge)){
            throw new \InvalidArgumentException('Name bridge must be at least one uppercase follow or not by alphanumeric or dash characters.');
        }

        $nameBridge=$nameBridge.'Bridge';
        $pathBridge = self::getDir() . $nameBridge . '.php';

        if( !file_exists($pathBridge) ){
            throw new \Exception('The bridge you looking for does not exist. It should be at path '.$pathBridge);
        }

        require_once $pathBridge;

		if(Bridge::isInstantiable($nameBridge)) {
        	return new $nameBridge();
        } else {
        	return FALSE;
        }
    }

    static public function setDir($dirBridge){
        if( !is_string($dirBridge) ){
            throw new \InvalidArgumentException('Dir bridge must be a string.');
        }

        if( !file_exists($dirBridge) ){
            throw new \Exception('Dir bridge does not exist.');
        }

        self::$dirBridge = $dirBridge;
    }

    static public function getDir(){
        $dirBridge = self::$dirBridge;

        if( is_null($dirBridge) ){
            throw new \LogicException(__CLASS__ . ' class need to know bridge path !');
        }

        return $dirBridge;
    }

    /**
    * Lists the available bridges.
    * @return array List of the bridges
    */
	static public function listBridges() {

		$pathDirBridge = self::getDir();
		$listBridge = array();
		$dirFiles = scandir($pathDirBridge);

        if( $dirFiles !== false ){
          foreach( $dirFiles as $fileName ) {
            if( preg_match('@^([^.]+)Bridge\.php$@U', $fileName, $out) ){
              $listBridge[] = $out[1];
            }
          }
        }

		return $listBridge;
	}
	static function isWhitelisted( $whitelist, $name ) {
      if(in_array($name, $whitelist) or in_array($name.'.php', $whitelist) or
        // DEPRECATED: the nameBridge notation will be removed in future releases
        in_array($name.'Bridge', $whitelist) or in_array($name.'Bridge.php', $whitelist) or
        count($whitelist) === 1 and trim($whitelist[0]) === '*')
		return TRUE;
	else
		return FALSE;
	}

}

abstract class RssExpander extends HttpCachingBridgeAbstract{

    public $name;
    public $uri;
    public $description;

    public function collectExpandableDatas(array $param, $name){
        if (empty($name)) {
            $this->returnServerError('There is no $name for this RSS expander');
        }
//       $this->message("Loading from ".$param['url']);
        // Notice WE DO NOT use cache here on purpose : we want a fresh view of the RSS stream each time
        $content=$this->getContents($name) or
          $this->returnServerError('Could not request '.$name);

        $rssContent = simplexml_load_string($content);
        //        $this->message("loaded RSS from ".$param['url']);
        // TODO insert RSS format detection
        // we suppose for now, we have some RSS 2.0
        $this->collect_RSS_2_0_data($rssContent);
    }

    protected function collect_RSS_2_0_data($rssContent) {
        $rssContent = $rssContent->channel[0];
//        $this->message("RSS content is ===========\n".var_export($rssContent, true)."===========");
        $this->load_RSS_2_0_feed_data($rssContent);
        foreach($rssContent->item as $item) {
//            $this->message("parsing item ".var_export($item, true));
            $this->items[] = $this->parseRSSItem($item);
        }
    }

    protected function RSS_2_0_time_to_timestamp($item)  {
        return DateTime::createFromFormat('D, d M Y H:i:s e', $item->pubDate)->getTimestamp();
    }

    // TODO set title, link, description, language, and so on
    protected function load_RSS_2_0_feed_data($rssContent) {
        $this->name = trim($rssContent->title);
        $this->uri = trim($rssContent->link);
        $this->description = trim($rssContent->description);
    }

    /**
     * Method should return, from a source RSS item given by lastRSS, one of our Items objects
     * @param $item the input rss item
     * @return a RSS-Bridge Item, with (hopefully) the whole content)
     */
    abstract protected function parseRSSItem($item);

    public function getDescription() {
        return $this->description;
    }
}


