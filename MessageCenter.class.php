<?php
//$this->msgTpl = (require "configs/message_tpl_config.php");
/*
 *
 */
class MessageCenter {
	private $postStr = '';
	private $postObj = null;
	private $msgType = '';
	private $msgTpl = array();
	
	private $fromUsername = '';
	private $toUsername = '';
	private $keyword = '';
	private $event = '';
	
	function __construct($postStr){
		if(empty($postStr)){
			die("postStr is empty!");
		}
		$this->postStr = $postStr;
		libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if($postObj){
			$this->fromUsername = $postObj->FromUserName;
			$this->toUsername = $postObj->ToUserName;
			$this->msgType = $postObj->MsgType;
			$this->postObj = $postObj;
		}else{
			die("Parse postStr failed!");
		}
		$this->msgTpl = (require "configs/message_tpl_config.php");
		//$this->dispatch();
	}
	
	public function getPostObj(){
		return $this->postObj;
	}
	
	public function dispatch(){
		switch($this->msgType){
			//事件处理
			case "event":
				$this->event = $this->postObj->Event;
				$this->parseEvent();
			break;
			
			//消息处理
			case "text":
				$keyword = trim($this->postObj->Content);
				if(!empty($keyword)){
					$this->keyword = $keyword;
					$this->parseKeyword();
				}
			break;
			case "image":
			
				$url = '转发url';
				$res = $this->http_post($url,$this->postStr);
				if($res){
					//echo $res;
					$text = '转发成功';
					$this->responseText($text);
				}else {
					$text = '转发失败';
					$this->responseText($text);
				}
				
				
			break;
			case "voice":
			
			break;
			case "video":
			
			break;
			case "shortvideo":
			
			break;
			case "location":
			
			break;
			case "link":
			
			break;
			case "shortvideo":
			
			break;
			default:			

		}
		
	}
	function responseText($text){
		$data = sprintf($this->msgTpl['text'],$this->fromUsername,$this->toUsername,time(),$text);
		echo $data;
	}	
	function parseKeyword(){
		$keyword = $this->keyword;
		$postStr = $this->postStr;
        $res = array('type'=>0,'data'=>'6666');
        if(preg_match('/^#666#/',$keyword)){
            $url = '转发URL';
            $res['type'] = 1;
            $realKeyword = substr($keyword,strlen('#666#'));
            $realContent = '<Content><![CDATA['.$realKeyword.']]></Content>';
            $realPostStr = preg_replace('/<Content>.*<\/Content>/',$realContent,$postStr);
            $res['data'] = $this->http_post($url,$realPostStr);
        }else if(preg_match('/^投票测试$/',$keyword)){
			$path = APP_ROOT_URL;
			$mess = $this->msgTpl['news1'];
			$title = 'title';
			$desc = 'desc';
			$pic = $path.'/static/images/900.jpg';
			$url = $path.'/vote.php?oid='.base64_encode('~~~'.$this->fromUsername.'~~');
			echo sprintf($mess,$this->fromUsername,$this->toUsername,time(),$title,$desc,$pic,$url);
			
		}else{
            $res['type'] = 0;	
            switch($keyword){                
                default:
                    $res['data'] = '默认回复';
            }
        }
        if(1 == $res['type']){
			echo $res['data'];
		}else if(0 == $res['type']){
			$data = sprintf($this->msgTpl['text'],$this->fromUsername,$this->toUsername,time(),$res['data']);
			echo $data;
		}else{
			
		}		
    }
	
	function parseEvent(){
		$event = $this->event;
		if('CLICK' == $this->event){
			$eventKey = $this->postObj->EventKey;
			if('activity_vote' == $eventKey){
				$path = APP_ROOT_URL;
				$news1Tpl = $this->msgTpl['news1'];
				$title = 'title';
				$desc = 'desc';
				$pic = $path.'/static/images/900.jpg';
				$url = $path.'/vote.php?oid='.base64_encode('~~~'.$this->fromUsername.'~~');
				echo sprintf($news1Tpl,$this->fromUsername,$this->toUsername,time(),$title,$desc,$pic,$url);
			
			}
		}else if('subscribe' == $event){
			$content = '您好，欢迎关注！';
			$data = sprintf($this->msgTpl['text'],$this->fromUsername,$this->toUsername,time(),$content);
			echo $data;
		}else{
			
		}
	}
    
	//用来转发消息
    function http_post($url,$data=''){
        $ch = curl_init ();
            
        $headers['Content-type'] = 'text/xml'; 
        foreach( $headers as $n => $v ) {
            $headerArr[] = $n .':' . $v;  
        }
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ($ch, CURLOPT_HTTPHEADER , $headerArr );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        $res = curl_exec ( $ch );
        curl_close ( $ch );
        return $res;
    }
}