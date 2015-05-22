<?php

require_once "DbMysqli.class.php";
			

/**
 *
 */
class WxRequestApi {

	private $access_token = '';
	private $jsapi_ticket = '';
	private $appid = '';
	private $appsecret = '';
	private $dbConn = null;

	function __construct($appid,$appsecret){		

		$this->dbConn = new DbMysqli();
		$this->initTable();
		date_default_timezone_set('PRC');
		$this->appid = $appid;
		$this->appsecret = $appsecret;

		$this->access_token = $this->getAccess_token();		

	}

    public function getAccess_token(){
    	if(empty($this->access_token)){
    		$sql = "SELECT `create_time`,`value` FROM `wx_ticket` WHERE `name`='access_token';";
			$res = $this->dbConn->execute_dql($sql);
			$currentTime = time();

			if(	isset($res[0]['create_time']) && 
				$currentTime-$res[0]['create_time'] < 1200 && 
				isset($res[0]['value'])){
				$this->access_token = $res[0]['value'];
			}else {
				$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
				//getAccess_token尝试三次
				$getCount = 3;
				do{
					$access_tokenJSON = $this->https_request($url);
					$access_tokenArr = json_decode($access_tokenJSON,true);
					if(isset($access_tokenArr['access_token'])){
						$this->access_token = $access_tokenArr['access_token'];
					}
				}while(empty($this->access_token) && --$getCount > 0 );
				if(empty($this->access_token)){
					die("get access_token 失败!");
				}
				$create_time = time();
				$sql = "INSERT INTO `wx_ticket` SET `name`='access_token',`value`='%s',`create_time`='%s' ON DUPLICATE KEY UPDATE `value`='%s',`create_time`='%s';";
				$sql = sprintf($sql,$this->access_token,$create_time,$this->access_token,$create_time);
				$res = $this->dbConn->execute_dml($sql);
			}		
    	}
    	return $this->access_token;    	
    }

	public function getUserInfo($openid){
		$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->access_token.'&openid='.$openid.'&lang=zh_CN';
		//var_dump($url);
		$res = $this->http_get($url);
		return json_decode($res,true);
	}
	public function getJsapi_ticket(){
		if(empty($this->jsapi_ticket)){
			$sql = "SELECT `create_time`,`value` FROM `wx_ticket` WHERE `name`='jsapi_ticket';";
			$res = $this->dbConn->execute_dql($sql);
			$currentTime = time();			
			if(	isset($res[0]['create_time']) && 
				$currentTime-$res[0]['create_time'] < 1200 && 
				isset($res[0]['value'])){
				$this->jsapi_ticket = $res[0]['value'];
			}else {
				$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->access_token.'&type=jsapi';
				//get jsapi_ticket 尝试三次
				$getCount = 3;
				do{
					$access_tokenJSON = $this->http_get($url);
					$access_tokenArr = json_decode($access_tokenJSON,true);
					if(isset($access_tokenArr['ticket'])){
						$this->jsapi_ticket = $access_tokenArr['ticket'];
					}
				}while(empty($this->jsapi_ticket) && --$getCount > 0 );
				if(empty($this->jsapi_ticket)){
					die("get jsapi_ticket 失败!");
				}
				$create_time = time();
				$sql = "INSERT INTO `wx_ticket` SET `name`='jsapi_ticket',`value`='%s',`create_time`='%s' ON DUPLICATE KEY UPDATE `value`='%s',`create_time`='%s';";
				$sql = sprintf($sql,$this->jsapi_ticket,$create_time,$this->jsapi_ticket,$create_time);
				$res = $this->dbConn->execute_dml($sql);
			}		
    	}
    	return $this->jsapi_ticket;   
	}
	

	function sendTextMsg($openid,$data)
	{
        $url  = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this->access_token;
		$textDataTpl = '{
		    "touser":"%s",
		    "msgtype":"text",
		    "text":
		    {
		         "content":"%s"
		    }
		}';
		$data = sprintf($textDataTpl,$openid,addslashes($data));
		$res = $this->https_request($url,$data);
		return json_decode($res,true);
	}
    
    function addForeverPicTextMedia($title,$thumb_media_id,$author,$digest,$show_cover_pic,$content,$content_source_url){
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_news?access_token='.$this->access_token;
    	$tpl = '{
  "articles": [{
       "title": "%s",
       "thumb_media_id": "%s",
       "author": "%s",
       "digest": "%s",
       "show_cover_pic": "%d",
       "content": "%s",
       "content_source_url": "%s"
    }
 ]
}';  
        $data = sprintf($tpl,$title,$thumb_media_id,$author,$digest,$show_cover_pic,$content,$content_source_url);
        $res = $this->https_request($url,$data);
        return json_decode($res,true);
    }
    
    function addForeverMedia($access_token,$type,$filepath){
        //$data = json_encode(array('media'=>$mediaUrl,'filename'=>'','filelength'=>getimagesize($mediaUrl),'content-type'=>'image/png'));
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token='.$this->access_token.'&type='.$type;
        $filedata=array("media" => "@".$filepath);
        $res = $this->https_request($url,$filedata);
        return json_decode($res,true);
	}
    
    function getForeverMedia($media_id){
    	$url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token='.$this->access_token;
        $data = json_encode(array('media_id'=>$media_id));
        $res = $this->https_request($url,$data);
        return json_decode($res,true);
    }









    private function initTable(){
    	$sql = "CREATE TABLE IF NOT EXISTS `wx_ticket`(
    		`name` VARCHAR(32) PRIMARY KEY NOT NULL,
    		`value` VARCHAR(128) DEFAULT '',
    		`create_time` INT DEFAULT 0
    		);";
		$this->dbConn->execute_dml($sql);
    }


	public function http_get($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);
		$res = curl_exec($curl);
		curl_close($curl);
		return $res;
	}
    
	private function https_request($url,$data=''){
    	$curl = curl_init();
    	curl_setopt($curl,CURLOPT_URL,$url);
    	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
    	curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
    	if(!empty($data)){
    		curl_setopt($curl,CURLOPT_POST,1);
    		curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
    	}
    	curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    	$output = curl_exec($curl);
    	curl_close($curl);
    	return $output;
	}





}
