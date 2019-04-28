<?php
namespace UI\Basic\Spotify;
use \UI\Basic\Text as Basic;

interface UI{
    function banner();
    function menu();
    function show();
}

class Text extends Basic implements UI{
    
    function first(){
        $this->return = "
        _____             _   _  __       _____       
       /  ___|           | | (_)/ _|     /  __ \      
       \ `--. _ __   ___ | |_ _| |_ _   _| /  \/_   __
        `--. \ '_ \ / _ \| __| |  _| | | | |   \ \ / /
       /\__/ / |_) | (_) | |_| | | | |_| | \__/\\\ V / 
       \____/| .__/ \___/ \__|_|_|  \__, |\____/ \_/  
             | |                     __/ |            
             |_|                    |___/             
       ";
        return $this;
    }

    function banner(){
        $this->return = $this->cls().$this->first()->show().PHP_EOL.PHP_EOL.$this->menu()->show();
       return $this;
    }

    function menu(){
        $menu = [
            "Get access token",
            "Account profile",
            "Convert playlists to mp3"
        ];
        $output = null;
        foreach ($menu as $key => $value) {
            $output .= "       [{$key}] {$value}".PHP_EOL;
        }
        $this->return = $output;
        return $this;
    }

    function show(){
        return $this->return;
    }
}