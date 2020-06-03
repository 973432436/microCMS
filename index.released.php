<?php 
setlocale(LC_ALL, 'zh_CN.UTF-8');define('APPPATH', realpath(dirname(__FILE__)));
define('MS001', '请求执行成功');
define('MS002', '请求参数异常');
define('MS003', '私钥认证失败');
define('CHARSET', 'utf-8');
ini_get('session.auto_start') or session_start();
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
ini_set('error_log', APPPATH.'/error.log');
error_reporting(E_ALL);

file_exists('released.php')?include_once('released.php'):null;

# == Admin.php code start ==


#############################################
#############################################
# PHP<5.6函数补全
if (!function_exists('array_column')) {
    function array_column($array, $columnKey, $indexKey = null){
        $result = array();
        foreach ($array as $subArray) {
            if (is_null($indexKey) && array_key_exists($columnKey, $subArray)) {
                $result[] = is_object($subArray)?$subArray->$columnKey: $subArray[$columnKey];
            } elseif (array_key_exists($indexKey, $subArray)) {
                if (is_null($columnKey)) {
                    $index = is_object($subArray)?$subArray->$indexKey: $subArray[$indexKey];
                    $result[$index] = $subArray;
                } elseif (array_key_exists($columnKey, $subArray)) {
                    $index = is_object($subArray)?$subArray->$indexKey: $subArray[$indexKey];
                    $result[$index] = is_object($subArray)?$subArray->$columnKey: $subArray[$columnKey];
                }
            }
        }
        return $result;
    }
}

if( !function_exists('mb_strlen') ){
	function mb_strlen($str=''){
		return strlen($str);
	}
}

#############################################
#############################################

register_shutdown_function(function(){
	setlocale(LC_ALL, "zh_CN.UTF-8");
	error_reporting(E_ALL ^ E_NOTICE);
	date_default_timezone_set("Asia/Shanghai");
	ini_set('date.timezone','Asia/Shanghai');
	ini_set('display_errors',0);
	ini_set('error_log',($error_log=__DIR__.DIRECTORY_SEPARATOR.'error.log'));
	ini_set('log_errors',1);
	ini_set('ignore_repeated_errors',1);
	if( file_exists($error_log) && filesize($error_log)>5*1024*1024 ){unlink($error_log);}
	$user_defined_err = error_get_last();
	if($user_defined_err['type'] > 0){
		$msg = sprintf('%s %s %s',$user_defined_err['message'],$user_defined_err['file'],$user_defined_err['line']);
		error_log($msg,0);
	}
});



/**
 * 后台接口(授权才能访问)
 * 调用示例：/?act=Admin/remote_db
 * 后台所有方法执行更新操作后，必须清理数据库缓存:
 * 	$this->db->delCache(); 
 */
class Admin{
	public $request;
	# 当前请求的方法
	protected $action = '';
	# 定义不登录直接访问的路径
	public $allow_actions = array(
		'login',
		'check_login',
		# 数据集允许模板公开调用
		'form_data_insert',
	);
	protected $db;
	
	private static $_instance;
	
	function __construct(){}
	
	private function __clone(){}
	
	#返回唯一实例
	public static function run(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
			self::$_instance->_init();
		}
		return self::$_instance;
	}
	
	protected function _init(){
		$this->db = &Esite::$db;
		$this->request = &Esite::app()->request;
		$this->allow_actions = array_map('strtolower', $this->allow_actions);
		$this->action = (preg_match('@admin/([^\/]+)@i', Esite::app()->request['act'], $match) && isset($match[1]))?strtolower($match[1]):strtolower($this->action);
		if( !in_array($this->action, $this->allow_actions) && !$this->check_login() ){
			EsiteApp::app()->admin();die;
		}
	}
	
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	
	public function check_login($die=false){
		$flag = isset($_SESSION['role']) && $_SESSION['role']=='admin' && isset($_SESSION['user_id']) && $_SESSION['user_id']>0;
		if( $die && !$flag ){
			$_SESSION = array();
			session_destroy();
			Msg::$show_serverInfo = false;
			Msg::json_encode('no', MS003, $this->request);
		}
		return $flag;
	}
	
	public function login(){
		$this->request['username'] = isset($this->request['username'])?$this->db->escapeString(trim($this->request['username'])):'';
		$this->request['password'] = isset($this->request['password'])?(trim($this->request['password'])):'';
		$this->request['captcha'] = isset($this->request['captcha'])?$this->db->escapeString($this->request['captcha']):'';
		$this->request['send_checkcode'] = isset($this->request['send_checkcode'])?(trim($this->request['send_checkcode'])):'';
		# 用户信息
		$user_info = $this->db->getRow("select * from admin where user_name='{$this->request['username']}'");
		if( !isset($user_info['user_id']) ){
			Msg::json_encode('no', 'user not exists.', $this->request);
		}
		# 用户是否启用邮箱验证
		$is_email_check = (isset($user_info['email']) && Helper::isEmail($user_info['email']) && strlen(Esite::app()->cfg['site_info']['mail_host']) && strlen(Esite::app()->cfg['site_info']['mail_port']) && strlen(Esite::app()->cfg['site_info']['mail_username']) && strlen(Esite::app()->cfg['site_info']['mail_password']));
		# 无需验证
		if( isset($this->request['send_checkcode']) && $this->request['send_checkcode'] ){
			if( !$is_email_check ){
				Msg::json_encode('no', 'Please login directly.', $this->request);
			}else{
				$_SESSION["email_checkcode_email"] = $user_info['email'];
				$_SESSION["email_checkcode"] = substr(str_shuffle('ABCDEFGHKLMNPQRSTUVWXYZ123456789'), 0, 6);
				if( Esite::app()->sendmail($user_info['email'], sprintf("MicroCMS CheckCode [%s]", Esite::app()->cfg['site_info']['site_name']), $_SESSION["email_checkcode"]) ){
					Msg::json_encode('yes', 'mail send success.');
				}else{
					Msg::json_encode('no', 'mail send fail, please try again later.');
				}
			}
		}
		# 验证校验码
		if( $is_email_check &&( !isset($this->request['captcha'])
			|| !isset($_SESSION["email_checkcode_email"])
			|| $_SESSION["email_checkcode_email"]!=$user_info['email']
			|| !isset($_SESSION["email_checkcode"])
			|| $_SESSION["email_checkcode"]!=$this->request['captcha'] )
		){
			Msg::json_encode('no', 'captcha error.');
		}
		
		#验证用户密码
		if( ($user_info['password']==$this->request['password']) || ($user_info['password']==md5($this->request['password'])) ){
			# 销毁验证码
			unset($_SESSION["email_checkcode_email"]);
			unset($_SESSION["email_checkcode"]);
			$_SESSION = array_merge($_SESSION, $user_info);
			$_SESSION['role'] = 'admin';
			# 更新登录时间
			$this->db->update('admin', array('last_login'=>time(), 'last_ip'=>Helper::real_ip()), " user_id='{$user_info['user_id']}' ");
			# 记录登录日志
			$this->write_log('login success.');
			Msg::json_encode('yes', 'login success.', null);
		}else{
			Msg::json_encode('no', 'username or password error.');
		}
	}
	
	function logout(){
		$this->write_log('logout.');
		$_SESSION = array();
		session_destroy();
		EsiteApp::app()->admin();die;
	}
	
	function write_log($action=''){
		$this->db->insert('admin_log', array(
			'create_time'=>time(),
			'user_name'=>$_SESSION['user_name'],
			'action'=>$action,
			'ip'=>$_SERVER['REMOTE_ADDR']
		));
	}
	
	# 清理缓存
	function clear_cache(){
		Esite::app()->clear_cache();
	}
	
	public function delete_row(){
		$this->request['table'] = isset($this->request['table'])?$this->db->escapeString($this->request['table']):'';
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):0;
		$this->request['table'] or Msg::json_encode('no', "Cannot delete, [table] not exists.");
		$this->request['id']>1 or Msg::json_encode('no', "Cannot delete, id={$this->request['id']}.");
		$primary_keys = array('product_category'=>'cat_id', 'admin'=>'user_id');
		$primary_key = isset($primary_keys[$this->request['table']])?$primary_keys[$this->request['table']]:'id';
		$this->write_log("delete {$this->request['table']} {$primary_key}={$this->request['id']}");
		# 定义id=1为初始数据不可通过程序删除，仅可修改
		$this->db->query("delete from {$this->request['table']} where {$primary_key}>1 and {$primary_key}='{$this->request['id']}'");
		$this->db->delCache();
		Msg::json_encode('yes');
	}
	
	function admin_log($pagesize=10,$limit_start=0){
		$pagesize = intval($pagesize);
		$limit_start = intval($limit_start);
		$page = isset($this->request['page'])&&$this->request['page']>0?ceil($this->request['page']):1;
		$pagesize = isset($this->request['pagesize'])&&$this->request['pagesize']>0?ceil($this->request['pagesize']):$pagesize;
		$limit_start = ($page-1)*$pagesize;
		$sql = "select * from admin_log order by id desc limit {$limit_start},{$pagesize}";
		$res = $this->db->getAll($sql);
		$res = $res?array_map(function($row){
			$row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
			return $row;
		}, $res):$res;
		Esite::app()->cfg['url_route']['page'] = $page;
		Esite::app()->cfg['url_route']['pagesize'] = $pagesize;
		Esite::app()->cfg['url_route']['total_rows'] = $this->db->getRow("select count(1) as total from admin_log")['total'];
		return $res;
	}
	
	#服务器状态输出接口
	public function server_status(){
		$status = array();
		$status['host_info'] = (@get_current_user()). '-'.$_SERVER['SERVER_NAME'] .'(' .('/'==DIRECTORY_SEPARATOR?$_SERVER['SERVER_ADDR']:@gethostbyname($_SERVER['SERVER_NAME'])). ')';
		$status['server'] = $_SERVER;
		$status['loaded_extensions'] = get_loaded_extensions();
		$status['php_version'] = PHP_VERSION;
		$status['run_mode'] = strtoupper(php_sapi_name());
		$status['memory_limit'] = get_cfg_var('memory_limit');
		$status['safe_mode'] = get_cfg_var('safe_mode');
		$status['post_max_size'] = get_cfg_var('post_max_size');
		$status['upload_max_filesize'] = get_cfg_var('upload_max_filesize');
		$status['precision'] = get_cfg_var('precision');
		$status['max_execution_time'] = get_cfg_var('max_execution_time');
		$status['default_socket_timeout'] = get_cfg_var('default_socket_timeout');
		$status['enable_dl'] = get_cfg_var('enable_dl');
		$status['display_errors'] = get_cfg_var('display_errors');
		$status['register_globals'] = get_cfg_var('register_globals');
		$status['magic_quotes_gpc'] = get_cfg_var('magic_quotes_gpc');
		$status['short_open_tag'] = get_cfg_var('short_open_tag');
		$status['asp_tags'] = get_cfg_var('asp_tags');
		$status['ignore_repeated_errors'] = get_cfg_var('ignore_repeated_errors');
		$status['ignore_repeated_source'] = get_cfg_var('ignore_repeated_source');
		$status['report_memleaks'] = get_cfg_var('report_memleaks');
		$status['magic_quotes_gpc'] = get_cfg_var('magic_quotes_gpc');
		$status['magic_quotes_runtime'] = get_cfg_var('magic_quotes_runtime');
		$status['allow_url_fopen'] = get_cfg_var('allow_url_fopen');
		$status['register_argc_argv'] = get_cfg_var('register_argc_argv');
		$status['isset_cookie'] = isset($_COOKIE);
		$status['bcadd'] = function_exists('bcadd');
		$status['preg_match'] = function_exists('preg_match');
		$status['pdf_close'] = function_exists('pdf_close');
		$status['snmpget'] = function_exists('snmpget');
		$status['curl_init'] = function_exists('curl_init');
		$status['enable_functions'] = get_defined_functions();
		$status['disable_functions'] = get_cfg_var("disable_functions");
		$status['date.timezone'] = get_cfg_var("date.timezone");
		$status['ftp_login'] = function_exists('ftp_login');
		$status['session_start'] = function_exists('session_start');
		$status['socket_accept'] = function_exists('socket_accept');
		$status['cal_days_in_month'] = function_exists('cal_days_in_month');
		$status['gd_info'] = function_exists('gd_info')?gd_info():'';
		$status['gzclose'] = function_exists('gzclose');
		$status['JDToGregorian'] = function_exists('JDToGregorian');
		$status['wddx_add_vars'] = function_exists('wddx_add_vars');
		$status['iconv'] = function_exists('iconv');
		$status['mcrypt_cbc'] = function_exists('mcrypt_cbc');
		$status['mb_eregi'] = function_exists('mb_eregi');
		$status['mhash_count'] = function_exists('mhash_count');
		$status['zend_version'] = zend_version();
		$status['apc'] = phpversion('APC');
		$status['xcache'] = phpversion('XCache');
		$status['eaccelerator'] = phpversion('eAccelerator');
		$status['sqlite'] = extension_loaded('sqlite3')?SQLite3::version():'';
		$status['file_mode_info'] = Helper::file_mode_info(dirname(__FILE__));
		$status['api_version'] = Esite::app()->api_version;
		$status['api_server_baseurl'] = Esite::app()->server_baseurl;
		$status['os'] = explode(" ", php_uname());
		$status['document_root'] = $_SERVER['DOCUMENT_ROOT']?str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']):str_replace('\\','/',dirname(__FILE__));
		return $status;
	}
	
	# 网站数据表记录数目
	public function table_summary(){
		$res = array();
		foreach(array('admin', 'article', 'member_list', 'member_orders', 'page', 'product') as $table){
			$res[$table] = (int)current($this->db->getRow("select count(1) as total from {$table}"));
		}
		return $res;
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function config($group=false){
		$site_config = $this->db->getAll("select * from config order by tab asc,sort asc");
		$site_config = $site_config?array_combine(array_column($site_config, 'name'), $site_config):$site_config;
		if( $group && $site_config ){
			$tmp = array();
			foreach($site_config as $k=>$v){
				$tmp[$v['tab']][$v['name']] = $v;
			}
			$site_config = $tmp;
		}
		return $site_config;
	}
	public function config_update(){
		$this->request['config'] = isset($this->request['config'])?array_map('trim', $this->request['config']):array();
		if( !is_array($this->request['config']) || empty($this->request['config']) ){
			Msg::json_encode('no');
		}
		$this->write_log('update site config');
		foreach($this->request['config'] as $name=>$val){
			$this->db->update('config', array('value'=>$this->db->escapeString($val)), " name='{$name}' ");
		}
		$this->db->delCache();
		Msg::json_encode('yes');
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public $nav_category = array('main', 'top', 'bottom', 'left', 'right');
	public function nav_list($genTree=false){
		$this->request['type'] = isset($this->request['type'])?$this->db->escapeString(trim($this->request['type'])):'main';
		$res = $this->db->getAll("select a.*,b.nav_name as parent_name from nav a left join nav b on b.id=a.parent_id where a.category='{$this->request['type']}' order by a.category,a.parent_id,a.sort");
		$res = $genTree&&$res?Helper::genTree($res, 'id', 'parent_id'):$res;
		return $res;
	}
	
	public function nav_data(){
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):0;
		return $this->db->getRow("select * from nav where id='{$this->request['id']}'");
	}
	
	public function nav_save(){
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):0;
		$this->request['nav'] = isset($this->request['nav'])?array_map(array($this->db,'escapeString'), $this->request['nav']):array();
		if( !is_array($this->request['nav']) || empty($this->request['nav']) || !(isset($this->request['nav']['nav_name']) &&strlen($this->request['nav']['nav_name'])>0) ){
			Msg::json_encode('no');
		}
		if( $this->request['id']>0 ){
			$this->write_log("update nav id={$this->request['id']}");
			$this->db->update('nav', $this->request['nav'], " id='{$this->request['id']}' ");
		}else{
			$this->write_log("update add nav id={$this->request['nav']['nav_name']}");
			$this->db->insert('nav', $this->request['nav'], true);
		}
		$this->db->delCache();
		Msg::json_encode('yes', 'success');
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function admin_list(){
		$res = $this->db->getAll("select * from admin order by user_name asc");
		return $res;
	}
	
	public function admin_data($id=0){
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):intval($id);
		return $this->db->getRow("select * from admin where user_id='{$this->request['id']}'");
	}
	
	public function admin_save(){
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):0;
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['user_name']) &&strlen($this->request['form']['user_name'])>0) ){
			Msg::json_encode('no');
		}
		if( $this->request['id']>0 ){
			$admin_user = $this->admin_data();
			(isset($admin_user['user_id']) && $admin_user['user_id']>0) or Msg::json_encode('no', 'user not exists.');
			# 修改密码
			if( isset($this->request['old_password']) && strlen($this->request['old_password'])>0 ){
				# 校验旧密码是否正确
				($admin_user['password']==md5($this->request['old_password'])) or Msg::json_encode('no', 'old password is error.');
				# 校验新密码是否两次相同
				(isset($this->request['password']) && isset($this->request['password_confirm']) && strlen($this->request['password'])>=6 && md5($this->request['password'])==md5($this->request['password_confirm'])) or Msg::json_encode('no', 'new password error.');
				# 存储密码
				$this->request['form']['password'] = md5($this->request['password']);
			}else{
				Msg::json_encode('no', 'please input old-password.');
			}
			$this->write_log("update admin id={$this->request['id']}");
			$this->db->update('admin', $this->request['form'], " user_id='{$this->request['id']}' ");
		}else{
			# 校验新密码是否两次相同
			(isset($this->request['password']) && isset($this->request['password_confirm']) && strlen($this->request['password'])>=6 && md5($this->request['password'])==md5($this->request['password_confirm'])) or Msg::json_encode('no', 'new password error.');
			# 存储密码
			$this->request['form']['password'] = md5($this->request['password']);
			$this->request['form']['add_time'] = time();
			$this->write_log("add admin id={$this->request['form']['user_name']}");
			$this->db->insert('admin', $this->request['form'], true);
		}
		$this->db->delCache();
		Msg::json_encode('yes', 'success');
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function slide_list(){
		$res = $this->db->getAll("select * from slide order by category asc,sort asc,id asc");
		return $res;
	}
	
	public function slide_data($id=0){
		$id = isset($this->request['id'])?intval($this->request['id']):intval($id);
		return $this->db->getRow("select * from slide where id='{$id}'");
	}
	
	public function slide_save(){
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		$this->request['form']['id'] = isset($this->request['form']['id'])?intval($this->request['form']['id']):0;
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['name']) &&strlen($this->request['form']['name'])>0) ){
			Msg::json_encode('no');
		}
		$this->request['form']['sort'] = isset($this->request['form']['sort'])?$this->request['form']['sort']:0;
		if( $this->request['form']['id']>0 ){
			$slide = $this->slide_data($this->request['form']['id']);
			(isset($slide['id']) && $slide['id']>0) or Msg::json_encode('no', 'data not exists.');
			$this->write_log("update slide id={$this->request['form']['id']}");
			$this->db->update('slide', $this->request['form'], " id='{$this->request['form']['id']}' ");
		}else{
			$this->write_log("add slide id={$this->request['form']['name']}");
			$this->db->insert('slide', $this->request['form'], true);
		}
		$this->db->delCache();
		Msg::json_encode('yes', 'success', $this->request);
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function file_upload(){
		$_FILES or Msg::json_encode('no', 'no data.');
		$files = $upfiles = array();
		$fileKey = key($_FILES);
		# html-file-multiple 多文件转换为 file 单文件数据格式
		if( isset($_FILES[$fileKey]['name']) && is_array($_FILES[$fileKey]['name']) && $_FILES[$fileKey]['name'] ){
			foreach($_FILES[$fileKey]['name'] as $k=>$v){
				foreach(array('name','type','tmp_name','error','size') as $k1=>$v1){
					$files["{$fileKey}{$k}"][$v1] = $_FILES[$fileKey][$v1][$k];
				}
			}
		}else{
			$files = $_FILES;
		}
		foreach($files as $k=>$file){
			/** 
				[name] => test.jpg
	            [type] => image/jpeg
	            [tmp_name] => C:\Users\Administrator\AppData\Local\Temp\php2E61.tmp
	            [error] => 0
	            [size] => 34834
			*/
			$file_md5 = md5_file($file['tmp_name']);
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$repeat_index = 0;
			# 遇到不同内容的重名文件则自动增加序号, 遇到相同内容重名文件则覆盖
			do{
				$file['name'] = str_replace(' ', '-', $file['name']);
				$filename = sprintf(
					APPPATH.'/static/upload/%s/%s.%s',
					substr($file_md5,0,2),
					basename($file['name'], ".{$ext}").($repeat_index?"-{$repeat_index}":""),
					$ext
				);
				$repeat_index++;
			}while(file_exists($filename) && md5_file($filename)!=$file_md5);
			Helper::make_dir(dirname($filename));
			# 检查文件后缀是否符合要求
			if( !in_array($ext, array_filter((array)explode('|', $ext_all=Esite::app()->cfg['site_info']['upfile_extension']))) ){
				@unlink($file['tmp_name']);
				Msg::json_encode('no', "[{$ext}] not in [{$ext_all}]");
			}
			if( move_uploaded_file($file['tmp_name'], $filename) && file_exists($filename) ){
				$upfiles[$k] = str_replace(APPPATH,'',$filename);
			}
		}
		Msg::json_encode('yes', "file upload success.", array('upfiles'=>$upfiles));
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function page_list(){
		$res = $this->db->getAll("select p.*,p.filename as unique_id,p.category as cat_id,u.uri from page as p left join uri_list as u on (u.source_table='page' and u.source_id=p.id) order by p.parent_id asc,p.sort asc");
		return $res;
	}
	
	public function page_data($id=0){
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):intval($id);
		return $this->db->getRow("select p.*,p.filename as unique_id,p.category as cat_id,u.uri from page as p left join uri_list as u on (u.source_table='page' and u.source_id=p.id) where p.id='{$this->request['id']}'");
	}
	
	public function page_categorys(){
		return $this->db->getAll("select distinct(category) as category from page where 1 order by category asc ");
	}
	
	public function page_parents(){
		return $this->db->getAll("select id, page_name from page where 1 order by parent_id asc,category asc ");
	}
	
	public function page_save(){
		$this->request['form']['id'] = $id = isset($this->request['form']['id'])?intval($this->request['form']['id']):0;
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['page_name']) &&strlen($this->request['form']['page_name'])>0) ){
			Msg::json_encode('no');
		}
		$this->request['form']['sort'] = isset($this->request['form']['sort'])?intval($this->request['form']['sort']):0;
		$this->request['form']['uri'] = isset($this->request['form']['uri'])?($this->request['form']['uri']):'';
		$udata = $this->request['form'];
		unset($udata['uri']);
		if( $this->request['form']['id']>0 ){
			$data = $this->page_data($this->request['form']['id']);
			(isset($data['id']) && $data['id']>0) or Msg::json_encode('no', 'data not exists.');
			$this->write_log("update page id={$this->request['form']['id']}");
			$this->db->update('page', $udata, " id='{$this->request['form']['id']}' ");
		}else{
			unset($udata['id']);
			$this->write_log("add page id={$this->request['form']['page_name']}");
			$this->db->insert('page', $udata, true);
			$id = $this->db->getRow("select max(id) as id from page")['id'];
		}
		$this->db->delCache();
		if( $id>0 ){
			Esite::app()->uri_update('page', $id, $this->request['form']['uri']);
			Msg::json_encode('yes', 'success', $this->db->info);
		}else{
			Msg::json_encode('no', 'error', $this->db->info);
		}
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function html_editor($name='textarea_name', $default_val='', $cfg=array()){
		$cfg['width'] = isset($cfg['width'])&&$cfg['width']?$cfg['width']:'100%';
		$cfg['height'] = isset($cfg['height'])&&$cfg['height']?$cfg['height']:'360px';
		$id = preg_replace('@[^0-9a-z]@i','_', $name);
		return <<<EOT
<script type="text/javascript" charset="utf-8" src="tinymce/tinymce.min.js"></script>
<script type="text/javascript">jQuery(document).ready(function(){
tinymce.init({
	selector:'#{$id}', language:'zh_CN', width:'{$cfg['width']}', height:'{$cfg['height']}', menubar:false,
	convert_urls :false,
	automatic_uploads:false,
	images_upload_url:'/?act=admin/html_editor_upload',
	plugins: ['autolink autosave lists link charmap textcolor lineheight', 'code hr bdmap pagebreak textpattern image imagetools', 'media table help wordcount fullscreen'],
	toolbar: 'fontsizeselect bold italic strikethrough forecolor backcolor alignleft aligncenter alignright alignjustify outdent indent numlist bullist lineheight | link unlink media charmap table hr removeformat undo redo restoredraft bdmap image code help fullscreen',
	init_instance_callback: function(editor){editor.on('Change', function(e){tinyMCE.triggerSave();});}
});
});
function insertMce(html) {
    tinyMCE.execCommand('mceInsertContent', false, html);
}
</script>
<textarea id="{$id}" name="{$name}" rows="10">{$default_val}</textarea>
EOT;
	}
	
	public function html_editor_upload(){
		$imageFolder = APPPATH.'/static/upload/';
		reset ($_FILES);
		$file = current($_FILES);
		if( $_FILES && !is_uploaded_file($file['tmp_name'])){
			header("HTTP/1.1 500 Server Error");
			exit;
		}
		$file_md5 = md5_file($file['tmp_name']);
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$repeat_index = 0;
		# 遇到不同内容的重名文件则自动增加序号, 遇到相同内容重名文件则覆盖
		do{
			$file['name'] = str_replace(' ', '-', $file['name']);
			$filename = sprintf(
				APPPATH.'/static/upload/%s/%s.%s',
				substr($file_md5,0,2),
				basename($file['name'], ".{$ext}").($repeat_index?"-{$repeat_index}":""),
				$ext
			);
			$repeat_index++;
		}while(file_exists($filename) && md5_file($filename)!=$file_md5);
		Helper::make_dir(dirname($filename));
		# 检查文件后缀是否符合要求
		if( !in_array($ext, array_filter((array)explode('|', $ext_all=Esite::app()->cfg['site_info']['upfile_extension']))) ){
			@unlink($file['tmp_name']);
			Msg::json_encode('no', "[{$ext}] not in [{$ext_all}]");
		}
		if( move_uploaded_file($file['tmp_name'], $filename) && file_exists($filename) ){
			die(json_encode(array('location'=>str_replace(APPPATH,'',$filename))));
		}
		Msg::json_encode('no', "upfile: fail.");
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function product_category($cat_id=0){
		$cat_id = isset($this->request['cat_id'])&&is_numeric($this->request['cat_id'])?$this->request['cat_id']:intval($cat_id);
		return $this->db->getRow("select * from product_category where cat_id='{$cat_id}'");
	}
	public function product_categorys(){
		return Esite::app()->get_product_categorys();
	}
	public function product_private_categorys(){
		return $this->db->getAll("select distinct(category) as category from product_category order by category asc");
	}
	public function product_category_save(){
		$this->request['form']['cat_id'] = isset($this->request['form']['cat_id'])?intval($this->request['form']['cat_id']):0;
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['cat_name']) &&strlen($this->request['form']['cat_name'])>0) ){
			Msg::json_encode('no', 'data error.');
		}
		$this->request['form']['sort'] = isset($this->request['form']['sort'])?intval($this->request['form']['sort']):0;
		if( $this->request['form']['cat_id']>0 ){
			$data = $this->product_category();
			(isset($data['cat_id']) && $data['cat_id']>0) or Msg::json_encode('no', 'data not exists.');
			$this->write_log("update product_category cat_id={$this->request['form']['cat_id']}");
			$this->db->update('product_category', $this->request['form'], " cat_id='{$this->request['form']['cat_id']}' ");
		}else{
			unset($this->request['form']['cat_id']);
			$this->write_log("add product_category cat_id={$this->request['form']['cat_name']}");
			$this->db->insert('product_category', $this->request['form'], true);
		}
		$this->db->delCache();
		Msg::json_encode('yes', 'success');
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function product($id=0){
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):intval($id);
		return $this->db->getRow("select p.*,c.unique_id as cat_unique_id,u.uri from product as p left join product_category c on p.cat_id=c.cat_id left join uri_list as u on (u.source_table='product' and u.source_id=p.id) where p.id='{$this->request['id']}'");
	}
	public function product_list(){
		$this->request = array_map(array($this->db,'escapeString'), $this->request);
		$where = " 1=1 ";
		$where .= isset($this->request['cat_id'])&&is_numeric($this->request['cat_id'])?" and p.cat_id='{$this->request['cat_id']}' ":"";
		$where .= isset($this->request['keyword'])&&strlen($this->request['keyword'])?" and p.name like '{$this->request['keyword']}' ":"";
		$page = isset($this->request['page'])&&$this->request['page']>0?ceil($this->request['page']):1;
		$pagesize = isset($this->request['pagesize'])&&$this->request['pagesize']>0?ceil($this->request['pagesize']):10;
		$limit_start = ($page-1)*$pagesize;
		$sql = "select p.*,c.unique_id as cat_unique_id,u.uri from product as p left join product_category c on p.cat_id=c.cat_id left join uri_list as u on (u.source_table='product' and u.source_id=p.id) where {$where} order by p.id desc limit {$limit_start},{$pagesize}";
		$res = $this->db->getAll($sql);
		if( $res ){
			foreach($res as $k=>$v){
				$res[$k]['category_info'] = $this->product_category($v['cat_id']);
			}
		}
		Esite::app()->cfg['url_route']['page'] = $page;
		Esite::app()->cfg['url_route']['pagesize'] = $pagesize;
		Esite::app()->cfg['url_route']['total_rows'] = $this->db->getRow("select count(1) as total from product where {$where}")['total'];
		return $res;
	}
	public function product_save(){
		$this->request['form']['id'] = $id = isset($this->request['form']['id'])?intval($this->request['form']['id']):0;
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['name']) &&strlen($this->request['form']['name'])>0) ){
			Msg::json_encode('no', 'data error.');
		}
		$this->request['form']['uri'] = isset($this->request['form']['uri'])?($this->request['form']['uri']):'';
		$this->request['form']['sort'] = isset($this->request['form']['sort'])?intval($this->request['form']['sort']):0;
		$this->request['form']['price'] = isset($this->request['form']['price'])?round($this->request['form']['price'],4):0;
		$this->request['form']['weight'] = isset($this->request['form']['weight'])?round($this->request['form']['weight'],4):0;
		$this->request['form']['edit_time'] = time();
		$udata = $this->request['form'];
		unset($udata['uri']);
		if( $this->request['form']['id']>0 ){
			$data = $this->product($this->request['form']['id']);
			(isset($data['id']) && $data['id']>0) or Msg::json_encode('no', 'data not exists.');
			$this->write_log("update product id={$this->request['form']['id']}");
			$this->db->update('product', $udata, " id='{$this->request['form']['id']}' ");
		}else{
			unset($udata['id']);
			$this->write_log("add product id={$this->request['form']['name']}");
			$this->db->insert('product', $udata, true);
			$id = $this->db->getRow("select max(id) as id from product")['id'];
		}
		$this->db->delCache();
		if( $id>0 ){
			Esite::app()->uri_update('product', $id, $this->request['form']['uri']);
			Msg::json_encode('yes', 'success');
		}else{
			Msg::json_encode('no', 'error');
		}
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function article_category($cat_id=0){
		$cat_id = isset($this->request['cat_id'])&&is_numeric($this->request['cat_id'])?$this->request['cat_id']:intval($cat_id);
		return $this->db->getRow("select * from article_category where cat_id='{$cat_id}'");
	}
	public function article_categorys(){
		return Esite::app()->get_article_categorys();
	}
	public function article_private_categorys(){
		return $this->db->getAll("select distinct(category) as category from article_category order by category asc");
	}
	public function article_category_save(){
		$this->request['form']['cat_id'] = isset($this->request['form']['cat_id'])?intval($this->request['form']['cat_id']):0;
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['cat_name']) &&strlen($this->request['form']['cat_name'])>0) ){
			Msg::json_encode('no', 'data error.');
		}
		$this->request['form']['sort'] = isset($this->request['form']['sort'])?intval($this->request['form']['sort']):0;
		$this->request['form']['parent_id'] = isset($this->request['form']['parent_id'])?intval($this->request['form']['parent_id']):0;
		if( $this->request['form']['cat_id']>0 ){
			$data = $this->article_category();
			(isset($data['cat_id']) && $data['cat_id']>0) or Msg::json_encode('no', 'data not exists.');
			$this->write_log("update article_category cat_id={$this->request['form']['cat_id']}");
			$this->db->update('article_category', $this->request['form'], " cat_id='{$this->request['form']['cat_id']}' ");
		}else{
			unset($this->request['form']['cat_id']);
			$this->write_log("add article_category cat_id={$this->request['form']['cat_name']}");
			$this->db->insert('article_category', $this->request['form'], true);
		}
		$this->db->delCache();
		Msg::json_encode('yes', 'success');
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function article($id=0){
		$this->request['id'] = isset($this->request['id'])?intval($this->request['id']):intval($id);
		return $this->db->getRow("select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category,u.uri from article as a left join article_category as c on a.cat_id=c.cat_id left join uri_list as u on (u.source_table='article' and u.source_id=a.id) where a.id='{$this->request['id']}'");
	}
	public function article_list(){
		$this->request = array_map(array($this->db,'escapeString'), $this->request);
		$where = " 1=1 ";
		$where .= isset($this->request['cat_id'])&&is_numeric($this->request['cat_id'])?" and a.cat_id='{$this->request['cat_id']}' ":"";
		$where .= isset($this->request['keyword'])&&strlen($this->request['keyword'])?" and a.title like '{$this->request['keyword']}' ":"";
		$page = isset($this->request['page'])&&$this->request['page']>0?ceil($this->request['page']):1;
		$pagesize = isset($this->request['pagesize'])&&$this->request['pagesize']>0?ceil($this->request['pagesize']):10;
		$limit_start = ($page-1)*$pagesize;
		$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category,u.uri from article as a left join article_category as c on a.cat_id=c.cat_id left join uri_list as u on (u.source_table='article' and u.source_id=a.id) where {$where} order by a.id desc limit {$limit_start},{$pagesize}";
		$res = $this->db->getAll($sql);
		if( $res ){
			foreach($res as $k=>$v){
				$res[$k]['category_info'] = $this->article_category($v['cat_id']);
			}
		}
		Esite::app()->cfg['url_route']['page'] = $page;
		Esite::app()->cfg['url_route']['pagesize'] = $pagesize;
		Esite::app()->cfg['url_route']['total_rows'] = $this->db->getRow("select count(1) as total from article where {$where}")['total'];
		return $res;
	}
	public function article_save(){
		$this->request['form']['id'] = $id = isset($this->request['form']['id'])?intval($this->request['form']['id']):0;
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['title']) &&strlen($this->request['form']['title'])>0) ){
			Msg::json_encode('no', 'data error.');
		}
		$this->request['form']['uri'] = isset($this->request['form']['uri'])?($this->request['form']['uri']):'';
		$this->request['form']['sort'] = isset($this->request['form']['sort'])?intval($this->request['form']['sort']):0;
		$this->request['form']['edit_time'] = time();
		$udata = $this->request['form'];
		unset($udata['uri']);
		if( $this->request['form']['id']>0 ){
			$data = $this->article($this->request['form']['id']);
			(isset($data['id']) && $data['id']>0) or Msg::json_encode('no', 'data not exists.');
			$this->write_log("update article id={$this->request['form']['id']}");
			$this->db->update('article', $udata, " id='{$this->request['form']['id']}' ");
		}else{
			unset($udata['id']);
			$this->write_log("add article id={$this->request['form']['title']}");
			$this->db->insert('article', $udata, true);
			$id = $this->db->getRow("select max(id) as id from article")['id'];
		}
		$this->db->delCache();
		if( $id>0 ){
			Esite::app()->uri_update('article', $id, $this->request['form']['uri']);
			Msg::json_encode('yes', 'success');
		}else{
			Msg::json_encode('no', 'error');
		}
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function table_list(){
		$tables = array();
		foreach( $this->db->getTables() as $table ){
			$sql = "select count(1) as total from {$table}";
			$tables[$table] = array('name'=>$table, 'rows'=>$this->db->getRow($sql)['total']);
		}
		ksort($tables);
		return $tables;
	}
	public function table_backup(){
		$this->request['tables'] = isset($this->request['tables'])?array_map(array($this->db,'escapeString'), $this->request['tables']):array();
		$tables = $this->db->getTables();
		$this->request['tables'] = array_intersect($tables, $this->request['tables']);
		$this->request['tables'] or Msg::json_encode('no', 'data error.');
		$filename = APPPATH.'/static/db-backup/'.date('YmdHis').'.sql';
		$filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);
		Helper::make_dir(dirname($filename));
		foreach($this->request['tables'] as $table){
			$page = 1;
			$pagesize = 100;
			do{
				$limit_start = ($page-1)*$pagesize;
				$res = $this->db->getAll("select * from {$table} limit {$limit_start},{$pagesize}");
				if( $res ){
					foreach($res as $k=>$v){
						file_put_contents($filename, $this->db->replace_string($table, array_map(array($this->db,'escapeString'), $v)), FILE_APPEND);
					}
				}
				$page++;
			}while( $res );
		}
		Msg::json_encode('yes', 'success');
	}
	public function table_backup_files(){
		$files = glob(APPPATH.'/static/db-backup/*.sql');
		$files = array_map(function($file){ return array('filename'=>$file, 'basename'=>basename($file), 'filesize'=>Helper::Size(filesize($file)), 'filemtime'=>filemtime($file));}, $files);
		return $files;
	}
	public function backup_restore(){
		$filename = isset($this->request['filename'])?APPPATH.'/static/db-backup/'.$this->request['filename']:'';
		$filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);
		($filename && file_exists($filename)) or Msg::json_encode('no', 'file not exists.');
		$lines = Helper::countFileLines($filename, ";\n");
		$success = 0;
		$pagesize = 100;
		$page = 1;
		do{
			$startLine = (($page-1) * $pagesize) +1;
			$endLine = $page*$pagesize;
			$contents = (array)Helper::getFileLines($filename, $startLine, $endLine, ";\n");
			if( $contents ){
				foreach($contents as $sql){
					$this->db->exec($sql);
					$success++;
				}
			}
			$page++;
		}while($lines>$endLine);
		Msg::json_encode('yes', "Total: {$lines}, Import: {$success}");
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function template_list($name=''){
		$name = trim($name);
		$files = glob(APPPATH.'/template/*/readme.ini');
		$files = array_map(function($row){
			$info = parse_ini_file($row, false);
			$row = array_merge($info, array(
				'path'=>str_replace('/', DIRECTORY_SEPARATOR, ($dirname=dirname($row))),
				'path_relative'=>str_replace(APPPATH, '', $dirname),
				'name'=>end(explode(DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $dirname))),
			));
			$row['logo'] = isset($row['logo'])?"{$row['path_relative']}/{$row['logo']}":"";
			return $row;
		}, $files);
		$files = $files?array_combine(array_column($files, 'name'), $files):$files;
		return strlen($name)&&isset($files[$name])?$files[$name]:$files;
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	public function sys_update(){
		$md5_file_old = APPPATH.'/.filelist';
		$git_basepath = Esite::app()->git_basepath;
		$md5_file_new = $git_basepath.'/.filelist';
		$update_files = array();
		( file_exists($md5_file_old) && filesize($md5_file_old)>32 ) or Msg::json_encode('no', '[.filelist] not exists.');
		# 开发环境禁止升级API文件
		$dev_files = glob(APPPATH.'/dev/*.php');
		empty($dev_files) or Msg::json_encode('no', 'No upgrade in development mode.');
		$md5_file_old_content = file_get_contents($md5_file_old);
		$md5_file_new_content = Helper::curl_get_contents($md5_file_new);
		$md5_file_new_content = strlen($md5_file_new_content)>32?$md5_file_new_content:file_get_contents($md5_file_new);
		$fileMatch = function($line){
			return preg_match('@([a-z0-9]{32})\s+(.*)@i',trim($line), $match)?array('file'=>$match[2],'md5'=>$match[1]):null;
		};
		$md5_files_old = array_map($fileMatch,explode("\n",$md5_file_old_content));
		$md5_files_new = array_map($fileMatch,explode("\n",$md5_file_new_content));
		$md5_files_old = $md5_files_old?array_combine(array_column($md5_files_old, 'md5'),array_column($md5_files_old,'file')):array();
		$md5_files_new = $md5_files_new?array_combine(array_column($md5_files_new, 'md5'),array_column($md5_files_new,'file')):array();
		count($md5_files_new)>1 or Msg::json_encode('no', '[microCMS.git-master.filelist] not exists.');
		foreach($md5_files_new as $md5=>$v){
			# 新文件的md5不存在则更新文件
			if( !isset($md5_files_old[$md5]) ){
				$update_files[] = '/'.ltrim(trim($v),'/');
			}
		}
		if( $update_files ){
			foreach($update_files as $filename){
				$fcontent = file_get_contents($git_basepath.$filename);
				# 当前api文件更新
				if( in_array($filename,array('/index.released.php','/index.php')) ){
					# 备份旧文件
					file_put_contents(__FILE__.date('YmdHis'), ($fcontent_old=file_get_contents(__FILE__)));
					# 核心变量替换
					foreach(explode(',','site_id,baseurl,api_version,auth_key,db_name,db_type,db_host,db_username,db_password') as $vkey){
						# $db_password = '@@db_password@@';
						# 从旧文件读取参数-更新到api模板变量中
						if( preg_match(sprintf('@(public|protected)\s+\$%s\s*=\s*[\'|\"]([^\'|^\"]+)[\'|\"]\s*;@',$vkey), $fcontent_old, $vmatch) && isset($vmatch[2]) ){
							$fcontent = str_replace('@@'.$vkey.'@@', $vmatch[2], $fcontent);
						}
					}
					# ===== 对新API代码生成缓存文件，模拟请求，判断API是否正常运行再执行替换！！！
					$testfile = APPPATH.'/index.fctest.php';
					file_put_contents($testfile, $fcontent);
					$tmp = file_get_contents(Esite::app()->baseurl."/?act=version");
					# 版本号符合要求则表示API运行正常
					if( is_numeric(trim($tmp)) ){
						# 重写替换当前API
						file_put_contents(__FILE__, $fcontent);
					}
					@unlink($testfile);
				}else{
					file_put_contents(APPPATH.$filename, $fcontent);
				}
			}
		}
		Msg::json_encode('yes', 'success', $update_files);
	}
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	/*
		自定义表单app
			用于存储站点自定义结构数据集
		
		[form_sys]系统表初始化；
		定义表单结构，通过JSON存储在[form_sys]，自动根据结构生成[sql]语句进行子表创建；
		后台[form_xxx]基本数据管理功能；
		前端[form_xxx]数据存储接口定义；
		帮助文档编写；
		--------------------------------------------------------
		create table if not exists form_sys(
			id int(11) not null AUTO_INCREMENT,
			name varchar(8) not null default '',
			title varchar(64) not null default '',
			notes longtext,
			def_struct longtext,
			PRIMARY KEY(id)
		)ENGINE=MyISAM DEFAULT CHARSET=utf8;
	*/
	# 表单系统初始化
	protected function form_sys_init(){
		if( in_array('form_sys', $tables=$this->db->getTables()) ){
			return true;
		}
		$git_basepath = Esite::app()->git_basepath;
		$sql_file = $git_basepath.(Esite::app()->db_type=='mysqli'?'/mysqli.sql':'/sqlite.sql');
		$sql_regu = '@(create\s+table[^;]+form_sys[^;]+)@i';
		$sql_code = Helper::curl_get_contents($sql_file);
		$sql_code = preg_match($sql_regu, $sql_code, $match)?$sql_code:file_get_contents($sql_file);
		if( !(preg_match($sql_regu, $sql_code, $match) && isset($match[1]) && $match[1]) ){
			return false;
		}
		# 创建数据表
		$this->db->exec($match[1]);
		if( in_array('form_sys', $this->db->getTables()) ){
			return true;
		}
		return false;
	}
	
	public function form_sys_list(){
		$list = array();
		# 初始化失败-直接报错
		if( !$this->form_sys_init() ){
			return $list;
		}
		$sql = "select * from form_sys";
		$list = $this->db->getAll($sql);
		return $list;
	}
	
	public function form_sys_info($id=0){
		$this->request['form_id'] = isset($this->request['form_id'])?intval($this->request['form_id']):$id;
		$sql = "select * from form_sys where id='{$this->request['form_id']}'";
		$res = $this->db->getRow($sql);
		return $res;
	}
	
	# 存储系统表单
	public function form_sys_save(){
		$this->request['form_id'] = isset($this->request['form_id'])?intval($this->request['form_id']):0;
		$this->request['form'] = isset($this->request['form'])?array_map(array($this->db,'escapeString'), $this->request['form']):array();
		if( !is_array($this->request['form']) || empty($this->request['form']) || !(isset($this->request['form']['def_struct']) &&strlen($this->request['form']['def_struct'])>0) ){
			Msg::json_encode('no');
		}
		if( $this->request['form_id']>0 ){
			$this->write_log("update form_sys id={$this->request['form_id']}");
			$this->db->update('form_sys', $this->request['form'], " id='{$this->request['form_id']}' ");
		}else{
			unset($this->request['form']['id']);
			$res = $this->db->getRow("select * from form_sys where name='{$this->request['form']['name']}'");
			if( isset($res['name']) && $res['name']==$this->request['form']['name'] ){
				Msg::json_encode('no', 'Data already exists!');
			}
			$this->write_log("update add form_sys id={$this->request['form']['name']}");
			$this->db->insert('form_sys', $this->request['form'], true);
		}
		$res = $this->db->getRow("select * from form_sys where name='{$this->request['form']['name']}'");
		if( isset($res['name']) && $res['name']==$this->request['form']['name'] ){
			# 创建数据集存储表
			$tables = $this->db->getTables();
			$tableName = "form_data_".$res['id'];
			$res['def_struct'] = json_decode($res['def_struct'],1);
			if( !in_array($tableName, $tables) && isset($res['def_struct']['cols']) && $res['def_struct']['cols'] ){
				# 根据数据集定义生成数据表结构
				if( Esite::app()->db_type=='mysqli' ){
					$def_struct_sql = "create table if not exists {$tableName}(id int(11) not null AUTO_INCREMENT";
					foreach($res['def_struct']['cols'] as $k=>$v){
						$def_struct_sql .= ", {$v['name']} ";
						if( in_array($v['type'], array('longtext')) ){
							$def_struct_sql .= " {$v['type']} ";
						}elseif( in_array($v['type'], array('int', 'decimal')) ){
							$def_struct_sql .= " {$v['type']}({$v['length']}) ";
							$def_struct_sql .= (strlen($v['default'])>0?" default '{$v['default']}' ":" default '0' ");
						}elseif( in_array($v['type'], array('varchar')) ){
							$v['length'] = intval($v['length']);
							$v['length']<65536 or Msg::json_encode('no', '[varchar] length cannot exceed 65535');
							$def_struct_sql .= " {$v['type']}({$v['length']}) ";
							$def_struct_sql .= (strlen($v['default'])>0?" default '{$v['default']}' ":" default '' ");
						}
					}
					if( isset($res['def_struct']['uniques']) && $res['def_struct']['uniques'] ){
						foreach($res['def_struct']['uniques'] as $k=>$v){
							$def_struct_sql .= sprintf(",unique key(%s) ", implode(',', $v));
						}
					}
					$def_struct_sql .= ", PRIMARY KEY(id))ENGINE=MyISAM DEFAULT CHARSET=utf8";
				}else{
					$def_struct_sql = "create table {$tableName}(id INTEGER PRIMARY KEY AUTOINCREMENT";
					foreach($res['def_struct']['cols'] as $k=>$v){
						$def_struct_sql .= ", {$v['name']} ";
						if( in_array($v['type'], array('longtext')) ){
							$def_struct_sql .= " {$v['type']} ";
						}elseif( in_array($v['type'], array('int', 'decimal')) ){
							$v['type'] = $v['type']=='int'?"INTEGER":$v['type'];
							$def_struct_sql .= " {$v['type']}({$v['length']}) ";
							$def_struct_sql .= (strlen($v['default'])>0?" default ('{$v['default']}') ":" default ('0') ");
						}elseif( in_array($v['type'], array('varchar')) ){
							$v['length'] = intval($v['length']);
							$v['length']<65536 or Msg::json_encode('no', '[varchar] length cannot exceed 65535');
							$def_struct_sql .= " {$v['type']}({$v['length']}) ";
							$def_struct_sql .= (strlen($v['default'])>0?" default ('{$v['default']}') ":" default ('') ");
						}
					}
					if( isset($res['def_struct']['uniques']) && $res['def_struct']['uniques'] ){
						foreach($res['def_struct']['uniques'] as $k=>$v){
							$def_struct_sql .= sprintf(",unique(%s) ", implode(',', $v));
						}
					}
					$def_struct_sql .= ")";
				}
				# 创建数据表
				$this->db->query($def_struct_sql);
				# 检查数据表是否创建成功
				$tables = $this->db->getTables();
				if( !in_array($tableName, $tables) ){
					Msg::$message[] = "error: Unable to create data table ({$tableName})";
				}
			}
			# 清理系统缓存
			$this->db->delCache();
			Msg::json_encode('yes', 'success', array($def_struct_sql, $this->db));
		}else{
			Msg::json_encode('no', 'please check your input...', array($this->request['form'], $res, $this->db));
		}
	}

	# 读取表单数据
	public function form_data_list($form_id=0){
		$list = array();
		$this->request['form_id'] = isset($this->request['form_id'])?intval($this->request['form_id']):intval($form_id);
		$tables = $this->db->getTables();
		$tableName = "form_data_".$this->request['form_id'];
		if( $this->request['form_id']>0 && in_array($tableName, $tables) ){
			$where = "1=1";
			if( isset($this->request['name']) && strlen($this->request['name'])>0 && isset($this->request['keyword']) && strlen($this->request['keyword'])>0 ){
				$where .= sprintf(" and %s like '%%%s%%' ", $this->request['name'], $this->request['keyword']);
			}
			$page = isset($this->request['page'])&&$this->request['page']>0?ceil($this->request['page']):1;
			$pagesize = isset($this->request['pagesize'])&&$this->request['pagesize']>0?ceil($this->request['pagesize']):10;
			$limit_start = ($page-1)*$pagesize;
			$sql = "select * from {$tableName} where {$where} order by id desc limit {$limit_start},{$pagesize}";
			$list = $this->db->getAll($sql);
			Esite::app()->cfg['url_route']['page'] = $page;
			Esite::app()->cfg['url_route']['pagesize'] = $pagesize;
			Esite::app()->cfg['url_route']['total_rows'] = $this->db->getRow("select count(1) as total from {$tableName} where {$where}")['total'];
		}
		return $list;
	}
	
	# 向指定数据集写入数据
	public function form_data_insert($form_id=0,$data=array()){
		$tables = $this->db->getTables();
		$tableName = "form_data_".$form_id;
		if( in_array($tableName, $tables) ){
			return $this->db->insert($tableName, $data, true);
		}else{
			Msg::$message[] = "data table ({$tableName}) not exists";
		}
		return false;
	}
	
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	
	
}
# == Admin.php code end ==

