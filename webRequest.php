<?php
/* ============================================================
 * webRequest.php
 * @param URL Path $url
 * @param REFERER Referer Path $ref
 * @param POST Data $req
 * @return Html page containing data returned from the path
 * ============================================================
 * Copyright 2018, Sergio Casizzone
 * ============================================================
 * @author   Sergio Casizzone (http://sergiocasizzone.it)
 * @version  0.1
 */

class webRequest {
	public function __construct() {
  }

	/**
	 * webrequest()
	 * @param URL Path $url
	 * @param REFERER Referer Path $ref
	 * @param POST Data $req
	 * @return Html page containing data returned from the path
	 */

	public function getUrl($url, $ref, array $req = array(), $verb = 'POST', $fresh = false){
		//$cookieFile = $_SERVER['REMOTE_ADDR'].'-cookie.txt';
		$cookieFile = 'cookie.txt';
		$timeout = 10; // wait 10 seconds

		// generate the POST data string
    $post_data = http_build_query($req, '', '&');
		// any extra headers
		$headers = ["User Agent" => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1;)Trident/4.0; InfoPath.2; .NET CLR 2.0.50727)\r\n"];

		// our curl handle (initialize if required)
		static $ch = null;
		if (is_null($ch)){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; InfoPath.2; .NET CLR 2.0.50727)');
		}

		curl_setopt ( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout);

		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // man-in-the-middle defense by verifying ssl cert.
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // man-in-the-middle defense by verifying ssl cert.
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);  // Enables session support
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);

		curl_setopt($ch, CURLOPT_REFERER, $ref);

		include (Yii::app()->params['libsPath']."/http-proxy.php");

		if ($fresh){
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		}

		if ($verb == 'POST'){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }else{
			curl_setopt($ch, CURLOPT_POST, 0);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// run the query
		$res = curl_exec($ch);

		// check timeout error
		if ($error_number = curl_errno($ch)) {
    	if (in_array($error_number, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED))) {
      	return ( json_encode(array("error"=>true,"error_number"=>524,"info"=>"A timeout occurred!")) );
    	}
		}
		curl_close($ch);
		return $res;
	}

	/**
	 * PHP/cURL function to check a web site status. If HTTP status is not 200 or 302, or
	 * the requests takes longer than 1 seconds, the website is unreachable.
	 *
	 * Follow me on Twitter: @sergiocasizzone
	 *
	 * @param string $url URL that must be checked
	 * @param integer $timeout Seconds to timeout
	 */
	function checkUrl( $url, $timeout = 1 ) {
		$ch = curl_init();

		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout);

		$http_respond = curl_exec($ch);
		$http_respond = trim( strip_tags( $http_respond ) );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		if ( ( $http_code == "200" )
			|| ( $http_code == "201" )
			|| ( $http_code == "302" )
		) {
			return true;
		} else {
			return false;
		}
		curl_close( $ch );
	}
}
?>
