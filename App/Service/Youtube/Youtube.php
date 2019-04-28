<?php

namespace Service\Youtube;

use \Http\Request\POST;
use \Http\Request\GET;
use \Http\Request\HttpRequest;

class Youtube extends HttpRequest{

    public $api_key = "AIzaSyC9PbnUX-8rXVn32Z5Ty2YnqP4XMnT9zuE";

    function search($query){
		$api_key = $this->api_key;
        $query_encoded = urlencode($query);
        $data = @file_get_contents("https://www.googleapis.com/youtube/v3/search?part=id,snippet&q={$query_encoded}&type=video&key={$api_key}");
        if (!$data) {
            return false;
        }
		$array_data = json_decode($data, 1);
		return "https://www.youtube.com/watch?v=".$array_data["items"][0]["id"]["videoId"]; // return video id
    }
    
    function convert2mp3($url, $format = "mp3"){
        $POST = new POST;
        $POST->set_url  = "https://www2.onlinevideoconverter.com/webservice";
        $POST->set_parameter = "function=validate&args[dummy]=1&args[urlEntryUser]={$url}&args[fromConvert]=urlconverter&args[requestExt]=mp3&args[nbRetry]=0&args[videoResolution]=-1&args[audioBitrate]=0&args[audioFrequency]=0&args[channel]=stereo&args[volume]=0&args[startFrom]=-1&args[endTo]=-1&args[custom_resx]=-1&args[custom_resy]=-1&args[advSettings]=false&args[aspectRatio]=-1";
        $POST->execute();
        $data = json_decode($POST->getBody(), 1);
        if ($data["result"]["status"] == "failed") {
            return false;
        }else{
            // proses 2
            $POST->set_url   = "https://www.onlinevideoconverter.com/success";
            //$POST->set_proxy = "127.0.0.1:8080";
            $POST->set_parameter = "id={$data["result"]["dPageId"]}";
            $POST->execute();
            return $this->parsing_data($POST->getBody());
            //return $curl;
        }
    }

    function parsing_data($data){
        // ambil sebagian data
            $data    = $this->get_string($data, "<div id='stepProcessEnd'>", '<div id="queue" style="display:none; font-weight: 500;"></div>');
        // ambil size
            $pattern = "/[0-9].[0-9]+MB/";
            @preg_match($pattern, $data, $match);
            $size    = ((isset($match[0])) ? $match[0] : false);
            //$size = $match[0];
    
        // ambil url download
            $pattern = "/https:\/\/[a-zA-Z0-9-]+.onlinevideoconverter.com\/download[?]file=[a-zA-Z0-9-]+/";
            @preg_match($pattern, $data, $match);
            $url     = ((isset($match[0])) ? $match[0] : false);
            //$url = $match[0];
    
        // ambil judul	
            $pattern = "/title=\"(.*?)\"/";
            @preg_match($pattern, $data, $match);
            $title   = ((isset($match[1])) ? $match[1] : false);
            //$title = $match[1];

            return ["size" => $size, "url_download" => $url, "title" => $title];
    }

    function download($file_source, $file_target){
		$rh = fopen($file_source, 'rb');
		$wh = fopen($file_target, 'w+b');
		if (!$rh || !$wh) {
			return false;
		}
		while (!feof($rh)) {
			if (fwrite($wh, fread($rh, 4096)) === FALSE) {
				return false;
			}
			flush();
		}
		fclose($rh);
		fclose($wh);
		return true;
	}
}

?>