# == Bat.php code start ==

/**
 * 定时任务批处理
 * minute/hour/day/week/month/year
 * 流程定义：
 * 		服务器定时每分钟向子站点发出任务处理请求；
 * 		子站点从任务列表读取符合条件的任务进行处理；
 */
class Bat{
	private static $_instance;
	
	function __construct(){
		defined('USE_CACHE')?null:define('USE_CACHE', false);
	}
	
	private function __clone(){}
	
	#返回唯一实例
	public static function run(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	function runTask(){
		$time = time();
		$sql = "select * from task_list where {$time}-last_time>cycle limit 0,100";
		$task_list = Esite::$db->getAll($sql);
		$task_urls = $task_list?array_unique(array_filter(array_map('trim', array_column($task_list, 'task_url')))):array();
		if( $task_urls ){
			Helper::base_rolling_curl($task_urls);
		}
		ob_clean();
		echo count($task_urls);
	}
}
# == Bat.php code end ==

# == Calendar.php code start ==

/**
名称：php日历
用途：实现文章日历，用户可以点击对应的日期搜索指定日期里的数据
作者：fancanjie 2017-10-24
示例：
	Calendar::$hitDays = array('2017-10-24', '2017-10-19', '2017-10-09');
	if( @$_REQUEST['month'] && @$_REQUEST['year'] ){
		echo Calendar::app()->getMonthView(@$_REQUEST['month'], @$_REQUEST['year']);
	}elseif( @$_REQUEST['year'] ){
		echo Calendar::app()->getYearView(@$_REQUEST['year']);
	}
*/

class Calendar{
	# 自定义星期起始点： 默认0 周日（对应星期名称）
	public static $startDay = 0;
	# 自定义月份起始点： 默认1 一月（对应月份名称）
	public static $startMonth = 1;
	# 自定义星期名称： 数组中第一个元素代表星期日
	public static $dayNames = array("S", "M", "T", "W", "T", "F", "S");
	# 自定义月份名称： 数组中第一个元素代表一月
	public static $monthNames = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	# 自定义平年每个月最大天数
	public static $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	# 自定义存储命中日期列表, 用于对列表中日期定义事件入口时调用, 格式： array('2017-10-23')
	public static $hitDays = array();
	# 自定义月份链接模板
	public static $monthLink = '';
	# 自定义日期链接模板
	public static $dayLink = '';
	# 自定义样式
	public static $style = '
		<style type="text/css">
		table.calendar{width: 100%; line-height: 20px;}
		.calendar{ background-color: #FFFFCC; font-size:12px; text-align:center; }
		.calendar a{text-decoration:none;display:block;}
		.calendarHeader,.calendarDateTitle{ font-weight: bolder; color: #CC0000; background-color: #FFFFCC; text-align:center;}
		.calendarToday { background-color: #CE0000; text-align: center;}
		.calendar .hitDay{ background-color: #FF9797; }
		</style>
	';
	# 存储当前日期参数
	protected static $date = array();
	
	private static $_instance;
	private function __construct(){}
	private function __clone(){}
	public static function app(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
			self::$date = getdate(time());
			self::$monthLink = self::$monthLink?(self::$monthLink):getenv('SCRIPT_NAME')."?year=%s&month=%s";
			self::$dayLink = self::$dayLink?(self::$dayLink):getenv('SCRIPT_NAME')."?year=%s&month=%s&day=%s";
		}
		return self::$_instance;
	}
	
	# 日历月份链接
	public function getCalendarLink($month, $year){
		$year = str_pad($year, 4, 0, STR_PAD_LEFT);
		$month = str_pad($month, 2, 0, STR_PAD_LEFT);
		return sprintf(self::$monthLink, $year, $month>0?$month:'');
	}
	
	# 日期命中链接
	public function getDateLink($day, $month, $year){
		$year = str_pad($year, 4, 0, STR_PAD_LEFT);
		$month = str_pad($month, 2, 0, STR_PAD_LEFT);
		$day = str_pad($day, 2, 0, STR_PAD_LEFT);
		return in_array("{$year}-{$month}-{$day}", self::$hitDays)?sprintf(self::$dayLink, $year, $month, $day):'';
	}

	# 获取当前月份日历HTML
	public function getCurrentMonthView(){
		return $this->getMonthView(self::$date["mon"], self::$date["year"]);
	}
	
	# 获取当前年份日历HTML
	public function getCurrentYearView(){
		return $this->getYearView(self::$date['year']);
	}
	
	# 获取指定月份日历HTML
	public function getMonthView($month, $year){
		$month = $month?$month:(self::$date['mon']);
		$year = $year?$year:(self::$date['year']);
		$s = self::$style;
		$s .= $this->getMonthHTML($month, $year);
		return $s;
	}
	
	# 获取指定年份日历HTML
	public function getYearView($year){
		$s = self::$style;
		$s .= $this->getYearHTML($year);
		return $s;
	}
	
	# 计算月份的天数，主要以平年月份天数表修正闰年天数最大值
	private function getDaysInMonth($month, $year){
		if ($month < 1 || $month > 12 || !isset(self::$daysInMonth[$month - 1])){
			return 0;
		}
		# 
		$d = self::$daysInMonth[$month - 1];
		if ($month == 2){
			# 检查闰年，精确到万年内，万年后的天文精度偏差忽略不计
			# 闰年算法：四年一闰，百年不闰，四百年再闰，四千年不润。
			if ($year%4 == 0){
				if ($year%100 == 0){
					if ($year%400 == 0){
						if( $year%1000 == 0 ){
							if( $year%4000 != 0 ){
								$d = 29;
							}
						}else{
							$d = 29;
						}
					}
				}else{
					$d = 29;
				}
			}
		}
		return $d;
	}

	# 月份日历HTML
	private function getMonthHTML($m, $y, $showYear = 1){
		$s = "";
		$a = $this->adjustDate($m, $y);
		$month = $a[0];
		$year = $a[1];
		
		$daysInMonth = $this->getDaysInMonth($month, $year);
		$date = getdate(mktime(12, 0, 0, $month, 1, $year));
		
		$first = $date["wday"];
		$monthName = self::$monthNames[$month - 1];
		
		$prev = $this->adjustDate($month - 1, $year);
		$next = $this->adjustDate($month + 1, $year);
		
		if ($showYear == 1){
			$prevMonth = $this->getCalendarLink($prev[0], $prev[1]);
			$nextMonth = $this->getCalendarLink($next[0], $next[1]);
		}else{
			$prevMonth = "";
			$nextMonth = "";
		}
		
		$header = sprintf(
			'<a href="%s" class="calendarDateTitle">%s</a>',
			$this->getCalendarLink(($showYear>0?0:$month), $year),
			($monthName.(($showYear>0)?" {$year}":""))
		);
		
		$s .= "<table class=\"calendar\">\n";
		$s .= "<tr>\n";
		$s .= "<td align=\"center\" valign=\"top\">" . (($prevMonth == "") ? "&nbsp;" : "<a href=\"{$prevMonth}\">&lt;&lt;</a>")  . "</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\" colspan=\"5\">{$header}</td>\n";
		$s .= "<td align=\"center\" valign=\"top\">" . (($nextMonth == "") ? "&nbsp;" : "<a href=\"{$nextMonth}\">&gt;&gt;</a>")  . "</td>\n";
		$s .= "</tr>\n";
		
		$s .= "<tr>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\">" . self::$dayNames[(self::$startDay)%7] . "</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\">" . self::$dayNames[(self::$startDay+1)%7] . "</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\">" . self::$dayNames[(self::$startDay+2)%7] . "</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\">" . self::$dayNames[(self::$startDay+3)%7] . "</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\">" . self::$dayNames[(self::$startDay+4)%7] . "</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\">" . self::$dayNames[(self::$startDay+5)%7] . "</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" class=\"calendarHeader\">" . self::$dayNames[(self::$startDay+6)%7] . "</td>\n";
		$s .= "</tr>\n";
		
		# 月份-星期起始点计算
		$d = self::$startDay + 1 - $first;
		while ($d > 1){
			$d -= 7;
		}

		# 当前日期定义，可以根据需要定义样式
		$today = getdate(time());
		
		while ($d <= $daysInMonth){
			$s .= "<tr>\n";
			for ($i = 0; $i < 7; $i++){
				$class = $this->isHitDay($d, $month, $year)?"hitDay":"";
				$class .= ($year == $today["year"] && $month == $today["mon"] && $d == $today["mday"]) ? " calendarToday" : " calendar";
				$s .= "<td class=\"{$class}\" align=\"right\" valign=\"top\">";	   
				if ($d > 0 && $d <= $daysInMonth){
					$link = $this->getDateLink($d, $month, $year);
					$s .= (($link == "") ? $d : "<a href=\"{$link}\">{$d}</a>");
				}else{
					$s .= "&nbsp;";
				}
	  			$s .= "</td>\n";
				$d++;
			}
			$s .= "</tr>\n";	
		}
		
		$s .= "</table>\n";
		
		return $s;  	
	}
	
	
	# 年份日历HTML
	private function getYearHTML($year){
		$s = "";
		$prev = $this->getCalendarLink(0, $year - 1);
		$next = $this->getCalendarLink(0, $year + 1);
		
		$s .= "<table class=\"calendar\" border=\"0\">\n";
		$s .= "<tr>";
		$s .= "<td align=\"center\" valign=\"top\" align=\"left\">" . (($prev == "") ? "&nbsp;" : "<a href=\"{$prev}\">&lt;&lt;</a>")  . "</td>\n";
		$s .= "<td class=\"calendarHeader\" valign=\"top\" align=\"center\">" . ((self::$startMonth > 1) ? $year . " - " . ($year + 1) : $year) ."</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" align=\"right\">" . (($next == "") ? "&nbsp;" : "<a href=\"{$next}\">&gt;&gt;</a>")  . "</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(0 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(1 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(2 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(3 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(4 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(5 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(6 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(7 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(8 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(9 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(10 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(11 + self::$startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "</table>\n";
		
		return $s;
	}

	# 跨年日期修正： 例如2001年第14个月是2002年第2个月
	private function adjustDate($month, $year){
		$a = array();  
		$a[0] = $month;
		$a[1] = $year;
		
		while ($a[0] > 12){
			$a[0] -= 12;
			$a[1]++;
		}
		
		while ($a[0] <= 0){
			$a[0] += 12;
			$a[1]--;
		}
		
		return $a;
	}
	
	# 判断日期是否命中
	private function isHitDay($day, $month, $year){
		return in_array("{$year}-{$month}-".str_pad($day, 2, 0, STR_PAD_LEFT), self::$hitDays);
	}
	
}
# == Calendar.php code end ==

# == Db_Drive.php code start ==

# 数据库驱动接口 @fancanjie by 2018-03-01 19:56:29
# 暂时支持 sqlite3/mysqli, 其它驱动后面根据用户需求扩展
interface Db_Drive{
	
	# 执行一个无结果的查询
	public function exec($sql);
	
	# 执行一个有结果集的查询
	public function query($sql);
	
	# 获取单行数据，返回值：关联数组
	public function getRow($sql, $model);
	
	# 获取多行数据，返回值：关联数组
	public function getAll($sql);
	
	# 插入数据，返回值：布尔值
	public function insert($table, $data, $ignore=false);
	public function insert_string($table, $data, $ignore=false);
	
	# [插入/更新]数据，返回值：布尔值
	public function replace($table, $data);
	public function replace_string($table, $data);
	
	# 更新数据，返回值：布尔值
	public function update($table, $args, $where='');
	public function update_string($table, $args, $where='');
	
	# 刪除数据，返回值：布尔值
	public function delete($table, $where);
	
	# 获取要操作的数据，返回合并后的SQL参数字符串
	public function getCode($args);
	
	# 获取新插入数据的id
	public function last_insert_id();
	
	# 错误捕获，返回数组：array('error_code'=>0, 'error_msg'=>'', 'changes'=>0, 'queries'=>array())
	public function get_info();
	
	# 读取缓存
	public function getCache($sql, $expire_time=0);
	
	# 写入缓存
	public function setCache($sql, $data);
	
	# 删除缓存
	public function delCache();
	
	# 数据表名称列表
	public function getTables();
	
	# 按需扩展：字符串转换，此名次以sqlite字符串转换方法同名
	#public function escapeString($str='');
}

class DBSqlite3 extends SQLite3 /*implements Db_Drive*/{
/**
 * sqlite3自定义扩展
 *	public void SQLite3::open ( filename, flags, encryption_key )
 *		打开一个 SQLite 3 数据库。如果构建包括加密，那么它将尝试使用的密钥。
 *		如果文件名 filename 赋值为 ':memory:'，那么 SQLite3::open() 将会在 RAM 中创建一个内存数据库，
 *		这只会在 session 的有效时间内持续。
 *		如果文件名 filename 为实际的设备文件名称，那么 SQLite3::open() 将使用这个参数值尝试打开数据库文件。
 *		如果该名称的文件不存在，那么将创建一个新的命名为该名称的数据库文件。
 *		可选的 flags 用于判断是否打开 SQLite 数据库。
 *		默认情况下，当使用 SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE 时打开。
 *	
 *	public bool SQLite3::exec ( string $query )
 *		该例程提供了一个执行 SQL 命令的快捷方式，SQL 命令由 sql 参数提供，可以由多个 SQL 命令组成。
 *		该程序用于对给定的数据库执行一个无结果的查询。
 *	
 *	public SQLite3Result SQLite3::query ( string $query )
 *		该例程执行一个 SQL 查询，如果查询到返回结果则返回一个 SQLite3Result 对象。
 *	
 *	public int SQLite3::lastErrorCode ( void )
 *		该例程返回最近一次失败的 SQLite 请求的数值结果代码。
 *	
 *	public string SQLite3::lastErrorMsg ( void )
 *		该例程返回最近一次失败的 SQLite 请求的英语文本描述。
 *	
 *	public int SQLite3::changes ( void )
 *		该例程返回最近一次的 SQL 语句更新或插入或删除的数据库行数。
 *	
 *	public bool SQLite3::close ( void )
 *		该例程关闭之前调用 SQLite3::open() 打开的数据库连接。
 *	
 *	public string SQLite3::escapeString ( string $value )
 *		该例程返回一个字符串，在 SQL 语句中，出于安全考虑，该字符串已被正确地转义。
 *	
 *	
 */

	public $info = array('error_code'=>0, 'error_msg'=>'', 'changes'=>0, 'queries'=>array());
	public $cache_path = 'cache/data-cache';
	public $expire_time = 300;
	public $auth_key = '';
	
	public function __construct($db_name=''){
		$db_name = $db_name?$db_name:'esite.db';
		if( !file_exists($db_name) ){
			file_put_contents($db_name, '');
		}
		$this->open($db_name);
		$this->get_info();
		if( $this->info['error_code']>0 ){
			die($this->info['error_msg']);
		}
		return $this;
	}
	
	public function getCache($sql, $expire_time=0){
		$expire_time = $expire_time>0?$expire_time:$this->expire_time;
		$filename = $this->cache_path.'/'.md5(md5($this->auth_key).md5($sql)).'.cache';
		if( file_exists($filename) && time()-filemtime($filename)<$expire_time ){
			return (array)include($filename);
		}else{
			return null;
		}
	}
	
	public function setCache($sql, $data){
		$filename = $this->cache_path.'/'.md5(md5($this->auth_key).md5($sql)).'.cache';
		file_put_contents($filename, '<?php return '.var_export($data, 1).';?>');
	}
	
	public function delCache(){
		$files = glob("{$this->cache_path}/*.cache");
		if( $files ){
			array_map('unlink', $files);
		}
	}
	
	public function get_info(){
		$this->info['error_code'] = $this->lastErrorCode();
		$this->info['error_msg'] = $this->lastErrorMsg();
		$this->info['changes'] = $this->changes();
		$this->info['queries'] = count($this->info['queries'])>50?array_slice($this->info['queries'], -50):$this->info['queries'];
		return $this->info;
	}
	
	public function query($sql){
		$res = parent::query($sql);
		$this->info['queries'][] = $sql;
		$this->get_info();
		return $res;
	}
	
	function exec($sql){
		$res = parent::exec($sql);
		$this->info['queries'][] = $sql;
		$this->get_info();
		return $res;
	}
	
	/**
	 * 查询一条数据
	 * 参数：
	 *	model
	 *		SQLITE3_ASSOC 关联数组，例如：array('title1'=>'value1', 'title2'=>'value2')
	 *		SQLITE3_NUM 索引数组，例如：array(0=>'value1', 1=>'value2')
	 *		SQLITE3_BOTH 复合数组，例如：array(0=>'value1', 'title1'=>'value1')
	 */
	public function getRow($sql, $model=SQLITE3_ASSOC){
		if( !is_null($res=$this->getCache($sql)) ){return $res;}
		$res = $this->query($sql)->fetchArray($model);
		$this->setCache($sql, $res);
		return $res;
	}
	
	# 查询多条数据
	public function getAll($sql){
		if( !is_null($res=$this->getCache($sql)) ){return $res;}
		$res = array();
		if($result = $this->query($sql)){
			while($row = $result->fetchArray(SQLITE3_ASSOC)){
				$res[] = $row;
			}
			unset($result);
		}
		$this->setCache($sql, $res);
		return $res;
	}
	
	# 插入数据
	function insert($table, $data, $ignore=false){
		$sql = $this->insert_string($table, $data, $ignore);
		return $this->exec($sql);
	}
	
	# 插入数据
	function insert_string($table, $data, $ignore=false){
		$sql = sprintf('INSERT %s INTO %s (%s) VALUES (%s)',
			($ignore?'OR IGNORE':''),
			($table),
			(sprintf('%s', implode(',', array_keys($data)))),
			(sprintf("'%s'", implode("','", array_values($data))))
		);
		$sql .= ";\n";
		return $sql;
	}
	
	# 插入数据
	function replace($table, $data){
		$sql = $this->replace_string($table, $data);
		return $this->exec($sql);
	}
	
	# 插入数据
	function replace_string($table, $data){
		$sql = sprintf('REPLACE INTO %s (%s) VALUES (%s)',
			($table),
			(sprintf('%s', implode(',', array_keys($data)))),
			(sprintf("'%s'", implode("','", array_values($data))))
		);
		$sql .= ";\n";
		return $sql;
	}
	
	# 更新数据
	function update($table, $args, $where=''){
		$sql = $this->update_string($table, $args, $where);
		return $this->exec($sql);
	}
	
	# 更新数据
	function update_string($table, $args, $where=''){
		$code = self::getCode($args);
		$sql = "UPDATE {$table} SET ";
		$sql .= $code;
		$sql .= ($where==''?'':" Where {$where}");
		$sql .= ";\n";
		return $sql;
	}
	
	# 刪除数据
	function delete($table, $where) {
		$sql = "DELETE FROM {$table} Where {$where}";
		$sql .= ';';
		$res = $this->exec($sql);
		return $res;
	}
	
	# 获取要操作的数据，返回合并后的SQL参数
	function getCode($args){
		$code = array();
		if (is_array ( $args )) {
			foreach ( $args as $k => $v ) {
				$code[] = "{$k}='{$v}'";
			}
		}
		return implode(',', $code);
	}
	
	# 获取新插入数据的id
	function last_insert_id(){
		$sql = "select last_insert_rowid() newid";
		$res = $this->getRow($sql);
		return isset($res['newid'])?intval($res['newid']):0;
	}
	
	function getTables(){
		$res = $this->getAll("SELECT name FROM sqlite_master where type='table' and tbl_name not like 'sqlite_%'");
		return array_column($res, 'name');
	}
	
	/*function escapeString($str=''){
		return parent::escapeString($str);
	}*/
	
	function __destruct(){
		$this->close();
	}

}

/**
 * mysql Drive
 * (单例模式)
 * @fancanjie by 2018-03-01 21:18:52
 * 单库调用示例：
 * 		# 初始化
 * 		Db_Mysql::app()->init('localhost', 'root', 'toor', 'test_db');
 * 		# 执行数据库操作
 * 		Db_Mysql::app()->getRow($sql);
 * 多库调用示例：
 * 		# 初始化
 * 		$db1 = Db_Mysql::app()->init('localhost', 'root', 'toor', 'test_db1');
 * 		$db2 = Db_Mysql::app()->init('localhost', 'root', 'toor', 'test_db2');
 * 		# 执行数据库操作
 * 		$db1->getRow($sql);
 * 		$db2->getRow($sql);
 */
class Db_Mysql/* implements Db_Drive*/{
	protected static $conn;
	protected $dbname = '';
	protected $username = '';
	protected $password = '';
	protected $host = 'localhost';
	public $info = array('error_code'=>0, 'error_msg'=>'', 'changes'=>0, 'queries'=>array());
	public $cache_path = 'cache/data-cache';
	public $expire_time = 300;
	public $auth_key = '';

	# 初始化数据连接对象
	public function __construct($host='', $username='', $password='', $dbname=''){
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->dbname = $dbname;
		self::$conn = mysqli_connect($this->host, $this->username, $this->password, $this->dbname);
		if( !self::$conn ) {
			die(mysqli_error(self::$conn));
		}
		# 设置访问数据库的编码
		mysqli_query(self::$conn, 'set names utf8');
		return $this;
	}
	
	public function getCache($sql, $expire_time=0){
		$expire_time = $expire_time>0?$expire_time:$this->expire_time;
		$filename = $this->cache_path.'/'.md5(md5($this->auth_key).md5($sql)).'.cache';
		if( file_exists($filename) && time()-filemtime($filename)<$expire_time ){
			return (array)include($filename);
		}else{
			return null;
		}
	}
	
	public function setCache($sql, $data){
		$filename = $this->cache_path.'/'.md5(md5($this->auth_key).md5($sql)).'.cache';
		file_put_contents($filename, '<?php return '.var_export($data, 1).';?>');
	}
	
	public function delCache(){
		$files = glob("{$this->cache_path}/*.cache");
		if( $files ){
			array_map('unlink', $files);
		}
	}
	
	public function get_info(){
		$this->info['error_code'] = mysqli_errno(self::$conn);
		$this->info['error_msg'] = mysqli_error(self::$conn);
		$this->info['changes'] = mysqli_affected_rows(self::$conn);
		$this->info['queries'] = count($this->info['queries'])>50?array_slice($this->info['queries'], -50):$this->info['queries'];
		return $this->info;
	}
	
	# 无返回值的查询
	public function exec($sql){
		$res = self::query($sql);
		return $res;
	}
	
	# 有返回值的查询
	public function query($sql){
		$res = mysqli_query(self::$conn, $sql);
		$this->info['queries'][] = $sql;
		$this->get_info();
		return $res;
	}
	
	# 获取单行数据( $model 定义数据格式为数组还是对象 )
	public function getRow($sql, $model=MYSQLI_ASSOC){
		if( !is_null($res=$this->getCache($sql)) ){return $res;}
		$res = $this->query($sql);
		$row = mysqli_fetch_array($res, MYSQLI_ASSOC);
		mysqli_free_result($res);
		unset($res);
		$this->setCache($sql, $row);
		return $row;
	}
	
	# 获取多行数据
	public function getAll($sql){
		if( !is_null($res=$this->getCache($sql)) ){return $res;}
		$res = $this->query($sql);
		if( function_exists('mysqli_fetch_all') ){
			$list = mysqli_fetch_all($res, MYSQLI_ASSOC);
		}else{
			while( $r = mysqli_fetch_assoc($res) ){
				$list[] = $r;
			}
		}
		mysqli_free_result($res);
		unset($res);
		$this->setCache($sql, $list);
		return $list;
	}
	
	# 插入数据
	public function insert($table, $data, $ignore=false){
		$sql = $this->insert_string($table, $data, $ignore);
		return $this->query($sql);
	}
	
	# 插入数据
	public function insert_string($table, $data, $ignore=false){
		$sql = sprintf('INSERT %s INTO %s (%s) VALUES (%s)',
			($ignore?'IGNORE':''),
			($table),
			(sprintf('%s', implode(',', array_keys($data)))),
			(sprintf("'%s'", implode("','", array_values($data))))
		);
		$sql .= ";\n";
		return $sql;
	}
	
	# 插入数据
	public function replace($table, $data){
		$sql = $this->replace_string($table, $data);
		return $this->query($sql);
	}
	
	# 插入数据
	public function replace_string($table, $data){
		$sql = sprintf('REPLACE INTO %s (%s) VALUES (%s)',
			($table),
			(sprintf('%s', implode(',', array_keys($data)))),
			(sprintf("'%s'", implode("','", array_values($data))))
		);
		$sql .= ";\n";
		return $sql;
	}
	
	# 更新数据
	public function update($table, $args, $where=''){
		$sql = $this->update_string($table, $args, $where);
		return $this->query($sql);
	}
	
	# 更新数据
	public function update_string($table, $args, $where=''){
		$code = self::getCode($args);
		$sql = "UPDATE {$table} SET ";
		$sql .= $code;
		$sql .= ($where==''?'':" Where {$where}");
		$sql .= ";\n";
		return $sql;
	}
	
	# 刪除数据
	public function delete($table, $where) {
		$sql = "DELETE FROM {$table} Where {$where}";
		$sql .= ';';
		return $this->query($sql);
	}
	
	# 获取要操作的数据，返回合并后的SQL参数
	public function getCode($args){
		$code = array();
		if (is_array ( $args )) {
			foreach ( $args as $k => $v ) {
				$code[] = "{$k}='{$v}'";
			}
		}
		return implode(',', $code);
	}
	
	# 获取新插入数据的id
	function last_insert_id(){
		$sql = "select last_insert_id() newid";
		$res = $this->getRow($sql);
		return isset($res['newid'])?intval($res['newid']):0;
	}

	function escapeString($str=''){
		return addslashes($str);
	}
	
	function getTables(){
		$res = $this->getAll("show tables");
		return array_column($res, 'Tables_in_'.$this->dbname);
	}
	
	function __destruct(){
		mysqli_close(self::$conn);
	}
	
}

class DB{
	protected static $_db;
	# 数据初始化回调方法（当数据库内不存在数据表则自动初始化）
	public static $call_struct_init = null;
	# 数据库类型：mysqli/sqlite
	public static function init($dbtype='sqlite', $dbname='', $host='', $username='', $password=''){
		switch($dbtype){
			case 'mysqli':
				self::$_db = new Db_Mysql($host, $username, $password, $dbname);
				$sql = "SELECT COUNT(*) as total FROM information_schema.TABLES where TABLE_SCHEMA = '{$dbname}'";
				$res = self::$_db->getRow($sql);
				if( (int)$res['total']<2 ){
					is_array(self::$call_struct_init)&&self::$call_struct_init?call_user_func_array(self::$call_struct_init, array(self::$_db)):null;
				}
				break;
			default:
				self::$_db = new DBSqlite3($dbname);
				# 数据表不存在则进行数据库初始化
				$sql = "SELECT count(*) as total FROM sqlite_master WHERE type='table' ";
				$res = self::$_db->getRow($sql);
				if( (int)$res['total']<2 ){
					is_array(self::$call_struct_init)&&self::$call_struct_init?call_user_func_array(self::$call_struct_init, array(self::$_db)):null;
				}
				break;
		}
		return self::$_db;
	}
	
	function __construct(){}
}
# == Db_Drive.php code end ==

# == Esite.php code start ==

/**
 * 更新日志：
 * 	2018.02.27 对入口业务进行公私分离，共有方法可以根据需要在任何位置进行扩展，而私有方法则受保护；
 * 任务列表：
 * 	外部数据推送接口：供第三方程序向当前站点发布数据，基于帐号Token认证；
 * 	外部数据提取接口：供第三方程序读取当前站点元数据，基于帐号Token认证；
 *  系统初始化接口：数据库备份、数据库连接测试 与 数据初始化；
 */

class Esite{
	/**
	 * 数据处理模型
	 * 所有方法必须为私有(private)/或受保护类型(protected)，防止外部程序重写
	 */
	#站点编号
	public $site_id = '@@site_id@@';
	#站点根目录
	public $baseurl = '@@baseurl@@';
	#服务端通讯入口
	public $server_baseurl = "http://esitecms.likun.work/ecms";
	# 源码仓库地址-部分api需要通过仓库读取源码实现自动升级
	public $git_basepath = 'https://gitee.com/fancanjie/microCMS/raw/master';
	# baseurl解析数据
	public $baseurls = array();
	# 支付类型
	public $pay_type = array('paypal', 'alipay');
	#api版本
	public $api_version = '20200603120802';
	public $db_type = '@@db_type@@';
	#站点通讯密钥
	protected $auth_key = '@@auth_key@@';
	#数据库连接参数
	protected $db_name = '@@db_name@@';
	protected $db_host = '@@db_host@@';
	protected $db_username = '@@db_username@@';
	protected $db_password = '@@db_password@@';
	
	#页面缓存路径
	public $page_cache = '';
	# 数据缓存
	public $data_cache = '';
	
	#模板目录
	public $page_template = '';
	
	#存储动态数据
	public $raw_data = '';
	#存储设定信息
	public $cfg = null;
	# 模板缓存周期
	public $tpl_expire = 300;
	# 模板后缀类型
	public $tpl_extension = 'xml|html';
	#站点主题名称
	public $themes = 'default';
	#用户设备类型(mobile|pc)
	public $user_device = 'pc';
	
	# 当前请求协议类型
	public $protocol = 'http';
	# 存储客户端当前访问地址(若存在映射则会重定向到映射地址)
	public $url = '';
	# 存储客户当前请求的地址
	public $request_uri = '';
	# 自定义url映射: array('新URI'=>'系统URI')
	public $url_maps = array();
	# 存储客户端请求数据
	public $request = array();
	
	#数据库对象
	public static $db;
	
	public $languages = array();
	
	#模板解析开始时间
	public $tpl_stime;
	#模板解析耗费时间
	public $tpl_rtime;
	# 调试模式(仅用于api开发，发布时需要手动关闭调试)
	protected static $debug = false;
	
	#对象单例模式唯一实例静态成员
	private static $_instance;
	
	private function __construct(){}
	
	private function __clone(){}
	
	#返回唯一实例
	public static function app(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
			self::$_instance->init();
		}
		return self::$_instance;
	}
	
	# 初始化
	protected function init(){
		set_time_limit(0);ignore_user_abort(false);	#不超时
		# 会话初始化
		$_SESSION['user_id'] = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;
		$_SESSION['role'] = isset($_SESSION['role'])&&$_SESSION['role']=='admin'?$_SESSION['role']:'user';
		$this->page_cache = APPPATH.'/cache/page-cache';
		$this->data_cache = APPPATH.'/cache/data-cache';
		Helper::make_dir($this->page_cache);
		Helper::make_dir($this->data_cache);
		$this->tpl_stime = Helper::microtime_float();
		# 协议类型
		$this->protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS'])!== 'off') ) ? 'https' : 'http';
		$this->baseurl = str_ireplace(array('http:', 'https:'), "{$this->protocol}:", $this->baseurl);
		$this->baseurl = rtrim($this->baseurl, '/');
		$this->baseurls = $this->baseurl?parse_url($this->baseurl):$this->baseurls;
		# 过滤url-rewrite过程会产生的动态URI参数
		$_REQUEST = substr(key($_REQUEST), 0,1)=='/'?array_slice($_REQUEST, 1):$_REQUEST;
		#提取用户请求
		$this->request = array_merge($_REQUEST, (preg_match_all('@[\?|\&]([^=]+)=([^\&]*)@i', urldecode($_SERVER['REQUEST_URI']), $match)?(array_combine( array_map(function($str){return trim($str,'&');},$match[1]), $match[2] )):array()));
		$this->request['ajax'] = ( isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") || (isset($this->request['ajax']) && strtolower($this->request['ajax']) == 'yes' )?'yes':'no';
		$this->request['act'] = isset($this->request['act'])?$this->request['act']:'';
		$this->url = $this->request_uri = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'/';
		$this->user_device = Helper::isMobile()?'mobile':'pc';
		$this->api_init();
		DB::$call_struct_init = array($this, 'db_struct_init');
		# 初始化数据库连接
		if( !self::$db ){
			self::$db = DB::init($this->db_type, $this->db_name, $this->db_host, $this->db_username, $this->db_password);
			self::$db->auth_key = $this->auth_key;
			$this->site_config();
		}
		# 模板路径补全
		if( !$this->tpl_demo_mode() ){
			$this->themes = ($this->user_device=='mobile'?@$this->cfg['site_info']['mobile_theme']:@$this->cfg['site_info']['site_theme']);
			$this->page_template = APPPATH."/template/{$this->themes}";
		}
		# 后台模板
		if( stripos($this->request_uri, rtrim($this->baseurls['path'], '/').'/admin')!==false ){
			$this->themes = 'admin';
			$this->page_template = APPPATH."/template";
		}
		
		$this->set_url_maps();
		$this->url_route();
		# 用户自定义业务查询缓存
		self::$db->expire_time = isset($this->cfg['site_info']['db_cache_time'])?intval($this->cfg['site_info']['db_cache_time']):3600;
		$this->tpl_expire = isset($this->cfg['site_config']['tpl_cache_time'])?intval($this->cfg['site_config']['tpl_cache_time']):$this->tpl_expire;
		# 动态请求禁止缓存
		$this->tpl_expire = isset($_SERVER['QUERY_STRING'])&&strlen($_SERVER['QUERY_STRING'])>0?0:$this->tpl_expire;
		# 模板目录初始化
		if( !file_exists($this->page_template) ){
			Helper::make_dir($this->page_template);
			file_put_contents("{$this->page_template}/index.html", '<!DOCTYPE html><html><head><title>Home page</title></head><body>This is default homepage.</body></html>');
		}
		(filesize(APPPATH.'/error.log')<1024*1024*2) or file_put_contents(APPPATH.'/error.log','');
		
		$this->set_languages();
		return $this;
	}
	
	protected function api_init(){
		$title = '站点api更新';
		$init = preg_match('@\$db_type\s*=\s*\'\@\@db_type\@\@\'\s*;@i', ($api_content=file_get_contents(__FILE__)), $match);
		$is_request = isset($this->request['db_type']) && isset($this->request['db_name']) && isset($this->request['baseurl']) && ($this->request['db_type']=='sqlite' || (
			isset($this->request['db_host']) && strlen($this->request['db_host'])
			&& isset($this->request['db_username']) && strlen($this->request['db_username'])
			&& isset($this->request['db_password'])
		));
		$tpl = <<<EOT
<!DOCTYPE html><html><head><title>init-microCMS</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><style type="text/css">body{background-color:#eee;font-family:Microsoft Yahei,Arial,Verdana,sans-serif;font-size:12px;color:#333;margin:0;padding:0}.input{width:330px;color:#333;border:2px solid #bbb;background-color:#eee;padding:5px 8px}.form p{margin: 15px 0;}.form p .title{width:120px; text-align:right;display: block;float: left;margin-right: 10px;line-height: 30px;}.form .btnBox{margin-left: 135px;}#init{width: 600px;margin: auto;margin-top: 30px;padding: 5px;}</style></head><body><div id="init"><form action="" method="post" onsubmit="" class="form"><p><b class="title"></b><h1 class="">站点数据初始化</h1></p><p><b class="title">数据库*类型</b><label><input type="radio" name="db_type" value="sqlite" onclick="set_db_type(this.value);">Sqlite</label><label><input type="radio" name="db_type" value="mysqli" onclick="set_db_type(this.value);">Mysqli</label></p><p class="db-config mysqli"><b class="title">数据库*主机名</b><input type="text" name="db_host" placeholder="127.0.0.1:3306" class="input"></p><p class="db-config mysqli sqlite"><b class="title">数据库*名称</b><input type="text" name="db_name" placeholder="esite" class="input"></p><p class="db-config mysqli"><b class="title">数据库*用户名</b><input type="text" name="db_username" placeholder="" class="input"></p><p class="db-config mysqli"><b class="title">数据库*密码</b><input type="text" name="db_password" placeholder="" class="input"></p><p class=""><b class="title">站点入口URL</b><input type="text" name="baseurl" placeholder="http://localhost.com/" class="input"></p><p class="btnBox"><input type="submit" class="btn" value="立即初始化"><br><br><a href="/LICENSE" target="_blank">使用协议</a>&nbsp;&nbsp;<a href="/">返回首页</a></p></form></div><script>var fc = {};fc.display = function(el, _display){	var els = document.querySelectorAll(el);	for(var k in els){if( typeof(els[k].style)!='undefined' )els[k].style.display=_display||'inline';}};window.onload = function(){	fc.display('.db-config', 'none');document.querySelector('input[name="db_type"][value="mysqli"]').click();};function set_db_type(db_type){fc.display('.db-config', 'none');fc.display('.db-config.'+db_type, 'block');document.querySelector('input[name="db_name"]').setAttribute('placeholder', db_type=='sqlite'?'esite.db':'esite');document.querySelector('input[name="baseurl"]').value=location.origin+location.pathname;}</script></body></html>
EOT;
		$init && $this->clear_cache(false);
		if( $init && !$is_request ){
			die($tpl);
		}elseif( $init && $is_request){
			# api数据库信息更新-由于后台未存储客户端数据库私密信息，需要在此处进行写入
			foreach(explode('|', 'db_type|db_host|db_name|db_username|db_password|baseurl') as $v){
				switch($v){
					case 'baseurl':
						$this->request[$v] = isset($this->request[$v])?rtrim($this->request[$v], '/').'/':'';
						break;
				}
				$this->request[$v] = isset($this->request[$v])?str_replace("'", '', trim($this->request[$v])):'';
				$api_content = str_replace("@@{$v}@@", $this->request[$v], $api_content);
				if( isset($this->{$v}) ){
					$this->{$v} = $this->request[$v];
				}
			}
			# 执行更新
			file_put_contents(__FILE__, $api_content);
			# 内容一致性校验
			if( md5(file_get_contents(__FILE__))!=md5($api_content) ){
				die($tpl);
			}
			# 进入首页
			$this->redirect($this->baseurl."/admin");die;
		}
	}

	# 模板演示模式：重定义数据库连接与模板路径
	# 请求示例：/?site_themes=default
	protected function tpl_demo_mode(){
		if( isset($_COOKIE['site_themes']) && $_COOKIE['site_themes'] && !isset($this->request['site_themes']) ){
			$this->request['site_themes'] = $_COOKIE['site_themes'];
		}
		if( isset($this->request['site_themes']) && $this->request['site_themes'] ){
			# 参数过滤
			$this->request['site_themes'] = preg_replace('@([a-z0-9_-]+)@i', '$1', $this->request['site_themes']);
			$filepath = APPPATH."/template/{$this->request['site_themes']}";
			if( file_exists($filepath) ){
				$this->clear_cache(false);
				$this->themes = $this->request['site_themes'];
				$this->page_template = APPPATH."/template/{$this->themes}";
				$this->request['flush'] = 'yes';
				setcookie('site_themes', $this->request['site_themes'], null, '/');
				# 模板开发模式：重定义数据库
				$template_db = "{$this->page_template}/esite.db";
				if( file_exists($template_db) ){
					$this->db_type = 'sqlite';
					$this->db_name = $template_db;
					self::$db = DB::init($this->db_type, $this->db_name, $this->db_host, $this->db_username, $this->db_password);
					$this->site_config();
				}
				return true;
			}
		}
		return false;
	}

	# 数据库初始化操作（数据库驱动回调，仅当数据表数量不合条件时回调此方法）
	public function db_struct_init($db=null){
		if( !$db ){
			return false;
		}
		$res = array_filter(array_map('trim',(array)file(APPPATH."/{$this->db_type}.sql")));
		$success = 0;
		$error_msgs = array();
		if( $res ){
			foreach($res as $k=>$v){
				$success += (int)$db->exec($v);
				if( $db->info->error_code ){
					$error_msgs[$k] = $db->info->error_msg;
				}
			}
			# 清理数据库缓存
			if( $success>0 ){
				$db->delCache();
			}
			return true;
		}
	}

	# 数据加密
	public function authencode($str=''){
		return $this->authcode($str, 'ENCODE', $this->auth_key, 0);
	}
	# 数据解密
	public function authdecode($str=''){
		return $this->authcode($str, 'DECODE', $this->auth_key, 0);
	}
	
	#数据加密与解密
	public function authcode($string='', $operation = 'ENCODE', $key = '', $expiry = 0) {
		# 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
		# 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
		# 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
		# 当此值为 0 时，则不产生随机密钥
		$ckey_length = 4;
	 
		# 密匙
		# 这里可以根据自己的需要修改
		$key = md5($key ? $key : $this->auth_key);
	 
		# 密匙a会参与加解密
		$keya = md5(substr($key, 0, 16));
		# 密匙b会用来做数据完整性验证
		$keyb = md5(substr($key, 16, 16));
		# 密匙c用于变化生成的密文
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
		# 参与运算的密匙
		$cryptkey = $keya.md5($keya.$keyc);
		$key_length = strlen($cryptkey);
		# 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
		# 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
		$string_length = strlen($string);
		$result = '';
		$box = range(0, 255);
		$rndkey = array();
		# 产生密匙簿
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
		# 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上并不会增加密文的强度
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		# 核心加解密部分
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			# 从密匙簿得出密匙进行异或，再转成字符
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
		if($operation == 'DECODE') {
			# substr($result, 0, 10) == 0 验证数据有效性
			# substr($result, 0, 10) - time() > 0 验证数据有效性
			# substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
			# 验证数据有效性，请看未加密明文的格式
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			# 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
			# 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	}
	
	###############################################
	###############################################
	###############################################
	
	public function del_cache(){
		#清除模板编译缓存
		array_map('unlink', glob("{$this->page_cache}/*.cache"));
		return true;
	}
	
	/**
	 * 根据url读取页面数据
	 */
	public function read_page(){
		#计算模板路径
		$tpl = $this->url2tpl($this->url);
		$tpl_filename = $this->page_template.$tpl;
		if(!file_exists($tpl_filename)){
			return false;
		}
		
		#计算缓存路径
		$cache_filename = $this->url2filename($this->url).'.cache';
		$cache_filename = $this->tpl_cache_filename($cache_filename);
		
		#请求刷新数据（文章更新后可以执行此操作）
		if(@$this->request['flush'] == 'yes'){
			@unlink($cache_filename);
		}
		
		if( file_exists($cache_filename) && (time()-filemtime($cache_filename)<$this->tpl_expire) ){
			#若缓存存在则直接加载文件数据
			$this->raw_data = file_get_contents($cache_filename);
			return true;
		}else{
			$this->raw_data = $this->parse_tpl($tpl_filename);
			# 当用户模板里没有设置禁用缓存，则自动缓存模板解析后的数据
			if( $this->raw_data && $this->use_cache() && (md5($this->raw_data)==md5(mb_convert_encoding($this->raw_data, 'UTF-8', 'UTF-8'))) ){
				@file_put_contents($cache_filename, $this->raw_data);
			}
			
			return true;
		}
		
		return false;
	}
	
	# 判断是否启用缓存
	public function use_cache(){
		return (!defined('USE_CACHE'))||(defined('USE_CACHE') && USE_CACHE!=false);
	}
	
	# 远程api接口
	public function server_api($api, $data=array()){
		$url = "{$this->server_baseurl}/{$api}";
		$snoopy = new Snoopy();
		$data['hostname'] = $_SERVER['SERVER_NAME'];
		$data['auth_key'] = $this->auth_key;
		$data['site_id'] = 0;
		$data['time'] = time();
		$res = $snoopy->submit($url, $data)->results;
		$res = $res?json_decode($res, 1):null;
		Msg::$message[] = $url;
		Msg::$message[] = $data;
		Msg::$message[] = $snoopy;
		return $res;
	}
	
	# 验证码校验
	function check_captcha($captcha='', $captcha_name='captcha_word'){
		$flag = isset($_SESSION[$captcha_name]) && strlen($_SESSION[$captcha_name])>0 && ($captcha==$_SESSION[$captcha_name]);
		if( $flag ){
			# 验证通过后销毁验证码，防止重复使用
			unset($_SESSION[$captcha_name]);
		}
		return $flag;
	}
	
	
	/**
	 * html修正
	 */
	public function html_fix($data){
		if(preg_match('@<head([^>]*)>@i', $data)){
			#页面基准路径修正
			$data = preg_match('@<base\s*[^>]*href[^>]+>@i', $data)?$data:preg_replace(array('@<head(\s[^>]*)>@i', '@<head(\s*)>@i'), sprintf('<head$1><base href="%s/" target="_top" >', $this->baseurl), $data);
		}
		$header_cache = function(){
			header("Cache-Control: public");
			header("Pragma: cache");
			header("Expires: ".gmdate("D, d M Y H:i:s", time() + (86400*30))." GMT");
		};
		$header_nocache = function(){
			header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			header("Pragma: no-cache");
		};
		#http response header修正
		if(preg_match('@(\.css)@i', $_SERVER['REQUEST_URI'])){
			$header_cache();
			header("Content-type:text/css; charset=utf-8");
		}elseif(preg_match('@(\.js)@i', $_SERVER['REQUEST_URI'])){
			$header_cache();
			header("Content-Type:application/javascript; charset=utf-8");
		}elseif(preg_match('@\.(jpg|jpeg|png|gif|ico)@i', $_SERVER['REQUEST_URI'])){
			$header_cache();
			header("Content-type: image/jpeg");
		}elseif(preg_match('@\.(json)@i', $_SERVER['REQUEST_URI'])){
			$header_cache();
			header("Content-type: application/json; charset=utf-8");
		}elseif(preg_match('@\.(pdf)@i', $_SERVER['REQUEST_URI'])){
			$header_cache();
			header("Content-type: application/pdf");
		}elseif(preg_match('@\.(xml)@i', $_SERVER['REQUEST_URI'])){
			$header_cache();
			header("Content-type: text/xml; charset=utf-8");
		}else{
			$header_nocache();
			header("Content-type:text/html; charset=utf-8");
			# 整站pv统计
			EsitePv::app()->update_pv('S0');
		}
		
		# 固定标签替换
		# PV数据统计：<!--count_pv:$sn-->
		/*if( preg_match('@<!--\s*count_pv:([AGPS][0-9]+)\s*-->@i', $data, $match) && isset($match[1]) ){
			$data = str_replace($match[0], EsitePv::app()->count_pv($match[1]), $data);
		}*/
		$data = preg_replace_callback('@<!--\s*count_pv:([AGPS][0-9]+)\s*-->@i', function($match){
			return EsitePv::app()->count_pv($match[1]);
		}, $data);
		
		return $data;
	}
	
	/**
	 * 转换url为文件名
	 * 缓存读写时候需要
	 */
	public function url2filename($url=null){
		$url = $url?$url:$this->url;
		$url = urldecode($url);
		$filename = md5($url);
		if( ($urls=parse_url($url)) && $this->cfg['url_route'] ){
			$filename = str_replace(array('/'), array('@'), "{$this->themes}@@".implode('-', $this->cfg['url_route']));
		}
		return $filename;
	}
	
	/**
	 * 转换url为模板文件名
	 */
	public function url2tpl($url=null){
		$url = $url?$url:$this->url;
		$url = urldecode($url);
		# 模板基准路径转换
		$url = isset($this->baseurls['path'])&&strlen($this->baseurls['path'])>0&&substr($url, 0, strlen($this->baseurls['path']))==$this->baseurls['path']?substr($url, strlen($this->baseurls['path'])):$url;
		$url = '/'.ltrim($url, '/');
		$urls = @parse_url($url);
		$filename = @$urls['path'];
		$filename = rtrim($filename, '/');
		$pathinfo = pathinfo($filename);
		$filename .= @$pathinfo['extension']==''?'/index.html':'';
		return $filename;
	}
	
	#模板缓存文件名
	public function tpl_cache_filename($tpl_filename){
		$filename = "{$this->page_cache}/{$tpl_filename}";
		return $filename;
	}
	
	public function debug($data, $die=false){
		if(@$this->request['debug']=='yes'){
			var_export($data);
			$die?die:'';
		}
	}
	
	#模板渲染
	public function parse_tpl($tpl_filename){
		# 校验模板文件是否存在，存储路径是否可写
		if( !file_exists($tpl_filename) ){
			return false;
		}
		# 非模板文件这直接输出
		if( !in_array(pathinfo($tpl_filename)['extension'], explode('|', $this->tpl_extension)) ){
			return file_get_contents($tpl_filename);
		}
		#初始化模板解析器
		$tplparse = Tplparse::app();
		$tplparse->setExtName('');	#模板扩展名(若在模板路径有填写，此处为空)
		$tplparse->setReal(true);	#实时编译
		$tplparse->setTmpDir(realpath(APPPATH).'/cache/');
		$tplparse->exec_php = true;	#允许解析php代码
		$tplparse->debug = false;		#是否开启调试
		$tplparse->tpl_extension = 'html|xml';		#是否开启调试
		#读取模板变量列表
		$tpl_vars = $tplparse->get_vars($tpl_filename);
		#进行模板变量赋值
		$tpl_vars = $this->render_var($tpl_vars);
		
		$this->debug($tpl_vars, false);
		
		if(!empty($tpl_vars)){
			#模板变量处理
			foreach ($tpl_vars as $k=>$v){
				$k = trim($k);
				if(!$k){
					continue;
				}
				$tplparse->assign($k, $v);
			}
		}
		
		#编译模板, 返回编译后的源代码
		return $tplparse->saveHtml( $tpl_filename, null, true );
	}
	
	#清除站点缓存
	public function clear_cache($die=true){
		$file_list = array_unique(array_merge(glob(APPPATH.'/cache/*.cache'), glob(APPPATH.'/cache/*/*.cache')));
		$num = 0;
		if($file_list){
			foreach($file_list as $k=>$v){
				if(!preg_match('@(\.htaccess)@', $v)){
					@unlink($v);
					$num ++;
				}
			}
		}
		$die==false or Msg::json_encode('yes', "success:{$num}");
	}
	
	# url解析规则：
	# 根据url数据格式约定，解析url里的信息，并重写当前url
	# 数据格式约定：
	# 文章页面：/article_category/[article_id|custom_filename]-article.html
	# 文章列表：/article_category/p1-articles.html
	# 商品页面：/product-category/[product_id|custom_filename]-product.html
	# 商品列表：/product-category/p1-products.html
	# 信息页面：[page_id|page_filename]-page.html
	# 模板固定名称：
	# 文章页面模板： article.html
	# 文章列表模板： articles.html
	# 商品页面模板： product.html
	# 商品列表模板： products.html
	# 信息页面模板： page.html
	# 数据格式：array( 'type'=>'[必须](article|articles|product|products|page)', 'id'=>'[可选]', 'page'=>'[可选]', 'pager_url'=>'可选, 分页url格式', 'pagesize'=>'可选', 'total_rows'=>'可选' )
	public function url_route($url=null){
		$url = $url?$url:$this->url;
		$url_paths = $this->get_url_paths($url);
		$this->request_uri = urldecode($url);
		$parse_url = parse_url($url);
		# URL映射转换：将自定义URL转为系统URL
		$url = $this->url_maps?str_ireplace(array_keys($this->url_maps), array_values($this->url_maps), $url):$url;
		$tpl_filename = $this->url2tpl($url);
		$this->cfg['url_route'] = array();
		
		Tplparse::app()->assign('seo_title', "{$this->cfg['site_info']['site_name']}");
		Tplparse::app()->assign('seo_description', "{$this->cfg['site_info']['site_description']}");
		Tplparse::app()->assign('seo_keywords', "{$this->cfg['site_info']['site_keywords']}");

		# 判断是否存在固定URI
		$uri_info = self::$db->getRow(sprintf("select * from uri_list where uri='%s'", self::$db->escapeString($tpl_filename)));
		$uri_pdata = null;
		switch($uri_info['source_table']){
			case 'article':
				$uri_pdata = $this->article_info($uri_info['source_id']);
				break;
			case 'product':
				$uri_pdata = $this->product_info($uri_info['source_id']);
				break;
			case 'page':
				$uri_pdata = $this->page_info($uri_info['source_id']);
				break;
		}
		if( $uri_info && $uri_pdata ){
			$this->cfg['url_route']['type'] = $uri_info['source_table'];
			$this->cfg['url_route']['id'] = $uri_info['source_id'];
			$this->cfg['url_route']['cat_id'] = $uri_pdata['cat_id'];
			$this->cfg['url_route']['page'] = 1;
		}
		#文章页面
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/([^\/]*)-(article\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
			&& ($uri_pdata=$this->article_info($match[1]))
			&& isset($uri_pdata['id'])
		){
			$this->cfg['url_route']['type'] = 'article';
			$this->cfg['url_route']['id'] = $uri_pdata['id'];
			$this->cfg['url_route']['cat_id'] = $uri_pdata['cat_id'];
			$this->cfg['url_route']['page'] = 1;
		}
		#文章列表-有分类
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/([^\/]*)\/p([0-9]+)-(articles\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
			&& @$match[2] 
			&& ($category_info=$this->article_category_info($match[1]))
			&& isset($category_info['cat_id'])
		){
			$this->cfg['url_route']['type'] = 'articles';
			$this->cfg['url_route']['id'] = $this->cfg['url_route']['cat_id'] = $category_info['cat_id'];
			$this->cfg['url_route']['page'] = @$match[2]>0?(int)$match[2]:1;
			Tplparse::app()->assign('seo_title', "{$category_info['title']} - {$this->cfg['site_info']['site_name']}");
			Tplparse::app()->assign('seo_description', "{$category_info['description']}");
			Tplparse::app()->assign('seo_keywords', "{$category_info['keywords']}");
		}
		#文章列表-无分类
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/p([0-9]+)-(articles\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
		){
			$this->cfg['url_route']['type'] = 'articles';
			$this->cfg['url_route']['id'] = $this->cfg['url_route']['cat_id'] = 0;
			$this->cfg['url_route']['page'] = @$match[1]>0?(int)$match[1]:1;
		}
		# 文章列表-按日期
		elseif( empty($this->cfg['url_route'])
			&& ($url_paths && isset($url_paths[0]) && is_numeric($url_paths[0]) && strlen($url_paths[0])==4 )
		){
			$this->cfg['url_route']['type'] = 'articles';
			$this->cfg['url_route']['id'] = $this->cfg['url_route']['cat_id'] = 0;
			$this->cfg['url_route']['page'] = 1;
			# 日期搜索：http://esite.likun.work/2016/p1-articles.html
			$this->cfg['url_route']['name'] = $url_paths[0];
			# 日期修正
			$url_paths[0] = $url_paths[0]>date('Y')?date('Y'):$url_paths[0];
			$url_paths[1] = ((!isset($url_paths[1]))||intval("{$url_paths[0]}{$url_paths[1]}")>date('Ym'))?'':$url_paths[1];
			$url_paths[2] = ((!isset($url_paths[2]))||$url_paths[2]>31)?'':$url_paths[2];
			# 日期搜索：http://esite.likun.work/2016/09/p1-articles.html
			if( ( isset($url_paths[1]) && is_numeric($url_paths[1]) && strlen($url_paths[1])==2 ) ){
				$this->cfg['url_route']['name'] = "{$url_paths[0]}-{$url_paths[1]}";
				# 日期搜索：http://esite.likun.work/2016/09/26/p1-articles.html
				if( ( @$url_paths[2] && is_numeric($url_paths[2]) && strlen($url_paths[2])==2 ) ){
					$this->cfg['url_route']['name'] = "{$url_paths[0]}-{$url_paths[1]}-{$url_paths[2]}";
				}
			}
			#读取分頁頁码
			if( preg_match('@p([0-9]+)-(articles\.html)@i', end($url_paths), $match) && isset($match[1]) ){
				$this->cfg['url_route']['page'] = (int)$match[1];
			}
		}
		#商品页面
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/([^\/]*)-(product\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
			&& ($uri_pdata=$this->product_info($match[1]))
			&& isset($uri_pdata['id'])
		){
			$this->cfg['url_route']['type'] = 'product';
			$this->cfg['url_route']['id'] = $uri_pdata['id'];
			$this->cfg['url_route']['cat_id'] = $uri_pdata['cat_id'];
			$this->cfg['url_route']['page'] = 1;
		}
		#商品列表-有分类
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/([^\/]*)\/p([0-9]+)-(products\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
			&& @$match[2] 
			&& ($category_info=$this->product_category_info($match[1]))
			&& isset($category_info['cat_id'])
		){
			$this->cfg['url_route']['type'] = 'products';
			$this->cfg['url_route']['id'] = $this->cfg['url_route']['cat_id'] = $category_info['cat_id'];
			$this->cfg['url_route']['page'] = @$match[2]>0?(int)$match[2]:1;
			Tplparse::app()->assign('seo_title', "{$category_info['title']} - {$this->cfg['site_info']['site_name']}");
			Tplparse::app()->assign('seo_description', "{$category_info['description']}");
			Tplparse::app()->assign('seo_keywords', "{$category_info['keywords']}");
		}
		#商品列表-无分类
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/p([0-9]+)-(products\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
		){
			$this->cfg['url_route']['type'] = 'products';
			$this->cfg['url_route']['id'] = $this->cfg['url_route']['cat_id'] = 0;
			$this->cfg['url_route']['page'] = @$match[1]>0?(int)$match[1]:1;
		}
		#单页面-有固定模板后缀(eg: /1-page.html | /about-page.html)
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/(.*)-(page\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
			&& ($uri_pdata=$this->page_info($match[1]))
			&& isset($uri_pdata['id'])
		){
			$this->cfg['url_route']['type'] = 'page';
			$this->cfg['url_route']['id'] = $uri_pdata['id'];
			$this->cfg['url_route']['cat_id'] = $uri_pdata['category'];
			$this->cfg['url_route']['page'] = 1;
		}
		#单页面-无固定模板后缀(eg: /1.html | /about.html)
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@\/(.*)(\.html)@i', $tpl_filename, $match)
			&& @$match[1] 
			&& ($uri_pdata=$this->page_info($match[1]))
			&& isset($uri_pdata['id'])
		){
			$this->cfg['url_route']['type'] = 'page';
			$this->cfg['url_route']['id'] = $uri_pdata['id'];
			$this->cfg['url_route']['cat_id'] = $uri_pdata['category'];
			$this->cfg['url_route']['page'] = 1;
		}
		#单页面-完全自定义文件名格式兼容(eg: /1 | /about | /category_name/about)
		elseif( empty($this->cfg['url_route'])
			&& preg_match('@^(.*)\/([^\/|^.]+)(\/index.html)?$@i', $tpl_filename, $match)
			&& @$match[2] 
			&& ($uri_pdata=$this->page_info($match[2]))
			&& isset($uri_pdata['id'])
		){
			$this->cfg['url_route']['type'] = 'page';
			$this->cfg['url_route']['id'] = $uri_pdata['id'];
			$this->cfg['url_route']['cat_id'] = $uri_pdata['category'];
			$this->cfg['url_route']['page'] = 1;
		}
		
		if( isset($this->cfg['url_route']['type']) ){
			switch($this->cfg['url_route']['type']){
				case 'article':
					EsitePv::app()->update_pv("A{$this->cfg['url_route']['id']}");
					Tplparse::app()->assign('seo_title', "{$uri_pdata['title']} - {$this->cfg['site_info']['site_name']}");
					Tplparse::app()->assign('seo_description', "{$uri_pdata['description']}");
					Tplparse::app()->assign('seo_keywords', "{$uri_pdata['keywords']}");
					# url检查
					$article_url = $this->get_article_url($this->cfg['url_route']['id']);
					$parse_url_ = parse_url($article_url);
					if( isset($parse_url['path']) && isset($parse_url_['path']) && urldecode($parse_url['path'])!=urldecode($parse_url_['path']) ){
						$this->redirect($article_url, 301);
					}
					break;
				case 'product':
					EsitePv::app()->update_pv("G{$this->cfg['url_route']['id']}");
					Tplparse::app()->assign('seo_title', "{$uri_pdata['title']} - {$this->cfg['site_info']['site_name']}");
					Tplparse::app()->assign('seo_description', "{$uri_pdata['description']}");
					Tplparse::app()->assign('seo_keywords', "{$uri_pdata['keywords']}");
					# url检查
					$product_url = $this->get_product_url($this->cfg['url_route']['id']);
					$parse_url_ = parse_url($product_url);
					if( isset($parse_url['path']) && isset($parse_url_['path']) && urldecode($parse_url['path'])!=urldecode($parse_url_['path']) ){
						$this->redirect($product_url, 301);
					}
					break;
				case 'page':
					EsitePv::app()->update_pv("P{$this->cfg['url_route']['id']}");
					Tplparse::app()->assign('seo_title', "{$uri_pdata['title']} - {$this->cfg['site_info']['site_name']}");
					Tplparse::app()->assign('seo_description', "{$uri_pdata['description']}");
					Tplparse::app()->assign('seo_keywords', "{$uri_pdata['keywords']}");
					# url检查
					$page_url = $this->get_product_url($this->cfg['url_route']['id']);
					$parse_url_ = parse_url($page_url);
					if( isset($parse_url['path']) && isset($parse_url_['path']) && urldecode($parse_url['path'])!=urldecode($parse_url_['path']) ){
						$this->redirect($page_url, 301);
					}
					break;
			}
		}
		
		#重写url，将url指向固定模板
		$this->url = isset($this->cfg['url_route']['type'])&&strlen($this->cfg['url_route']['type'])>0?"/{$this->cfg['url_route']['type']}.html":$this->url;
		# 调试信息
		if( $this->cfg['url_route'] ){
			$this->cfg['url_route']['url'] = $url;
			$this->cfg['url_route']['tpl_filename'] = $tpl_filename;
		}
		
		return $this->cfg['url_route'];
	}
	
	# 自定义分页方法
	public function pagenation($cfg=array()){
		#计算总页数
		$cfg['pagesize'] = $cfg['pagesize']>0?$cfg['pagesize']:10;
		$total_page = ceil($cfg['total_rows'] / $cfg['pagesize']);
		$pagers = [
			'tag_start'=>isset($cfg['tag_start'])?$cfg['tag_start']:'<ul>',	# 起始标签
			'tag_end'=>isset($cfg['tag_end'])?$cfg['tag_end']:'</ul>',	# 结束标签
			'page_first'=>isset($cfg['page_first'])?$cfg['page_first']:'<li><a href="%s">首页</a></li>',	# 首页模板
			'page_str'=>isset($cfg['page_str'])?$cfg['page_str']:'<li class="%s"><a href="%s">%s</a></li>',	# 分页模板
			'page_last'=>isset($cfg['page_last'])?$cfg['page_last']:'<li><a href="%s">末页</a></li>',	# 尾页模板
			'page'=>$cfg['page'],	# 当前页码
			'page_fix'=>$cfg['page_fix'],	# 当前页码
			'page_total'=>$total_page,	# 总页码
			'pagesize'=>$cfg['pagesize'],	# 每页显示多少数据
			'total_rows'=>$cfg['total_rows'],	# 总记录数
			'url'=>$cfg['url'],
			'current_page_calss'=>isset($cfg['current_page_calss'])?$cfg['current_page_calss']:'',	# 当前页css样式名称
			'summary_str'=>isset($cfg['summary_str'])?$cfg['summary_str']:'<span class="paging-summary">共%s条，第%s/%s页</span>',
			'code'=>'',	# 存储完整数据
			'summary_code'=>'',	# 汇总信息
		];

		$code = '';
		if($pagers['page_total']>1){
			$pager = array();
			$pager[] = $pagers['tag_start'];
			#首页
			$pager[] = sprintf($pagers['page_first'], sprintf($pagers['url'], 1));
			#页码列表
			for($page_num=$pagers['page']-$pagers['page_fix']; $page_num<$pagers['page']+$pagers['page_fix']; $page_num++){
				if($page_num<1 || $page_num>$pagers['page_total']){
					continue;
				}
				$pager[] = sprintf( $pagers['page_str'], ( $page_num==$pagers['page']?$pagers['current_page_calss']:'' ), sprintf($pagers['url'], $page_num), $page_num );
			}
			#末页
			$pager[] = sprintf( $pagers['page_last'], sprintf($pagers['url'], $pagers['page_total']));
			$pager[] = $pagers['tag_end'];
			$code = implode("\n", $pager);
			unset($pager);
		}
		$pagers['code'] = $code;
		$pagers['summary_code'] = sprintf($pagers['summary_str'], intval($cfg['total_rows']), intval($cfg['page']), intval($total_page));

		return $pagers;
	}

	public function set_languages($data=array()){
		$this->languages = $data;
	}
	
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	###### 分 界 线 ###### 分 界 线 ###### 分 界 线 ######
	
	#模板全局变量
	public $global_vars = array();
	#当前实例中禁止调用的公共方法
	public $disable_vars = array(
		'app', 'run'
	);
	/**
	 * 进行模板变量渲染
	 * @param $vars
	 */
	public function render_var($vars=array()){
		if(is_array($vars)){
			$vars = array_merge($vars, (array)$this->global_vars);
			$tmp = array();
			foreach ($vars as $k=>$v){
				if( !in_array($v, array_map('strtolower', $this->disable_vars)) && is_callable(array(EsiteApp::app(), $v)) ){
					$time_start = time();
					$tmp[$v] = EsiteApp::app()->{$v}();
					$time_use = time() - $time_start;
				}
			}
			$vars = $tmp;
			unset($tmp);
		}
		return $vars;
	}
	
	#读取站点配置信息
	public function site_config(){
		$sql = "select name,value from config ";
		$res = self::$db->getAll($sql);
		if($res){
			foreach($res as $k=>$v){
				$this->cfg['site_info'][$v['name']] = $v['value'];
			}
		}
		return $this->cfg['site_info'];
	}
	
	# 生成URL映射表
	public function set_url_maps(){
		$sql = "select * from url_maps";
		$this->url_maps = self::$db->getAll($sql);
		$this->url_maps = $this->url_maps?array_combine(array_column($this->url_maps, 'url_new'), array_column($this->url_maps, 'url_sys')):$this->url_maps;
		return $this->url_maps;
	}
	
	#文章数据列表
	public function articles_data($pagesize=10, $order_by='sort', $limit=true){
		$pagesize = (int)$pagesize;
		$pagesize = $pagesize<1?0:$pagesize;
		$order_by = in_array($order_by, array('sort', 'cat_id', 'pv', 'edit_time'))?$order_by:'sort';
		$this->cfg['url_route']['pagesize'] = $pagesize;
		$offset = isset($this->cfg['url_route']['page'])&&$this->cfg['url_route']['page']>1&&$limit?($this->cfg['url_route']['page']-1)*$pagesize:0;
		$where = '1';
		$where .= isset($this->cfg['url_route']['cat_id'])&&($this->cfg['url_route']['type']=='articles')&&$this->cfg['url_route']['cat_id']?sprintf(" and c.cat_id='%s' ", $this->cfg['url_route']['cat_id']):'';
		# 关键词搜索
		if( isset($this->request['q'])&&strlen(trim($this->request['q']))>0 ){
			$this->request['q'] = self::$db->escapeString(trim($this->request['q']));
			$where .= " and (a.filename like '%{$this->request['q']}%' or a.title like '%{$this->request['q']}%' or a.description like '%{$this->request['q']}%' or a.keywords like '%{$this->request['q']}%' or c.unique_id like '%{$this->request['q']}%' or c.cat_name like '%{$this->request['q']}%' or c.title like '%{$this->request['q']}%' or c.keywords like '%{$this->request['q']}%') ";
		}
		# 日期搜索
		if( isset($this->cfg['url_route']['name']) && $this->cfg['url_route']['name'] ){
			$where .= ($this->db_type=='sqlite')?" and date(a.edit_time, 'unixepoch') like '{$this->cfg['url_route']['name']}%' ":" and from_unixtime(a.edit_time, '%Y-%m-%d') like '{$this->cfg['url_route']['name']}%' ";
		}
		
		$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category from article as a left join article_category as c on a.cat_id=c.cat_id where {$where} order by {$order_by} desc limit {$offset},{$pagesize} ";
		$res = self::$db->getAll($sql);
		$sql = "select count(*) as total from article as a left join article_category as c on a.cat_id=c.cat_id where {$where} ";
		$res_count = self::$db->getRow($sql);
		$this->cfg['url_route']['total_rows'] = isset($res_count['total'])?$res_count['total']:0;
		
		if($res){
			foreach($res as $k=>$v){
				$res[$k] = $this->format_article_data($v);
			}
		}
		return $res;
	}
	
	# 文章列表-分类搜索
	public function getArtielesByCatId($cat_id=0, $order_by='', $pagesize=10){
		$cat_id = intval($cat_id);
		$order_by = $order_by?$order_by:"a.id desc";
		$pagesize = intval($pagesize);
		# 多级分类搜索
		if( $cat_id>0 ){
			$categorys = $this->get_article_categorys();
			$cat_ids = array_unique(array_column(Helper::sonTree($categorys, $cat_id, 1, 'cat_id', 'parent_id'), 'cat_id'));
			$cat_ids[] = $cat_id;
			$cat_ids = implode(',', $cat_ids);
		}else{
			$cat_ids = $cat_id;
		}
		$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category,u.uri from article as a left join article_category as c on a.cat_id=c.cat_id left join uri_list as u on (u.source_table='article' and u.source_id=a.id) where a.cat_id in ({$cat_ids}) order by {$order_by} limit 0,{$pagesize}";
		$articles = Esite::$db->getAll($sql);
		$articles = $articles?array_map(array(Esite::app(), 'format_article_data'), $articles):$articles;
		if($articles){
			foreach($articles as $k=>$v){
				$articles[$k] = $this->format_article_data($v);
			}
		}
		return $articles;
	}
	
	#读取文章分类信息
	public function article_category_info($unique_id=''){
		$sql = "select * from article_category where unique_id='{$unique_id}' ";
		return self::$db->getRow($sql);
	}
	
	#读取文章信息
	public function article_info($key=''){
		$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category,u.uri from article as a left join article_category as c on a.cat_id=c.cat_id left join uri_list as u on (u.source_table='article' and u.source_id=a.id) where (a.filename='{$key}' or a.id='{$key}') ";
		return self::$db->getRow($sql);
	}
	
	#读取商品分类信息
	public function product_category_info($unique_id=''){
		$sql = "select * from product_category where (unique_id='{$unique_id}' or cat_id='{$unique_id}') ";
		return self::$db->getRow($sql);
	}
	
	#读取商品信息
	public function product_info($key=''){
		$sql = "select p.*,c.unique_id as cat_unique_id,u.uri from product as p left join product_category c on p.cat_id=c.cat_id left join uri_list as u on (u.source_table='product' and u.source_id=p.id) where (p.filename='{$key}' or p.id='{$key}') ";
		$product_info = self::$db->getRow($sql);
		$product_info = $product_info?$this->format_product_data($product_info):$product_info;
		return $product_info;
	}
	
	# 按商品分类id读取商品(多个cat_id采用逗号分隔)
	public function products_data($cat_ids, $page=1, $pagesize=10){
		$page = $page>0?ceil($page):1;
		$limit_start = ($page-1)*$pagesize;
		$pagesize = ceil($pagesize);
		$sql = "select p.*,c.unique_id as cat_unique_id,u.uri from product as p left join product_category c on p.cat_id=c.cat_id left join uri_list as u on (u.source_table='product' and u.source_id=p.id) where p.cat_id in ({$cat_ids}) order by p.sort desc,p.edit_time desc limit {$limit_start},{$pagesize} ";
		$res = self::$db->getAll($sql);
		if( $res ){
			foreach($res as $k=>$v){
				$res[$k] = $this->format_product_data($v);
			}
		}
		return $res;
	}
	
	#读取单页信息
	public function page_info($key=''){
		$sql = "select p.*,p.filename as unique_id,p.category as cat_id,u.uri from page as p left join uri_list as u on (u.source_table='page' and u.source_id=p.id) where (p.filename='{$key}' or p.id='{$key}') ";
		return self::$db->getRow($sql);
	}
	
	#文章url
	#	/article_category/[article_id|custom_filename]-article.html
	public function get_article_url($data){
		$data = is_array($data)&&isset($data['id'])?intval($data['id']):intval($data);
		$data = self::$db->getRow("select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category,u.uri from article as a left join article_category as c on a.cat_id=c.cat_id left join uri_list as u on (u.source_table='article' and u.source_id=a.id) where a.id='{$data}' ");
		if(!$data){
			return '';
		}
		if( !(isset($data['uri']) && $data['uri']) ){
			$url = '/';
			$url .= @$data['cat_unique_id']?"{$data['cat_unique_id']}/":'';
			$url .= @$data['filename']?"{$data['filename']}-article.html":"{$data['id']}-article.html";
		}else{
			$url = $data['uri'];
		}
		return $url;
	}
	
	#文章(分类)列表url
	#	/article_category/p1-articles.html
	public function get_articles_url($category='', $page=1){
		$page = (int)$page;
		$page = $page>1?$page:1;
		$url = '/';
		$url .= @$category?"{$category}/":'';
		$url .= "p{$page}-articles.html";
		return $url;
	}
	
	#商品url
	#	/product-category/[product_id|custom_filename]-product.html
	public function get_product_url($data){
		$data = is_array($data)&&isset($data['id'])?intval($data['id']):intval($data);
		if( is_numeric($data) ){
			$data = self::$db->getRow("select p.*,c.unique_id as cat_unique_id,u.uri from product as p left join product_category c on p.cat_id=c.cat_id left join uri_list as u on (u.source_table='product' and u.source_id=p.id) where p.id='{$data}'");
		}
		if(!$data){
			return '';
		}
		if( !(isset($data['uri']) && $data['uri']) ){
			$url = '/';
			$url .= @$data['cat_unique_id']?"{$data['cat_unique_id']}/":'';
			$url .= @$data['filename']?"{$data['filename']}-product.html":"{$data['id']}-product.html";
		}else{
			$url = $data['uri'];
		}
		return $url;
	}
	
	#商品(分类)列表url
	#	/product-category/p1-products.html
	public function get_products_url($category, $page=1){
		$page = (int)$page;
		$page = $page>1?$page:1;
		$url = '/';
		$url .= $category?"{$category}/":'';
		$url .= "p{$page}-products.html";
		return $url;
	}
	
	# 单页面url：[page_id|page_filename]-page.html
	public function get_page_url($data){
		$data = is_array($data)&&isset($data['id'])?intval($data['id']):intval($data);
		if( is_numeric($data) ){
			$data = self::$db->getRow("select p.*,p.filename as unique_id,p.category as cat_id,u.uri from page as p left join uri_list as u on (u.source_table='page' and u.source_id=p.id) where p.id='{$data}'");
		}
		if(!$data){
			return '';
		}
		if( !(isset($data['uri']) && $data['uri']) ){
			$url = '/';
			$url .= @$data['unique_id']?"{$data['unique_id']}-page.html":"{$data['id']}-page.html";
		}else{
			$url = $data['uri'];
		}
		return $url;
	}
	
	public function uri_update($source_table,$source_id,$uri){
		$uri = trim($uri);
		# uri不存在则自动生成
		if( strlen($uri)<2 ){
			switch($source_table){
				case 'article':
					$uri = $this->get_article_url($source_id);
					break;
				case 'product':
					$uri = $this->get_product_url($source_id);
					break;
				case 'page':
					$uri = $this->get_page_url($source_id);
					break;
			}
		}
		$uri = rtrim($uri, '/');
		$uri = self::$db->escapeString($uri);
		# 判断uri是否重复
		$data = self::$db->getRow(sprintf("select * from uri_list where uri='%s' and (source_table!='{$source_table}' or source_id!='{$source_id}')", $uri));
		if( $data ){
			return false;
		}
		self::$db->replace('uri_list', array(
			'source_table'=>$source_table,
			'source_id'=>$source_id,
			'uri'=>$uri
		));
		return true;
	}
	
	# 单页分类列表
	public function get_page_categorys(){
		$sql = "select id as cat_id,category,parent_id,filename,filename as unique_id,page_name as cat_name,title,keywords,description from page ";
		$res = self::$db->getAll($sql);
		if($res){
			$res = array_combine(array_column($res, 'cat_id'), $res);
			foreach($res as $k=>$v){
				$res[$k]['category_url'] = $this->get_page_url($v);
			}
		}
		return $res;
	}
	
	# 文章分类列表
	public function get_article_categorys($relation=true, $split_str=',', $categorys=array()){
		$sql = "select * from article_category order by category asc,parent_id asc,sort asc";
		$category_all = self::$db->getAll($sql);
		$category_all = is_array($category_all)&&$category_all?array_combine(array_column($category_all, 'cat_id'), $category_all):array();
		$categorys = $categorys?array_combine(array_column($categorys, 'cat_id'), $categorys):$category_all;
		if( $categorys ){
			foreach($categorys as $k=>$v){
				if( $relation ){
					$categorys[$k]['relation'] = Helper::fatherTree($category_all, $v['cat_id'], 'cat_id', 'parent_id');
					$categorys[$k]['relation_str'] = $categorys[$k]['relation']?implode($split_str, array_column($categorys[$k]['relation'], 'cat_name')):'';
					$categorys[$k]['relation_ids'] = $categorys[$k]['relation']?implode($split_str, array_column($categorys[$k]['relation'], 'cat_id')):'';
				}
				$categorys[$k]['category_url'] = $this->get_articles_url($v['unique_id']);
			}
		}
		return $categorys;
	}
	
	# 根据分类ID读取分类信息
	public function get_article_category($id=0){
		$id = intval($id);
		$all = $this->get_article_categorys();
		return isset($all[$id])?$all[$id]:array();
	}
	
	# 商品分类列表
	public function get_product_categorys($relation=true, $split_str=',', $categorys=array()){
		$sql = "select * from product_category order by category asc,parent_id asc,sort asc";
		$category_all = self::$db->getAll($sql);
		$category_all = is_array($category_all)&&$category_all?array_combine(array_column($category_all, 'cat_id'), $category_all):array();
		$categorys = $categorys?array_combine(array_column($categorys, 'cat_id'), $categorys):$category_all;
		if( $categorys ){
			foreach($categorys as $k=>$v){
				if( $relation ){
					$categorys[$k]['relation'] = Helper::fatherTree($category_all, $v['cat_id'], 'cat_id', 'parent_id');
					$categorys[$k]['relation_str'] = $categorys[$k]['relation']?implode($split_str, array_column($categorys[$k]['relation'], 'cat_name')):'';
					$categorys[$k]['relation_ids'] = $categorys[$k]['relation']?implode($split_str, array_column($categorys[$k]['relation'], 'cat_id')):'';
				}
				$categorys[$k]['category_url'] = $this->get_products_url($v['unique_id']);
			}
		}
		return $categorys;
	}
	
	#读取当前文章上一篇相关文章
	public function get_last_article($article_data){
		$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category from article as a left join article_category as c on a.cat_id=c.cat_id where a.cat_id='{$article_data['cat_id']}' and a.id<'{$article_data['id']}' order by a.id desc limit 0,1 ";
		$data = self::$db->getRow($sql);
		if(!$data){
			$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category from article as a left join article_category as c on a.cat_id=c.cat_id where a.id<'{$data['id']}' order by a.id desc limit 0,1 ";
			$data = self::$db->getRow($sql);
		}
		if($data){
			$data['article_url'] = $this->get_article_url($data);
			$data['category_url'] = $this->get_articles_url($data['cat_unique_id']);
		}
		return $data;
	}
	
	#读取当前文章下一篇相关文章
	public function get_next_article($article_data){
		$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category from article as a left join article_category as c on a.cat_id=c.cat_id where a.cat_id='{$article_data['cat_id']}' and a.id>'{$article_data['id']}' order by a.id asc limit 0,1 ";
		$data = self::$db->getRow($sql);
		if(!$data){
			$sql = "select a.*, c.unique_id as cat_unique_id,c.cat_name,c.category from article as a left join article_category as c on a.cat_id=c.cat_id where a.id>'{$data['id']}' order by a.id asc limit 0,1 ";
			$data = self::$db->getRow($sql);
		}
		if($data){
			$data['article_url'] = $this->get_article_url($data);
			$data['category_url'] = $this->get_articles_url($data['cat_unique_id']);
		}
		return $data;
	}
	
	#商品数据格式化
	public function format_product_data($data){
		if( $data ){
			$data = array_map('stripslashes', $data);
			$data['product_url'] = $this->get_product_url($data);
			$data['category_url'] = $this->get_products_url($data['cat_unique_id']);
			$data['image'] = isset($data['image'])&&$data['image']?(array)explode(',', $data['image']):[];
		}
		return $data;
	}
	
	#文章数据格式化
	public function format_article_data($data){
		if( $data ){
			$data = array_map('stripslashes', $data);
			$data['article_url'] = $this->get_article_url($data);
			$data['category_url'] = $this->get_articles_url($data['cat_unique_id']);
			$data['category_info'] = $this->get_article_category($data['cat_id']);
			$data['last_article'] = $this->get_last_article($data);
			$data['next_article'] = $this->get_next_article($data);
			# seo描述
			$data['description'] = strlen($data['description'])>4?(Helper::compileHtml($data['description'])):(Helper::sub_str((Helper::compileHtml($data['content'])), 320, '...'));
			# 内容描述
			$data['content_description'] = Helper::sub_str((Helper::compileHtml($data['content'])), 320, '...');
		}
		return $data;
	}
	
	#单页数据格式化
	public function format_page_data($data){
		#格式化当前页url
		$data['category_url'] = $this->get_page_url($data);
		return $data;
	}
	
	# 获取商品价格(后期根据此处扩展库存价格读取与折扣功能)
	public function product_price($product_id=0){
		$product_id = intval($product_id);
		$product_info = $this->product_info($product_id);
		$price = isset($product_info['price'])?round($product_info['price'], 3):0;
		return $price;
	}
	
	# 购物车：增加商品、更新商品数量、移除商品
	# 	添加商品：product_id>0 && cart_id<1
	# 	更新数量：product_id<1 && cart_id>0 && numbers>0
	# 	移除商品：product_id<1 && cart_id>0 && numbers<=0
	public function cart(){
		self::$db->expire_time = 0;
		# 商品ID
		$this->request['product_id'] = isset($this->request['product_id'])?intval($this->request['product_id']):0;
		# 商品数量：大于等于0表示更新; 小于0表示移除;
		$this->request['numbers'] = isset($this->request['numbers'])?intval($this->request['numbers']):0;
		# 商品参数：['ext1'=>'','ext2'=>'','ext3'=>'',]
		$this->request['exts'] = isset($this->request['exts'])&&is_array($this->request['exts'])?$this->request['exts']:[];
		# 属性排序
		ksort($this->request['exts']);
		# 购物车数据编号：大于0表示更新; 等于0表示新增;
		$this->request['cart_id'] = isset($this->request['cart_id'])?intval($this->request['cart_id']):0;
		# 购物车：添加商品（不可传递购物车ID）
		if( $this->request['product_id']>0 && $this->request['cart_id']<1 ){
			$exts = self::$db->escapeString(json_encode($this->request['exts']));
			$price = $this->product_price($this->request['product_id']);
			# 查询购物车商品: 当前用户 + 指定商品 + 相同属性
			$sql = "select * from cart where user_id='{$_SESSION['user_id']}' and product_id='{$this->request['product_id']}' and exts='{$exts}' limit 0,1 ";
			$cart_product = self::$db->getRow($sql);
			if( isset($cart_product['id']) && $cart_product['id']>0 ){
				# 更新商品数量 = 新增数量 + 原有数量
				$numbers = $this->request['numbers']<2?($this->request['numbers']+$cart_product['numbers']):$this->request['numbers'];
				self::$db->update('cart', [
					'numbers'=>$numbers,
					'price'=>$price,
					'money'=>round(($price * ($numbers)), 3),
					'utime'=>time(),
				], " id='{$cart_product['id']}' ");
			}else{
				# 添加商品
				self::$db->insert('cart', [
					'user_id'=>$_SESSION['user_id'],
					'product_id'=>$this->request['product_id'],
					'numbers'=>$this->request['numbers'],
					'exts'=>self::$db->escapeString(json_encode($this->request['exts'])),
					'price'=>$price,
					'money'=>round(($price * $this->request['numbers']), 3),
					'utime'=>time(),
				]);
			}
		}
		# 购物车：更新/删除商品（不可传递商品ID）
		elseif( $this->request['product_id']<1 && $this->request['cart_id']>0 ){
			# 限制用户仅能更新自己的购物车
			$unique_where = " user_id='{$_SESSION['user_id']}' and id='{$this->request['cart_id']}' ";
			# 移除商品
			if( $this->request['numbers']<=0 ){
				self::$db->query("delete from cart where {$unique_where} ");
			}else{
				# 查询购物车商品
				$sql = "select * from cart where {$unique_where} limit 0,1 ";
				$cart_product = self::$db->getRow($sql);
				if( isset($cart_product['id']) && $cart_product['id']>0 ){
					$price = $this->product_price($cart_product['product_id']);
					# 更新商品数量
					$numbers = $this->request['numbers'];
					self::$db->update('cart', [
						'numbers'=>$numbers,
						'price'=>$price,
						'money'=>round(($price * ($numbers)), 3),
						'utime'=>time(),
					], " id='{$cart_product['id']}' ");
				}
			}
		}
		
		# 读取用户购物车数据
		$carts = [];
		$carts['products'] = self::$db->getAll("select * from cart where user_id='{$_SESSION['user_id']}' ");
		if( $carts['products'] ){
			$products = self::$db->getAll(sprintf("select * from product where id in (%s) ", implode(',', array_unique(array_column($carts['products'], 'product_id')))));
			$products = $products?array_combine(array_column($products, 'id'), $products):$products;
			if( $products ){
				$products_category = $this->get_product_categorys();
				# 补全分类
				foreach($products as $k=>$v){
					$products[$k]['cat_unique_id'] = $products_category[$v['cat_id']]['unique_id'];
				}
				# 商品数据补全
				$products = $products?array_map([$this, 'format_product_data'], $products):$products;
				foreach($carts['products'] as $k=>$v){
					unset($products[$v['product_id']]['content']);
					$carts['products'][$k]['info'] = isset($products[$v['product_id']])?$products[$v['product_id']]:[];
				}
			}
		}
		$carts['numbers'] = count($carts['products']);
		$carts['money'] = $carts['numbers']>0?array_sum(array_column($carts['products'], 'money')):0;
		return $carts;
	}
	
	# 提交数据到邮件队列
	public function mailbox_add($data=array()){
		$data['mail_title'] = isset($data['mail_title'])?$data['mail_title']:'';
		$data['mail_body'] = isset($data['mail_body'])?$data['mail_body']:'';
		$data['mail_to'] = isset($data['mail_to'])&&Helper::isEmail($data['mail_to'])?$data['mail_to']:$this->cfg['site_info']['email'];
		$data['mail_from'] = isset($this->cfg['site_info']['mail_username'])?$this->cfg['site_info']['mail_username']:$this->cfg['site_info']['email'];
		$data = array_map('trim', $data);
		if( empty($data['mail_title']) || empty($data['mail_body']) || empty($data['mail_to']) ){
			return false;
		}
		$data['user_ip'] = Helper::real_ip();
		$data['add_time'] = time();
		self::$db->insert('mailbox', $data, false);
		# 处理邮件队列最新一条记录
		$this->mailbox_send(1);
		return true;
	}
	
	# 处理邮件队列(由每分钟定时任务受理)
	# send_time <0 表示发送失败；
	# send_time =0 表示等待发送；
	# send_time >0 表示发送成功；
	# err_count >10 表示发送失败；
	# sending = 0 表示未发送；
	# sending = 1 表示发送中；
	# sending = 2 表示已发送；
	public function mailbox_send($pagesize=60){
		$pagesize = ($pagesize>60||$pagesize<1)?10:$pagesize;
		$time = time();
		# 超时处理：超过一个小时未处理的则直接不受理
		$sql = "update mailbox set err_count=100,send_time=-1 where {$time}>add_time+3600";
		self::$db->query($sql);
		# 队列处理
		$sql = "select * from mailbox where send_time<1 and sending=0 and err_count<10 order by add_time desc limit 0,{$pagesize}";
		$data = self::$db->getAll($sql);
		if( count($data)>0 ){
			# 更新队列状态为发送中
			$sql = sprintf("update mailbox set sending=1 where id in (%s)", implode(',', array_column($data, 'id')));
			self::$db->query($sql);
			foreach($data as $k=>$v){
				$flag = $this->sendmail($v['mail_to'], $v['mail_title'], $v['mail_body'], $v['mail_from']);
				if( $flag ){
					$sql = "update mailbox set send_time={$time},sending=2 where id='{$v['id']}' ";
					self::$db->query($sql);
				}else{
					$sql = "update mailbox set err_count=err_count+1,sending=0 where id='{$v['id']}' ";
					self::$db->query($sql);
				}
			}
		}
		return true;
	}
	
	public function sendmail($to,$title,$body,$from=''){
		if( !Helper::isEmail($to) ){
			return false;
		}
		$mail = new SocketSendMail();
		$mail->setServer(
			$this->cfg['site_info']['mail_host'],
			$this->cfg['site_info']['mail_username'],
			$this->cfg['site_info']['mail_password'],
			$this->cfg['site_info']['mail_port']
		);
		$mail->setFrom($from?$from:$this->cfg['site_info']['mail_username']);
		$mail->setReceiver($to);
		$mail->setMailInfo($title, $body);
		return $mail->sendMail();
	}
	
	public function redirect($url='', $http_code='301'){
		$url = $url?$url:$this->baseurl;
		header("HTTP/1.1 {$http_code} Moved Permanently");
		header("Location: {$url}");die;
	}
	
	# 站点设定
	public function setConfig($name, $data=array()){
		return self::$db->update('config', $data, " name='{$name}' ");
	}
	
	public function get_url_paths($url){
		$url = urldecode(urldecode($url));
		$url_parse = @parse_url($url);
		$url_paths = explode('/', @$url_parse['path']);
		$url_paths = array_map('trim', (array)$url_paths);
		$url_paths = array_filter((array)$url_paths);
		$url_paths = array_values($url_paths);
		return $url_paths;
	}

	# 读取站点每日文章发布数量
	public function countArticleBayDay(){
		if( $this->db_type=='sqlite' ){
			$sql = "SELECT date(edit_time, 'unixepoch') as day, count(1) as total FROM article where 1 group by date(edit_time, 'unixepoch')";
		}else{
			$sql = "SELECT from_unixtime(edit_time, '%Y-%m-%d') as day, count(1) as total FROM article where 1 group by from_unixtime(edit_time, '%Y-%m-%d')";
		}
		$res = self::$db->getAll($sql);
		$res = $res?array_combine(array_column($res, 'day'), $res):$res;
		return $res;
	} 
	
}
# == Esite.php code end ==

# == EsiteApp.php code start ==

class EsiteApp{
/**
 * 应用入口
 * 负责系统业务处理
 * 业务模块为公共类型(public)，可以根据需要扩展重写
 * 控制器权限约定：模型中存在【run】方法，则表示接受http访问
 * 调用示例：
 * 		Esite::app()->run();
 */
	
	#对象单例模式唯一实例静态成员
	private static $_instance;
	
	private function __construct(){}
	
	private function __clone(){}
	
	#返回唯一实例
	public static function app(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	#处理用户请求
	public function run(){
		if( preg_match('@^([a-z0-9_]+)$@i', Esite::app()->request['act']) && is_callable(array($this, Esite::app()->request['act'])) ){
			Msg::$show_serverInfo = false;
			$this->{Esite::app()->request['act']}();
		}elseif( strpos(Esite::app()->request['act'], '/') && count($act=explode('/', Esite::app()->request['act']))==2 && is_callable($act) ){
			Msg::$show_serverInfo = true;
			$act[0]::run()->{$act[1]}();
		}else{
			Msg::$show_serverInfo = false;
			if(Esite::app()->read_page(Esite::app()->request)){
				Esite::app()->tpl_rtime = round((Helper::microtime_float() - Esite::app()->tpl_stime), 6);
				Esite::app()->raw_data = Esite::app()->html_fix(Esite::app()->raw_data);
				Esite::app()->raw_data = str_ireplace('</html>', sprintf('</html><!-- 程序耗时：%s秒 -->', Esite::app()->tpl_rtime), Esite::app()->raw_data);
				echo Esite::app()->raw_data;
			}else{
				#页面不存在
				if(file_exists(Esite::app()->page_template.'/404.html')){
					#返回404
					echo file_get_contents(Esite::app()->page_template.'/404.html');
				}elseif(file_exists(Esite::app()->page_template.'/index.html')){
					#若存在默认首页,则返回首页
					header("Location: /index.html", true, 301);die;
				}else{
					#返回错误消息
					echo 'Request parameter error.';
				}
			}
		}
	}
	
	#系统管理界面-登录
	#授权流程：
	#	用户请求登录系统 http://yourdomain.com/admin/login.html
	#	页面通过iframe加载系统登录界面
	#	进行用户名、密码、邮箱校验码验证
	public function admin(){
		Esite::app()->redirect(Esite::app()->baseurl.'/admin/login.html');
	}
	
	# 文章数据列表-默认
	public function articles_data($pagesize=10, $order_by=''){
		return Esite::app()->articles_data($pagesize, $order_by);
	}
	# 文章数据列表-热门
	public function articles_hot($pagesize=10){
		return Esite::app()->articles_data($pagesize, 'pv', false);
	}
	# 文章数据列表-最新
	public function articles_new($pagesize=10){
		return Esite::app()->articles_data($pagesize, 'edit_time', false);
	}
	# 文章分类搜索
	public function artielesByCatId($cat_id=0, $order_by='', $pagesize=10){
		return Esite::app()->getArtielesByCatId($cat_id, $order_by, $pagesize);
	}
	
	#读取文章数据
	public function article_data($key=''){
		$key = $key?$key:(isset(Esite::app()->cfg['url_route']['id'])?Esite::app()->cfg['url_route']['id']:$key);
		$article_data = Esite::app()->article_info($key);
		$article_data = Esite::app()->format_article_data($article_data);
		if( isset($article_data['category_info']) && $article_data['category_info'] ){
			$article_data['category_info'] = array_merge($article_data['category_info'], $this->category_info($article_data['cat_id']));
		}
		return $article_data;
	}
	
	#读取所有文章分类
	public function article_categorys(){
		return Helper::genTree(Esite::app()->get_article_categorys(), 'cat_id', 'parent_id', 'children');
	}

	# 文章日历
	public function article_calendar($year='', $month=''){
		$dates = isset(Esite::app()->cfg['url_route']['name'])?Esite::app()->cfg['url_route']['name']:'';
		$dates = explode('-', $dates);
		$year = is_numeric($year)&&intval($year)>0?str_pad($year, 4, 0, STR_PAD_LEFT):(isset($dates[0])&&intval($dates[0])>0?str_pad($dates[0], 4, 0, STR_PAD_LEFT):date('Y'));
		$month = is_numeric($month)&&intval($month)>0?str_pad($month, 2, 0, STR_PAD_LEFT):(isset($dates[1])&&intval($dates[1])>0?str_pad($dates[1], 2, 0, STR_PAD_LEFT):date('m'));
		if( intval("{$year}{$month}")>date('Ym') ){
			$year = date('Y');
			$month = date('m');
		}
		$days = Esite::app()->countArticleBayDay();
		$days = $days?array_keys($days):array();
		Calendar::$hitDays = $days;
		Calendar::$monthLink = Esite::app()->baseurl."/%s/%s";
		Calendar::$dayLink = Esite::app()->baseurl."/%s/%s/%s";
		return Calendar::app()->getMonthView($month, $year);
	}
	
	# 地址列表
	public function address_list(){
		$address_list = Member::app()->address_list();
		if( Esite::app()->request['ajax']!='yes' ){
			return $address_list;
		}else{
			Msg::json_encode(($address_list?'yes':'no'), '', $address_list);
		}
	}
	
	# 根据id读取地址信息
	public function address_info($id=0){
		Esite::app()->request['id'] = isset(Esite::app()->request['id'])?intval(Esite::app()->request['id']):intval($id);
		$address_info = Member::app()->address_info(Esite::app()->request['id']);
		if( Esite::app()->request['ajax']!='yes' ){
			return $address_info;
		}else{
			Msg::json_encode(($address_info?'yes':'no'), '', $address_info);
		}
	}
	
	# 编辑地址信息(添加/更新)
	public function address_edit(){
		$flag = Member::app()->address_edit();
		if( Esite::app()->request['ajax']!='yes' ){
			return $flag;
		}else{
			Msg::json_encode(($flag?'yes':'no'), '');
		}
	}
	
	# 流量统计
	# 调用语法：
	# 		EsiteApp::count_pv($sn);
	# 		$sn=A1	# 表示读取文章ID=1的pv数据
	# 		$sn=G1	# 表示读取商品ID=1的pv数据
	# 		$sn=P1	# 表示读取单页ID=1的pv数据
	# 		$sn=S0	# 表示读取整站全局的pv数据
	public function count_pv($sn=''){
		return EsitePv::app()->count_pv($sn);
	}
	
	# 读取会话信息
	public function _SESSION(){
		$_SESSION['date'] = date('Y-m-d H:i:s');
		return $_SESSION;
	}
	
	public function _REQUEST(){
		return ($_REQUEST = Esite::app()->request);
	}
	
	# 国家代码列表（地址信息编辑时候需要）（php/ajax）
	public function CountryCode(){
		$res = Esite::$db->getCache('api/getCountryCode');
		if( empty($res) ){
			$res = Esite::app()->server_api('api/getCountryCode', 86400);
			$res = isset($res['data'])?$res['data']:array();
			Esite::$db->setCache('api/getCountryCode', $res);
		}
		if( Esite::app()->request['ajax']!='yes' ){
			return $res;
		}else{
			Msg::json_encode(($res?'yes':'no'), '', $res);
		}
	}
	
	# 图片验证码
	# 请求类型：HTTP/AJAX
	# 返回值示例： <img src="xxx" >
	# http请求示例： /?act=captcha&ajax=yes
	# PHP通过 $_SESSION['captcha_word'] 读取验证码内字符串
	public function captcha($width=0, $height=0, $fontsize=0, $img_id=''){
		$width = isset(Esite::app()->request['width'])?Esite::app()->request['width']:intval($width);
		$height = isset(Esite::app()->request['height'])?Esite::app()->request['height']:intval($height);
		$fontsize = isset(Esite::app()->request['fontsize'])?Esite::app()->request['fontsize']:intval($fontsize);
		$img_id = isset(Esite::app()->request['img_id'])?Esite::app()->request['img_id']:strval($img_id);
		$res = Esite::app()->server_api('api/checkcode_img', array(
			'width'=>$width,
			'height'=>$height,
			'fontsize'=>$fontsize,
			'img_id'=>$img_id,
		));
		$html = '';
		if( isset($res['status']) && $res['status']=='yes' && isset($res['data']['image']) && isset($res['data']['word']) ){
			$html = base64_decode($res['data']['image']);
			$_SESSION['captcha_word'] = $res['data']['word'];
		}
		if( Esite::app()->request['ajax']!='yes' ){
			return $html;
		}else{
			echo $html;die;
		}
	}
	
	# 购物车
	public function cart(){
		$carts = Esite::app()->cart();
		if( Esite::app()->request['ajax']!='yes' ){
			return $carts;
		}else{
			Msg::json_encode('yes', '', $carts);
		}
	}

	# 分类信息(传递分类ID或分类名称)
	public function category_info($cat_id=0){
		$res = [];
		$cat_id = isset(Esite::app()->cfg['url_route']['cat_id'])?(Esite::app()->cfg['url_route']['cat_id']):intval($cat_id);
		$cat_id = is_numeric($cat_id)?intval($cat_id):Esite::$db->escapeString($cat_id);
		$category_list = [];
		if( isset(Esite::app()->cfg['url_route']['type']) && in_array(Esite::app()->cfg['url_route']['type'], ['product', 'products']) && strlen($cat_id)>0 ){
			$res['cat_id'] = $cat_id;
			# 读取所有分类
			$category_list = Esite::app()->get_product_categorys();
		}
		if( isset(Esite::app()->cfg['url_route']['type']) && in_array(Esite::app()->cfg['url_route']['type'], ['article', 'articles']) && strlen($cat_id)>0 ){
			$res['cat_id'] = $cat_id;
			# 读取所有分类
			$category_list = Esite::app()->get_article_categorys();
		}
		if( isset(Esite::app()->cfg['url_route']['type']) && in_array(Esite::app()->cfg['url_route']['type'], ['page']) && strlen($cat_id)>0 ){
			$res['cat_id'] = $cat_id;
			# 读取所有分类
			$category_list = Esite::app()->get_page_categorys();
		}
		if( $category_list ){
			$category_genTree = Helper::genTree($category_list, 'cat_id', 'parent_id');
			# 分类树格式化
			$res['sonTree'] = Helper::sonTree($category_list, $res['cat_id'], 1, 'cat_id', 'parent_id');	# 当前分类子树图
			$res['fatherTree'] = Helper::fatherTree($category_list, $res['cat_id'], 'cat_id', 'parent_id');	# 当前分类路由图
			$res['father_id'] = key($res['fatherTree']);	# 当前分类顶级分类id
			$res['genTree'] = isset($category_genTree[$res['father_id']])?$category_genTree[$res['father_id']]:$res['fatherTree'];	#当前分类根树图
			$res['relation_str'] = isset($res['fatherTree'][$res['cat_id']])?implode(',', array_column($res['fatherTree'], 'cat_name')):'';
			$res['category_url'] = isset($res['fatherTree'][$res['cat_id']]['category_url'])?$res['fatherTree'][$res['cat_id']]['category_url']:'';
			$res['cat_name'] = isset($res['fatherTree'][$res['cat_id']]['cat_name'])?$res['fatherTree'][$res['cat_id']]['cat_name']:'';
			$res['content'] = isset($res['fatherTree'][$res['cat_id']]['content'])?$res['fatherTree'][$res['cat_id']]['content']:'';
			$res['description'] = isset($res['fatherTree'][$res['cat_id']]['description'])?$res['fatherTree'][$res['cat_id']]['description']:'';
			$res['keywords'] = isset($res['fatherTree'][$res['cat_id']]['keywords'])?$res['fatherTree'][$res['cat_id']]['keywords']:'';
			$res['parent_id'] = isset($res['fatherTree'][$res['cat_id']]['parent_id'])?$res['fatherTree'][$res['cat_id']]['parent_id']:'';
			$res['title'] = isset($res['fatherTree'][$res['cat_id']]['title'])?$res['fatherTree'][$res['cat_id']]['title']:'';
			$res['unique_id'] = isset($res['fatherTree'][$res['cat_id']]['unique_id'])?$res['fatherTree'][$res['cat_id']]['unique_id']:'';
		}
		return $res;
	}
	
	#读取菜单列表，以树形结构返回
	#可以根据模块名称读取指定模块的菜单
	public function nav_data($category=''){
		$sql = "select * from nav where category='{$category}' order by sort desc";
		$res = Esite::$db->getAll($sql);
		$res = Helper::genTree($res, 'id', 'parent_id');
		return $res;
	}
	
	#读取幻灯片列表
	#根据幻灯片分类名称读取对应的列表
	public function slide_data($category=''){
		$sql = "select * from slide where category='{$category}' order by sort desc";
		$res = Esite::$db->getAll($sql);
		return $res;
	}
	
	# 会员登录(ajax-json)
	public function member_login(){
		Esite::app()->request['email'] = isset(Esite::app()->request['email'])?Esite::$db->escapeString(Esite::app()->request['email']):'';
		Esite::app()->request['captcha'] = isset(Esite::app()->request['captcha'])?trim(Esite::app()->request['captcha']):'';
		$flag = false;
		$member = array();
		# 验证邮箱与验证码
		if( Helper::isEmail(Esite::app()->request['email']) && Esite::app()->check_captcha(Esite::app()->request['captcha'], 'mail_captcha_word') ){
			# 获取用户信息
			$member = Esite::$db->getRow(sprintf("select * from member_list where email='%s' ", Esite::app()->request['email']));
			# 会员不存在则自动注册基本信息
			if( !isset($member['email']) ){
				$sql = sprintf("insert into member_list (email,reg_time) values ('%s', '%s')", Esite::app()->request['email'], time());
				Esite::$db->exec($sql);
				$member = Esite::$db->getRow(sprintf("select * from member_list where email='%s' ", Esite::app()->request['email']));
			}
			# 更新登录信息
			$sql = sprintf("update member_list set last_login='%s',login_count=login_count+1 where email='%s' ", time(), Esite::app()->request['email']);
			Esite::$db->exec($sql);
			# 更新会话信息
			$_SESSION = array_merge($_SESSION, $member);
			# 用户名称补全
			if( isset($_SESSION['username']) && strlen(trim($_SESSION['username']))<1 ){
				$_SESSION['username'] = $_SESSION['email'];
			}
			$flag = true;
		}
		Msg::json_encode(($flag?'yes':'no'), '');
	}

	# 退出登录(php/ajax)
	public function member_exit(){
		$_SESSION = array();
		Msg::json_encode('yes', '');
	}
	
	# 会员信息(php/ajax)
	public function member_info(){
		$email = isset($_SESSION['email'])?$_SESSION['email']:'';
		$member_info = Member::app()->get_member_info($email);
		if( Esite::app()->request['ajax']!='yes' ){
			return $member_info;
		}else{
			Msg::json_encode(($member_info?'yes':'no'), '', $member_info);
		}
	}
	
	# 更新会员信息(php/ajax)
	public function update_member_info(){
		$flag = Member::app()->update_member_info(Esite::app()->request, Esite::app()->request['captcha']);
		if( Esite::app()->request['ajax']!='yes' ){
			return $flag;
		}else{
			Msg::json_encode(($flag?'yes':'no'), ($flag?'success':'fail, please try again'));
		}
	}
	
	# 会员订单列表(php/ajax)
	public function member_orders(){
		$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
		$page = isset(Esite::app()->request['page'])&&Esite::app()->request['page']>0?ceil(Esite::app()->request['page']):1;
		$pagesize = isset(Esite::app()->request['pagesize'])&&Esite::app()->request['pagesize']>0?ceil(Esite::app()->request['pagesize']):10;
		$orders = Member::app()->orders($user_id, $page, $pagesize);
		if( Esite::app()->request['ajax']!='yes' ){
			return $orders;
		}else{
			Msg::json_encode(($orders?'yes':'no'), '', $orders);
		}
	}
	
	# 提交订单(php/ajax)
	public function orders_add(){
		$orders_id = Member::app()->orders_add();
		if( Esite::app()->request['ajax']!='yes' ){
			return $orders_id;
		}else{
			Msg::json_encode(($orders_id>0?'yes':'no'), '', ['orders_id'=>$orders_id]);
		}
	}
	
	# 确认订单(php/ajax)
	public function orders_checkout(){
		$flag = Member::app()->orders_checkout();
		if( Esite::app()->request['ajax']!='yes' ){
			if( strlen(Member::app()->payment_url)>8 ){
				header("Location: ".Member::app()->payment_url);die;
			}else{
				return $flag;
			}
		}else{
			Msg::json_encode(($flag?'yes':'no'), '', ['payment_url'=>Member::app()->payment_url, 'debug'=>Msg::$message]);
		}
	}
	
	# 订单信息
	public function orders_info(){
		$orders_id = (isset(Esite::app()->request['orders_id'])&&Esite::app()->request['orders_id']>0?intval(Esite::app()->request['orders_id']):(isset($_SESSION['orders_id'])?intval($_SESSION['orders_id']):0));
		$orders_info = Member::app()->orders_info($orders_id);
		if( Esite::app()->request['ajax']!='yes' ){
			return $orders_info;
		}else{
			Msg::json_encode(($orders_info?'yes':'no'), '', $orders_info);
		}
	}
	
	# 留言列表(php/ajax)
	public function msg_list(){
		$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
		$page = isset(Esite::app()->request['page'])&&Esite::app()->request['page']>0?ceil(Esite::app()->request['page']):1;
		$pagesize = isset(Esite::app()->request['pagesize'])&&Esite::app()->request['pagesize']>0?ceil(Esite::app()->request['pagesize']):10;
		$messages = Member::app()->msg_list($user_id, $page, $pagesize);
		if( Esite::app()->request['ajax']!='yes' ){
			return $messages;
		}else{
			Msg::json_encode(($messages?'yes':'no'), '', $messages);
		}
	}
	
	# 发表新留言(php/ajax)
	public function msg_save(){
		$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
		Esite::app()->request['msg_id'] = isset(Esite::app()->request['msg_id'])?intval(Esite::app()->request['msg_id']):0;
		$flag = Member::app()->msg_save($user_id, Esite::app()->request['msg_id'], array(
			'title'=>isset(Esite::app()->request['title'])?trim(Esite::app()->request['title']):'',
			'content'=>isset(Esite::app()->request['content'])?trim(Esite::app()->request['content']):'',
		));
		if( Esite::app()->request['ajax']!='yes' ){
			return $flag;
		}else{
			Msg::json_encode(($flag?'yes':'no'));
		}
	}
	
	# 回复留言(php/ajax)
	public function msg_reply_save(){
		$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
		Esite::app()->request['msg_id'] = isset(Esite::app()->request['msg_id'])?intval(Esite::app()->request['msg_id']):0;
		Esite::app()->request['id'] = isset(Esite::app()->request['id'])?intval(Esite::app()->request['id']):0;
		Esite::app()->request['content'] = isset(Esite::app()->request['content'])?trim(Esite::app()->request['content']):'';
		$flag = Member::app()->msg_reply_save($user_id, Esite::app()->request['msg_id'], Esite::app()->request['id'], Esite::app()->request['content']);
		if( Esite::app()->request['ajax']!='yes' ){
			return $flag;
		}else{
			Msg::json_encode(($flag?'yes':'no'));
		}
	}
	
	public function microtime(){return microtime(true);}
	public function time(){return time();}
	public function Ymd(){return date('Ymd');}
	public function year(){return date('Y');}
	public function month(){return date('m');}
	public function day(){return date('d');}
	
	###################################
	###################################
	###################################
	###################################
	###################################
	###################################
	###################################
	###################################
	
	
	#分页列表, 适用于文章列表、商品列表
	public function pager_data($cfg=array()){
		$url = preg_replace(
			array(
				'@\/(articles\.html)@i',	#文章列表
				'@\/(p[0-9]+-articles\.html)@i',	#文章列表
				'@\/(products\.html)@i',		#商品列表
				'@\/(p[0-9]+-products\.html)@i',		#商品列表
				'@([\?|\&])page=([0-9]+)@'	#动态分页参数
			),
			array(
				'/p1-articles.html',	#文章列表
				'/p%s-articles.html',	#文章列表
				'/p1-products.html',	#商品列表
				'/p%s-products.html',	#商品列表
				'$1page=%s',			#动态分页参数
			),
			Esite::app()->request_uri	#当前页面url
		);
		# 不在规则范围的分页数据采用动态参数
		if( !preg_match('@(articles|products)\.html@i', Esite::app()->request_uri) ){
			$url = Helper::getBaseUrl();
			$url = trim($url, '?&');
			$url = strpos($url, '?')===false?"{$url}?page=%s":str_replace('?', "?page=%s&", $url);
		}
		# $url, $cur_page=1, $page_fix=2, $total_rows=0, $pagesize=10, $cur_page_calss='active'
		$cfg['url'] = $url;
		$cfg['page'] = Esite::app()->cfg['url_route']['page'];
		$cfg['total_rows'] = Esite::app()->cfg['url_route']['total_rows'];
		$cfg['pagesize'] = Esite::app()->cfg['url_route']['pagesize'];
		$cfg['page_fix'] = isset($cfg['page_fix'])?intval($cfg['page_fix']):3;
		$cfg['current_page_calss'] = isset($cfg['current_page_calss'])?$cfg['current_page_calss']:'active';
		return Esite::app()->pagenation($cfg);
	}
	
	#读取所有页面分类
	public function page_categorys(){
		return Helper::genTree(Esite::app()->get_page_categorys(), 'cat_id', 'parent_id', 'children');
	}
	
	#读取页面数据
	public function page_data($key=''){
		$key = $key?$key:(isset(Esite::app()->cfg['url_route']['id'])?Esite::app()->cfg['url_route']['id']:$key);
		$page_data = Esite::app()->page_info($key);
		$page_data = Esite::app()->format_page_data($page_data);
		return $page_data;
	}
	
	#读取商品数据
	public function product_data($key=''){
		$key = $key?$key:(isset(Esite::app()->cfg['url_route']['id'])?Esite::app()->cfg['url_route']['id']:$key);
		return Esite::app()->product_info($key);
	}
		
	#商品数据列表
	public function products_data($pagesize=10){
		# 读取所有分类
		$products_category = Esite::app()->get_product_categorys();
		# 分类树格式化
		$products_category_tree = Helper::genTree($products_category, 'cat_id', 'parent_id', 'children');
		
		$pagesize = (int)$pagesize;
		Esite::app()->cfg['url_route']['pagesize'] = $pagesize;
		$offset = isset(Esite::app()->cfg['url_route']['page'])&&Esite::app()->cfg['url_route']['page']>1?(Esite::app()->cfg['url_route']['page']-1)*$pagesize:0;
		$where = '1';
		$where .= isset(Esite::app()->cfg['url_route']['cat_id'])&&(Esite::app()->cfg['url_route']['type']=='products')&&Esite::app()->cfg['url_route']['cat_id']?sprintf(" and c.cat_id in (%s) ", implode(',', array_unique(array_merge([Esite::app()->cfg['url_route']['cat_id']], array_column(Helper::sonTree($products_category, Esite::app()->cfg['url_route']['cat_id'], 1, 'cat_id', 'parent_id'), 'cat_id'))))):'';
		if( isset(Esite::app()->request['q'])&&strlen(trim(Esite::app()->request['q']))>0 ){
			$q = Esite::$db->escapeString(trim(Esite::app()->request['q']));
			$where .= " and (p.name like '%{$q}%' or p.filename like '%{$q}%' or a.title like '%{$q}%' or a.description like '%{$q}%' or a.keywords like '%{$q}%' or c.unique_id like '%{$q}%' or c.cat_name like '%{$q}%' or c.title like '%{$q}%' or c.keywords like '%{$q}%') ";
		}
		
		$sql = "select p.*, c.unique_id as cat_unique_id,c.cat_name,c.category from product as p left join product_category as c on p.cat_id=c.cat_id where {$where} order by p.sort desc,p.edit_time desc,p.id desc limit {$offset},{$pagesize} ";
		$res = Esite::$db->getAll($sql);
		
		$sql = "select count(*) as total from product as p left join product_category as c on p.cat_id=c.cat_id where {$where} ";
		$res_count = Esite::$db->getRow($sql);
		Esite::app()->cfg['url_route']['total_rows'] = isset($res_count['total'])?$res_count['total']:0;
		if($res){
			foreach($res as $k=>$v){
				$res[$k] = Esite::app()->format_product_data($v);
			}
		}
		return $res;
	}

	# 关联商品（简单内容加权算法）
	public function products_relation($pagesize=10){
		return $this->products_data($pagesize);
	}

	# 商品版块列表（按顶级分类分组提取下面TOP-N商品，用于商品版块展示，每个顶级分类为一个版块）
	public function products_section_data($page=1, $pagesize=10){
		# 读取所有分类
		$products_category = Esite::app()->get_product_categorys();
		# 分类树格式化
		$products_category_tree = Helper::genTree($products_category, 'cat_id', 'parent_id', 'children');
		# 存储版块数据
		$section_data = array();
		if($products_category_tree){
			foreach($products_category_tree as $k1=>$v1){
				$section_data[$v1['cat_id']] = $v1;
				$cat_ids = implode(',', array_unique(array_column(Helper::sonTree($products_category, $v1['cat_id'], 1, 'cat_id', 'parent_id'), 'cat_id')));
				$cat_ids = $cat_ids?$cat_ids:intval($v1['cat_id']);
				$section_data[$v1['cat_id']]['products'] = Esite::app()->products_data($cat_ids, $page, $pagesize);
			}
		}
		return $section_data;
	}
	
	#读取所有商品分类
	public function product_categorys(){
		return Helper::genTree(Esite::app()->get_product_categorys(), 'cat_id', 'parent_id', 'children');
	}
	
	# 支付接口回调处理
	public function payment_callback(){
		$stime = microtime(1);
		$res = Esite::app()->server_api('api/payment_callback', Esite::app()->request);
		Esite::app()->redirect();
	}
	
	#邮件接口
	#用于站内表单信息提交时邮件发送
	#请求类型：AJAX
	#返回值：yes/no
	#应用场景：
	#	联系我们页面，客户填写表单后，系统可以将信息发送站点管理者设定的邮箱
	#参数说明：
	# captcha		验证码，请求发送邮件必须进行基本验证
	# mail_title 	邮件标题，不能为空
	# mail_body		邮件内容，不能为空
	# mail_to		收件人，为空时表示将邮件发送给站点管理员，不为空则将邮件发送指定邮箱
	public function sendmail(){
		$captcha = isset(Esite::app()->request['captcha'])?Esite::app()->request['captcha']:'';
		$mail_title = isset(Esite::app()->request['mail_title'])?Esite::app()->request['mail_title']:'';
		$mail_body = isset(Esite::app()->request['mail_body'])?Esite::app()->request['mail_body']:'';
		$mail_to = isset(Esite::app()->request['mail_to'])?Esite::app()->request['mail_to']:'';
		# 仅受理ajax请求
		if( Esite::app()->request['ajax']=='yes' ){
			# 站点设定【未开启验证码】 或 【开启验证码并且验证码正确】
			if( !Esite::app()->cfg['site_info']['img_captcha'] || (Esite::app()->cfg['site_info']['img_captcha'] && isset($_SESSION['captcha_word']) && $captcha==$_SESSION['captcha_word']) ){
				if( Esite::app()->mailbox_add(array(
					'mail_title'=>$mail_title,
					'mail_body'=>$mail_body,
					'mail_to'=>$mail_to
				)) ){
					Msg::json_encode('yes', '');
				}
			}
		}
		Msg::json_encode('no', '');
	}
	
	# 邮件验证码
	public function mail_captcha(){
		Esite::app()->request['email'] = isset(Esite::app()->request['email'])?Esite::app()->request['email']:'';
		# 邮件格式验证
		if( !filter_var(Esite::app()->request['email'], FILTER_VALIDATE_EMAIL) ){
			Msg::json_encode('no', 'email has error.');
		}
		# 校验间隔(禁止恶意频繁操作)
		if( isset($_SESSION["mail_captcha_stime"]) && ($after_sec=time()-$_SESSION["mail_captcha_stime"])<60 ){
			Msg::json_encode('no', sprintf('Retry after %s seconds', (60-$after_sec)));
		}
		# 请求远程邮件验证码数据
		$res = Esite::app()->server_api('api/checkcode_email', ['email'=>Esite::app()->request['email'], 'ip'=>$_SERVER['REMOTE_ADDR']]);
		$_SESSION["mail_captcha_stime"] = time();
		$_SESSION['mail_captcha_word'] = isset($res['data']['code'])?$res['data']['code']:'';
		Msg::json_encode($res['status'], $res['message']);
	}
	
	#读取站点配置信息
	public function site_config(){
		return Esite::app()->site_config();
	}
	
	public function url_route(){
		return Esite::app()->cfg['url_route'];
	}
	
	public function site_urls(){
		$urls = array();
		$urls[] = array(
			'url'=>Esite::app()->baseurl,
			'lastmod'=>date('Y-m-d')
		);
		$baseurl = rtrim(Esite::app()->baseurl, '/');
		
		# 分类URL
		$cats = ((array)Esite::app()->get_article_categorys(false)) + ((array)Esite::app()->get_product_categorys(false));
		if( $cats ){
			foreach($cats as $k=>$v){
				if( !isset($v['category_url']) ){continue;}
				$urls[] = array(
					'url'=>$baseurl.$v['category_url'],
					'lastmod'=>date('Y-m-d')
				);
			}
			$cats = null;
		}
		
		# 文章URL
		$articles = Esite::$db->getAll("select a.id,a.cat_id,a.filename,c.unique_id as cat_unique_id,c.cat_name,edit_time from article as a left join article_category as c on a.cat_id=c.cat_id");
		if( $articles ){
			foreach($articles as $k=>$v){
				$urls[] = array(
					'url'=>$baseurl.Esite::app()->get_article_url($v),
					'lastmod'=>date('Y-m-d', $v['edit_time'])
				);
			}
			$articles = null;
		}
		
		# 商品url
		$products = Esite::$db->getAll("select p.id,p.cat_id,p.filename,c.unique_id as cat_unique_id,c.cat_name,edit_time from product as p left join product_category as c on p.cat_id=c.cat_id");
		if( $products ){
			foreach($products as $k=>$v){
				$urls[] = array(
					'url'=>$baseurl.Esite::app()->get_product_url($v),
					'lastmod'=>date('Y-m-d', $v['edit_time'])
				);
			}
			$products = null;
		}
		
		# 页面url
		$pages = Esite::$db->getAll("select id,filename,filename as unique_id,page_name as cat_name from page");
		if( $pages ){
			foreach($pages as $k=>$v){
				$urls[] = array(
					'url'=>$baseurl.Esite::app()->get_page_url($v),
					'lastmod'=>date('Y-m-d')
				);
			}
			$pages = null;
		}
		
		return $urls;
	}
	
	public function version(){
		die(Esite::app()->api_version);
	}
}
# == EsiteApp.php code end ==

# == EsitePv.php code start ==

/**
 * 阅读量统计类
 * 数据字段：
 * 		sn：统计对象编号，A文章、G商品、P单页面、S站点(整站全局PV)
 * 		last_pv：统计对象上次提交时的pv数值，若为0则从对象数据源提取
 * 		temp_pv：尚未提交的pv数值
 * 		utime：pv更新时间，超过指定周期未更新则提交数据到对象表，然后清理-回收资源
 * 计算公式：pv = last_pv + temp_pv
 * 存储规则：
 * 		当临时pv数值达到某个阈值n(10-100)则提交到对应统计对象数据表，表示pv为n的整数倍触发提交操作；
 * 		统计对象数据表存储最终数据，用于进行热门数据排序；
 * 		全局pv采用站点设定项进行永久存储；
 * 读取规则：数据读取缓存时间阈值随机范围10~20秒
 * 调用语法：
 * 		模板语法：EsiteApp::count_pv('$sn');	# 此方式在服务器html缓存刷新时候才能看到最新pv数据
 * 		固定标签：<!--count_pv:$sn-->		# 此方式加载实时pv数据-不受服务器html缓存影响
 * 		$sn=A1	# 表示读取文章ID=1的pv数据
 * 		$sn=G1	# 表示读取商品ID=1的pv数据
 * 		$sn=P1	# 表示读取单页ID=1的pv数据
 * 		$sn=S0	# 表示读取整站全局的pv数据
 * 如果身上有块烂肉，我会选择削去，不会傻傻的等时间将其修复。
 * 时间只会让犯错的人犯更大的错，只会让坏的更坏，好的更好，让坏的变好必须经历巨变，这种代价一般人都不想去承受。
 * 生活如此美好，人，为何要去犯贱。
 */
class EsitePv{
	#对象单例模式唯一实例静态成员
	private static $_instance;
	
	private function __construct(){}
	
	private function __clone(){}
	
	#返回唯一实例
	public static function app(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public $sn_tags = array(
			'A'=>'article',
			'G'=>'product',
			'P'=>'page',
			'S'=>'config',
		);
	
	# 统计当前页面的pv
	public function count_pv($sn=''){
		$sn = strtoupper(trim($sn));
		$sn = $sn?$sn:'S0';
		$pv = 0;
		$time = time();
		preg_match('@([AGPS])([0-9]+)@i', $sn, $match);
		if( !isset($match[1]) || !isset($match[2]) || !isset($this->sn_tags[$match[1]]) ){
			return $pv;
		}
		$table = $this->sn_tags[$match[1]];
		$id = $match[2]>0?$match[2]:'site_pv';
		# 数据缓存时间
		$expire_time = Esite::$db->expire_time;
		# 重置缓存时间
		Esite::$db->expire_time = 0;
		# 查询总pv
		$sql = "select *,(last_pv+temp_pv) as pv from pageview where sn='{$sn}' ";
		$res = Esite::$db->getRow($sql);
		$pv = isset($res['pv'])?($res['pv']):$pv;
		# 数值矫正:last_pv丢失则尝试从原表提取数据
		if( isset($res['last_pv']) && $res['last_pv']<1 ){
			if( $table=='config' ){
				$sql = "select value as pv from {$table} where name='{$id}'";
			}else{
				$sql = "select pv from {$table} where id='{$id}'";
			}
			$tmp = Esite::$db->getRow($sql);
			$res['last_pv'] = isset($tmp['pv'])?$tmp['pv']:$res['last_pv'];
			$pv = $res['last_pv']+$res['temp_pv'];
			unset($tmp);
		}
		$pv = intval($pv);
		# 触发数据提交
		if( $pv>0 && $pv%10==0 ){
			# 更新last_pv
			Esite::$db->exec("update pageview set last_pv={$pv},temp_pv=0,utime={$time} where sn='{$sn}' and last_pv<{$pv} ");
			# 提交数据
			if( $table=='config' ){
				Esite::$db->update($table, array('value'=>$pv), " name='{$id}' and value<{$pv} ");
			}else{
				Esite::$db->update($table, array('pv'=>$pv), " id='{$id}' and pv<{$pv} ");
			}
		}
		# 还原缓存时间
		Esite::$db->expire_time = $expire_time;
		return $pv;
	}
	
	# 更新pv
	public function update_pv($sn=''){
		$sn = strtoupper(trim($sn));
		$sn = $sn?$sn:'S0';
		$time = time();
		preg_match('@([AGPS])([0-9]+)@i', $sn, $match);
		if( !isset($match[1]) || !isset($match[2]) || !isset($this->sn_tags[$match[1]]) ){
			return false;
		}
		# 更新数据
		$sql = "update pageview set temp_pv=temp_pv+1,utime={$time} where sn='{$sn}'";
		Esite::$db->query($sql);
		# 不存在则只需插入操作
		if( !Esite::$db->info['changes'] ){
			Esite::$db->insert('pageview', array(
				'sn'=>Esite::$db->escapeString($sn),
				'last_pv'=>0,
				'temp_pv'=>1,
				'utime'=>time(),
			), true);
		}
		return true;
	}
}
# == EsitePv.php code end ==

# == Filebox.php code start ==


# 文件管理类： /?act=Filebox/dirTree
class Filebox{
	public static $editDocTypes = array('php', 'css', 'js', 'html', 'htm', 'tpl', 'htaccess');
	
	public static function run(){
		Admin::run()->check_login(true);
		return new self();
	}
	
	# 路径过滤
	public static function dirFilter($dir){
		$dir = str_replace(array('\\'), array('/'), $dir);
		$dir = realpath($dir);
		return $dir;
	}
	
	# 路径检测：限制用户仅可以修改当前应用内相关文件
	public static function dirCheck($dir){
		if( $dir===false ){
			return false;
		}
		$dir = self::dirFilter($dir);
		return (bool)($dir!=str_replace(APPPATH, '', $dir) && strlen($dir)>=strlen(APPPATH));
	}
	
	# 获取目录树
	public static function dirTree(){
		$request = Esite::app()->request;
		$request['dir'] = isset($request['dir'])&&$request['dir']?$request['dir']:APPPATH;
		$request['dir'] = self::dirFilter($request['dir']);
		$request['dir'] = self::dirCheck($request['dir'])?$request['dir']:APPPATH;
		$filelist = Helper::listDir($request['dir'], false);
		Msg::json_encode('yes', '', get_defined_vars());
	}
	
	# 重命名
	public static function renam(){
		$request = Esite::app()->request;
		$request['dir'] = isset($request['dir'])&&$request['dir']?$request['dir']:APPPATH;
		$request['dir'] = self::dirFilter($request['dir']);
		$request['dir'] = self::dirCheck($request['dir'])?$request['dir']:APPPATH;
		$request['oldname'] = "{$request['dir']}/{$request['oldname']}";
		$request['newname'] = "{$request['dir']}/{$request['newname']}";
		if( Helper::renam($request['oldname'], $request['newname']) ){
			Msg::json_encode('yes', '操作成功', get_defined_vars());
		}else{
			Msg::json_encode('no', '操作失败', get_defined_vars());
		}
	}
	
	# 解压
	public static function unzip(){
		$request = Esite::app()->request;
		$request['file'] = isset($request['file'])&&$request['file']?$request['file']:APPPATH;
		$request['file'] = self::dirFilter($request['file']);
		$request['file'] = self::dirCheck($request['file'])?$request['file']:APPPATH;
		$request['ndir'] = isset($request['ndir'])&&$request['ndir']?trim($request['ndir']):APPPATH;
		Helper::make_dir($request['ndir']);
		$request['ndir'] = self::dirFilter($request['ndir']);
		$request['ndir'] = self::dirCheck($request['ndir'])?$request['ndir']:APPPATH;
		if( Helper::unzip($request['file'], $request['ndir'], false) ){
			Msg::json_encode('yes', '', get_defined_vars());
		}else{
			Msg::json_encode('no', '解压失败', get_defined_vars());
		}
	}
	
	# 修改权限
	public static function updateAll(){
		$request = Esite::app()->request;
		$request['opt'] = isset($request['opt'])?$request['opt']:'';
		$request['newdir'] = isset($request['newdir'])?$request['newdir']:'';
		$files = isset($request['file'])&&$request['file']?$request['file']:APPPATH;
		$mod = isset($request['mod'])&&strlen($request['mod'])==3&&is_numeric($request['mod'])?"0{$request['mod']}":0755;
		$files = (array)explode(',', $files);
		$files = array_map('trim', array_unique(array_map('self::dirFilter', $files)));
		$success = 0;
		if( $files ){
			foreach($files as $k=>$file){
				if( file_exists($file) && self::dirCheck($file) ){
					switch($request['opt']){
						case 'chmodAll':
							$success += (int)(Helper::chmodAll($file, octdec(sprintf("%04d", $mod))));
							break;
						case 'rmAll':
							$success += (int)(Helper::deleteDir($file));
							break;
					}
					#print_r([__LINE__, $file, sprintf("%04d", $mod), substr(sprintf("%o",fileperms($file)),-3)]);die;
				}else{
					unset($files[$k]);
				}
			}
			# 压缩多个文件或目录
			if( $request['opt']=='zipAll' && file_exists($request['newdir']) && self::dirCheck($request['newdir']) ){
				$zipfile = rtrim($request['newdir'], '/').'/Backup_'.date('YmdHis').'.zip';
				$success += (int)Helper::zipAll($files, $zipfile, APPPATH);
			}
			# 复制多个文件或目录
			if( $request['opt']=='cpAll' && file_exists($request['newdir']) && self::dirCheck($request['newdir']) ){
				$success += (int)Helper::cpAll($files, $request['newdir']);
			}
			# 移动多个文件或目录
			if( $request['opt']=='mvAll' && file_exists($request['newdir']) && self::dirCheck($request['newdir']) ){
				$success += (int)Helper::mvAll($files, $request['newdir']);
			}
		}
		
		if( $success>0 ){
			Msg::json_encode('yes', '操作成功', get_defined_vars());
		}else{
			Msg::json_encode('no', '操作失败', get_defined_vars());
		}
	}
	
	# 远程文件下载
	public function remoteDownload(){
		$request = Esite::app()->request;
		$request['url'] = isset($request['url'])?$request['url']:'';
		$dir = isset($request['dir'])&&$request['dir']&&self::dirCheck($request['dir'])?$request['dir']:APPPATH;
		$newfname = $dir.'/'.date('YmdHis').'.'.basename($request['url']);
		if( $request['url'] && strlen($request['url'])>8 ){
			# 要求离线也可下载
			ignore_user_abort(true);
			# 设置超时时间
        	set_time_limit (24 * 60 * 60);
			if( $file = fopen($request['url'], "rb") ){
				if( $newf = fopen ($newfname, "wb") ){
					while( !feof($file) ){
						fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
					}
					fclose($newf);
				}
				fclose($file);
			}
		}
		Msg::json_encode('yes', '操作已经提交，请稍后查看...', get_defined_vars());
	}
	
	# 创建空白文件
	public function createFile(){
		$request = Esite::app()->request;
		$request['filename'] = isset($request['filename'])?$request['filename']:'';
		$dir = isset($request['dir'])&&$request['dir']&&self::dirCheck($request['dir'])?$request['dir']:APPPATH;
		$newfname = $dir.'/'.$request['filename'];
		if( $request['filename'] && strlen($request['filename'])>0 ){
			if( !file_exists($newfname) && @file_put_contents($newfname, '')!==false ){
				Msg::json_encode('yes', '文件创建成功', get_defined_vars());
			}
		}
		Msg::json_encode('no', '文件创建失败', get_defined_vars());
	}
	
	# 读取文件内容（仅限文本文档）
	public function readFile(){
		$request = Esite::app()->request;
		$request['filename'] = isset($request['filename'])?urldecode($request['filename']):'';
		if(
			self::dirCheck($request['filename'])
			&& file_exists($request['filename'])
			&& in_array(strtolower(pathinfo($request['filename'], PATHINFO_EXTENSION)), self::$editDocTypes)
		){
			$fileEncode = 'base64_encode';
			$fileDecode = 'base64_decode';
			$fileContent = $fileEncode(file_get_contents($request['filename']));
			Msg::json_encode('yes', '', get_defined_vars());
		}
		Msg::json_encode('no', '文件读取失败', get_defined_vars());
	}
	
	# 保存文件内容（仅限文本文档）
	public function saveFile(){
		$request = Esite::app()->request;
		$request['filename'] = isset($request['filename'])?urldecode($request['filename']):'';
		$request['fileContent'] = isset($request['fileContent'])?$request['fileContent']:'';
		if(
			self::dirCheck($request['filename'])
			&& file_exists($request['filename'])
			&& in_array(strtolower(pathinfo($request['filename'], PATHINFO_EXTENSION)), self::$editDocTypes)
		){
			if( file_put_contents($request['filename'], $request['fileContent'])!==false ){
				Msg::json_encode('yes', '文件保存成功', get_defined_vars());
			}
		}
		Msg::json_encode('no', '文件保存失败', get_defined_vars());
	}
	
}
# == Filebox.php code end ==

# == Helper.php code start ==


class Helper{
	
	static function genTree($items,$id='id',$pid='pid',$son = 'children'){
		$tree = array();
		$tmpMap = array();

		foreach ($items as $item) {
			$tmpMap[$item[$id]] = $item;
		}

		foreach ($items as $item) {
			if (isset($tmpMap[$item[$pid]]) && $item[$id] != $item[$pid]) {
				if (!isset($tmpMap[$item[$pid]][$son]))
					$tmpMap[$item[$pid]][$son] = array();
				$tmpMap[$item[$pid]][$son][$item[$id]] = &$tmpMap[$item[$id]];
			} else {
				$tree[$item[$id]] = &$tmpMap[$item[$id]];
			}
		}

		return $tree;
	}
	static function fatherTree($items,$cat_id, $id='id', $pid='pid', $loop_lev=0) {
	    static $Tree;
		if( $loop_lev<1 ){
			$Tree = array();
		}
	    foreach($items as $k=>$v) {
	        if($v[$id] == $cat_id) {
	            self::fatherTree($items, $v[$pid], $id, $pid, 1);
	            $Tree[$v[$id]] = $v;
	        }
	    }
	    return $Tree;
	}
	static function sonTree($items,$parent_id=0,$lev=1, $id='id', $pid='pid', $loop_lev=0) {
	    static $Tree;
		if( $loop_lev<1 ){
			$Tree = array();
		}
	    foreach($items as $k=>$v) {
	        if($v[$pid] == $parent_id) {
	            $v['lev'] = $lev;
	            $Tree[$v[$id]] = $v;
	            self::sonTree($items,$v[$id],$lev+1, $id, $pid, 1);
	        }
	    }
	    return $Tree;
	}
	

	static function isMobile(){
		if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
			return true;
		}
		# 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		if (isset ($_SERVER['HTTP_VIA'])){
			# 找不到为flase,否则为true
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		if (isset ($_SERVER['HTTP_USER_AGENT'])){
			$clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
			# 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if ( preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])) ){
				return true;
			}
		}
		if (isset ($_SERVER['HTTP_ACCEPT'])){
			# 如果只支持wml并且不支持html那一定是移动设备
			# 如果支持wml和html但是wml在html之前则是移动设备
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
				return true;
			}
		}
		return false;
	}
		
	/**
	 * 文件或目录权限检查函数
	 *
	 * @access		  public
	 * @param		   string  $file_path   文件路径
	 * @param		   bool	$rename_prv  是否在检查修改权限时检查执行rename()函数的权限
	 *
	 * @return		  int	 返回值的取值范围为{0 <= x <= 15}，每个值表示的含义可由四位二进制数组合推出。
	 *						  返回值在二进制计数法中，四位由高到低分别代表
	 *						  可执行rename()函数权限、可对文件追加内容权限、可写入文件权限、可读取文件权限。
	 */
	static function file_mode_info($file_path)
	{
		return substr(sprintf("%o",fileperms($file_path)),-4);
		/* 如果不存在，则不可读、不可写、不可改 */
		if (!file_exists($file_path))
		{
			return false;
		}
	
		$mark = 0;
	
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			/* 测试文件 */
			$test_file = $file_path . '/cf_test.txt';
	
			/* 如果是目录 */
			if (is_dir($file_path))
			{
				/* 检查目录是否可读 */
				$dir = @opendir($file_path);
				if ($dir === false)
				{
					return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
				}
				if (@readdir($dir) !== false)
				{
					$mark ^= 1; //目录可读 001，目录不可读 000
				}
				@closedir($dir);
	
				/* 检查目录是否可写 */
				$fp = @fopen($test_file, 'wb');
				if ($fp === false)
				{
					return $mark; //如果目录中的文件创建失败，返回不可写。
				}
				if (@fwrite($fp, 'directory access testing.') !== false)
				{
					$mark ^= 2; //目录可写可读011，目录可写不可读 010
				}
				@fclose($fp);
	
				@unlink($test_file);
	
				/* 检查目录是否可修改 */
				$fp = @fopen($test_file, 'ab+');
				if ($fp === false)
				{
					return $mark;
				}
				if (@fwrite($fp, "modify test.\r\n") !== false)
				{
					$mark ^= 4;
				}
				@fclose($fp);
	
				/* 检查目录下是否有执行rename()函数的权限 */
				if (@rename($test_file, $test_file) !== false)
				{
					$mark ^= 8;
				}
				@unlink($test_file);
			}
			/* 如果是文件 */
			elseif (is_file($file_path))
			{
				/* 以读方式打开 */
				$fp = @fopen($file_path, 'rb');
				if ($fp)
				{
					$mark ^= 1; //可读 001
				}
				@fclose($fp);
	
				/* 试着修改文件 */
				$fp = @fopen($file_path, 'ab+');
				if ($fp && @fwrite($fp, '') !== false)
				{
					$mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
				}
				@fclose($fp);
	
				/* 检查目录下是否有执行rename()函数的权限 */
				if (@rename($test_file, $test_file) !== false)
				{
					$mark ^= 8;
				}
			}
		}
		else
		{
			if (@is_readable($file_path))
			{
				$mark ^= 1;
			}
	
			if (@is_writable($file_path))
			{
				$mark ^= 14;
			}
		}
	
		return $mark;
	}
	
	# 获取高精度时间
	static function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	# 列举目录下所有文件/子目录
	static function listDir($path, $loop=false, $onlyfile=false){
		$dirList=array();
		$path = str_replace(array('/','\\'), DIRECTORY_SEPARATOR, $path);
		if(!file_exists($path)||!is_dir($path)){
			return null;
		}
		if(substr($path, -1,1)==DIRECTORY_SEPARATOR){
			$path = substr($path, 0,-1);
		}
		$dir=opendir($path);
		while($file=readdir($dir)){
			if($file!=='.'&&$file!=='..'){
				$file = $path.DIRECTORY_SEPARATOR.$file;
				if(is_dir($file)){
					if( !$onlyfile ){
						$dirList[] = array(
							'name'=>$file,
							'isDir'=>intval(is_dir($file)),
							'chmod'=>substr(sprintf("%o",fileperms($file)),-3),
							'pathinfo'=>pathinfo($file),
							'filemtime'=>filemtime($file),
							#'filesize'=>self::Size(self::dirSize($file)),	# long time
							'filesize'=>0,
						);
					}
					$dirList = $loop?array_merge($dirList, self::listDir($file, $loop, $onlyfile)):$dirList;
				}else{
					$dirList[] = array(
						'name'=>$file,
						'isDir'=>intval(is_dir($file)),
						'chmod'=>substr(sprintf("%o",fileperms($file)),-3),
						'pathinfo'=>pathinfo($file),
						'filemtime'=>filemtime($file),
						'filesize'=>self::Size(filesize($file)),
						'md5_file'=>md5_file($file),
					);
				}
			};
		};
		closedir($dir);
		return $dirList;
	}

	# 目录大小
	static function dirSize($directoty){
		$dir_size=0;
		if($dir_handle=opendir($directoty)){
			while($filename=readdir($dir_handle)){
				$subFile=$directoty.DIRECTORY_SEPARATOR.$filename;
				if($filename=='.'||$filename=='..'){
					continue;
				}elseif (is_dir($subFile)){
					$dir_size+=self::dirSize($subFile);
				}elseif (is_file($subFile)){
					$dir_size+=filesize($subFile);
				}
			}
			closedir($dir_handle);
		}
		return ($dir_size);
	}

	# 目录复制
	static function copy_dir($src,$dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					self::copy_dir($src . '/' . $file,$dst . '/' . $file);
					continue;
				}else {
					copy($src . '/' . $file,$dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}
	
	# 检查目标文件夹是否存在，如果不存在则自动创建该目录
	static function make_dir($folder){
		$reval = false;
		if (!file_exists($folder)){
			#如果目录不存在则尝试创建该目录
			@umask(0);
			#将目录路径拆分成数组
			preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);
			#如果第一个字符为/则当作物理路径处理
			$base = ($atmp[0][0] == '/') ? '/' : '';
			#遍历包含路径信息的数组
			foreach ($atmp[1] AS $val){
				if ('' != $val){
					$base .= $val;
					if ('..' == $val || '.' == $val){
						#如果目录为.或者..则直接补/继续下一个循环
						$base .= '/';
						continue;
					}
				}else{
					continue;
				}
				$base .= '/';
				if (!file_exists($base)){
					#尝试创建目录，如果创建失败则继续循环
					if (@mkdir(rtrim($base, '/'), 0777)){
						@chmod($base, 0777);
						$reval = true;
					}
				}
			}
		}else{
			#路径已经存在。返回该路径是不是一个目录
			$reval = is_dir($folder);
		}
		clearstatcache();
		return $reval;
	}

	#删除文件夹及其文件夹下所有文件
	static function deleteDir($dir) {
		if(!file_exists($dir)){
			return true;
		}
		if(is_file($dir) && !is_dir($dir)){
			return @unlink($dir);
		}else{
			#先删除目录下的文件
			$dh = @opendir($dir);
			while ($file = @readdir($dh)) {
				if($file!="." && $file!="..") {
					$fullpath = $dir."/".$file;
					if(!is_dir($fullpath)) {
						@unlink($fullpath);
					} else {
						@rmdir($fullpath);
						self::deleteDir($fullpath);
					}
				}
			}
			@closedir($dh);
			return @rmdir($dir);	#删除当前文件夹
		}
	}
	
	# 远程下载文件(可选解压、解压后删除压缩包)
	static function remote_download($url, $folder, $unzip=false, $delzip=false){
		if(empty($folder)){
			$folder="./";
		}
		$nfolder = $folder;
		$nurl = $url;
		$url = trim($url);
		$folder = trim($folder);
		if($url!==""){
			# 要求离线也可下载
			ignore_user_abort(true);
			# 设置超时时间
			set_time_limit (24 * 60 * 60);
			if (!file_exists($folder)){
				self::make_dir($folder, 0755);
			}
			# 取得文件的名称
			$newfname = $folder . basename($url);
			if(function_exists('curl_init')){
				$file = self::curl_get_contents($url);file_put_contents($newfname,$file);
			}else{
				$file=fopen($url,"rb");
				if($file){
					$newf = fopen ($newfname, "wb");
					if($newf){
						while (!feof($file)) {
							fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
						}
					}
				}
				if($file){fclose($file);}
				if($newf){fclose($newf);}
			}
			# 文件保存成功
			$end = explode('.', basename($url));
			if( (end($end)=="zip") && $unzip ){
				if(class_exists('ZipArchive')){
					$zip = new ZipArchive();
					if ($zip->open($folder.basename($url)) === TRUE) {
						if($zip->extractTo($folder)){
							$zip->close();
						}
						if( $delzip ){
							@unlink($folder.basename($url));
						}
					}
				}
			}
			return true;
		}
		return false;
	}
	
	# 解压文件：原文件名，原目录，解压至目录，是否删除源文件
	static function unzip($file, $ndir,$del=false){
		self::make_dir($ndir);
		if (!file_exists($file)){
			return false;
		}
		if(class_exists('ZipArchive')){
			$zip = new ZipArchive();
			if ($zip->open($file) === TRUE){
				if($zip->extractTo($ndir)){
					$zip->close();
					if( $del ){
						@unlink($file);
					}
					return true;
				}
			}
		}
		return false;
	}
	
	# 压缩文件
	static function zipAll($files, $zipfile, $basedir){
		$files = (array)$files;
		if( empty($files) || !file_exists(dirname($zipfile)) || !class_exists('ZipArchive') || !file_exists($basedir) ){
			return false;
		}
		$zip = new ZipArchive();
		$res = $zip->open($zipfile, ZipArchive::CREATE);
		if( $res!==true ){
			return false;
		}
		$tmp = array();
		foreach($files as $k=>$file){
			$subfiles = self::listDir($file, true, false);
			if( $subfiles ){
				$tmp = array_merge($tmp, array_column($subfiles, 'name'));
			}else{
				$tmp[] = $file;
			}
		}
		$files = $tmp;
		$files = array_unique(array_filter($files));
		if( $files ){
			foreach($files as $k=>$file){
				if( is_dir($file) ){
					$zip->addEmptyDir(str_replace($basedir, '', $file));
				}else{
					$zip->addFile($file, str_replace($basedir, '', $file));
				}
			}
		}
		$zip->close();
		unset($files, $tmp);
		return file_exists($zipfile);
	}

	# 创建文件： 文件完整路径，文件内容
	static function create_file($filename, $data){
		self::make_dir(dirname($filename));
		return file_put_contents($filename, $data);
	}
	
	# 创建目录： 目录完整路径
	static function create_dir($path){
		return self::make_dir(dirname($path));
	}

	# 重命名： 文件原路径，文件新路径
	static function renam($oldname, $newname){
		return file_exists($oldname)&&(@rename($oldname, $newname));
	}
	
	# 批量复制文件： 文件列表(一维数组)，新路径
	static function cpAll($files, $ndir){
	    $files = (array)$files;
		$ndir = rtrim($ndir, '/').'/';
		if( empty($files) || !file_exists(dirname($ndir)) ){
			return false;
		}
		$tmp = array();
		foreach($files as $k=>$file){
			$subfiles = self::listDir($file, true, false);
			if( $subfiles ){
				$tmp = array_merge($tmp, array_column($subfiles, 'name'));
			}else{
				$tmp[] = $file;
			}
		}
		$files = $tmp;
		$files = array_unique(array_filter($files));
		$success = 0;
		if( $files ){
			foreach($files as $k=>$file){
				if( !file_exists($file) ){
					continue;
				}
				if( !is_dir($file) ){
					$pathinfo = pathinfo($file);
					$newfile = "{$ndir}{$pathinfo['filename']}".(strlen($pathinfo['extension'])>0?".{$pathinfo['extension']}":'');
					if (file_exists($newfile)){
						$i = 0;
						$flag = true;
						while( $flag ){
							$i++;
							$newfile = "{$ndir}{$pathinfo['filename']}({$i})".(strlen($pathinfo['extension'])>0?".{$pathinfo['extension']}":'');
							$flag = file_exists($newfile);
						}
						$success += (int)@copy($file, $newfile);
			        }else{
			        	self::make_dir(dirname($newfile));
			        	$success += (int)@copy($file, $newfile);
			        }
				}
			}
		}
		return $success;
	}
	
	# 批量移动文件： 文件列表(一维数组)，新路径
	static function mvAll($files, $ndir){
	    $files = (array)$files;
		$ndir = rtrim($ndir, '/').'/';
		if( empty($files) || !file_exists(dirname($ndir)) ){
			return false;
		}
		$tmp = array();
		foreach($files as $k=>$file){
			$subfiles = self::listDir($file, true, false);
			if( $subfiles ){
				$tmp = array_merge($tmp, array_column($subfiles, 'name'));
			}else{
				$tmp[] = $file;
			}
		}
		$files = $tmp;
		$files = array_unique(array_filter($files));
		$success = 0;
		if( $files ){
			foreach($files as $k=>$file){
				if( !file_exists($file) ){
					continue;
				}
				if( !is_dir($file) ){
					$pathinfo = pathinfo($file);
					$newfile = "{$ndir}{$pathinfo['filename']}".(strlen($pathinfo['extension'])>0?".{$pathinfo['extension']}":'');
					if (file_exists($newfile)){
						$i = 0;
						$flag = true;
						while( $flag ){
							$i++;
							$newfile = "{$ndir}{$pathinfo['filename']}({$i})".(strlen($pathinfo['extension'])>0?".{$pathinfo['extension']}":'');
							$flag = file_exists($newfile);
						}
						$success += (int)@rename($file, $newfile);
			        }else{
			        	self::make_dir(dirname($newfile));
			        	$success += (int)@rename($file, $newfile);
			        }
				}
			}
		}
		return $success;
	}

	# 修改文件权限： 文件路径， 权限
	static function chmodAll($file, $chmod=0755){
        $nfile = $file;
        $file = trim($file);
        if( is_file($file) ){
            @chmod($file, $chmod);
        }elseif(is_dir($file)){
            @chmod($file, $chmod);
            $foldersAndFiles = scandir($file);
            $entries = array_slice($foldersAndFiles, 2);
			if( $entries ){
	            foreach($entries as $entry){
	                //$nentry = iconv("GBK", "UTF-8",$entry);
	                self::chmodAll($nfile.'/'.$entry, $chmod);
	            }
			}
        }
		return true;
    }
	
	# 判断目录是否为空	
	static function is_empty_dir($pathdir){
		$d=opendir($pathdir);
		$i=0;
		while($a=readdir($d)){ $i++; }
		closedir($d);
		if($i>2){ return false; }
		return true;
	}

	# 计算文件大小
	static function Size($size) {
		$sz = str_split(' KMGTP');
		$factor = floor((strlen($size) - 1) / 3);
		#return ($size>=1024)?sprintf("%.2f", $size / pow(1024, $factor)) . @$sz[$factor]:$size;
		return (($size>=1024)&&isset($sz[$factor])?sprintf("%.2f", $size / pow(1024, $factor)) . $sz[$factor]:$size);
	}
	
	# 读取远程数据
	static function curl_get_contents($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$r = curl_exec($ch);
		curl_close($ch);
		return $r;
	}
	
	# 读取服务器ip
	static function real_server_ip(){
		static $serverip = NULL;
	
		if ($serverip !== NULL)
		{
			return $serverip;
		}
	
		if (isset($_SERVER))
		{
			if (isset($_SERVER['SERVER_ADDR']))
			{
				$serverip = $_SERVER['SERVER_ADDR'];
			}
			else
			{
				$serverip = '0.0.0.0';
			}
		}
		else
		{
			$serverip = getenv('SERVER_ADDR');
		}
	
		return $serverip;
	}

	# 获得用户的真实IP地址
	static function real_ip(){
		static $realip = NULL;
		if ($realip !== NULL){
			return $realip;
		}
		if (isset($_SERVER)){
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
				$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				# 取X-Forwarded-For中第一个非unknown的有效IP字符串
				foreach ($arr AS $ip){
					$ip = trim($ip);
					if ($ip != 'unknown'){
						$realip = $ip;
						break;
					}
				}
			}elseif (isset($_SERVER['HTTP_CLIENT_IP'])){
				$realip = $_SERVER['HTTP_CLIENT_IP'];
			}else{
				if (isset($_SERVER['REMOTE_ADDR'])){
					$realip = $_SERVER['REMOTE_ADDR'];
				}else{
					$realip = '0.0.0.0';
				}
			}
		}else{
			if (getenv('HTTP_X_FORWARDED_FOR')){
				$realip = getenv('HTTP_X_FORWARDED_FOR');
			}elseif (getenv('HTTP_CLIENT_IP')){
				$realip = getenv('HTTP_CLIENT_IP');
			}else{
				$realip = getenv('REMOTE_ADDR');
			}
		}
		preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
		$realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
		return $realip;
	}
	
	# 匹配字符串中的中文字符
	static function preg_zh($str){
		$s = '';
		if(preg_match_all('/[\x{4e00}-\x{9fa5}]+/u',$str,$arr)){
			foreach($arr[0] as $v){
				$s .= $v;
			}
		}
		return $s;
	}
	
	# 匹配字符串中的中英字符及数字与空格
	static function preg_str($str){
	  $s = '';
	  if(preg_match_all('/([a-zA-Z0-9\x{4e00}-\x{9fa5}]|[\s　])+/u',$str,$arr)){
		foreach($arr[0] as $v){
		  $s .= $v;
		}
	  }
	  return $s;
	}
	
	#计算两个字符串相似度（0~100 浮点数）
	static function similar_str($s1,$s2){
		$s1 = preg_str($s1);
		$s2 = preg_str($s2);
		similar_text($s1,$s2,$percent);
		return $percent;
	}
	
	/**
	 * 截取UTF-8编码下字符串的函数
	 *
	 * @param   string	  $str		被截取的字符串
	 * @param   int		 $length	 截取的长度
	 * @param   bool		$append	 是否附加省略号
	 *
	 * @return  string
	 */
	static function sub_str($str, $length = 0, $append = true){
		$str = trim($str);
		$strlength = strlen($str);
	
		if ($length == 0 || $length >= $strlength){
			return $str;
		}elseif ($length < 0){
			$length = $strlength + $length;
			if ($length < 0){
				$length = $strlength;
			}
		}
	
		if (function_exists('mb_substr')){
			$newstr = mb_substr($str, 0, $length, CHARSET);
		}elseif (function_exists('iconv_substr')){
			$newstr = iconv_substr($str, 0, $length, CHARSET);
		}else{
			//$newstr = trim_right(substr($str, 0, $length));
			$newstr = substr($str, 0, $length);
		}
	
		if ($append && $str != $newstr){
			$newstr .= '...';
		}
	
		return $newstr;
	}
	
	# 过滤HTML,用于提取内容摘要
	static function compileHtml($str){
		$str= htmlspecialchars_decode(($str));
		$search = array(
			'@<script[^>]*?>.*?</script>@si',	# Strip out javascript
			'@<[\/\!]*?[^<>]*?>@si',			# Strip out HTML tags
			'@<style[^>]*?>.*?</style>@siU',	# Strip style tags properly
			'@<![\s\S]*?--[ \t\n\r]*>@'			# Strip multi-line comments including CDATA 
		);
		$str = preg_replace($search, '', $str);
		$str = str_replace(array('"',"'",'&nbsp;',"\r","\t","\n"),' ',$str);
		#连续空白字符替换成一个空格
		$str = preg_replace('@\s(?=\s)@i', ' ', $str);
		$str = htmlspecialchars($str);
		return $str;
	}

	# 文件下载
	static function download_file($filepath){
		if($filepath && file_exists($filepath)){
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename($filepath));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filepath));
			readfile($filepath);
			return true;
		}else{
			echo 'file not exists';
			return false;
		}
	}
	
