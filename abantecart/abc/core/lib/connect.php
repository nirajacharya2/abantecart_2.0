<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\Registry;
use DOMDocument;

/**
 * Class AConnect
 *
 * @package abc\core\lib
 * @property AConfig  $config
 * @property ASession $session
 */
final class AConnect
{
    /**
     * @var string
     */
    public $connect_method;
    /**
     *  error text
     *
     * @var array
     */
    public $errors = [];
    /**
     * enable/disable silent mode, mode without any messages to web-page
     *
     * @var boolean
     */
    private $silent_mode;
    /**
     * registry to provide access to cart objects
     *
     * @var Registry
     */
    private $registry;
    /**
     * http-headers list
     *
     * @var array
     */
    private $request_headers;
    /**
     * url of page - source of request
     *
     * @var string
     */
    private $request_referer;
    /**
     * timeout in seconds
     *
     * @var int
     */
    private $timeout = 5;
    /**
     * http-authorization parameters
     *
     * @var array
     */
    private $auth = ['name' => null, 'pass' => null];
    /**
     * array with options for curl request
     *
     * @var array
     */
    private $curl_options = [];
    /**
     * array with http-headers of socket request
     *
     * @var array
     */
    private $socket_options;
    /*
     * array with response http-headers
     * */
    public $response_headers = [];
    /**
     * @var int  - max allowed redirect count
     */
    private $redirect_count;

    /**
     * @param boolean $silent_mode
     * @param bool    $direct - sign of connection without redirect
     */
    public function __construct($silent_mode = true, $direct = false)
    {

        $this->redirect_count = $direct ? 0 : 3;
        $this->silent_mode = $silent_mode;
        $this->registry = Registry::getInstance();
        $this->connect_method = null;
        //check available connections on the server.
        $this->_check();
        $this->request_headers['User-Agent'] = ABC::env('APP_NAME').'/'.ABC::env('VERSION');
        //??? $this->request_referer = $this->uri;
    }

    /**
     * getter
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->registry->get($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    /**
     * @param string $name
     */
    public function setAuthName($name)
    {
        $this->auth['name'] = $name;
    }

    /**
     * @param string $pass
     */
    public function setAuthPass($pass)
    {
        $this->auth['pass'] = $pass;
    }

    /**
     * @param string $url
     *
     * @return false|array( "mime" <string>, "length" int, "content" string)
     * @throws AException
     */
    public function getResponse($url)
    {
        if (!$url = $this->_checkURL($url)) {
            return false;
        }
        $output = $this->getData($url);

        return $output;
    }

    /**
     * @param string $url
     *
     * @return false | array( "mime" <string>, "length" int, "content" string)
     * @throws AException
     */
    public function getResponseSecure($url)
    {
        if (!$url = $this->_checkURL($url, true)) {
            return false;
        }
        $output = $this->getData($url, 443);

        return $output;
    }

    /**
     * @param string $url
     * @param string $new_filename
     * @param boolean $secure
     *
     * @return array | boolean
     * @throws AException
     */
    public function getFile($url, $new_filename = '', $secure = false)
    {
        if (!$url = $this->_checkURL($url, $secure)) {
            return false;
        }
        $url = $this->_checkURL($url);
        $port = $secure ? 443 : 80;
        if (!$new_filename) {
            $new_filename = $this->_checkFilename($url);
        }
        if (!$new_filename) {
            $this->errors[] = "Error: unknown file name for saving. ";

            return false;
        }
        if ($this->_check_socket()) {
            $this->connect_method =
                'socket'; // set this hard because have no ability to get content-disposition http-header form curl
        }

        $result = $this->getData($url, $port, false, $new_filename);
        if (!isset($this->response_headers['Content-Disposition'])
            || !is_int(strpos($this->response_headers['Content-Disposition'], 'attachment'))) {
            // if attachment is absent - try to get filename from url
            $file_name = parse_url($url);
            if (pathinfo($file_name['path'], PATHINFO_EXTENSION)) {
                $file_name = pathinfo($file_name['path'], PATHINFO_BASENAME);
            } else {
                $file_name = '';
            }

            if (!$file_name) {
                $this->errors[] = "Error: Cannot to download file. Attachment is absent.";

                return false;
            }
        }

        return $result;
    }

