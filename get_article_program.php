<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no">
<meta name="robots" content="noindex,nofollow">
<title>ヘッドレスCMSから取込（PHP）</title>
</head>
<body>
<?php
  ini_set('display_errors', "On");
  $cms_url = "http://cms.example.com"; // 弊社ヘッドレスCMS URL（/を除く）
  $cms_rss_url = $cms_url . "/rss2all.xml"; // 弊社ヘッドレスCMS RSSのURL
  $cms_id = "id_of_basic_auth"; // 弊社ヘッドレスCMS BASIC認証ID
  $cms_pw = "password_of_basic_auth"; // 弊社ヘッドレスCMS BASIC認証パスワード
  $mt_home_url = "http://mt.example.com"; // 貴社Movable TypeのトップページのURL
  define("API_PATH", $mt_home_url . "/mt/mt-data-api.cgi/v4");
  $mt_site_id = 1; // 貴社Movable Typeで使用しているサイトID
  $mt_user = "username"; // 貴社Movable Typeのユーザー
  $mt_password = "password_of_mt"; // 貴社Movable Typeの上記ユーザーの「webサービス」パスワード
  $get_entry_url = API_PATH . "/sites/".$mt_site_id."/entries";
  $set_asset_url = API_PATH . "/assets/upload?site_id=" . $mt_site_id;
  $set_entry_url = API_PATH . "/sites/".$mt_site_id."/entries";

  /**
   * BASIC認証のURLを返す
   * @return string
   */
  function generate_basic_auth_url($url, $id, $pw) {
    if (!empty($id) && !empty($pw)) {
      $return = http_build_url($url,
        array(
          "user" => $id,
          "pass" => $pw
        )
      );
      return $return;
    }
    return $url;
  }

  /**
   * URL constants as defined in the PHP Manual under "Constants usable with
   * http_build_url()".
   *
   * @see http://us2.php.net/manual/en/http.constants.php#http.constants.url
   */
  if (!defined('HTTP_URL_REPLACE')) {
  	define('HTTP_URL_REPLACE', 1);
  }
  if (!defined('HTTP_URL_JOIN_PATH')) {
  	define('HTTP_URL_JOIN_PATH', 2);
  }
  if (!defined('HTTP_URL_JOIN_QUERY')) {
  	define('HTTP_URL_JOIN_QUERY', 4);
  }
  if (!defined('HTTP_URL_STRIP_USER')) {
  	define('HTTP_URL_STRIP_USER', 8);
  }
  if (!defined('HTTP_URL_STRIP_PASS')) {
  	define('HTTP_URL_STRIP_PASS', 16);
  }
  if (!defined('HTTP_URL_STRIP_AUTH')) {
  	define('HTTP_URL_STRIP_AUTH', 32);
  }
  if (!defined('HTTP_URL_STRIP_PORT')) {
  	define('HTTP_URL_STRIP_PORT', 64);
  }
  if (!defined('HTTP_URL_STRIP_PATH')) {
  	define('HTTP_URL_STRIP_PATH', 128);
  }
  if (!defined('HTTP_URL_STRIP_QUERY')) {
  	define('HTTP_URL_STRIP_QUERY', 256);
  }
  if (!defined('HTTP_URL_STRIP_FRAGMENT')) {
  	define('HTTP_URL_STRIP_FRAGMENT', 512);
  }
  if (!defined('HTTP_URL_STRIP_ALL')) {
  	define('HTTP_URL_STRIP_ALL', 1024);
  }

  if (!function_exists('http_build_url')) {

  	/**
  	 * Build a URL.
  	 *
  	 * The parts of the second URL will be merged into the first according to
  	 * the flags argument.
  	 *
  	 * @param mixed $url     (part(s) of) an URL in form of a string or
  	 *                       associative array like parse_url() returns
  	 * @param mixed $parts   same as the first argument
  	 * @param int   $flags   a bitmask of binary or'ed HTTP_URL constants;
  	 *                       HTTP_URL_REPLACE is the default
  	 * @param array $new_url if set, it will be filled with the parts of the
  	 *                       composed url like parse_url() would return
  	 * @return string
  	 */
  	function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = array())
  	{
  		is_array($url) || $url = parse_url($url);
  		is_array($parts) || $parts = parse_url($parts);

  		isset($url['query']) && is_string($url['query']) || $url['query'] = null;
  		isset($parts['query']) && is_string($parts['query']) || $parts['query'] = null;

  		$keys = array('user', 'pass', 'port', 'path', 'query', 'fragment');

  		// HTTP_URL_STRIP_ALL and HTTP_URL_STRIP_AUTH cover several other flags.
  		if ($flags & HTTP_URL_STRIP_ALL) {
  			$flags |= HTTP_URL_STRIP_USER | HTTP_URL_STRIP_PASS
  				| HTTP_URL_STRIP_PORT | HTTP_URL_STRIP_PATH
  				| HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT;
  		} elseif ($flags & HTTP_URL_STRIP_AUTH) {
  			$flags |= HTTP_URL_STRIP_USER | HTTP_URL_STRIP_PASS;
  		}

  		// Schema and host are alwasy replaced
  		foreach (array('scheme', 'host') as $part) {
  			if (isset($parts[$part])) {
  				$url[$part] = $parts[$part];
  			}
  		}

  		if ($flags & HTTP_URL_REPLACE) {
  			foreach ($keys as $key) {
  				if (isset($parts[$key])) {
  					$url[$key] = $parts[$key];
  				}
  			}
  		} else {
  			if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
  				if (isset($url['path']) && substr($parts['path'], 0, 1) !== '/') {
  					$url['path'] = rtrim(
  							str_replace(basename($url['path']), '', $url['path']),
  							'/'
  						) . '/' . ltrim($parts['path'], '/');
  				} else {
  					$url['path'] = $parts['path'];
  				}
  			}

  			if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
  				if (isset($url['query'])) {
  					parse_str($url['query'], $url_query);
  					parse_str($parts['query'], $parts_query);

  					$url['query'] = http_build_query(
  						array_replace_recursive(
  							$url_query,
  							$parts_query
  						)
  					);
  				} else {
  					$url['query'] = $parts['query'];
  				}
  			}
  		}

  		if (isset($url['path']) && substr($url['path'], 0, 1) !== '/') {
  			$url['path'] = '/' . $url['path'];
  		}

  		foreach ($keys as $key) {
  			$strip = 'HTTP_URL_STRIP_' . strtoupper($key);
  			if ($flags & constant($strip)) {
  				unset($url[$key]);
  			}
  		}

  		$parsed_string = '';

  		if (isset($url['scheme'])) {
  			$parsed_string .= $url['scheme'] . '://';
  		}

  		if (isset($url['user'])) {
  			$parsed_string .= $url['user'];

  			if (isset($url['pass'])) {
  				$parsed_string .= ':' . $url['pass'];
  			}

  			$parsed_string .= '@';
  		}

  		if (isset($url['host'])) {
  			$parsed_string .= $url['host'];
  		}

  		if (isset($url['port'])) {
  			$parsed_string .= ':' . $url['port'];
  		}

  		if (!empty($url['path'])) {
  			$parsed_string .= $url['path'];
  		} else {
  			$parsed_string .= '/';
  		}

  		if (isset($url['query'])) {
  			$parsed_string .= '?' . $url['query'];
  		}

  		if (isset($url['fragment'])) {
  			$parsed_string .= '#' . $url['fragment'];
  		}

  		$new_url = $url;

  		return $parsed_string;
  	}
  }

  function authentication( $username, $password ) {

      $api = API_PATH . '/authentication';

      $postdata = array(
          'username' => $username,
          'password' => $password,
          'clientId' => 'hogehoge',
      );

      $options = array('http' =>
          array(
              'method' => 'POST',
              'header' => array('Content-Type: application/x-www-form-urlencoded'),
              'content' => http_build_query($postdata),
          )
      );

      $response = file_get_contents($api, false, stream_context_create($options));
      $response = json_decode($response);
      return $response;
  }

  function get_entry($site_id, $token, $postdata) {
      $api = API_PATH . '/sites/' . $site_id . '/entries';

      $header = array(
          'X-MT-Authorization: MTAuth accessToken=' . $token,
      );

      $options = array('http' =>
          array(
              'method' => 'GET',
              'header' => $header,
              'content' => http_build_query($postdata),
          )
      );

      $response = file_get_contents($api, false, stream_context_create($options));
      return $response;
  }

  function post_entry($site_id, $token, $postdata) {
      $api = API_PATH . '/sites/' . $site_id . '/entries';

      $header = array(
          'X-MT-Authorization: MTAuth accessToken=' . $token,
      );

      $options = array('http' =>
          array(
              'method' => 'POST',
              'header' => $header,
              'content' => http_build_query($postdata),
          )
      );

      $response = file_get_contents($api, false, stream_context_create($options));
      return $response;
  }

  function upload_asset($site_id, $token, $file_url) {
    $api = API_PATH . '/assets/upload?site_id=' . $site_id;

    $file = file_get_contents(generate_basic_auth_url($file_url, $cms_id, $cms_pw));
    $file_path = explode("?",$file_url)[0];
    $file_name = basename($file_path);

    $boundary = '--------------------------'.microtime(true);

    $content =  "--".$boundary."\r\n".
                "Content-Disposition: form-data; name=\"file\"; filename=\"".$file_name."\"\r\n".
                "Content-Type: ".mime_content_type($file_name)."\r\n\r\n".
                $file."\r\n";

    // add some POST fields to the request too: $_POST['foo'] = 'bar'
    /* $content .= "--".$boundary."\r\n".
                "Content-Disposition: form-data; name=\"site_id\"\r\n\r\n".
                "\r\n"; */

    // signal end of request (note the trailing "--")
    $content .= "--".$boundary."--\r\n";

    $header = array(
        'X-MT-Authorization: MTAuth accessToken=' . $token,
        'Content-Type: multipart/form-data; boundary=' . $boundary
    );

    $options = array('http' =>
        array(
            'method' => 'POST',
            'header' => $header,
            'content' => $content,
        )
    );

    $response = file_get_contents($api, false, stream_context_create($options));
    return $response;
  }

  function replace_image($body, $mt_asset_url){
    preg_replace('/img src="(.+)">/', $mt_asset_url, $body);
    return $body;
  }

  // Authentication
  $tokens = authentication($mt_user, $mt_password);
  $accessToken = $tokens->accessToken;

  $xml = simplexml_load_file(generate_basic_auth_url($cms_rss_url, $cms_id, $cms_pw));
  $getData = array(
    json_encode(array(
      'limit' => 50,
      'fields' =>	'id,customFields'
    )),
  );

  $posts = get_entry($mt_site_id, $accessToken, $getData);
  $posts = mb_convert_encoding($posts, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
  $posts_arr = json_decode($posts, true);
  $posts_id_arr = [];
  foreach($posts_arr as $item){
    $posts_id_arr[] = (string) $item[0]["customFields"]["hash"];
  }
  $data = [];

  foreach($xml->channel->item as $item){
    /* $x = array();
    $x['title'] = (string)$item->title;
    $x['link'] = (string)$item->link;
    $x['description_summary'] = (string)$item->description_summary;
    $x['description_all'] = (string)$item->description_all;
    $x['image_files'] = (string)$item->image_files;
    $x['showDate'] = (string)$item->showDate;
    $x['hash'] = (string)$item->hash;
    $x['tag'] = (string)$item->tag;
    $x['location'] = (string)$item->location; */
    if(array_search($item->hash, $posts_id_arr) === false) {
      $asset = upload_asset($mt_site_id, $accessToken, generate_basic_auth_url((string)$item->image_files, $cms_id, $cms_pw));
      $asset_obj = json_decode($asset, true);
      $body = str_replace($item->image_files, $asset_obj["url"], $item->description_all); // 画像をヘッドレスCMSから貴社Movable Typeへ取り込み
      $postData = array(
         'entry' => json_encode(array(
           'title'    => (string)$item->title,
           'date'     => (string)$item->showDate,
           'body'     => (string)$body,
           'excerpt' => (string)$item->description_summary,
           'status'   => 'Draft',
           'assets'   => [array('id' => (string)$asset_obj["id"])],
           'tags'     => [(string)$item->tag],
           'customFields' => [
              array ('basename' => 'hash','value' => (string) $item->hash),
            ],// hashが登録されない場合はカスタムフィールドで「hash」が登録されているかご確認ください。
         )),
      );
      $data[] = $postData;
    }
  }

  foreach($data as $item){
    post_entry($mt_site_id, $accessToken, $item);
  }
  ?>
</body>
</html>