	# 二维数组按指定的键值排序
	static function array_sort($arr, $key, $type = 'desc') {
	    $keysvalue = $new_array = array();
	    foreach ($arr as $k => $v) {
	        $keysvalue[$k] = $v[$key];
	    }
	    if ($type == 'asc') {
	        asort($keysvalue);
	    } else {
	        arsort($keysvalue);
	    }
	    reset($keysvalue);
	    foreach ($keysvalue as $k => $v) {
	        $new_array[$k] = $arr[$k];
	    }
	    return $new_array;
	}
	
	# 判断邮箱格式是否正确
	static function isEmail($email=''){
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	/**
	 * CURL并发请求
	 * @param unknown $urls
	 * @param number $delay
	 * @return multitype:multitype:
	 */
	static function base_rolling_curl($urls, $callback_rolling_curl='callback_base_rolling_curl', $delay=0, $return=false, $time_out=1200) {
		###########################
		$queue = curl_multi_init();
		$map = array();
	
		foreach ($urls as $url) {
			$ch = curl_init();
	
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_NOSIGNAL, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
			curl_multi_add_handle($queue, $ch);
			$map[(string) $ch] = $url;
		}
	
		$responses = array();
		do {
			while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;
	
			if ($code != CURLM_OK) { break; }
	
			// a request was just completed -- find out which one
			while ($done = curl_multi_info_read($queue)) {
	
				// get the info and content returned on the request
				$info = curl_getinfo($done['handle']);
				$error = curl_error($done['handle']);
				$results = is_object($callback_rolling_curl)?$callback_rolling_curl(curl_multi_getcontent($done['handle']), $delay, $info):(self::callback_base_rolling_curl(curl_multi_getcontent($done['handle']), $delay, $info));
				if($return){
					$responses[$map[(string) $done['handle']]] = compact('info', 'error', 'results');
				}
	
				// remove the curl handle that just completed
				curl_multi_remove_handle($queue, $done['handle']);
				curl_close($done['handle']);
			}
	
			// Block for data in / output; error handling is done by curl_multi_exec
			if ($active > 0) {
				curl_multi_select($queue, 0.5);
			}
	
		} while ($active);
	
		curl_multi_close($queue);
		return $responses;
	}
	
	/**
	 * 回调处理(后期根据需要可以根据返回值进行相应的处理)
	 * @param unknown $data
	 * @param unknown $delay
	 * @return multitype:
	 */
	static function callback_base_rolling_curl($data, $delay=0, $info=array()) {
		usleep($delay);
		return $data;
	}
	
	# 获取当前页的url
	static function getBaseUrl($pageString='page'){
		$script_name = parse_url(Esite::app()->request_uri, PHP_URL_PATH);	# 当前uri-path
		$arr = Esite::app()->request;
		unset($arr[$pageString]);	# 删除page项
		$query_string = http_build_query($arr);		# 将数组转换成新的string地址
		# 组成新的URL
		if(empty($query_string)){
			$url=$script_name.'?';
		}
		$url=$script_name.'?'.$query_string.'&';
		$url = trim($url, '?&');
		return $url;
	}
	
	# 计算文件行数 (兼容[自然行[\n], 非自然行[xxx\n]])
	static function countFileLines($filename, $line_ending="\n"){
		$total = 0;
		$fp = new SplFileObject($filename, 'r');
		while ( !$fp->eof() ) {
			$total += substr_count($fp->fgets(), $line_ending);
		}
		return $total;
	}
	
	# 从文件指定行读取内容 (兼容[自然行[\n], 非自然行[xxx\n]])
	static function getFileLines($filename, $startLine = 1, $endLine=50, $line_ending="\n"){
		$startLine = $startLine>1?$startLine:1;
		$content = array();
		$fp = new SplFileObject($filename, 'r');
		if( $fp ){
			# 跳过前面N行
			$total = 0;
			while ( !$fp->eof() && $total<$startLine-1 ) {
				$total += substr_count($fp->fgets(), $line_ending);
			}
			$n = 0;
			$total = $endLine-$startLine+1;
			$line_ending_size = strlen($line_ending);
			while( !$fp->eof() && count($content)<$total ){
				$n++;
				$content[$n] = "";
				while( !$fp->eof() && substr($content[$n], -1*$line_ending_size, $line_ending_size)!=$line_ending ){
					$content[$n] .= $fp->fgets();
				}
			}
		}
		return (array)array_filter($content);
	}
	
	#读取大文件
	static function getFileLinesByEnd($filename,$pagesize=100,$line_ending="\n"){
		$filelines = self::countFileLines($filename,$line_ending);
		return (array)self::getFileLines($filename, $filelines-$pagesize, $filelines, $line_ending);
	}
}
# == Helper.php code end ==

# == Member.php code start ==

/**
 * 会员数据管理
 * 		会员数据（会员信息、会员订单、会员留言）操作，初始化时候会强制要求用户必须登录
 */
class Member{
	# 订单状态列表
	public $orders_status = array(
		0=>'待付款',	# [会员]表示用户新提交的订单
		1=>'已付款',	# [会员]表示用户已经支付成功，提醒后台管理员发货
		2=>'发货中',	# [后台]表示后台已经确认订单受理（此时自动生成发货单）
		3=>'已发货',	# [后台]表示后台已经确认订单发货（仓库根据发货单操作）
		4=>'已取消',	# [会员]用户取消支付
		5=>'已收货',	# [会员]表示用户已经点击【已收货】，或【已发货】超过15天自动调整为收货状态
		6=>'已完成',	# [机器]表示用户收货7天后自动更新为【已完成】
		7=>'已关闭',	# [机器]超过7天【已取消】的订单，或超过7天【已退款】的订单
		8=>'退款中',	# [会员|后台]表示会员申请退款，或超过7天仍处于【待发货】状态、或超过7天仍处于【发货中】
		9=>'已退款',	# [后台]表示后台将【退款中】订单退款处理完毕
	);
	# 订单状态流程，防止订单状态越级操作
	public $orders_status_action = array(
		0=>[1,4],	# 未付款：付款或取消
		1=>[8],		# 已付款：申请退款
		2=>[8],		# 发货中：申请退款
		3=>[5],		# 已发货：确认收货
		4=>[0,7],	# 已取消：重新支付
		5=>[],
		6=>[],
		7=>[],
		8=>[1],		# 退款中：取消退款
		9=>[7],		# 已退款：关闭交易
	);
	
