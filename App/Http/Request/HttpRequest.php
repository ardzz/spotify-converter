<?php

namespace Http\Request;

abstract class HttpRequest{
    
    public $set_url = NULL;
    public $set_parameter = NULL;
    public $set_timeout = NULL;
    public $set_proxy = NULL;
    public $set_proxy_auth = NULL;
    public $set_headers = NULL;
    public $set_user_agent = NULL;
    public $set_cookies = NULL;
    public $default_timeout = 15; // 15 second

    function __construct(){
        $requirements = $this->requirements();
        if(!empty($requirements->get()[0])){
            echo $this->json([
                "error" => true,
                "error_detail" => $requirements->get()
            ])->get().PHP_EOL;
            exit();
        }
    }

    function requirements(){
        $output = [];
        if (!function_exists('curl_version')) {
            $output[] = "cURL isn't installed!";
        }
        elseif ((float)phpversion() < 7){
            $output[] = "PHP Version < 7.x !";
        }
        else{
            $output[] = NULL;
        }
        if (($key = array_search(NULL, $output)) !== false) {
            unset($output[$key]);
        }
        $this->get = $output;
        return $this;
    }

    function get_string($str, $find_start, $find_end) {
		$start = @strpos($str,$find_start);
		if (!$start) {
			return false;
		}
		$length = strlen($find_start);
		$end    = strpos(substr($str,$start +$length),$find_end);
		return trim(substr($str,$start +$length,$end));
    }
    
    function save($name, $source){
		$fopen = @fopen($name, "a");
		$output = @fwrite($fopen, $source);
        @fclose($fopen);
        return (boolean) str_replace(0, 1, $output);
    }

    function getTimeout(){
        return (isset($this->set_timeout) ? $this->set_timeout : $this->default_timeout);
    }

    function setURL(){
        return (isset($this->set_parameter) ? $this->set_url."?".$this->set_parameter : $this->set_url);
    }

    protected function parse_cookie($headers){
        $output = [];
        if (stripos(($headers), "Set-Cookie:")) {
            if (preg_match_all("/Set-Cookie: (.*?);(.*?)\r\n/", ($headers), $cookies)) {
                /*if (isset($cookies[1])) {
                    foreach ($cookies[1] as $x => $val) {
                        list($key, $value) = explode("=", $val);
                        $output[$key] = $value;
                    }
                    $this->get = $output;
                }*/
                $this->get = $cookies[1];
            }
            elseif(preg_match_all("/Set-Cookie: (.*?)\r\n/", ($headers), $cookies)){
                $this->get = $cookies[1];
            }
            else{
                $this->get = null;
            }
        }
        else{
            $this->get = null;
        }
        return $this;
    }

    protected function parse_headers($response){
        $headers = array();
    
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
    
        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0){
                $headers['http_code_status'] = $line;
                list(,$code, $status) = explode(' ', $line, 3);
                $headers["http_code"] = $code;
            }
            else{
                list ($key, $value) = explode(': ', $line);
    
                $headers[$key] = $value;
            }
        unset($headers["Set-Cookie"], $headers["set-cookie"]);
        $this->get = $headers;
        return $this;
    }

    function getBody(){
        return (($this->get["success"]) ? $this->get["response"]["body"] : FALSE);
    }

    function getHeaders($key = null){
        return (($this->get["success"]) ? ((isset($key) && array_key_exists($key, $this->get["response"]["parsed_headers"])) ? $this->get["response"]["parsed_headers"][$key] : $this->get["response"]["parsed_headers"]) : FALSE);
    }

    function getRealHeaders(){
        return (($this->get["success"]) ? $this->get["response"]["headers"] : FALSE);
    }

    function getCookies(){
        return (($this->get["success"]) ? $this->get["response"]["parsed_cookies"] : FALSE);
    }

    function getHttpCode(){
        return (($this->get["success"]) ? $this->get["response"]["parsed_headers"]["http_code"] : FALSE);
    }

    function get($PHP_EOL = null){
        return ((isset($PHP_EOL)) ? $this->get.PHP_EOL : $this->get);
    }

    function json($data = null){
		((isset($data)) ? $this->get = json_encode((array)$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $this->get = json_encode((array)$this->get, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		return $this;
	}
}
?>