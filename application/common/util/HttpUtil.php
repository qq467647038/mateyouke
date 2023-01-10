<?php
namespace app\common\util;

class HttpUtil{
	
	public static function strexists($string, $find) {
    	return !(strpos($string, $find) === FALSE);
	}

	public static function httpRequest($url, $post = '', $extra = array(), $timeout = 60) {
	    $urlset = parse_url($url);
	    if (empty($urlset['path'])) {
	        $urlset['path'] = '/';
	    }
	    if (!empty($urlset['query'])) {
	        $urlset['query'] = "?{$urlset['query']}";
	    }else {
	        $urlset['query'] = '';
	    }
	    if (empty($urlset['port'])) {
	        $urlset['port'] = $urlset['scheme'] == 'https' ? '443' : '80';
	    }
	    if (self::strexists($url, 'https://') && !extension_loaded('openssl')) {
	        if (!extension_loaded("openssl")) {
	            throw new \Core\Exception('请开启您PHP环境的openssl');
	        }
	    }
	    if (function_exists('curl_init') && function_exists('curl_exec')) {
	        $ch = curl_init();
	        if (!empty($extra['ip'])) {
	            $extra['Host'] = $urlset['host'];
	            $urlset['host'] = $extra['ip'];
	            unset($extra['ip']);
	        }
	        curl_setopt($ch, CURLOPT_URL, $urlset['scheme'] . '://' . $urlset['host'] . ($urlset['port'] == '80' ? '' : ':' . $urlset['port']) . $urlset['path'] . $urlset['query']);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	        curl_setopt($ch, CURLOPT_HEADER, 1);
	        if ($post) {
	            if (is_array($post)) {
	                $filepost = false;
	                foreach ($post as $name => $value) {
	                    if (substr($value, 0, 1) == '@' || (class_exists('CURLFile') && $value instanceof CURLFile)) {
	                        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
	                        $post[$name] = new CURLFile(realpath(substr($value, 1)));
	                        $filepost = true;
	                        break;
	                    }else {
	                        if (defined('CURLOPT_SAFE_UPLOAD')) {
	                            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	                        }
	                    }
	                }
	                if (!$filepost) {
	                    $post = http_build_query($post);
	                }
	            }
	             
	            curl_setopt($ch, CURLOPT_POST, 1);
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	        }
	        if (!empty($GLOBALS['_W']['config']['setting']['proxy'])) {
	            $urls = parse_url($GLOBALS['_W']['config']['setting']['proxy']['host']);
	            if (!empty($urls['host'])) {
	                curl_setopt($ch, CURLOPT_PROXY, "{$urls['host']}:{$urls['port']}");
	                $proxytype = 'CURLPROXY_' . strtoupper($urls['scheme']);
	                if (!empty($urls['scheme']) && defined($proxytype)) {
	                    curl_setopt($ch, CURLOPT_PROXYTYPE, constant($proxytype));
	                } else {
	                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
	                    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
	                }
	                if (!empty($GLOBALS['_W']['config']['setting']['proxy']['auth'])) {
	                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['_W']['config']['setting']['proxy']['auth']);
	                }
	            }
	        }
	        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
	        if (defined('CURL_SSLVERSION_TLSv1')) {
	            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
	        }
	        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
	        if (!empty($extra) && is_array($extra)) {
	            $headers = array();
	            foreach ($extra as $opt => $value) {
	                if (self::strexists($opt, 'CURLOPT_')) {
	                    curl_setopt($ch, constant($opt), $value);
	                } elseif (is_numeric($opt)) {
	                    curl_setopt($ch, $opt, $value);
	                } else {
	                    $headers[] = "{$opt}: {$value}";
	                }
	            }
	            if (!empty($headers)) {
	                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	            }
	        }
	        $data = curl_exec($ch);
	        $status = curl_getinfo($ch);
	        $errno = curl_errno($ch);
	        $error = curl_error($ch);
	        curl_close($ch);
	        if ($errno || empty($data)) {
	            return self::httpError(1, $error);
	        } else {
	            return self::httpResponseParse($data);
	        }
	    }
	    $method = empty($post) ? 'GET' : 'POST';
	    $fdata = "{$method} {$urlset['path']}{$urlset['query']} HTTP/1.1\r\n";
	    $fdata .= "Host: {$urlset['host']}\r\n";
	    if (function_exists('gzdecode')) {
	        $fdata .= "Accept-Encoding: gzip, deflate\r\n";
	    }
	    $fdata .= "Connection: close\r\n";
	    if (!empty($extra) && is_array($extra)) {
	        foreach ($extra as $opt => $value) {
	            if (!self::strexists($opt, 'CURLOPT_')) {
	                $fdata .= "{$opt}: {$value}\r\n";
	            }
	        }
	    }
	    $body = '';
	    if ($post) {
	        if (is_array($post)) {
	            $body = http_build_query($post);
	        } else {
	            $body = urlencode($post);
	        }
	        $fdata .= 'Content-Length: ' . strlen($body) . "\r\n\r\n{$body}";
	    } else {
	        $fdata .= "\r\n";
	    }
	    if ($urlset['scheme'] == 'https') {
	        $fp = fsockopen('ssl://' . $urlset['host'], $urlset['port'], $errno, $error);
	    } else {
	        $fp = fsockopen($urlset['host'], $urlset['port'], $errno, $error);
	    }
	    stream_set_blocking($fp, true);
	    stream_set_timeout($fp, $timeout);
	    if (!$fp) {
	        return self::hrrtError(1, $error);
	    } else {
	        fwrite($fp, $fdata);
	        $content = '';
	        while (!feof($fp))
	            $content .= fgets($fp, 512);
	        fclose($fp);
	        return self::httpResponseParse($content, true);
	    }
	}


	public static function httpResponseParse($data, $chunked = false) {
	    $rlt = array();
	    $headermeta = explode('HTTP/', $data);
	    if (count($headermeta) > 2) {
	        $data = 'HTTP/' . array_pop($headermeta);
	    }
	    $pos = strpos($data, "\r\n\r\n");
	    $split1[0] = substr($data, 0, $pos);
	    $split1[1] = substr($data, $pos + 4, strlen($data));

	    $split2 = explode("\r\n", $split1[0], 2);
	    preg_match('/^(\S+) (\S+) (\S+)$/', $split2[0], $matches);
	    
	    $rlt['code'] = $matches[2];
	    $rlt['status'] = $matches[3];
	    $rlt['responseline'] = $split2[0];
	    $rlt['headers'] = '';
	    $header = explode("\r\n", $split2[1]);
	    $isgzip = false;
	    $ischunk = false;

	    foreach ($header as $v) {
	        $pos = strpos($v, ':');
	        $key = substr($v, 0, $pos);
	        $value = trim(substr($v, $pos + 1));
	        $rlt = array();
	        if (isset($rlt['headers'][$key]) && is_array($rlt['headers'][$key])) {
	            $rlt['headers'][$key][] = $value;
	        } elseif (!empty($rlt['headers'][$key])) {
	            $temp = $rlt['headers'][$key];
	            unset($rlt['headers'][$key]);
	            $rlt['headers'][$key][] = $temp;
	            $rlt['headers'][$key][] = $value;
	        } else {
	            $rlt['headers'][$key] = $value;
	        }
	        if(!$isgzip && strtolower($key) == 'content-encoding' && strtolower($value) == 'gzip') {
	            $isgzip = true;
	        }
	        if(!$ischunk && strtolower($key) == 'transfer-encoding' && strtolower($value) == 'chunked') {
	            $ischunk = true;
	        }
	    }
	    if($chunked && $ischunk) {
	        $rlt['content'] = self::httpResponseParseUnchunk($split1[1]);
	    } else {
	        $rlt['content'] = $split1[1];
	    }
	    if($isgzip && function_exists('gzdecode')) {
	        $rlt['content'] = gzdecode($rlt['content']);
	    }

	    $rlt['meta'] = $data;
	    if($rlt['code'] == '100') {
	        return self::httpResponseParse($rlt['content']);
	    }
	    return $rlt;
	}

	public static function httpResponseParseUnchunk($str = null) {
	    if(!is_string($str) or strlen($str) < 1) {
	        return false;
	    }
	    $eol = "\r\n";
	    $add = strlen($eol);
	    $tmp = $str;
	    $str = '';
	    do {
	        $tmp = ltrim($tmp);
	        $pos = strpos($tmp, $eol);
	        if($pos === false) {
	            return false;
	        }
	        $len = hexdec(substr($tmp, 0, $pos));
	        if(!is_numeric($len) or $len < 0) {
	            return false;
	        }
	        $str .= substr($tmp, ($pos + $add), $len);
	        $tmp  = substr($tmp, ($len + $pos + $add));
	        $check = trim($tmp);
	    } while(!empty($check));
	    unset($tmp);
	    return $str;
	}


	public static function httpGet($url) {
	    return self::httpRequest($url);
	}

	public static function httpPost($url, $data) {
	    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
	    return self::httpRequest($url, $data, $headers);
	}

	public static function httpError($errno, $message = '') {
	    $return = array('errno' => $errno, 'message' => $message);
	    return json_encode($return, JSON_UNESCAPED_UNICODE);
	    
	}
}

?>