	private static $_instance;
	
	private function __construct(){
		# 禁用缓存
		defined('USE_CACHE')?null:define('USE_CACHE', false);
		# 禁止输出服务器信息
		Msg::$show_serverInfo = false;
		# 检测会员是否登录
		$flag = isset($_SESSION['user_id'])&&$_SESSION['user_id']>0;
		if( Esite::app()->request['ajax']!='yes' ){
			if( !$flag ){
				header("Location: /");die;
			}
		}else{
			if( !$flag ){
				Msg::json_encode('no', 'please login.');
			}
		}
	}
	
	private function __clone(){}
	
	#返回唯一实例
	public static function app(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	# 读取会员信息
	function get_member_info($user_key=''){
		if( Helper::isEmail($user_key) ){
			$sql = sprintf("select * from member_list where email='%s' ", $user_key);
		}else{
			$sql = sprintf("select * from member_list where user_id='%s' ", intval($user_key));
		}
		$member = Esite::$db->getRow($sql);
		return $member;
	}
	
	# 更新会员信息
	function update_member_info($member_info=array(), $captcha=''){
		if( !Esite::app()->check_captcha($captcha, 'mail_captcha_word') ){
			return false;
		}
		$udata = array();
		# 'username', 'phone'
		if( isset($member_info['username']) ){
			$udata['username'] = $member_info['username'];
		}
		if( isset($member_info['phone']) ){
			$udata['phone'] = $member_info['phone'];
		}
		if( empty($udata) ){
			return false;
		}else{
			Esite::$db->update('member_list', $udata, " email='{$_SESSION['email']}' ");
			return true;
		}
	}
	
	# 获取订单信息列表
	function orders($user_id=0, $page=1, $pagesize=10){
		$user_id = $user_id>0?intval($user_id):intval(@$_SESSION['user_id']);
		$page = $page>0?intval($page):1;
		$pagesize = $pagesize>0?intval($pagesize):10;
		$limit = ($page-1)*$pagesize;
		$sql = "select orders_id from member_orders where user_id='{$user_id}' limit {$limit},{$pagesize} ";
		$orders = Esite::$db->getAll($sql);
		if( count($orders)>0 ){
			$orders = array_combine(array_column($orders, 'orders_id'), $orders);
			foreach($orders as $k=>$v){
				$orders[$v['orders_id']] = $this->orders_info($v['orders_id']);
			}
		}
		return $orders;
	}
	
	#############################################
	#############################################
	#############################################
	
	
	# 生成订单号
	function get_orders_sn($site_id, $orders_id){
		return sprintf("S{$site_id}O%sE", str_pad($orders_id, 6, '0', STR_PAD_LEFT));
	}
	
	# 解析订单号
	function parse_orders_sn($orders_sn=''){
		$orders_info = array();
		if( preg_march('@S([0-9]+)O([0-9]+)E@i', $orders_sn, $match) ){
			$orders_info['site_id'] = isset($match[1])?intval($match[1]):0;
			$orders_info['orders_id'] = isset($match[2])?intval($match[2]):0;
		}
		return $orders_info;
	}
	
	# 订单信息
	function orders_info($orders_id=0){
		$orders_id = intval($orders_id);
		# 订单基本信息
		$orders_info = Esite::$db->getRow("select * from member_orders where orders_id='{$orders_id}' and user_id='{$_SESSION['user_id']}' ");
		if( $orders_info ){
			$orders_info['address'] = $this->address_info($orders_info['address_id']);
			# 订单商品信息
			$goods_info = Esite::$db->getAll("select * from member_orders_goods where orders_id='{$orders_id}' ");
			# 订单用户信息
			$member_info = isset($orders_info['user_id'])?$this->get_member_info($orders_info['user_id']):array();
			# 站点信息
			$site_info = Esite::app()->cfg['site_info'];
			$site_info = $site_info?array_combine(array_column($site_info, 'name'), array_column($site_info, 'value')):array();
			$site_info['site_id'] = Esite::app()->site_id;
			$site_info['weight_unit'] = isset($site_info['weight_unit'])&&strlen($site_info['weight_unit'])>0?$site_info['weight_unit']:'Kg';
			$site_info['unit_freight'] = isset($site_info['unit_freight'])&&is_numeric($site_info['unit_freight'])?round($site_info['unit_freight'], 4):0;
			$site_info['basic_freight'] = isset($site_info['basic_freight'])&&is_numeric($site_info['basic_freight'])?round($site_info['basic_freight'], 4):0;
			$site_info['free_freight'] = isset($site_info['free_freight'])&&is_numeric($site_info['free_freight'])?round($site_info['free_freight'], 4):0;
			# 发货单编号
			$orders_sn = $this->get_orders_sn($site_info['site_id'], $orders_id);
			$orders_atime = time();
			# 货品重量计算
			$weight_total = 0;
			if( is_array($goods_info) && $goods_info ){
				foreach($goods_info as $k=>$v){
					$weight_total += ($v['goods_num']*$v['goods_weight']);
				}
				$weight_total = round($weight_total, 4);
			}
			# 运费计算
			# 	按重量计费，当订单金额达到【free_freight】的数值则免运费；
			# 名词定义：
			# 	weight_unit		重量单位，默认Kg
			#	unit_freight	每重量单位的累加运费金额
			#	basic_freight	基本运费，表示货物总重量不超过单位重量时的运费
			#	free_freight	免运费订单金额标准，表示订单金额在此数值以上就免运费，为0表示所有订单免运费
			# 运费 = ((ceil(sum([商品数量] * [商品重量])) - 1) * [unit_freight]) + [basic_freight]
			$freight = 0;
			if( isset($orders_info['orders_money']) && $site_info['free_freight']>round($orders_info['orders_money'], 4) ){
				$freight = $weight_total>1?((ceil($weight_total) - 1) * $site_info['unit_freight']) + $site_info['basic_freight']:$site_info['basic_freight'];
			}
			$pay_money = isset($orders_info['orders_money'])?round($freight + $orders_info['orders_money'], 4):0;
			$orders_info['status_name'] = isset($this->orders_status[$orders_info['status']])?$this->orders_status[$orders_info['status']]:'';
		}
		return get_defined_vars();
	}

	# 新增订单: 来自购物车商品提交
	# 参数（HTTP）：
	#	cart_ids	购物车商品id列表，采用半角逗号分隔多个参数
	# 返回值：
	#	orders_id	新订单编号
	function orders_add(){
		# 参数初始化
		$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
		$cart_ids = isset(Esite::app()->request['cart_ids'])?implode(',', array_unique(array_filter(array_map('trim', (array)explode(',', Esite::app()->request['cart_ids']))))):'';
		# 读取购物车商品（当有提交部分商品ID这仅提取部分商品）
		$where = $cart_ids?" c.id in ({$cart_ids}) ":' 1=1 ';
		$sql = "select c.*,p.name as product_name,p.weight as product_weight from cart as c left join product as p on p.id=c.product_id where {$where}";
		$products = Esite::$db->getAll($sql);
		if( (count($products) * $user_id)<1 ){
			return 0;
		}
		# 生成订单基本信息
		$orders_info = array();
		$orders_info['user_id'] = $user_id;
		$orders_info['orders_money'] = $products?array_sum(array_column($products, 'money')):0;	# 系统计算，禁止修改
		$orders_info['status'] = 0;
		$orders_info['atime'] = time();
		Esite::$db->insert('member_orders', $orders_info);
		# 读取用户最新订单
		$orders_info = Esite::$db->getRow("select * from member_orders where user_id='{$user_id}' and atime='{$orders_info['atime']}' ");
		if( $orders_info ){
			# 更新订单商品
			foreach($products as $k=>$v){
				$udata = array(
					'orders_id'=>$orders_info['orders_id'],
					'goods_id'=>$v['product_id'],
					'goods_name'=>$v['product_name'],
					'goods_price'=>$v['price'],
					'goods_weight'=>$v['product_weight'],
					'goods_num'=>$v['numbers'],
					'exts'=>$v['exts'],
				);
				$udata = array_map(array(Esite::app()->db, 'escapeString'), $udata);
				Esite::$db->insert('member_orders_goods', $udata, true);
			}
			# 删除购物车对应商品
			$sql = sprintf("delete from cart where id in (%s)", implode(',', array_column($products, 'id')));
			Esite::$db->query($sql);
		}
		$_SESSION['orders_id'] = isset($orders_info['orders_id'])?$orders_info['orders_id']:0;
		return $_SESSION['orders_id'];
	}

	# 订单审查（用户）
	#	address_id	收货地址
	#	notes		订单备注
	#	pay_type	付款类型：paypal
	function orders_checkout(){
		# 参数初始化
		$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
		$orders_id = isset(Esite::app()->request['orders_id'])?intval(Esite::app()->request['orders_id']):(isset($_SESSION['orders_id'])?intval($_SESSION['orders_id']):0);
		$address_id = isset(Esite::app()->request['address_id'])&&Esite::app()->request['address_id']?trim(Esite::app()->request['address_id']):0;
		$notes = isset(Esite::app()->request['notes'])?trim(Esite::app()->request['notes']):'';
		$pay_type = isset(Esite::app()->request['pay_type'])&&in_array(Esite::app()->request['pay_type'], Esite::app()->pay_type)?Esite::app()->request['pay_type']:current(Esite::app()->pay_type);
		if( ($address_id * $orders_id * $user_id)<1 ){
			Msg::$messages[] = 'address_id/orders_id/user_id is empty';
			return false;
		}
		# 读取订单信息
		$orders_info = $this->orders_info($orders_id);
		if( !isset($orders_info['pay_money']) 
			|| !isset($orders_info['orders_info']['user_id']) 
			|| $orders_info['orders_info']['user_id']!=$user_id 
			|| $orders_info['orders_info']['status']!=0
		){
			Msg::$messages[] = 'error: orders_info.';
			return false;
		}
		# 更新订单信息
		$udata = array(
			'pay_type'=>strtolower($pay_type),
			'pay_money'=>$orders_info['pay_money'],
			'notes'=>$notes,
			'address_id'=>$address_id,
			'utime'=>time()
		);
		$flag = Esite::$db->update('member_orders', $udata, " orders_id='{$orders_id}' and user_id='{$user_id}' ");
		if( $flag ){
			if( isset($_SESSION['orders_id']) ){
				unset($_SESSION['orders_id']);
			}
			$flag = $this->orders_pay($orders_id);
		}
		return $flag;
	}

	# 订单付款
	public $payment_url = '';
	function orders_pay($orders_id){
		$orders_id = intval($orders_id);
		$res = Esite::app()->server_api('api/payment', array('orders_id'=>$orders_id));
		$this->payment_url = isset($res['data']['payment_url'])?$res['data']['payment_url']:'';
		return isset($res['status'])&&$res['status']=='yes';
	}

	# 消息列表
	function msg_list($user_id=0, $page=1, $pagesize=10){
		$user_id = $user_id>0?intval($user_id):intval(@$_SESSION['user_id']);
		$page = $page>0?intval($page):1;
		$pagesize = $pagesize>0?intval($pagesize):10;
		$limit = ($page-1)*$pagesize;
		$sql = "select * from member_msg where user_id='{$user_id}' order by id desc limit {$limit},{$pagesize} ";
		$messages = Esite::$db->getAll($sql);
		if( count($messages)>0 ){
			$messages = array_combine(array_column($messages, 'id'), $messages);
			foreach($messages as $k=>$v){
				$messages[$v['id']]['reply'] = Esite::$db->getAll("select * from member_msg_reply where msg_id='{$v['id']}' ");
			}
		}
		return $messages;
	}
	
	# 消息存储
	function msg_save($user_id, $msg_id, $msg_data=array()){
		$user_id = $user_id>0?intval($user_id):intval(@$_SESSION['user_id']);
		$msg_id = intval($msg_id);
		$udata = array(
			'user_id'=>$user_id,
			'title'=>isset($msg_data['title'])?$msg_data['title']:'',
			'content'=>isset($msg_data['content'])?$msg_data['content']:'',
			'atime'=>time(),
		);
		if( strlen($udata['title'])<1 ){
			return false;
		}
		$udata = array_map(array(Esite::app()->db, 'escapeString'), $udata);
		$tmp = Esite::$db->getRow("select * from member_msg where (user_id='{$user_id}' and title='{$udata['title']}') or id='{$msg_id}' ");
		if( isset($tmp['id']) && $tmp['id']>0 ){
			# 仅支持更新内容，禁止更新主题，防止用户更新主题后【回复与主题】不一致情况
			Esite::$db->update('member_msg', ['content'=>$udata['content']], " id='{$tmp['id']}' ");
		}else{
			Esite::$db->insert('member_msg', $udata);
		}
		return true;
	}
	
	# 对话存储
	function msg_reply_save($user_id, $msg_id, $id, $content=''){
		$user_id = $user_id>0?intval($user_id):intval(@$_SESSION['user_id']);
		$msg_id = intval($msg_id);
		$id = intval($id);
		# 判断数据是否存在
		$tmp = Esite::$db->getRow("select * from member_msg where user_id='{$user_id}' and id='{$msg_id}' ");
		if( !isset($tmp['id']) || strlen($content)<1 ){
			return false;
		}
		$udata = array(
			'msg_id'=>$msg_id,
			'user_id'=>$user_id,
			'content'=>isset($content)?$content:'',
			'atime'=>time(),
		);
		$udata = array_map(array(Esite::app()->db, 'escapeString'), $udata);
		$tmp = Esite::$db->getRow("select * from member_msg_reply where id='{$id}' ");
		if( isset($tmp['id']) && $tmp['id']>0 ){
			Esite::$db->update('member_msg_reply', $udata, " id='{$tmp['id']}' ");
		}else{
			Esite::$db->insert('member_msg_reply', $udata);
		}
		return true;
	}

	# 收货地址列表
	function address_list(){
		$user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;
		$address_list = Esite::$db->getAll("select * from member_address where user_id='{$user_id}' order by is_default desc ");
		$address_list = $address_list?array_map(array($this, 'address_format'), $address_list):array();
		return $address_list;
	}
	
	# 地址格式化
	function address_format($address_info=array()){
		if($address_info){
			$address_info['address'] = "{$address_info['countryCode']} {$address_info['state']} {$address_info['city']} {$address_info['line1']} {$address_info['line2']}";
		}
		return $address_info;
	}
	
	# 根据地址ID读取地址信息
	function address_info($id=0){
		$id = intval($id);
		$user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;
		$address_info = Esite::$db->getRow("select * from member_address where id='{$id}' and user_id='{$user_id}' ");
		$address_info = $this->address_format($address_info);
		return $address_info;
	}
	
	# 编辑地址信息(更新/添加)
	function address_edit(){
		$request = Esite::app()->request;
		$user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;
		# 数据验证
		if(
			!(isset($request['username']) && strlen($request['username'])>0)
			|| !(isset($request['line1']) && strlen($request['line1'])>0)
			|| !(isset($request['line2']) && strlen($request['line2'])>0)
			|| !(isset($request['city']) && strlen($request['city'])>0)
			|| !(isset($request['state']) && strlen($request['state'])>0)
			|| !(isset($request['phone']) && strlen($request['phone'])>0)
			|| !(isset($request['postalCode']) && strlen($request['postalCode'])>0)
			|| !(isset($request['countryCode']) && strlen($request['countryCode'])>0)
			|| !(isset($request['is_default']) && strlen($request['is_default'])>0)
		){
			return false;
		}
		$request = array_map(array(Esite::app()->db, 'escapeString'), $request);
		$udata = array(
			'username'=>$request['username'],
			'line1'=>$request['line1'],
			'line2'=>$request['line2'],
			'city'=>$request['city'],
			'state'=>$request['state'],
			'phone'=>$request['phone'],
			'postalCode'=>$request['postalCode'],
			'countryCode'=>$request['countryCode'],
			'is_default'=>$request['is_default'],
			'user_id'=>$user_id
		);
		# 设置默认收货地址，清除原有的默认收货地址
		if( $udata['is_default']==1 ){
			Esite::$db->update('member_address', array('is_default'=>0), " user_id='{$user_id}' ");
		}
		if( isset($request['id']) && is_numeric($request['id']) && $request['id']>0){
			Esite::$db->update('member_address', $udata, " id='{$request['id']}' and user_id='{$user_id}' ");
		}else{
			Esite::$db->insert('member_address', $udata);
		}
		return true;
	}
}
# == Member.php code end ==

# == Msg.php code start ==


# 输出类
class Msg{
	public static $show_serverInfo = true;
	# 存储应用消息
	public static $message = array();

