<?php

namespace Service\Spotify;

use \Http\Request\POST;
use \Http\Request\GET;
use \Service\Youtube\Youtube;

class Spotify extends Youtube{

    public $email = null;
    public $password = null;
    public $access_token = null;

    function EmailExists(){
        if (!isset($this->email)) return false;
        $GET = new GET;
        $GET->set_url = "https://www.spotify.com/id/xhr/json/isEmailAvailable.php?signup_form%5Bemail%5D={$this->email}&email={$this->email}";
        $GET->execute();
        return ($GET->getBody() == "false");
    }

    protected function GetCSRF(){
        $GET = new GET;
        $GET->set_url = "https://accounts.spotify.com/login/?_locale=id-ID&continue=https%3A//www.spotify.com/id/account/overview/";
        //$GET->set_proxy = "127.0.0.1:8080";
        $GET->set_headers = [
            "Host: accounts.spotify.com",
			"User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0",
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Referer: https://www.spotify.com/",
			"DNT: 1",
			"Connection: close",
			"Upgrade-Insecure-Requests: 1",
        ];        
        $GET->execute();
        $output = $GET->getCookies();
        return (isset($output[0]) ? explode("=", $output[0])[1] : die("       [!] Failed to get CSRF!\n       [*] Jika masih error silahkan cek koneksi internet Anda\n"));
    }

    function Login(){
        $csrf = $this->GetCSRF();
        $email = $this->email;
        $password = $this->password;
        sleep(2);
        $POST = new POST();
        $POST->set_url = "https://accounts.spotify.com/api/login";
        //$POST->set_proxy = "127.0.0.1:8080";
        $POST->set_parameter = [
            "remember" => "true",
            "username" => $email,
            "password" => $password,
            "captcha_token" => "",
            "csrf_token" => $csrf
        ];
        $POST->set_headers = [
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.62 Safari/537.36",
            "Cookie: csrf_token={$csrf}; __bon=MHwwfDExMDc5NjU4MDR8NDY1MzQ1NjM3Njh8MXwxfDF8MQ==; fb_continue=https%3A%2F%2Fwww.spotify.com%2Fid%2Faccount%2Foverview%2F; remember={$email}"
        ];
        $POST->execute();
        $Output = $POST->get();
        if ($POST->getHttpCode() == "200") {
            $this->cookie = $Output["response"]["parsed_cookies"][1];
            $this->csrf = explode("=", $Output["response"]["parsed_cookies"][2])[1];
            $this->save_session();
        }
        return ($POST->getHttpCode() == "200");
    }

    function save_session(){
        return @file_put_contents("Cache/{$this->email}.json", $this->json(["cookie" => $this->cookie, "csrf" => $this->csrf])->get());
    }

    function GetAccessToken($login = null){
        if (!$login) {
            if(!$this->Login()){
                return false;
            }
        }
        $POST = new POST();
        $POST->set_url = "https://accounts.spotify.com/authorize/accept";
        $POST->set_headers = [
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.62 Safari/537.36",
			"Cookie: csrf_token={$this->csrf}; {$this->cookie};",
        ];
        $POST->set_parameter = [
            "response_type" => "token",
            "redirect_uri" => "https://developer.spotify.com/callback",
            "client_id" => "774b29d4f13844c495f206cafdad9c86",
            "scope" => "user-read-private+user-read-email+user-library-read+user-top-read+playlist-modify-public+user-read-playback-state+user-follow-read+user-modify-playback-state+user-read-recently-played+user-read-currently-playing+user-follow-modify+playlist-modify-private+playlist-read-collaborative+user-library-modify+playlist-read-private+user-read-birthdate",
            "csrf_token" => $this->csrf
        ];
        $POST->execute();
        $output = (stripos($POST->getHeaders("Location"), "access_token") ? $this->get_string($POST->getHeaders("Location"), "access_token=", "&") : false);
        $this->accesstoken = $output;
        return $output;
    }

    function CheckAccessToken($token){
        $GET = new GET();
        $GET->set_url = "https://api.spotify.com/v1/me";
        $GET->set_headers = [
            "Accept: application/json",
            "Content-type: application/json",
            "Authorization: Bearer {$token}"
        ];
        $GET->execute();
        return ($GET->getHttpCode() == "200");
    }

    function GetProfile($token = null){
        if (!$token) {
            $token = $this->GetAccessToken();
            if (!$token) {
                return false;
            }
        }else{
            if (!$this->CheckAccessToken($token)) {
                return false;
            }elseif(!isset($this->csrf, $this->cookie)){
                if (!isset($this->email, $this->password)) {
                   return false;
                }
                elseif (!$this->Login()) {
                    return false;
                }else{}
            }else{}
        }

        // first
        $GET = new GET();
        $GET->set_url = "https://api.spotify.com/v1/me";
        $GET->set_headers = [
            "Accept: application/json",
            "Content-type: application/json",
            "Authorization: Bearer {$token}"
        ];
        $GET->execute();
        $first = json_decode($GET->getBody(), 1);

        // second
        $headers = [
            "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0",
            "Cookie: {$this->cookie}"
        ];
        $search = ['<b class="recurring-date">', '<b class="recurring-price">', '</b>'];

        $GET->set_url = "https://www.spotify.com/id/account/overview/";
        $GET->set_headers = $headers;
        $GET->execute();
        $second = $GET->getBody();

        $product_name = $this->get_string($second, '<h3 class="product-name">', "</h3>");
        $subscription = str_replace($search, [""], $this->get_string($second, '<p class="subscription-status subscription-compact">', '</p>'));
        $expired = $this->get_string($second, '<b class="recurring-date">','</b>');
        $price = $this->get_string($second, '<b class="recurring-price">', '</b>');

        $profile = [
            "name" => $first["display_name"],
            "id" => $first["id"],
            "birthdate" => $first["birthdate"],
            "country" => json_decode(file_get_contents("http://country.io/names.json"), 1)[$first["country"]],
            "product_name" => $product_name,
            "subscription" => $first["product"],
            "subscription_detail" => $subscription,
            "expired" => ($expired ? $expired : "N/A"),
            "recurring_price" => ($price ? $price : "N/A")
        ];
        return $profile;
    }

    function GetId($string){
		if (preg_match('/(.*?)spotify:playlist:(.*?)/si', $string)) {
			return explode(":", $string)[4];
		}
		elseif (preg_match("'<iframe src=\"https://open.spotify.com/embed/user/spotify/playlist/(.*?)\" width=\"300\" height=\"380\" frameborder=\"0\" allowtransparency=\"true\" allow=\"encrypted-media\"></iframe>'si", $string, $match)) {
			return $match[1];
		}
		elseif (preg_match("'https:\/\/open.spotify.com\/user\/spotify\/playlist\/(.*?)si=(.*?)'si", $string, $match)) {
			return str_replace("?", "", $match[1]);
		}
		else{
			return $string;
		}
    }
    
	function GetPlaylists($id_playlist){
		$id_playlist = $this->GetId($id_playlist);
        $access_token = $this->accesstoken;
        $GET = new GET();
        $GET->set_url = "https://api.spotify.com/v1/playlists/{$id_playlist}";
		$GET->set_headers = [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.62 Safari/537.36',
			"authorization: Bearer {$access_token}",
        ];
        $GET->execute();
        if ($GET->getHttpCode() == "200") {
            return json_decode($GET->getBody(), 1);
		}
		else{
			return false;
		}
	}
}
?>
