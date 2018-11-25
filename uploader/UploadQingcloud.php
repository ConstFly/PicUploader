<?php
	/**
	 * Created by PhpStorm.
	 * User: Bruce Xie
	 * Date: 2018-11-23
	 * Time: 16:29
	 */
	
namespace uploader;

use Guzzle\Common\Exception\ExceptionCollection;
use QingStor\SDK\Config;
use QingStor\SDK\Service\QingStor;

class UploadQingcloud extends Upload{
	
	public $accessKeyId;
	public $secretAccessKey;
	public $bucket;
	//区域
	public $zone;
	public $domain;
	//config from config.php, using static because the parent class needs to use it.
	public static $config;
	//arguments from php client, the image absolute path
	public $argv;
	
	/**
	 * Upload constructor.
	 *
	 * @param $config
	 * @param $argv
	 */
	public function __construct($config, $argv)
	{
		$tmpArr = explode('\\',__CLASS__);
		$className = array_pop($tmpArr);
		$ServerConfig = $config['storageTypes'][strtolower(substr($className,6))];
		
		$this->accessKeyId = $ServerConfig['accessKeyId'];
		$this->secretAccessKey = $ServerConfig['secretAccessKey'];
		$this->bucket = $ServerConfig['bucket'];
		//endPoint不是域名，外链域名是 bucket.'.'.endPoint
		$this->zone = $ServerConfig['zone'];
		$this->domain = $ServerConfig['domain'];
		
		$this->argv = $argv;
		static::$config = $config;
	}
	
	/**
	 * Upload images to QingCloud
	 * @param $key
	 * @param $uploadFilePath
	 * @param $originFilename
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function upload($key, $uploadFilePath, $originFilename){
		try {
			$config = new Config($this->accessKeyId, $this->secretAccessKey);
			$service = new QingStor($config);
			$bucket = $service->Bucket($this->bucket, $this->zone);
			
			// Put object
			$body = file_get_contents($uploadFilePath);
			$res = $bucket->putObject($key, ['body' => $body]);
			//http状态码201表示Created，即创建成功（这里表示文件在服务器创建成功，即上传成功）
			if($res->statusCode==201){
				$publicLink = 'http://'.$this->bucket.'.'.$this->zone.'.'.$this->domain.'/'.$key;
				//按配置文件指定的格式，格式化链接
				$link = $this->formatLink($publicLink, $originFilename);
				return $link;
			}else{
				throw new \Exception('error_code => '.$res->code."\nerror_message => ".$res->message, $res->statusCode);
			}
		} catch (\Exception $e) {
			//上传数错，记录错误日志
			$this->writeLog($e->getMessage()."\n", 'error_log');
		}
	}
}