	public static function json_encode($status='yes', $message='', $data=null, $die=true){
		if( self::$show_serverInfo ){
			$data['APPPATH'] = APPPATH;
			$data['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
			$data['DB_INFO'] = Esite::$db->info;
		}
		$message .= self::$message?"\n".implode("\n", self::$message):"";
		if( $die ){
			die( json_encode(array('status'=>$status, 'message'=>$message, 'data'=>$data)) );
		}else{
			echo json_encode(array('status'=>$status, 'message'=>$message, 'data'=>$data));
		}
	}
	
	public static function log($log=''){
		$log = sprintf("%s\t%s\n\n");
		file_put_contents('esite.log', $log, FILE_APPEND);
	}
	
}
# == Msg.php code end ==

# == Sendmail.php code start ==


/**
* 邮件发送类
* 仅支持发送纯文本和HTML内容邮件
* 需要的php扩展，sockets
* @example
* $mail = new SocketSendMail();
* $mail->setServer("XXXXX", "XXXXX@XXXXX", "XXXXX"); 设置smtp服务器
* $mail->setFrom("XXXXX"); 设置发件人
* $mail->setReceiver("XXXXX"); 设置收件人
* $mail->setMailInfo("test", "<b>test</b>"); 设置邮件主题、内容
* $mail->sendMail(); 发送
*/
class SocketSendMail {
	/**
	* @var string 邮件传输代理用户名
	* @access private
	*/
	private $_userName;

