<?php
# api发布（此文件仅用于开发模式）
defined('APPPATH') or define('APPPATH', dirname(__FILE__));
# 将多个接口文件合并为一个文件进行发布
include_once('../esitecms.ebers.com/application/libraries/ReleaseFilter.php');
$files = glob(APPPATH.'/dev/*.php');
if( $files ){
	$code = "<?php \n";
	$code = "setlocale(LC_ALL, 'zh_CN.UTF-8');";
	$code .= "define('APPPATH', realpath(dirname(__FILE__)));\n";
	$code .= "define('MS001', '请求执行成功');\n";
	$code .= "define('MS002', '请求参数异常');\n";
	$code .= "define('MS003', '私钥认证失败');\n";
	$code .= "define('CHARSET', 'utf-8');\n";
	$code .= "ini_get('session.auto_start') or session_start();\n";
	$code .= "ini_set('display_errors', 'On');\n";
	$code .= "ini_set('log_errors', 'On');\n";
	$code .= "ini_set('error_log', 'error.log');\n";
	$code .= "error_reporting(E_ALL);\n";
	$code .= "\n";
	$code .= "file_exists('released.php')?include_once('released.php'):null;\n";
	$code .= "file_exists('My_EsiteApp.php')?(My_EsiteApp::app()->run()):(EsiteApp::app()->run());\n";
	$code .= "\n";
	foreach($files as $filename){
		$file_content = file_get_contents($filename);
		$file_content = trim($file_content);
		$basename = pathinfo($filename)['basename'];
		$code .= "\n# == {$basename} code start ==\n";
		$code .= rtrim(ltrim(trim($file_content), '<?php'), '?>');
		$code .= "\n# == {$basename} code end ==\n";
	}
	$code = "<?php\n{$code}";
	# 全局参数写入
	$code = str_replace('@@api_version@@', date('YmdHis'), $code);
	# 写入客户端API
	$code = str_replace('@@site_id@@', 0, $code);
	$code = str_replace('@@auth_key@@', '@@auth_key@@', $code);	# 测试站点无需更改auth_key
	$code = str_replace('@@baseurl@@', 'http://microcms.ebers.com/', $code);
	$code = str_replace('@@db_type@@', 'sqlite', $code);
	$code = str_replace('@@db_name@@', 'esite.db', $code);
	# 发布
	file_put_contents(APPPATH.'/index.php', preg_replace(
		array(
			'@[\'|\"]display_errors[\'|\"]\s*,\s*[\'|\"]On[\'|\"]@is',
		),
		array(
			"'display_errors', 'Off'",
		),
		ReleaseFilter::replace_php($code)
	));
}
