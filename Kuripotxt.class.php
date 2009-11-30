<?php
require('HttpClient.class.php');

/**
* Version 0.1, 30 Nov. 2009 - Francois-Guillaume Ribreau ( http://fgribreau.com.com/ )
*  Manual: http://blog.geekfg.net
*/
class Kuripotxt
{
	var $session = array();
	var $phpsessid = '';
	
	var $kuripotxt_Domain = 'kuripotxt.net';
	var $kuripotxt_Ressource = 'smsSender.php';
	
	var $client;//HttpClient
	
	function __construct()
	{
		//quick & dirty
		$this->client = new HttpClient('www.'.$this->kuripotxt_Domain, 80);
		$this->client->setPersistReferers(false);
		$this->client->setReferer('http://www.'.$this->kuripotxt_Domain.'/');
		$this->client->setPersistCookies(true);
		$this->client->setUserAgent('Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-us) Gecko/'.(20090000+rand(0,999)).' Firefox/3.5.2');
	}
	
	private function _getSession(){
		if(!$this->client->get('/'))//.$this->kuripotxt_Ressource
			throw new Exception('An error occurred: '.$this->client->getError());
		else
			$pageContent = $this->client->getContent();
		
		//echo $pageContent;
		if($phpsession = $this->client->getHeader('set-cookie')){
			$phpsession = explode(';',$phpsession);
			if(!empty($phpsession[0]))
				$phpsession = explode('=',$phpsession[0]);
				$this->client->setCookie($phpsession[0],$phpsession[1]);
		}
			
		
		if(empty($pageContent))
			throw new Exception('Bad content: '.$this->kuripotxt_Domain.'/');
			
		$this->_getSessionValue($pageContent,false);
	}
	
	private function _getSessionValue(&$pageContent,$js = true){
		//I've added multiple \s just in case :)
		if($js)
			$match = preg_match_all('#refresh\s*\(\s*\'(.*)\'\s*,\s*\'(.*)\'\s*\)#', $pageContent, $matches);//quick & dirty
		else
			$match = preg_match_all('#name\s*=\s*\"(.*)\"\s.*"captcha"\s*value\s*=\s*"\s*(.*)\s*"#', $pageContent, $matches);//quick & dirty
			
			
		if($match === 1 && empty($matches[1]) && empty($matches[2])){
			throw new Exception('Unable to get the session key/value.');
		}
		
		$this->session = array(trim($matches[1][0]),trim($matches[2][0]));//quick & dirty
	}
	
	public function sendSms($mobileNumber, $message){
		if(empty($this->session))
			$this->_getSession();

		$data = array(
		    'msg' => $message,
		    'num' => $mobileNumber);
		$data[$this->session[0]] = $this->session[1];

		//this line is optional
		$this->client->addHeaders(array('X-Requested-With'	=>	'XMLHttpRequest'));

		if(!$this->client->post('http://www.'.$this->kuripotxt_Domain.'/'.$this->kuripotxt_Ressource, $data)){
			$this->session = array();
			throw new Exception('An error occurred: '.$this->client->getError());
		}

		//get the new session key/value (for an hypothetical new request)
		$pageContent = $this->client->getContent();

		if(strpos($pageContent,'failed') !== FALSE){
			$this->session = array();
			throw new Exception('Unable to send your sms.');
		}
		else
			$this->_getSessionValue($pageContent,true);
	}
}
?>