	/**
	* @var string 邮件传输代理密码
	* @access private
	*/
	private $_password;

	/**
	* @var string 邮件传输代理服务器地址
	* @access private
	*/
	private $_sendServer;

	/**
	* @var int 邮件传输代理服务器端口
	* @access protected
	*/
	protected $_port=25;

	/**
	* @var string 发件人
	* @access protected
	*/
	protected $_from;

	/**
	* @var string 收件人
	* @access protected
	*/
	protected $_to;

	/**
	* @var string 主题
	* @access protected
	*/
	protected $_subject;

	/**
	* @var string 邮件正文
	* @access protected
	*/
	protected $_body;

	/**
	* @var reource socket资源
	* @access protected
	*/
	protected $_socket;

	/**
	* @var string 错误信息
	* @access protected
	*/
	protected $_errorMessage;


	/**
	* 设置邮件传输代理，如果是可以匿名发送有邮件的服务器，只需传递代理服务器地址就行
	* @access public
	* @param string $server 代理服务器的ip或者域名
	* @param string $username 认证账号
	* @param string $password 认证密码
	* @param int $port 代理服务器的端口，smtp默认25号端口
	* @return boolean
	*/
	public function setServer($server, $username="", $password="", $port=25) {
		$this->_sendServer = $server;
		$this->_port = $port;
		if(!empty($username)) {
			$this->_userName = base64_encode($username);
		}
		if(!empty($password)) {
			$this->_password = base64_encode($password);
		}
		return true;
	}