    /**
     * @param string  $url
     * @param boolean $secure
     *
     * @return string $url
     */
    private function _checkURL($url, $secure = false)
    {
        $url = trim($url);

        if (!$url) {
            $this->errors[] = "Error: empty URL was given for connection.";
            $this->registry->get('log')->write(implode("\n", $this->errors));

            return false;
        }
        if (substr($url, 0, 4) != 'http') {
            $url = ($secure ? 'https' : 'http').'://'.base64_decode('d3d3LmFiYW50ZWNhcnQuY29t').$url;
        } else {
            if ($secure) {
                $url = str_replace("http://", "https://", $url);
                if (substr($url, 0, 8) != 'https://') {
                    $url = "https://".$url;
                }
            } else {
                $url = str_replace("https://", "http://", $url);
                if (substr($url, 0, 7) != 'http://') {
                    $url = "http://".$url;
                }
            }
        }

        return $url;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function _checkFilename($filename)
    {
        if (!$filename) {
            return false;
        }
        $filename = explode("/", $filename);
        end($filename);

        return current($filename);
    }

    /**
     * @param string $url
     * @param int $port
     *
     * @return false | int (bytes)
     * @throws AException
     */
    public function getDataLength($url, $port = 80)
    {
        $url .= !is_int(strpos($url, '?')) ? '?file_size=1' : '&file_size=1';
        if (!$url = $this->_checkURL($url, ($port == 443 ? true : false))) {
            return false;
        }

        return $this->getData($url, $port, true);
    }

    /**
     * @param string $url
     * @param int $port
     *
     * @return int (bytes)
     * @throws AException
     */
    public function getDataHeaders($url, $port = 80)
    {
        if (!($url = $this->_checkURL($url, ($port == 443 ? true : false)))) {
            return false;
        }

        return $this->getData($url, $port, false, false, true);
    }

    /**
     * @param string  $url
     * @param int     $port
     * @param boolean $length_only
     * @param boolean $save_filename
     * @param bool    $headers_only
     *
     * @throws AException
     * @return false | int | array ( "mime" <string>, "length" int, "content" string) or false
     */
    public function getData($url, $port = null, $length_only = false, $save_filename = null, $headers_only = false)
    {
        //check url
        $protocol = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($protocol, ['http', 'https'])) {
            $this->errors[] = "ERROR: wrong URL!";

            return false;
        }

        $port = !$port ? ($protocol == 'http' ? 80 : 443) : (int)$port;
        if (!$port) {
            $this->errors[] = "ERROR: wrong port number!";

            return false;
        }

        switch ($this->connect_method) {
            case 'curl' :
                $ret_buffer = $this->_processCurl($url, $port, $save_filename, $length_only, $headers_only);
                break;
            case 'socket' :
                if ($this->connect_method == 'socket' && $protocol == 'https') {
                    $url = str_replace("https://", "ssl://", $url);
                    $port = $port == 80 ? 443 : $port;
                }

                $ret_buffer = $this->_processSocket($url, $port, $save_filename, $length_only, $headers_only);

                break;
            default :
                if ($this->silent_mode) {
                    return false;
                } else {
                    throw new AException('No connect method available ( curl | socket )', AC_ERR_CONNECT_METHOD);
                }
                break;
        }

        if (!$ret_buffer) {
            return false;
        }

        if ($length_only || $headers_only) {
            return $ret_buffer;
        }

        if (!$save_filename) {
            return $this->_convertToArray($ret_buffer);
        } else {
            if (!file_put_contents($save_filename, $ret_buffer["data"])) {
                $this->errors[] = "ERROR: Can't save file as ".$save_filename."!";

                return false;
            }
            @chmod($save_filename, 0777);

            return true;
        }

    }

