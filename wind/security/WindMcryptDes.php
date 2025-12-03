<?php
/**
 * @fileName: WindMcryptDes.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-03-03 20:17:32
 * @desc: 
 **/
Wind::import('WIND:security.IWindSecurity');

class WindMcryptDes implements IWindSecurity {

	public function encrypt($input, $key) {
		$key = substr($key, 0, 24);
		$data = openssl_encrypt($input, 'DES-EDE3-ECB', $key, OPENSSL_RAW_DATA);
		return base64_encode($data);
	}

	public function decrypt($input, $key ) {
		$encrypted = base64_decode($input);
		$key = substr($key, 0, 24);
		return openssl_decrypt($encrypted, 'DES-EDE3-ECB', $key, OPENSSL_RAW_DATA);
	}

} 