	/**
	* 设置发件人
	* @access public
	* @param string $from 发件人地址
	* @return boolean
	*/
	public function setFrom($from) {
		$this->_from = $from;
		return true;
	}

	/**
	* 设置收件人
	* @access public
	* @param string $to 收件人地址
	* @return boolean
	*/
	public function setReceiver($to) {
		$this->_to = $to;
		return true;
	}

	/**
	* 设置邮件信息
	* @access public
	* @param string $body 邮件主题
	* @param string $subject 邮件主体内容，可以是纯文本，也可是是HTML文本
	* @return boolean
	*/
	public function setMailInfo($subject, $body) {
		$this->_subject = $subject;
		$this->_body = base64_encode($body);
		if(!empty($attachment)) {
			$this->_attachment = $attachment;
		}
		return true;
	}

	/**
	* 发送邮件
	* @access public
	* @return boolean
	*/
	public function sendMail() {
		$command = $this->getCommand();
		$this->socket();
		
		foreach ($command as $value) {
			if($this->sendCommand($value[0], $value[1])) {
				continue;
			}
			else{
				return false;
			}
		}
		
		# 其实这里也没必要关闭，smtp命令：QUIT发出之后，服务器就关闭了连接，本地的socket资源会自动释放
		$this->close();
		return true;
	}

	/**
	* 返回错误信息
	* @return string
	*/
	public function error(){
		if(!isset($this->_errorMessage)) {
			$this->_errorMessage = "";
		}
		return $this->_errorMessage;
	}

	/**
	* 返回mail命令
	* @access protected
	* @return array
	*/
	protected function getCommand() {
		$separator = "----=_Part_" . md5($this->_from . time()) . uniqid(); //分隔符

		$command = array(
				array("HELO sendmail\r\n", 250)
			);
		if(!empty($this->_userName)){
			$command[] = array("AUTH LOGIN\r\n", 334);
			$command[] = array($this->_userName . "\r\n", 334);
			$command[] = array($this->_password . "\r\n", 235);
		}

		//设置发件人
		$command[] = array("MAIL FROM: <" . $this->_from . ">\r\n", 250);
		$header = "FROM: <" . $this->_from . ">\r\n";

		//设置收件人
		$command[] = array("RCPT TO: <" . $this->_to . ">\r\n", 250);
		$header .= "TO: <" . $this->_to . ">\r\n";

		$header .= "Subject: " . $this->_subject ."\r\n";
		$header .= "Content-Type: multipart/alternative;\r\n";

		//邮件头分隔符
		$header .= "\t" . 'boundary="' . $separator . '"';

		$header .= "\r\nMIME-Version: 1.0\r\n";
		$header .= "\r\n--" . $separator . "\r\n";
		$header .= "Content-Type:text/html; charset=utf-8\r\n";
		$header .= "Content-Transfer-Encoding: base64\r\n\r\n";
		$header .= $this->_body . "\r\n";
		$header .= "--" . $separator . "\r\n";

		//结束数据
		$header .= "\r\n.\r\n";


		$command[] = array("DATA\r\n", 354);
		$command[] = array($header, 250);
		$command[] = array("QUIT\r\n", 221);
		
		return $command;
	}

	/**
	* 发送命令
	* @access protected
	* @param string $command 发送到服务器的smtp命令
	* @param int $code 期望服务器返回的响应吗
	* @return boolean
	*/
	protected function sendCommand($command, $code) {
		//发送命令给服务器
		try{
			if(socket_write($this->_socket, $command, strlen($command))){

				//当邮件内容分多次发送时，没有$code，服务器没有返回
				if(empty($code))  {
					return true;
				}

				//读取服务器返回
				$data = trim(socket_read($this->_socket, 1024));

				if($data) {
					$pattern = "/^".$code."/";
					if(preg_match($pattern, $data)) {
						return true;
					}
					else{
						$this->_errorMessage = "Error:" . $data . "|**| command:";
						return false;
					}
				}
				else{
					$this->_errorMessage = "Error:" . socket_strerror(socket_last_error());
					return false;
				}
			}
			else{
				$this->_errorMessage = "Error:" . socket_strerror(socket_last_error());
				return false;
			}
		}catch(Exception $e) {
			$this->_errorMessage = "Error:" . $e->getMessage();
		}
	}

	/**
	* 建立到服务器的网络连接
	* @access private
	* @return boolean
	*/
	private function socket() {
		if(!function_exists("socket_create")) {
			$this->_errorMessage = "Extension sockets must be enabled";
			return false;
		}
		//创建socket资源
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
		
		if(!$this->_socket) {
			$this->_errorMessage = socket_strerror(socket_last_error());
			return false;
		}

		socket_set_block($this->_socket);//设置阻塞模式

		//连接服务器
		if(!socket_connect($this->_socket, $this->_sendServer, $this->_port)) {
			$this->_errorMessage = socket_strerror(socket_last_error());
			return false;
		}
		socket_read($this->_socket, 1024);
		
		return true;
	}

	/**
	* 关闭socket
	* @access private
	* @return boolean
	*/
	private function close() {
		if(isset($this->_socket) && is_object($this->_socket)) {
			$this->_socket->close();
			return true;
		}
		$this->_errorMessage = "No resource can to be close";
		return false;
	}
}
# == Sendmail.php code end ==

# == Snoopy.php code start ==

class Snoopy{
	/* user definable vars */
	var $isDebug = false;	#是否启用调试，启用调试会打印HTTP头
	var $formatUrl = false;	#是否自动补全内容中的URL
	var $formatBaseURI = '';	#url自动补充时候需要的基准地址
	var $scheme = 'http'; // http or https
	var $host = "www.php.net"; // host name we are connecting to
	var $port = 80; // port we are connecting to
	var $proxy_host = ""; // proxy host to use
	var $proxy_port = ""; // proxy port to use
	var $proxy_user = ""; // proxy user to use
	var $proxy_pass = ""; // proxy password to use

	var $agent = ""; // agent we masquerade as
	var $referer = ""; // referer info to pass
	var $cookies = array(); // array of cookies to pass
	// $cookies["username"]="joe";
	var $rawheaders = array(); // array of raw headers to send
	// $rawheaders["Content-type"]="text/html";

	var $maxredirs = 5; // http redirection depth maximum. 0 = disallow
	var $lastredirectaddr = ""; // contains address of last redirected address
	var $offsiteok = true; // allows redirection off-site
	var $maxframes = 0; // frame content depth maximum. 0 = disallow
	var $expandlinks = true; // expand links to fully qualified URLs.
	// this only applies to fetchlinks()
	// submitlinks(), and submittext()
	var $passcookies = true; // pass set cookies back through redirects
	// NOTE: this currently does not respect
	// dates, domains or paths.

	var $user = ""; // user for http authentication
	var $pass = ""; // password for http authentication

	// http accept types
	var $accept = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";

	var $results = ""; // where the content is put

	var $error = ""; // error messages sent here
	var $response_code = ""; // response code returned from server
	var $headers = array(); // headers returned from server sent here
	var $maxlength = 500000; // max return data length (body)
	var $read_timeout = 0; // timeout on read operations, in seconds
	// supported only since PHP 4 Beta 4
	// set to 0 to disallow timeouts
	var $timed_out = false; // if a read operation timed out
	var $status = 0; // http request status

	var $temp_dir = "/tmp"; // temporary directory that the webserver
	// has permission to write to.
	// under Windows, this should be C:\temp

	var $curl_path = false;
	// deprecated, snoopy no longer uses curl for https requests,
	// but instead requires the openssl extension.

	// send Accept-encoding: gzip?
	var $use_gzip = true;

	// file or directory with CA certificates to verify remote host with
	var $cafile;
	var $capath;

	/**** Private variables ****/

	var $_maxlinelen = 4096; // max line length (headers)

	var $_httpmethod = "GET"; // default http request method
	var $_httpversion = "HTTP/1.0"; // default http request version
	var $_submit_method = "POST"; // default submit method
	var $_submit_type = "application/x-www-form-urlencoded"; // default submit type
	var $_mime_boundary = ""; // MIME boundary for multipart/form-data submit type
	var $_redirectaddr = false; // will be set if page fetched is a redirect
	var $_redirectdepth = 0; // increments on an http redirect
	var $_frameurls = array(); // frame src urls
	var $_framedepth = 0; // increments on frame depth

	var $_isproxy = false; // set if using a proxy server
	var $_fp_timeout = 30; // timeout for socket connection

