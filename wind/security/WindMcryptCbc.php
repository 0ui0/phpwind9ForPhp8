<?php
Wind::import('WIND:security.IWindSecurity');
/**
 * 基于cbc算法实现的加密组件
 *
 * @author Qiong Wu <papa0924@gmail.com> 2011-12-1
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id$
 * @package security
 */
class WindMcryptCbc implements IWindSecurity {

	/* (non-PHPdoc)
	 * @see IWindSecurity::encrypt()
	 */
	public function encrypt($string, $key, $iv = '') {
		if ($string === '') return '';
		if (!extension_loaded('openssl')) {
			throw new WindException('[security.WindMcryptCbc.encrypt] extension \'openssl\' is not loaded.');
		}
		if (!$key || !is_string($key)) {
			throw new WindException('[security.WindMcryptCbc.encrypt] security key is required. ', 
				WindException::ERROR_PARAMETER_TYPE_ERROR);
		}
		
		$size = 16;
		$iv = substr(md5($iv ? $iv : $key), -$size);
        $key = substr(str_pad($key, $size, "\0"), 0, $size);
		return openssl_encrypt($string, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
	}

	/* (non-PHPdoc)
	 * @see IWindSecurity::decrypt()
	 */
	public function decrypt($string, $key, $iv = '') {
		if ($string === '') return '';
		if (!extension_loaded('openssl')) {
			throw new WindException('[security.WindMcryptCbc.decrypt] extension \'openssl\' is not loaded.');
		}
		if (!$key || !is_string($key)) {
			throw new WindException('[security.WindMcryptCbc.decrypt] security key is required.', 
				WindException::ERROR_PARAMETER_TYPE_ERROR);
		}
		
		$size = 16;
		$iv = substr(md5($iv ? $iv : $key), -$size);
        $key = substr(str_pad($key, $size, "\0"), 0, $size);
		$result = openssl_decrypt($string, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($result === false) {
            $error = openssl_error_string();
            file_put_contents(Wind::getRealPath('DATA:log.openssl_error.txt'), date('Y-m-d H:i:s') . " Decrypt failed: $error\nKeyLen: " . strlen($key) . "\nIVLen: " . strlen($iv) . "\n", FILE_APPEND);
        }
        return $result;
	}
}

?>
