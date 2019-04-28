<?php
namespace UI\Basic;
use \Http\Request\HttpRequest;

interface UI{
    function banner();
    function menu();
    function show();
}

class Text extends HttpRequest implements UI{

    /*function __construct(){
        echo $this->cls();
    }*/

    function banner(){
        $this->show = "
        _   _ _   _        ______                           _   
       | | | | | | |       | ___ \                         | |  
       | |_| | |_| |_ _ __ | |_/ /___  __ _ _   _  ___  ___| |_ 
       |  _  | __| __| '_ \|    // _ \/ _` | | | |/ _ \/ __| __|
       | | | | |_| |_| |_) | |\ \  __/ (_| | |_| |  __/\__ \ |_ 
       \_| |_/\__|\__| .__/\_| \_\___|\__, |\__,_|\___║___/\__|
                     | |                 | |                    
                     |_|                 |_|                                              
                     
                ••• Ardhana • Awokology Science +62 •••
        ".PHP_EOL;
       return $this;
    }

    function menu(){
        $this->show = [
            "HttpRequest" => [ 
                "Method" => [
                    "GET",
                    "POST"
                ],
                "Fiture" => [
                    "getBody" => "To get response body",
                    "getHeaders" => "To get response headers",
                    "getCookies" => "To get cookies",
                    "getHttpCode" => "To get response code"
                ],
                "Service" => [
                    "Spotify" => [
                        "GetAccessToken",
                        "GetProfile",
                        "Login",
                        "EmailExists"
                    ]
                ]
            ]
        ];
        return $this;
    }

    function render(){
        $menu = $this->menu()->show();
        $output  = $this->banner()->show();
        $output .= "       Available Method: ".implode(", ", $menu["HttpRequest"]["Method"]).PHP_EOL;
        $output .= "       ──────────────────────────────•".PHP_EOL.PHP_EOL;
        $output .= "       Fiture:".PHP_EOL.PHP_EOL;
        while (list($var, $val) = each($menu["HttpRequest"]["Fiture"])) {
            $output .= "       • {$var} ⮕  {$val}".PHP_EOL;
        }
        $output .= PHP_EOL."       Servie: ".PHP_EOL.PHP_EOL;
        while (list($var, $val) = each($menu["HttpRequest"]["Service"])) {
            $output .= "       • {$var} ⮕  ".implode(", ", $val).PHP_EOL;
        }
        return $output.PHP_EOL;
    }

    function show(){
        return $this->show;
    }

    function cls(){
		return chr(27).chr(91).'H'.chr(27).chr(91).'J';
	}
    
    function generate_file(){

        $file = uniqid("Cache__awokology__");
        $this->file_name = "Cache/Codes/{$file}__.php";
        $this->file_name_output = "Cache/Output/{$file}__.json";

        $this->save($this->file_name, "<?php".PHP_EOL.PHP_EOL."
        /**
         *
         * @author Ardhana <zeebploit212@gmail.com>
         * @package Cache Code Awokology
         * 
         */".PHP_EOL.PHP_EOL.PHP_EOL);
        return $this->file_name;
    }

    function destroy(){
        unlink($this->file_name);
        return $this->generate_file();
    }

    function write($source){
        if ($this->save($this->file_name, $source)) {
            return true;
        }else{
            echo "       Can't write codes!".PHP_EOL;
            exit();
        }
    }

    function write_code($source){
        if (!isset($this->file_name)) {
            $this->file_name = "Cache/Codes/".uniqid("Cache__awokology__")."__.php";
        }

        // create codes :(
        $final = null;
        $method = ((isset($this->method)) ? "\${$this->method}" : false);

        $temp = strtolower(str_replace(PHP_EOL, "", $source));
        if ($temp == "use http-request get") {
            $this->method = "GET";
            $this->write("use \Http\Request\GET;".PHP_EOL."\$GET = new GET();".PHP_EOL.PHP_EOL);
        }

        elseif($temp == "use http-request post"){
            $this->method = "POST";
            $this->write("use \Http\Request\POST;".PHP_EOL."\$POST = new POST();".PHP_EOL.PHP_EOL);
        }

        elseif ($temp == "use http-request") {
            $this->write("use \Http\Request\GET;".PHP_EOL."use \Http\Request\POST;".PHP_EOL.PHP_EOL);
            $this->write("\$GET = new GET();".PHP_EOL."\$POST = new POST();".PHP_EOL.PHP_EOL);
        }

        elseif(preg_match("/get.set_url/", $temp)){
            $this->method = "GET";
            $url = explode(" = ", $temp);
            $this->write("\$GET->set_url = \"{$url[1]}\";".PHP_EOL);
        }

        elseif(preg_match("/post.set_url/", $temp)){
            $this->method = "POST";
            $url = explode(" = ", $temp);
            $this->write("\$POST->set_url = \"{$url[1]}\";".PHP_EOL);
        }

        elseif (preg_match("/post.set_param/", $temp)) {
            $this->method = "POST";
            $param = explode(" = ", $temp);
            $this->write("\$POST->set_parameter = \"{$param[1]}\";".PHP_EOL);
        }

        elseif ($temp == "run") {
            $this->write("{$method}->execute();".PHP_EOL.PHP_EOL."@file_put_contents(\"{$this->file_name_output}\", {$method}->json()->get());");
            $this->RunCode();
            return "       [ Awokology ]🠶  Output saved in {$this->file_name_output}".PHP_EOL;
        }

        elseif ($temp == "run getbody") {
            $this->write("{$method}->execute();".PHP_EOL.PHP_EOL."\$output = {$method}->json([{$method}->getBody()])->get();".PHP_EOL."@file_put_contents(\"{$this->file_name_output}\", \$output );");
            $this->RunCode();
            return "       [ Awokology ]🠶  Output saved in {$this->file_name_output}".PHP_EOL;
        }

        elseif ($temp == "clear code") {
            $this->destroy();
        }

        elseif ($temp == "cache info") {
            return "       [ Awokology ]🠶  {$this->file_name}|".hash_file("md5", $this->file_name).PHP_EOL;
        }

        elseif ($temp == "cls") {
            echo $this->cls();
        }
    }

    function RunCode(){
        if (!file_exists($this->file_name)) {
            return "       Error".PHP_EOL;
        }else{
            eval(str_replace("<?php", "", @file_get_contents($this->file_name)));
        }
    }

}

?>