	function __construct(){
		$userAgents = array(
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)',
			'Mozilla/5.0 (Windows; U; Windows NT 5.2) Gecko/2008070208 Firefox/3.0.1',
			'Opera/9.27 (Windows NT 5.2; U; zh-cn)',
			'Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Version/3.1 Safari/525.13',
			'Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.12) Gecko/20080219 Firefox/2.0.0.12 Navigator/9.0.0.6',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; 360SE)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; 360SE)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ;  QIHU 360EE)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; Maxthon/3.0)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; TencentTraveler 4.0; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) )',
			'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/534.55.3 (KHTML, like Gecko) Version/5.1.5 Safari/534.55.3',
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.94 Safari/537.36'
		);
		$this->agent = empty($this->agent)?$userAgents[array_rand($userAgents,1)]:$this->agent;
	}
	
	/*======================================================================*\
		Function:	fetch
		Purpose:	fetch the contents of a web page
					(and possibly other protocols in the
					future like ftp, nntp, gopher, etc.)
		Input:		$URI	the location of the page to fetch
		Output:		$this->results	the output text from the fetch
	\*======================================================================*/

	function fetch($URI)
	{
		$this->formatBaseURI = empty($this->formatBaseURI)?$URI:$this->formatBaseURI;
		$URI_PARTS = parse_url($URI);
		if (!empty($URI_PARTS["user"]))
			$this->user = $URI_PARTS["user"];
		if (!empty($URI_PARTS["pass"]))
			$this->pass = $URI_PARTS["pass"];
		if (empty($URI_PARTS["query"]))
			$URI_PARTS["query"] = '';
		if (empty($URI_PARTS["path"]))
			$URI_PARTS["path"] = '';

		$fp = null;

		switch (strtolower($URI_PARTS["scheme"])) {
			case "https":
				if (!extension_loaded('openssl')) {
					trigger_error("openssl extension required for HTTPS", E_USER_ERROR);
					exit;
				}
				$this->port = 443;
			case "http":
				//设置http默认80端口，防止在http与https多级跳转时候端口出错
				$this->port = strtolower($URI_PARTS["scheme"])=='http'?80:$this->port;
				$this->scheme = strtolower($URI_PARTS["scheme"]);
				$this->host = $URI_PARTS["host"];
				if (!empty($URI_PARTS["port"])){
					$this->port = $URI_PARTS["port"];
				}
				if ($this->_connect($fp)) {
					if ($this->_isproxy) {
						// using proxy, send entire URI
						$this->_httprequest($URI, $fp, $URI, $this->_httpmethod);
					} else {
						$path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
						// no proxy, send only the path
						$this->_httprequest($path, $fp, $URI, $this->_httpmethod);
					}

					$this->_disconnect($fp);

					if ($this->_redirectaddr) {
						/* url was redirected, check if we've hit the max depth */
						if ($this->maxredirs > $this->_redirectdepth) {
							// only follow redirect if it's on this site, or offsiteok is true
							if (preg_match("|^https?://" . preg_quote($this->host) . "|i", $this->_redirectaddr) || $this->offsiteok) {
								/* follow the redirect */
								$this->_redirectdepth++;
								$this->lastredirectaddr = $this->_redirectaddr;
								$this->fetch($this->_redirectaddr);
							}
						}
					}

					if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0) {
						$frameurls = $this->_frameurls;
						$this->_frameurls = array();

						foreach($frameurls as $frameurl) {
							if ($this->_framedepth < $this->maxframes) {
								$this->fetch($frameurl);
								$this->_framedepth++;
							} else
								break;
						}
					}
				} else {
					return false;
				}
				return $this;
				break;
			default:
				// not a valid protocol
				$this->error = 'Invalid protocol "' . $URI_PARTS["scheme"] . '"\n';
				return false;
				break;
		}
		return $this;
	}

	/*======================================================================*\
		Function:	submit
		Purpose:	submit an http(s) form
		Input:		$URI	the location to post the data
					$formvars	the formvars to use.
						format: $formvars["var"] = "val";
					$formfiles  an array of files to submit
						format: $formfiles["var"] = "/dir/filename.ext";
		Output:		$this->results	the text output from the post
	\*======================================================================*/

	function submit($URI, $formvars = "", $formfiles = "")
	{
		unset($postdata);

		$postdata = $this->_prepare_post_body($formvars, $formfiles);

		$URI_PARTS = parse_url($URI);
		if (!empty($URI_PARTS["user"]))
			$this->user = $URI_PARTS["user"];
		if (!empty($URI_PARTS["pass"]))
			$this->pass = $URI_PARTS["pass"];
		if (empty($URI_PARTS["query"]))
			$URI_PARTS["query"] = '';
		if (empty($URI_PARTS["path"]))
			$URI_PARTS["path"] = '';

		switch (strtolower($URI_PARTS["scheme"])) {
			case "https":
				if (!extension_loaded('openssl')) {
					trigger_error("openssl extension required for HTTPS", E_USER_ERROR);
					exit;
				}
				$this->port = 443;
			case "http":
				$this->scheme = strtolower($URI_PARTS["scheme"]);
				$this->host = $URI_PARTS["host"];
				if (!empty($URI_PARTS["port"]))
					$this->port = $URI_PARTS["port"];
				if ($this->_connect($fp)) {
					if ($this->_isproxy) {
						// using proxy, send entire URI
						$this->_httprequest($URI, $fp, $URI, $this->_submit_method, $this->_submit_type, $postdata);
					} else {
						$path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
						// no proxy, send only the path
						$this->_httprequest($path, $fp, $URI, $this->_submit_method, $this->_submit_type, $postdata);
					}

					$this->_disconnect($fp);
					if ($this->_redirectaddr) {
						/* url was redirected, check if we've hit the max depth */
						if ($this->maxredirs > $this->_redirectdepth) {
							if (!preg_match("|^" . $URI_PARTS["scheme"] . "://|", $this->_redirectaddr))
								$this->_redirectaddr = $this->_expandlinks($this->_redirectaddr, $URI_PARTS["scheme"] . "://" . $URI_PARTS["host"]);

							// only follow redirect if it's on this site, or offsiteok is true
							if (preg_match("|^https?://" . preg_quote($this->host) . "|i", $this->_redirectaddr) || $this->offsiteok) {
								/* follow the redirect */
								$this->_redirectdepth++;
								$this->lastredirectaddr = $this->_redirectaddr;
								if (strpos($this->_redirectaddr, "?") > 0){
									$this->fetch($this->_redirectaddr); // the redirect has changed the request method from post to get
								}else{
									$this->submit($this->_redirectaddr, $formvars, $formfiles);
								}
							}
						}
					}
					if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0) {
						$frameurls = $this->_frameurls;
						$this->_frameurls = array();

						foreach($frameurls as $frameurl) {
							if ($this->_framedepth < $this->maxframes) {
								$this->fetch($frameurl);
								$this->_framedepth++;
							} else
								break;
						}
					}

				} else {
					return false;
				}
				return $this;
				break;
			default:
				// not a valid protocol
				$this->error = 'Invalid protocol "' . $URI_PARTS["scheme"] . '"\n';
				return false;
				break;
		}
		return $this;
	}

	/*======================================================================*\
		Function:	fetchlinks
		Purpose:	fetch the links from a web page
		Input:		$URI	where you are fetching from
		Output:		$this->results	an array of the URLs
	\*======================================================================*/

	function fetchlinks($URI)
	{
		if ($this->fetch($URI) !== false) {
			if ($this->lastredirectaddr)
				$URI = $this->lastredirectaddr;
			if (is_array($this->results)) {
				for ($x = 0; $x < count($this->results); $x++)
					$this->results[$x] = $this->_striplinks($this->results[$x]);
			} else
				$this->results = $this->_striplinks($this->results);

			if ($this->expandlinks)
				$this->results = $this->_expandlinks($this->results, $URI);
			return $this;
		} else
			return false;
	}

	/*======================================================================*\
		Function:	fetchform
		Purpose:	fetch the form elements from a web page
		Input:		$URI	where you are fetching from
		Output:		$this->results	the resulting html form
	\*======================================================================*/

	function fetchform($URI)
	{

		if ($this->fetch($URI) !== false) {

			if (is_array($this->results)) {
				for ($x = 0; $x < count($this->results); $x++)
					$this->results[$x] = $this->_stripform($this->results[$x]);
			} else
				$this->results = $this->_stripform($this->results);

			return $this;
		} else
			return false;
	}


	/*======================================================================*\
		Function:	fetchtext
		Purpose:	fetch the text from a web page, stripping the links
		Input:		$URI	where you are fetching from
		Output:		$this->results	the text from the web page
	\*======================================================================*/

	function fetchtext($URI)
	{
		if ($this->fetch($URI) !== false) {
			if (is_array($this->results)) {
				for ($x = 0; $x < count($this->results); $x++)
					$this->results[$x] = $this->_striptext($this->results[$x]);
			} else
				$this->results = $this->_striptext($this->results);
			return $this;
		} else
			return false;
	}

	/*======================================================================*\
		Function:	submitlinks
		Purpose:	grab links from a form submission
		Input:		$URI	where you are submitting from
		Output:		$this->results	an array of the links from the post
	\*======================================================================*/

	function submitlinks($URI, $formvars = "", $formfiles = "")
	{
		if ($this->submit($URI, $formvars, $formfiles) !== false) {
			if ($this->lastredirectaddr)
				$URI = $this->lastredirectaddr;
			if (is_array($this->results)) {
				for ($x = 0; $x < count($this->results); $x++) {
					$this->results[$x] = $this->_striplinks($this->results[$x]);
					if ($this->expandlinks)
						$this->results[$x] = $this->_expandlinks($this->results[$x], $URI);
				}
			} else {
				$this->results = $this->_striplinks($this->results);
				if ($this->expandlinks)
					$this->results = $this->_expandlinks($this->results, $URI);
			}
			return $this;
		} else
			return false;
	}

	/*======================================================================*\
		Function:	submittext
		Purpose:	grab text from a form submission
		Input:		$URI	where you are submitting from
		Output:		$this->results	the text from the web page
	\*======================================================================*/

	function submittext($URI, $formvars = "", $formfiles = "")
	{
		if ($this->submit($URI, $formvars, $formfiles) !== false) {
			if ($this->lastredirectaddr)
				$URI = $this->lastredirectaddr;
			if (is_array($this->results)) {
				for ($x = 0; $x < count($this->results); $x++) {
					$this->results[$x] = $this->_striptext($this->results[$x]);
					if ($this->expandlinks)
						$this->results[$x] = $this->_expandlinks($this->results[$x], $URI);
				}
			} else {
				$this->results = $this->_striptext($this->results);
				if ($this->expandlinks)
					$this->results = $this->_expandlinks($this->results, $URI);
			}
			return $this;
		} else
			return false;
	}


	/*======================================================================*\
		Function:	set_submit_multipart
		Purpose:	Set the form submission content type to
					multipart/form-data
	\*======================================================================*/
	function set_submit_multipart()
	{
		$this->_submit_type = "multipart/form-data";
		return $this;
	}


	/*======================================================================*\
		Function:	set_submit_normal
		Purpose:	Set the form submission content type to
					application/x-www-form-urlencoded
	\*======================================================================*/
	function set_submit_normal()
	{
		$this->_submit_type = "application/x-www-form-urlencoded";
		return $this;
	}




	/*======================================================================*\
		Private functions
	\*======================================================================*/


	/*======================================================================*\
		Function:	_striplinks
		Purpose:	strip the hyperlinks from an html document
		Input:		$document	document to strip.
		Output:		$match		an array of the links
	\*======================================================================*/

	function _striplinks($document)
	{
		preg_match_all("'<\s*a\s.*?href\s*=\s*			# find <a href=
						([\"\'])?					# find single or double quote
						(?(1) (.*?)\\1 | ([^\s\>]+))		# if quote found, match up to next matching
													# quote, otherwise match up to next space
						'isx", $document, $links);


		// catenate the non-empty matches from the conditional subpattern
		$match = '';
		foreach($links[2] as $key=>$val) {
			if (!empty($val))
				$match[] = $val;
		}

		foreach($links[3] as $key=>$val) {
			if (!empty($val))
				$match[] = $val;
		}

		// return the links
		return $match;
	}

	/*======================================================================*\
		Function:	_stripform
		Purpose:	strip the form elements from an html document
		Input:		$document	document to strip.
		Output:		$match		an array of the links
	\*======================================================================*/

	function _stripform($document)
	{
		preg_match_all("'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi", $document, $elements);

		// catenate the matches
		$match = implode("\r\n", $elements[0]);

		// return the links
		return $match;
	}


	/*======================================================================*\
		Function:	_striptext
		Purpose:	strip the text from an html document
		Input:		$document	document to strip.
		Output:		$text		the resulting text
	\*======================================================================*/

	function _striptext($document)
	{

		// I didn't use preg eval (//e) since that is only available in PHP 4.0.
		// so, list your entities one by one here. I included some of the
		// more common ones.

		$search = array("'<script[^>]*?>.*?</script>'si", // strip out javascript
			"'<[\/\!]*?[^<>]*?>'si", // strip out html tags
			"'([\r\n])[\s]+'", // strip out white space
			"'&(quot|#34|#034|#x22);'i", // replace html entities
			"'&(amp|#38|#038|#x26);'i", // added hexadecimal values
			"'&(lt|#60|#060|#x3c);'i",
			"'&(gt|#62|#062|#x3e);'i",
			"'&(nbsp|#160|#xa0);'i",
			"'&(iexcl|#161);'i",
			"'&(cent|#162);'i",
			"'&(pound|#163);'i",
			"'&(copy|#169);'i",
			"'&(reg|#174);'i",
			"'&(deg|#176);'i",
			"'&(#39|#039|#x27);'",
			"'&(euro|#8364);'i", // europe
			"'&a(uml|UML);'", // german
			"'&o(uml|UML);'",
			"'&u(uml|UML);'",
			"'&A(uml|UML);'",
			"'&O(uml|UML);'",
			"'&U(uml|UML);'",
			"'&szlig;'i",
		);
		$replace = array("",
			"",
			"\\1",
			"\"",
			"&",
			"<",
			">",
			" ",
			chr(161),
			chr(162),
			chr(163),
			chr(169),
			chr(174),
			chr(176),
			chr(39),
			chr(128),
			"ä",
			"ö",
			"ü",
			"Ä",
			"Ö",
			"Ü",
			"ß",
		);

		$text = preg_replace($search, $replace, $document);

		return $text;
	}

	/*======================================================================*\
		Function:	_expandlinks
		Purpose:	expand each link into a fully qualified URL
		Input:		$links			the links to qualify
					$URI			the full URI to get the base from
		Output:		$expandedLinks	the expanded links
	\*======================================================================*/

	function _expandlinks($links, $URI){
		preg_match("/^[^\?]+/", $URI, $match);
		$match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|", "", $match[0]);
		$match = preg_replace("|/$|", "", $match);
		$match_part = @parse_url($match);
		$match_root = $match_part["scheme"] . "://" . $match_part["host"];
		$parse_links = @parse_url($links);
		if($parse_links['host']!=$this->host){
			return $links;
		}
		$search = array(
			"|https://" . preg_quote($this->host) . "/|i",
			"|http://" . preg_quote($this->host) . "/|i",
			"|^(\/)|i",
			"|^(?!http://)(?!mailto:)|i",
			"|/\./|",
			"|/[^\/]+/\.\./|"
		);
		$replace = array(
			"",
			"",
			$match_root . "/",
			$match . "/",
			"/",
			"/"
		);
		$expandedLinks = preg_replace($search, $replace, $links);
		$expandedLinks = str_ireplace('/:'.$this->port,':'.$this->port,$expandedLinks);
		return $expandedLinks;
	}

	/*======================================================================*\
		Function:	_httprequest
		Purpose:	go get the http(s) data from the server
		Input:		$url		the url to fetch
					$fp			the current open file pointer
					$URI		the full URI
					$body		body contents to send if any (POST)
		Output:
	\*======================================================================*/

	function _httprequest($url, $fp, $URI, $http_method, $content_type = "", $body = "")
	{
		if(substr($url, 0,5)=='/http'){
			//$url = substr($url, 1);
		}
		$cookie_headers = '';
		//if ($this->passcookies && $this->_redirectaddr){}
		if ($this->passcookies){
			$this->setcookies();
		}

		$URI_PARTS = parse_url($URI);
		if (empty($url)){
			$url = "/";
		}
		$headers = $http_method . " " . $url . " " . $this->_httpversion . "\r\n";
		if (!empty($this->host) && !isset($this->rawheaders['Host'])) {
			$headers .= "Host:" . $this->host;
			//if (!empty($this->port) && $this->port != '80'){
			if (!empty($this->port) && ($this->port != '80' && $this->scheme != 'http')){
				$headers .= ":" . $this->port;
			}
			$headers .= "\r\n";
		}
		if (!empty($this->agent))
			$headers .= "User-Agent:" . $this->agent . "\r\n";
		if (!empty($this->accept))
			$headers .= "Accept:" . $this->accept . "\r\n";
		if ($this->use_gzip) {
			// make sure PHP was built with --with-zlib
			// and we can handle gzipp'ed data
			if (function_exists('gzinflate')) {
				$headers .= "Accept-encoding:gzip\r\n";
			} else {
				trigger_error(
					"use_gzip is on, but PHP was built without zlib support." .
					"  Requesting file(s) without gzip encoding.",
					E_USER_NOTICE);
			}
		}
		if (!empty($this->referer))
			$headers .= "Referer:" . $this->referer . "\r\n";
		if (!empty($this->cookies)) {
			if (!is_array($this->cookies))
				$this->cookies = (array)$this->cookies;

			reset($this->cookies);
			if (count($this->cookies) > 0) {
				$cookie_headers .= 'Cookie:';
				foreach ($this->cookies as $cookieKey => $cookieVal) {
					$cookie_headers .= $cookieKey . "=" . urlencode($cookieVal) . "; ";
				}
				$headers .= substr($cookie_headers, 0, -2) . "\r\n";
			}
		}
		if (!empty($this->rawheaders)) {
			if (!is_array($this->rawheaders))
				$this->rawheaders = (array)$this->rawheaders;
			foreach($this->rawheaders as $headerKey=>$headerVal){
				$headers .= $headerKey . ":" . $headerVal . "\r\n";
			}
		}
		if (!empty($content_type)) {
			$headers .= "Content-type:$content_type";
			if ($content_type == "multipart/form-data")
				$headers .= "; boundary=" . $this->_mime_boundary;
			$headers .= "\r\n";
		}
		if (!empty($body))
			$headers .= "Content-length:" . strlen($body) . "\r\n";
		if (!empty($this->user) || !empty($this->pass))
			$headers .= "Authorization:Basic " . base64_encode($this->user . ":" . $this->pass) . "\r\n";

		//add proxy auth headers
		if (!empty($this->proxy_user))
			$headers .= 'Proxy-Authorization:' . 'Basic ' . base64_encode($this->proxy_user . ':' . $this->proxy_pass) . "\r\n";


		$headers .= "\r\n";
		if($this->isDebug){
			print_r($headers);
		}
		// set the read timeout if needed
		if ($this->read_timeout > 0)
			socket_set_timeout($fp, $this->read_timeout);
		$this->timed_out = false;

		fwrite($fp, $headers . $body, strlen($headers . $body));

		$this->_redirectaddr = false;
		//unset($this->headers);
		$this->headers = array();

		// content was returned gzip encoded?
		$is_gzipped = false;

		while ($currentHeader = fgets($fp, $this->_maxlinelen)) {
			if ($this->read_timeout > 0 && $this->_check_timeout($fp)) {
				$this->status = -100;
				return false;
			}

			if ($currentHeader == "\r\n")
				break;

			// if a header begins with Location: or URI:, set the redirect
			if (preg_match("/^(Location:|URI:)/i", $currentHeader)) {
				#$this->_submit_method = 'GET';
				// get URL portion of the redirect
				preg_match("/^(Location:|URI:)[ ]+(.*)/i", chop($currentHeader), $matches);
				// look for :// in the Location header to see if hostname is included
				if (!preg_match("|\:\/\/|", $matches[2])) {
					// no host in the path, so prepend
					$this->_redirectaddr = $URI_PARTS["scheme"] . "://" . $this->host . ":" . $this->port;
					// eliminate double slash
					if (!preg_match("|^/|", $matches[2])){
						$this->_redirectaddr .= "/" . $matches[2];
					}else{
						$this->_redirectaddr .= $matches[2];
					}
				} else{
					$this->_redirectaddr = $matches[2];
				}
			}

			if (preg_match("|^HTTP/|", $currentHeader)) {
				if (preg_match("|^HTTP/[^\s]*\s(.*?)\s|", $currentHeader, $status)) {
					$this->status = $status[1];
				}
				$this->response_code = $currentHeader;
			}

			if (preg_match("/Content-Encoding: gzip/", $currentHeader)) {
				$is_gzipped = true;
			}

			$this->headers[] = $currentHeader;
		}
		if($this->isDebug){
			echo __LINE__.' Line : ';
			print_r($this->headers);
			echo "\n\n";
		}
		$results = '';
		do {
			$_data = fread($fp, $this->maxlength);
			if (strlen($_data) == 0) {
				break;
			}
			$results .= $_data;
		} while (true);

		// gunzip
		if ($is_gzipped) {
			// per http://www.php.net/manual/en/function.gzencode.php
			$results = substr($results, 10);
			$results = gzinflate($results);
		}

		if ($this->read_timeout > 0 && $this->_check_timeout($fp)) {
			$this->status = -100;
			return false;
		}

		// check if there is a a redirect meta tag

		if (preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i", $results, $match)) {
			$this->_redirectaddr = $this->_expandlinks($match[1], $URI);
		}

		// have we hit our frame depth and is there frame src to fetch?
		if (($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i", $results, $match)) {
			$this->results[] = $results;
			for ($x = 0; $x < count($match[1]); $x++)
				$this->_frameurls[] = $this->_expandlinks($match[1][$x], $URI_PARTS["scheme"] . "://" . $this->host);
		} // have we already fetched framed content?
		elseif (is_array($this->results)){
			$this->results[] = $results;
		}
		// no framed content
		else{
			$this->results = $results;
		}
		
		if($this->formatUrl==true){
			$this->results = $this->perfectUrl($this->results, $this->formatBaseURI);
		}

		return $this;
	}

	/*======================================================================*\
		Function:	setcookies()
		Purpose:	set cookies for a redirection
	\*======================================================================*/

	function setcookies(){
		for($x = 0; $x < count($this->headers); $x++) {
			if (preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $this->headers[$x], $match)){
				$this->cookies[$match[1]] = urldecode($match[2]);
			}
		}
		return $this;
	}


	/*======================================================================*\
		Function:	_check_timeout
		Purpose:	checks whether timeout has occurred
		Input:		$fp	file pointer
	\*======================================================================*/

	function _check_timeout($fp)
	{
		if ($this->read_timeout > 0) {
			$fp_status = socket_get_status($fp);
			if ($fp_status["timed_out"]) {
				$this->timed_out = true;
				return true;
			}
		}
		return false;
	}

	/*======================================================================*\
		Function:	_connect
		Purpose:	make a socket connection
		Input:		$fp	file pointer
	\*======================================================================*/

	function _connect(&$fp)
	{
		if (!empty($this->proxy_host) && !empty($this->proxy_port)) {
			$this->_isproxy = true;

			$host = $this->proxy_host;
			$port = $this->proxy_port;

			if ($this->scheme == 'https') {
				trigger_error("HTTPS connections over proxy are currently not supported", E_USER_ERROR);
				exit;
			}
		} else {
			$host = $this->host;
			$port = $this->port;
		}

		$this->status = 0;

		$context_opts = array();

		if ($this->scheme == 'https') {
			// if cafile or capath is specified, enable certificate
			// verification (including name checks)
			if (isset($this->cafile) || isset($this->capath)) {
				$context_opts['ssl'] = array(
					'verify_peer' => true,
					'CN_match' => $this->host,
					'disable_compression' => true,
				);

				if (isset($this->cafile))
					$context_opts['ssl']['cafile'] = $this->cafile;
				if (isset($this->capath))
					$context_opts['ssl']['capath'] = $this->capath;
			}
					
			$host = 'ssl://' . $host;
		}

		$context = stream_context_create($context_opts);

		if (version_compare(PHP_VERSION, '5.0.0', '>')) {
			if($this->scheme == 'http')
				$host = "tcp://" . $host;
			$fp = stream_socket_client(
				"$host:$port",
				$errno,
				$errmsg,
				$this->_fp_timeout,
				STREAM_CLIENT_CONNECT,
				$context);
		} else {
			$fp = fsockopen(
				$host,
				$port,
				$errno,
				$errstr,
				$this->_fp_timeout,
				$context);
		}

		if ($fp) {
			// socket connection succeeded
			return true;
		} else {
			// socket connection failed
			$this->status = $errno;
			switch ($errno) {
				case -3:
					$this->error = "socket creation failed (-3)";
				case -4:
					$this->error = "dns lookup failure (-4)";
				case -5:
					$this->error = "connection refused or timed out (-5)";
				default:
					$this->error = "connection failed (" . $errno . ")";
			}
			return false;
		}
	}

	/*======================================================================*\
		Function:	_disconnect
		Purpose:	disconnect a socket connection
		Input:		$fp	file pointer
	\*======================================================================*/

	function _disconnect($fp)
	{
		return (fclose($fp));
	}


	/*======================================================================*\
		Function:	_prepare_post_body
		Purpose:	Prepare post body according to encoding type
		Input:		$formvars  - form variables
					$formfiles - form upload files
		Output:		post body
	\*======================================================================*/

	function _prepare_post_body($formvars, $formfiles)
	{
		settype($formvars, "array");
		settype($formfiles, "array");
		$postdata = '';

		if (count($formvars) == 0 && count($formfiles) == 0)
			return;

		switch ($this->_submit_type) {
			case "application/x-www-form-urlencoded":
				reset($formvars);
				foreach($formvars as $key=>$val){
					if (is_array($val) || is_object($val)) {
						foreach($val as $cur_key=>$cur_val) {
							$postdata .= urlencode($key) . "[]=" . urlencode($cur_val) . "&";
						}
					} else
						$postdata .= urlencode($key) . "=" . urlencode($val) . "&";
				}
				break;

			case "multipart/form-data":
				$this->_mime_boundary = "Snoopy" . md5(uniqid(microtime()));

				reset($formvars);
				foreach($formvars as $key=>$val){
					if (is_array($val) || is_object($val)) {
						foreach($val as $cur_key=>$cur_val) {
							$postdata .= "--" . $this->_mime_boundary . "\r\n";
							$postdata .= "Content-Disposition: form-data; name=\"$key\[\]\"\r\n\r\n";
							$postdata .= "$cur_val\r\n";
						}
					} else {
						$postdata .= "--" . $this->_mime_boundary . "\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
						$postdata .= "$val\r\n";
					}
				}

				reset($formfiles);
				foreach($formfiles as $field_name=>$file_names) {
					settype($file_names, "array");
					foreach($file_names as $file_name) {
						if (!is_readable($file_name)) continue;

						$fp = fopen($file_name, "r");
						$file_content = fread($fp, filesize($file_name));
						fclose($fp);
						$base_name = basename($file_name);

						$postdata .= "--" . $this->_mime_boundary . "\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$field_name\"; filename=\"$base_name\"\r\n\r\n";
						$postdata .= "$file_content\r\n";
					}
				}
				$postdata .= "--" . $this->_mime_boundary . "--\r\n";
				break;
		}

		return $postdata;
	}

	/*======================================================================*\
	Function:	getResults
	Purpose:	return the results of a request
	Output:		string results
	\*======================================================================*/

	function getResults()
	{
		return $this->results;
	}
	
	
	/**
	 * 将一个URL转换为完整URL，示例：
	 *		   $srcurl = '/guestbook.php';
	 *		   $baseurl = 'http://www.msphome.cn/index.php/ddd.html';
	 *		   echo format_url($srcurl, $baseurl);
	 *
	 */
	function format_url($srcurl, $baseurl) {
		if(empty($baseurl)){
			return $srcurl;
		}
		$baseinfo = parse_url($baseurl);
		$srcurl = substr($srcurl, 0, 2)=='//'?"{$baseinfo['scheme']}:{$srcurl}":$srcurl;
		$srcurl = (!in_array(substr($srcurl, 0, 1), array('h','/','.'))?('./'.$srcurl):$srcurl);
		$srcinfo = parse_url($srcurl);
		if(isset($srcinfo['scheme'])) {
			return $srcurl;
		}
		$url = $baseinfo['scheme'].'://'.$baseinfo['host'];
		if(substr($srcinfo['path'], 0, 1) == '/') {
			$path = $srcinfo['path'];
		}else{
			$path = dirname($baseinfo['path']).'/'.$srcinfo['path'];
		}
		$rst = array();
		$path_array = explode('/', $path);
		if(!$path_array[0]) {
			$rst[] = '';
		}
		foreach ($path_array AS $key => $dir) {
			if ($dir == '..') {
				if (end($rst) == '..') {
					$rst[] = '..';
				}elseif(!array_pop($rst)) {
					$rst[] = '..';
				}
			}elseif($dir && $dir != '.') {
				$rst[] = $dir;
			}
		}
		if(!end($path_array)) {
			$rst[] = '';
		}
		$url .= implode('/', $rst);
		$url = str_replace('\\', '/', $url);
		$url = str_replace('../', '/', $url);
		$url .= @$srcinfo['query']?"?{$srcinfo['query']}":'';
		$url .= @$srcinfo['fragment']?"#{$srcinfo['fragment']}":'';
		return $url;
	}
	
	//根据内容获取URL
	function get_linkfromStr($str,$url='') {
		#preg_match_all("'<\s*a\s.*?href\s*=\s*([\"\'])?(?(1) (.*?)\\1 | ([^\s\>]+))'isx", $str, $links);
		preg_match_all('@(href|src)=[\'|\"]([^\'|\"]+)[\'|\"]@i', $str, $links);
		// catenate the non-empty matches from the conditional subpattern
		$match=array();
		if( isset($links[2]) && $links[2] ){
			foreach($links[2] as $key=>$val) {
				if (!empty($val) && !stristr($val,'//')){
					$val = $this->format_url($val,$url);
				}
				$match[] = $val;
			}
		}
		if( isset($links[3]) && $links[3] ){
			foreach($links[3] as $key=>$val) {
				if (!empty($val) && !stristr($val,'//')){
					$val = $this->format_url($val,$url);
				}
				$match[] = $val;
			}
		}
		arsort($match);
		$match = array_filter(array_unique($match));
		
		// return the links
		return $match;
	}
	
	#替换内容中的url
	function perfectUrl($html='',$baseurl=''){
		$urls = $this->get_linkfromStr($html,'');	#获取原始URL
		if(empty($urls)){
			return $html;
		}
		$urls1 = array();
		foreach ($urls as $k=>$url){
			if(in_array(trim($url), array('/','#'))){
				unset($urls[$k]);
			}else{
				$urls1[$k] = $this->format_url($url, $baseurl);
			}
		}
		#合并
		$urls = array_combine(array_values($urls), array_values($urls1));
		#排序
		ksort($urls);
		#替换
		if( count($urls)>1 ){
			foreach($urls as $k=>$url){
				$html = str_ireplace(array("'{$k}'", '"'.$k.'"'), array("'{$url}'", '"'.$url.'"'), $html);
			}
		}
		unset($urls,$urls1);
		return $html;
	}
	
	
}
# == Snoopy.php code end ==

# == Tplparse.php code start ==


class Tplparse {
/**
 * 模板语法：
 * {loop $array $key $value}..........{/loop} 循环
 * {loop $array $value}..........{/loop} 循环
 * {if condition}...{elseif condition}..{else}..{/if} if条件语句
 * {$val} 输出变量值
 * {ephp}echo "ok";{/ephp} 运行PHP代码
 * {template file} 包含另外一个模板
 * {layout file} 调用主模板-每个子模板页面只能在头部调用一个主模板文件
 * {layout_content} 主模板中通过此固定语法输出子模板内容
 * 
 * 对象方法:
 *  setTemplateDir($dir)
 *  setReal($real)
 *  setExtName($ext)		#建议设置
 *  setTmpDir($dir)			#必须设置，否则写入编译文件会出现异常
 *  setU(&$dispatcher)
 *  assign($name, $value=null)
 *  getVal($name)
 *  saveHtml($tFile, $html)
 *  display($tFile)
 *  
 *  变量命名约定：
 *  	变量名以‘$’作为前缀修饰符；
 *  	变量名只能以字母或下划线开头；
 *  	变量名只能包含"大小写字母/数字/下划线"；
 *  
 */
	private $tDir; #模板文件目录
	private $tTmpDir; #编译好后的文件目录
	private $tVal;  #模板变量
	private $tFile; #模板文件
	private $tExtName = '.html'; #模板文件的扩展名,例如：''
	private $tContent; #模板内容
	private $uDispatcher; #URL调度器
	private $real = false; #实时编译
	public $exec_php = false;	#是否允许运行php代码
	public $raw_html = '';	#存储模板解析后的内容
	public $debug = false;

	#对象单例模式唯一实例静态成员
	private static $_instance;
	private function __construct(){}
	private function __clone(){}
	#返回唯一实例
	public static function app(){
		if( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * 从模板源代码读取所有变量列表
	 */
	function get_vars($tpl_filename=''){
		$this->tDir = dirname($tpl_filename);
		if(file_exists($tpl_filename)){
			$this->tContent = file_get_contents($tpl_filename);
			$this->parse_Include();
			preg_match_all('@\$([a-z_][0-9a-z_]*)@i', $this->tContent, $match);
			if($match[1]){
				#限制变量唯一
				$match[1] = array_unique( array_values( $match[1] ) );
			}
			
			return (array)$match[1];
		}
		return false;
	}
 
	/**
	 * 设置模板文件目录
	 * @param string $dir
	 */
	public function setTemplateDir($dir) {
		$this->tDir = $dir;
	}
 
	/**
	 * 是否实时编译
	 * @param bool $real
	 */
	public function setReal($real) {
		$this->real = (bool) $real;
	}
 
	/**
	 * 设置模板文件的扩展名
	 * @param string $ext 扩展名
	 */
	public function setExtName($ext) {
		$this->tExtName = $ext;
	}
 
	/**
	 * 临时文件目录
	 * @param string $dir
	 */
	public function setTmpDir($dir) {
		if (!file_exists($dir)) {
			if (!mkdir($dir, 0, true))
				die("tmp dir $dir can't to mkdir");
		}
		$this->tTmpDir = realpath($dir);
	}
 
	/**
	 * URL调度器
	 * @param Dispatcher $dispatcher
	 */
	public function setU(&$dispatcher) {
		if (is_object($dispatcher) && method_exists($dispatcher, 'U')) {
			$this->uDispatcher = $dispatcher;
		}
	}
 
	/**
	 * 注册用户自己的函数
	 * 当$function是string时则是方函数，如果是键值对数组时则键是类名，值是类中的静态方法
	 * @param mix $function 函数
	 */
	public function registerFunction($function) {
		if (is_array($function)) {
			foreach ($function as $key => $value) {
				$this->userFunctions['classes'][$key] = $value;
			}
		} else {
			$this->userFunctions['functions'][] = $function;
		}
	}
 
	/**
	 * 变量赋值
	 * 如果$name是一个键值对的数组，则直接使用对$name数组进行赋值
	 * @param mixed $name 变量名
	 * @param mixed $value 值
	 */
	public function assign($name, $value=null) {
		if (is_array($name)) {
			foreach ($name as $key => $val) {
				$this->tVal[$key] = $val;
			}
		} else {
			$this->tVal[$name] = $value;
		}
	}
 
	/**
	 * 取得模板的变量
	 * @param string $name
	 */
	public function getVal($name) {
		if (isset($this->tVal[$name])) {
			return $this->tVal[$name];
		}else
			return false;
	}
 
	/**
	 * 将运行好后的内容，保存到一个html文件中
	 * @param string $tFile	模板文件路径
	 * @param string $html	解析后存储路径
	 * @param bool $return_html	解析后的源代码
	 */
	public function saveHtml($tFile, $html=null, $return_html=false) {
		ob_start();
		$this->display($tFile);
		$this->raw_html = ob_get_contents();
		# XML-PHP短标签解锁
		$this->raw_html = str_replace('<？xml', '<?xml', $this->raw_html);
		ob_end_clean();
		$this->raw_html = $this->filter_bom_header($this->raw_html);
		
		if($return_html){
			$res = $this->raw_html;
		}else{
			$res = file_put_contents($html, $this->raw_html);
		}
		unlink($this->getTmpFile());
		return $res;
	}
	
	#去除bom头
	public function filter_bom_header($contents=''){
		$contents = trim($contents);
		$charset[1] = substr($contents, 0, 1);
		$charset[2] = substr($contents, 1, 1);
		$charset[3] = substr($contents, 2, 1);
		if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
			$contents = substr($contents, 3);
		}
		return $contents;
	}
 
	/**
	 * 运行并显示模板内容
	 * @param string $tfile
	 */
	public function display($tFile) {
		$this->tFile = $this->parse_TemplatePath($tFile);
		if (!file_exists($this->getTmpFile()) || $this->real) {
			$this->parse();
		}
		extract($this->tVal, EXTR_OVERWRITE);
		include $this->getTmpFile();
	}
	
	######################################
	######################################
	######################################
	
	/**
	 * 编译好后的文件
	 * @return string $filepath
	 */
	private function getTmpFile() {
		$basename = basename($this->tFile);
		$pos = strrpos($basename, '.');
		$tmp = 'tpl_' . substr($basename, 0, $pos) . '.cache';
		return $this->tTmpDir . '/' . $tmp;
	}
 
	private function parse() {
		$this->tContent = file_get_contents($this->tFile);
		# XML-PHP短标签锁定
		$this->tContent = str_replace('<?xml', '<？xml', $this->tContent);
		$this->parse_Include();
		$this->parse_Section();
		$this->parse_Val();
		$this->parse_ephp();

		if (!$this->real) {
			#如果是在非调试环境下，则替换一些没用的内容
			$search = array("/\r?\n/", "/\s{2,}/");
			$repace = array('', '');
			$this->tContent = preg_replace($search, $repace, $this->tContent);
		}
		file_put_contents($this->getTmpFile(), $this->tContent);
	}
	
	private function parse_layout(){
		if( preg_match('@\{layout\s+([^\}]+)\}@', $this->tContent, $match) && isset($match[1]) ){
			$layout = file_get_contents($this->parse_TemplatePath($match[1]));
			$layout_content = str_replace($match[0], '', $this->tContent);
			$this->tContent = str_replace('{layout_content}', $layout_content, $layout);
		}
	}
 
	/**
	 * 解析模板中的子模板
	 */
	private function parse_Include(){
		do{
			$this->parse_layout();
			$this->tContent = preg_replace_callback("/\{template\s+([^}]+)\}/is", array($this, 'call_subtemplate'), $this->tContent);
		}while( preg_match('@\{template\s+([^\}]+)\}@', $this->tContent, $match) && isset($match[1]) );
	}
 
	/**
	 * 获取子模板
	 * @param array $file
	 */
	private function call_subtemplate($file) {
		$file = $file[1];
		return file_get_contents($this->parse_TemplatePath($file));
	}
 
	/**
	 * 解析模板路径
	 * @param string $file
	 * @return string $filepath
	 */
	private function parse_TemplatePath($tFile) {
		$tFile.=$this->tExtName;
		$tFile = $this->tDir.DIRECTORY_SEPARATOR.basename($tFile);
		if (!file_exists($tFile)) {
			die("No template file $tFile");
		} else {
			$tFile = realpath($tFile);
		}
		return $tFile;
	}
 
	/**
	 * 解析变量
	 */
	private function parse_Val() {
		# 常规变量调用
		# {$abc['ddd'][0]}
		$this->tContent = preg_replace("/\{(\\$[^\}]+)\}/is", '<?php echo ($1) ;?>', $this->tContent);
		# 自定义类对象调用
		# Helper::sub_str($row['action'], 32)
		$this->tContent = preg_replace("/\{([0-9a-z_]+::[^\}]{1,32}[^\}]+)\}/is", '<?php echo ($1) ;?>', $this->tContent);
		# 函数调用
		# implode(' ', $server['os'])
		$this->tContent = preg_replace("/\{([0-9a-z_]+\([^\}]+\))\}/is", '<?php echo ($1) ;?>', $this->tContent);
		# 强类型转换
		# (int)$abc['ddd']
		$this->tContent = preg_replace("/\{(\([0-9a-z_]+\)[^\}]+)\}/is", '<?php echo ($1) ;?>', $this->tContent);
	}

	/**
	 * 解析段落
	 */
	private function parse_Section() {
		#逻辑
		$this->tContent = preg_replace("/\{elseif\s*(.+?)\}/is", '<?php } elseif($1) { ?>', $this->tContent);
		$this->tContent = preg_replace("/\{else\}/is", '<?php } else { ?>', $this->tContent);
		#$this->tContent = preg_replace("/\{U\((.+?)\)\}/is", $this->parse_Url('$1'), $this->tContent);
		#循环
		for ($i = 0; $i < 6; $i++) {
			$this->tContent = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/is", '<?php if(is_array($1)) { foreach($1 as $2) { ?>'.'$3<?php } } ?>', $this->tContent);
			$this->tContent = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/is", '<?php if(is_array($1)) { foreach($1 as $2 => $3) { ?>'.'$4<?php } } ?>', $this->tContent);
			$this->tContent = preg_replace("/\{if\s*(.+?)\}(.+?)\{\/if\}/is", '<?php if($1) { ?>'.'$2<?php } ?>', $this->tContent);
		}
	}
 
	#标签处理
	private function stripvtags($expr, $statement='') {
		$expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "$1", $expr));
		$statement = str_replace("\\\"", "\"", $statement);
		return $expr . $statement;
	}
 
	/**
	 * 解析PHP语句
	 * {ephp}echo "ok";{/ephp}
	 */
	private function parse_ephp() {
		if($this->exec_php){
			$this->tContent = preg_replace("/\{(ephp|php|e|p)\}(.+?)\{\/(ephp|php|e|p)\}/is", "<?php $2 ?>", $this->tContent);
		}
	}
 
	/**
	 * 解析URL
	 */
	private function parse_Url($url) {
		if (is_object($this->uDispatcher)) {
			return $this->uDispatcher->U($url);
		} else {
			return $url;
		}
	}

}
# == Tplparse.php code end ==
file_exists('My_EsiteApp.php')?(My_EsiteApp::app()->run()):(EsiteApp::app()->run());

