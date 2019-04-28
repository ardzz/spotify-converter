<?php

/**
*
* @author Ardhana <zeebploit212@gmail.com>
*
*/

require_once "vendor/autoload.php";

use \Service\Spotify\Spotify;
use \UI\Basic\Spotify\Text as UI;

$UI = new UI();
$Spotify = new Spotify();

$Spotify->email = "";
$Spotify->password = "";
$Spotify->set_path = __DIR__."/Output";

echo $UI->banner()->show().PHP_EOL.PHP_EOL;
$menu = readline("       [*] Select menu number : ");
echo PHP_EOL;

$green  = "\e[1;92m";
$cyan   = "\e[1;36m";
$normal = "\e[0m";
$blue   = "\e[34m";
$green1 = "\e[0;92m";
$yellow = "\e[93m";
$red    = "\e[1;91m";

if ($menu == 0) {
    $login = $Spotify->Login();
    if ($login) {
        echo "       [*] Successfully login".PHP_EOL;
        $token = $Spotify->GetAccessToken(1);
        if ($token) {
            echo "       [*] Here is your access token : {$token}".PHP_EOL.PHP_EOL;
        }else{
            echo "       [!] Failed to retrieve access token".PHP_EOL;
        }
    }else{
        echo "       [!] Login failed".PHP_EOL;
    }
}

elseif ($menu == 1) {
    $login = $Spotify->Login();
    if ($login) {
        echo "       [*] Successfully login".PHP_EOL;
        $token = $Spotify->GetAccessToken(1);
        if ($token) {
            echo "       [*] Successfully get access token".PHP_EOL;
            $profile = $Spotify->GetProfile($token);
            if ($profile) {
                echo "       [*] Successfully retrieve data".PHP_EOL.PHP_EOL;
                while (list($key, $value) = each($profile)) {
                    echo "       [*] ".ucfirst(str_replace("_", " ", $key))." : {$value}".PHP_EOL;
                }
            }else{
                echo "       [!] Failed to retrieve data".PHP_EOL;
            }
        }else{
            echo "       [!] Failed to retrieve access token".PHP_EOL;
        }
    }else{
        echo "       [!] Login failed".PHP_EOL;
    }
}

elseif ($menu == 2) {
    $login = $Spotify->Login();
    if ($login) {
		echo "       [*] Successfully login".PHP_EOL.PHP_EOL;
        echo "       [*] Example : 37i9dQZF1DX3rxVfibe1L0".PHP_EOL;
        $playlists = readline("       [*] Playlists id : ");
        $token = $Spotify->GetAccessToken(1);
        if ($token) {
            echo "       [*] Successfully get access token".PHP_EOL.PHP_EOL;
            $playlist = $Spotify->GetPlaylists($playlists);
            if ($playlist) {
                echo "       [*] Playlist Name : ".$playlist["name"].PHP_EOL;
                echo "       [*] Description   : ".$playlist["description"].PHP_EOL.PHP_EOL;
                $x = 1;
                foreach ($playlist["tracks"]["items"] as $var){
                    $song = $var["track"]["name"];
                    $url = $var["track"]["external_urls"]["spotify"];
                    $artists = [];
                    foreach($var["track"]["artists"] as $y) {
                        $artists[] = $y["name"];
                    }
                    $artist = implode(", ", $artists);
                    $id = $Spotify->search($song." ".$artist);
                    $title = "{$song} - {$artist}.mp3";
                    echo "       [".$x++."] {$song} ⎯ {$cyan}{$artist}{$normal}".PHP_EOL;
                    echo "       [*] {$url} [ {$green}Spotify{$normal} ]".PHP_EOL;

                    if (!is_dir($Spotify->set_path."/".$playlist["name"])) {
                        @mkdir($Spotify->set_path."/".$playlist["name"]);
                    }

                    $convert_to_mp3 = $Spotify->convert2mp3($id);
                    if ($convert_to_mp3) {
                        if (empty($convert_to_mp3["url_download"])) {
                        }else{
                            echo "       {$green1}[*] Success convert to mp3{$normal}".PHP_EOL;
                            echo "       [*] URL Download : ".$convert_to_mp3["url_download"].PHP_EOL;
                            echo "       [*] Size         : ".$convert_to_mp3["size"].PHP_EOL;
                            echo "       [*] Downloading file ...".PHP_EOL;
                            $path = $Spotify->set_path."/".$playlist["name"];
                            $title = str_replace("/"," - ", $title);
                            if(!file_exists($path."/".$title)){
                                if ($Spotify->download($convert_to_mp3["url_download"], $path."/".$title)) {
                                    echo "       {$green1}[*] Successfully downloaded{$normal}".PHP_EOL.PHP_EOL;
                                }
                                else{
                                    echo "       {$red}[!] Failed to download file{$normal}".PHP_EOL.PHP_EOL;
                                }
                            }
                        }
                    }else{}
                }
            } 
            else {}
        }else{
            echo "       [!] Failed to retrieve access token".PHP_EOL;
        }
    }else{
        echo "       [!] Login failed".PHP_EOL;
    }
}
?>