    /**
     * available methods of getting data check
     */
    private function _check()
    {
        //Check if we have connection set in the settings
        //???? will be developed later
        if (is_callable($this->config) && $this->config->get('connection_method')) {
            $this->connect_method = $this->config->get('connection_method');

            return null;
        }
        //We prefer Curl first, Curl is the fastest performing
        if ($this->_check_curl()) {
            $this->connect_method = 'curl';
        } else {
            // we have no choice to use socket.
            $this->connect_method = 'socket';
        }
    }

    /**
     * @param string  $url
     * @param int     $port
     * @param null    $filename
     * @param boolean $length_only
     * @param bool    $headers_only
     *
     * @return false | int | array( "mime" <string>, "length" int, "content" string) or false
     */
    private function _processCurl($url, $port = 80, $filename = null, $length_only = false, $headers_only = false)
    {
        //Curl Connection for HTTP and HTTPS
        $authentication = $this->auth['name'] ? 1 : 0;
        $curl_sock = curl_init();
        // write handler into session for response-preloader.
        $this->session->data['curl_handler'] = $curl_sock;
        // set default options for curl
        if (!$this->curl_options) {
            $this->curl_options = [
                CURLOPT_CONNECTTIMEOUT => $this->timeout,  //wait for connect
                CURLOPT_TIMEOUT        => !$headers_only ? $this->timeout : 1,  // timeout for open connection
                CURLOPT_HTTPHEADER     => ['Expect:'],
                CURLOPT_MAXREDIRS      => 4,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ];
        }

        // for safe-mode part. Problem is redirects while connect.
        $this->curl_options[CURLOPT_SSL_VERIFYPEER] = false;
        if ($port != 80) {
            $this->curl_options[CURLOPT_PORT] = $port;
        }
        $redirect_count = $this->redirect_count;

        $this->curl_options[CURLOPT_FOLLOWLOCATION] = false;
        if ($redirect_count > 0) {
            $new_url = $url;
            $rch = curl_copy_handle($curl_sock);
            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
            do {
                if ($this->curl_options) {
                    // check for curl options
                    foreach ($this->curl_options as $k => $v) {
                        if (!is_int($k)) {
                            $this->registry->get('log')->write('Warning: Unknown CURL option: '.$k
                                .' was given to AConnect class.');
                            unset($this->curl_options[$k]);
                        }
                    }
                    curl_setopt_array($rch, $this->curl_options);
                }
                curl_setopt($rch, CURLOPT_URL, $new_url);
                $header = curl_exec($rch);
                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $new_url = trim(array_pop($matches));
                    } else {
                        $code = 0;
                    }
                }
                $redirect_count--;
            } while ($code && $redirect_count);
            curl_close($rch);
            if (!$redirect_count) {
                return false;
            }
            $url = $new_url;
        }

        $this->curl_options[CURLOPT_URL] = $url;

        if ($authentication) {
            $this->curl_options[CURLOPT_USERPWD] = $this->auth['name'].':'.$this->auth['pass'];
        }
        $this->curl_options[CURLOPT_REFERER] = $this->request_referer;
        if ($length_only || $headers_only) {
            $this->curl_options[CURLOPT_HEADER] = true;
            $this->curl_options[CURLOPT_NOBODY] = true;
        } else {
            unset($this->curl_options[CURLOPT_HEADER], $this->curl_options[CURLOPT_NOBODY]);
        }

        curl_setopt_array($curl_sock, $this->curl_options);
        if (!($response = curl_exec($curl_sock))) {
            if (curl_errno($curl_sock)) {
                $this->errors[] = 'Error: '.curl_error($curl_sock);
            } else {
                $this->errors[] = 'Error: Response contain zero byte.';
            }

            return false;
        } else {
            $content_type = curl_getinfo($curl_sock, CURLINFO_CONTENT_TYPE);
            $content_length = curl_getinfo($curl_sock, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $status = (int)curl_getinfo($curl_sock, CURLINFO_HTTP_CODE);
            if (!in_array($status, [0, 200, 300, 301, 302, 304, 305, 307])) {
                $this->errors[] =
                    'Error: Can\'t get data(file '.$filename.') by URL '.$url.', HTTP status code : '.$status;

                return false;
            }

            if ($headers_only) {
                $headers = curl_getinfo($curl_sock);
                curl_close($curl_sock);

                return $headers;
            }
            if ($length_only) {
                $content_length = curl_getinfo($curl_sock, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                curl_close($curl_sock);

                return $content_length;
            }
        }
        curl_close($curl_sock);
        unset($this->session->data['curl_handler']);

        $content_length = $content_length ? (int)$content_length : -1;
        $content_type = $content_type ? $content_type : -1;

        return ["mime" => $content_type, "length" => $content_length, "data" => $response];
    }

    /**
     * @param string  $url
     * @param int     $port
     * @param null    $filename
     * @param boolean $length_only
     * @param bool    $headers_only
     *
     * @return false | int | array( "mime" <string>, "length" int, "content" string)
     */
    public function _processSocket($url, $port, $filename = null, $length_only = false, $headers_only = false)
    {
        //Socket Connection for HTTP and HTTPS
        $authentication = $this->auth['name'] ? 1 : 0;
        $url = parse_url($url);
        $fsockhost = $url['scheme'] == 'ssl' ? 'ssl://'.$url['host'] : $url['host'];
        $sock = fsockopen($fsockhost, $port, $errno, $errstr, 5);
        if (!$sock) {
            $this->errors[] = "Error: ".$errstr."(".$errno.")";

            return false;
        }
        $sent_headers = "GET ".$url['path'].'?'.$url['query']." HTTP/1.1\r\n";
        $sent_headers .= "Host: ".$url['host']."\r\n";
        $sent_headers .= "Connection: Close\r\n";
        // add on more custom http-headers
        if ($this->socket_options) {
            if (!is_array($this->socket_options)) {
                foreach ($this->socket_options as $request_header) {
                    $sent_headers .= $request_header."\r\n";
                }
            } else {
                $sent_headers .= $this->socket_options."\r\n";
            }
        }
        if ($authentication) {
            $sent_headers .= 'Authorization: Basic '.base64_encode($this->auth['name'].':'.$this->auth['pass'])."\r\n";
        }
        $sent_headers .= "\r\n";
        fwrite($sock, $sent_headers);
        $headers = [];
        $response = '';
        if ($length_only || $headers_only) {
            $i = 0;
            while (strpos($response, "\r\n\r\n") === false && $i < 50000) {
                $response .= fgets($sock, 1280);
                $i++;
            }
            $headers = $this->_http_parse_headers($response);
            $status = explode(' ', $headers['status']);
            $status = (int)$status[1];

            if (!in_array($status, [0, 200, 300, 301, 302, 304, 305, 307])) {
                $this->errors[] = 'Error: Can\'t get length of data(file). HTTP status code : '.$headers['status'];

                return false;
            }
            fclose($sock);
            if (in_array($status, [0, 300, 301, 302, 304, 305, 307])) { // if redirected
                if ($headers['Location']) {
                    if (strpos($headers['Location'], 'https://') !== false) {
                        $headers['Location'] = str_replace('https://', 'ssl://', $headers['Location']);
                        $port = 443;
                    }

                    return $this->_processSocket($headers['Location'], $port, $filename, $length_only, $headers_only);
                }
            }

            if ($headers_only) {
                return $headers;
            }

            if ($headers['Content-Length']) {
                return (int)$headers['Content-Length'];
            } else {
                $this->errors[] = 'Error: http status code : '.$headers['status'];

                return false;
            }
        } else {
            while (!feof($sock)) {
                $response .= fgets($sock, 1280);
            }
        }
        fclose($sock);
        $offset = strpos($response, "\r\n\r\n");
        if ($offset !== false) {
            $headers = substr($response, 0, $offset + 4);
            $response = substr($response, $offset + 4);
        }
        $headers = $this->_http_parse_headers($headers);
        $status = explode(' ', $headers['status']);
        $status = (int)$status[1];
        if (!in_array($status, [0, 200, 300, 301, 302, 304, 305, 307])) {
            $this->errors[] = 'Error: Can\'t get data(file) by url '.print_r($url).':'.$port.'. HTTP status code : '
                .$headers['status'];

            return false;
        }

        if (in_array($status, [0, 300, 301, 302, 304, 305, 307])) { // if redirected
            if ($headers['Location']) {
                if (strpos($headers['Location'], 'https://') !== false) {
                    $headers['Location'] = str_replace('https://', 'ssl://', $headers['Location']);
                    $port = 443;
                }

                return $this->_processSocket($headers['Location'], $port, $filename, $length_only, $headers_only);
            }
        }

        $content_length = $headers['Content-Length'] ? (int)$headers['Content-Length'] : -1;
        $content_type = $headers['Content-Type'] ? $headers['Content-Type'] : -1;

        return ["mime" => $content_type, "length" => $content_length, "data" => $response];
    }

    /**
     * @return bool
     */
    private function _check_curl()
    {
        return function_exists('curl_version');
    }

    /**
     * @return bool
     */
    private function _check_socket()
    {
        return function_exists('fsockopen');
    }

    /**
     * @param array $opt
     * @param bool  $override
     *
     * @return boolean
     */
    public function setCurlOptions($opt, $override = false)
    {
        if (is_array($opt)) {
            if ($override) {
                $this->curl_options = $opt;
            } else {
                $this->curl_options = $this->curl_options + $opt;
            }
        } else {
            $this->errors['curl_options'] =
                'AConnect: Cannot to set curl options. Parameter must be an array. Given: '.var_export($opt, true);
        }

        return true;
    }

    /**
     * this func can add http-header for request
     *
     * @param array $opt
     * @param bool  $override
     *
     * @return bool
     */
    public function setSocketOptions($opt, $override = false)
    {
        if (is_array($opt)) {
            if ($override) {
                $this->socket_options = $opt;
            } else {
                $this->socket_options = $this->socket_options + $opt;
            }
        } else {
            $this->errors['socket_options'] =
                'AConnect: Cannot to set socket options. Parameter must be an array. Given: '.var_export($opt, true);
        }

        return true;
    }

    /**
     * @param array ( "mime" <string>, "length" int, "content" string)
     *
     * @return mixed string|array|boolean
     */
    private function _convertToArray($response_array = [])
    {
        if (!$response_array) {
            return [];
        } else {
            if (strpos($response_array["mime"], 'xml')) {
                $output = new DOMDocument();
                $output->loadXML($response_array["data"]);
                $out = new MyDOMDocument();
                $output = $out->toArray($output);
            } elseif (strpos($response_array["mime"], 'json')) {
                if (!$output = json_decode($response_array["data"], true)) {
                    $this->errors[] = "Error: json parse error. ";

                    return false;
                }

            } else { // if something else - return source
                $output = $response_array["data"];
            }
        }

        return $output;
    }

    /**
     * @param bool|string $headers
     *
     * @return array|false
     */
    private function _http_parse_headers($headers = false)
    {
        if ($headers === false) {
            return false;
        }
        $headers = str_replace("\r", "", $headers);
        $headers = explode("\n", $headers);
        $headers_data = [];
        foreach ($headers as $value) {
            $header = explode(": ", $value);
            if (strpos($value, ':') === false && strpos($value, 'HTTP') !== false) {
                $headers_data['status'] = $header[0];
            } elseif ($header[0] && $header[1]) {
                $headers_data[$header[0]] = $header[1];
            }
        }
        if (!isset($headers_data['Content-Disposition']) && isset($headers_data['Content-disposition'])) {
            $headers_data['Content-Disposition'] = $headers_data['Content-disposition'];
        }
        $this->response_headers = $headers_data;

        return $headers_data;
    }

    /**
     * @return null|string
     */
    public function get_connect_method()
    {
        return $this->connect_method;
    }
}