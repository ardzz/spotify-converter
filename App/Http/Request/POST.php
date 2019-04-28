<?php

namespace Http\Request;

class POST extends HttpRequest{
    function execute(){
        if (isset($this->set_parameter)) {
            $query = $this->set_parameter;
            if (is_array($this->set_parameter)) {
                $this->set_parameter = urldecode(http_build_query($query));
            }
        }
        if (!isset($this->set_url)) {
            $this->get = [
                "success" => false
            ];
            return $this;
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->set_url);
        if (isset($this->set_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->set_proxy);
            if (isset($this->set_proxy_auth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->set_proxy_auth);
            }
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HEADER, 1);

        if (isset($this->set_cookies, $this->set_user_agent)) {
            $headers = [
                "Cookies: {$this->set_cookies}",
                "User-Agent: {$this->set_user_agent}",
            ];
        }elseif(isset($this->set_headers)){
            $headers = $this->set_headers;
        }
        else{
            $headers = [
                "Uset-Agent: ".\Http\Request\UAgent::random()
            ];
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->set_parameter);
        curl_setopt($ch, CURLOPT_POST, 1);
        
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        if (curl_errno($ch)) {
            $this->get = [
                "success" => false,
                "error_msg" => curl_error($ch)
            ];
            return $this;
        }

        $headers = substr($result, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $body = substr($result, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        
        curl_close ($ch);
        $this->get = [
            "success" => true,
            "response" => [
                "total_time" => floor($info["total_time"] % 60)."s",
                "body" => $body,
                "headers" => $headers,
                "parsed_headers" => $this->parse_headers($headers)->get(),
                "parsed_cookies" => $this->parse_cookie($headers)->get(),
            ]
        ];
        return $this;
    }
}