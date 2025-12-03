<?php
/**
 * 应用基础接口
 * 
 * 应用基础接口,该接口包含4个接口<i>run,getRequest,getResponse,getWindFactory</i>,自定义应用类型需要实现该接口.
 * 基础实现有<i>WindWebApplication</i>
 * @author Qiong Wu <papa0924@gmail.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id: AbstractWindApplication.php 3919 2013-01-25 03:09:56Z yishuo $
 * @package base
 */
abstract class AbstractWindApplication extends WindModule {
	/**
	 * 请求对象
	 *
	 * @var WindHttpRequest
	 */
	protected $request;
	
	/**
	 * 响应对象
	 *
	 * @var WindHttpResponse
	 */
	protected $response;
	
	/**
	 * 组建工厂对象
	 *
	 * @var WindFactory
	 */
	protected $factory = null;
	
	/**
	 * 路由对象
	 *
	 * @var WindRouter
	 */
	protected $handlerAdapter = null;

	/**
	 * 应用初始化操作
	 *
	 * @param WindHttpRequest $request
	 * @param WindHttpResponse $response
	 * @param WindFactory $factory
	 */
	public function __construct($request, $response, $factory) {
		$this->response = $response;
		$this->request = $request;
		$this->factory = $factory;
	}

	/**
	 * 请求处理完毕后，进一步分发
	 * 
	 * @param WindForward $forward        
	 * @param boolean $display        
	 */
	abstract public function doDispatch($forward);

	/**
	 * 处理错误请求
	 * 根据错误请求的相关信息,将程序转向到错误处理句柄进行错误处理
	 *
	 * @param WindErrorMessage $errorMessage
	 * @param int $errorcode
	 * @return void
	 */
	abstract protected function sendErrorMessage($errorMessage, $errorcode);
	
	/*
	 * (non-PHPdoc) @see IWindApplication::run()
	 */
	public function run($handlerAdapter = null) {
		$handlerAdapter !== null && $this->handlerAdapter = $handlerAdapter;
		$module = $this->getModules();
		$handlerPath = $module['controller-path'] . '.' . ucfirst($this->handlerAdapter->getController()) . $module['controller-suffix'];
		$className = Wind::import($handlerPath);
		if (!class_exists($className)) throw new WindException(
			'Your requested \'' . $handlerPath . '\' was not found on this server.', 404);
		$handler = new $className();
		$handler->setDelayAttributes(
			array('errorMessage' => array('ref' => 'errorMessage'), 'forward' => array('ref' => 'forward')));
		
		$handlerAdapter !== null && $this->resolveActionFilters($handler);
		
		try {
			$forward = $handler->doAction($this->handlerAdapter);
			$this->doDispatch($forward);
		} catch (WindForwardException $e) {
			$this->doDispatch($e->getForward());
		} catch (WindActionException $e) {
			$this->sendErrorMessage(($e->getError() ? $e->getError() : $e->getMessage()), $e->getCode());
		} catch (WindException $e) {
			$this->sendErrorMessage($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * 添加module配置
	 * <code>
	 * <controller-path>controller</controller-path>
	 * <!-- 指定该模块下的controller的后缀格式 -->
	 * <controller-suffix>Controller</controller-suffix>
	 * <!-- 配置该模块的error处理的action controller类 -->
	 * <error-handler>WIND:web.WindErrorHandler</error-handler>
	 * <!-- 试图相关配置，config中配置可以根据自己的需要进行配置或是使用缺省 -->
	 * <!-- 可以在这里进行view的配置，该配置只会影响该module下的view行为，该配置可以设置也可以不设置 -->
	 * <!-- 指定模板路径 -->
	 * <template-dir>template</template-dir>
	 * <!-- 指定模板后缀 -->
	 * <template-ext>htm</template-ext></code>
	 * 
	 * @param string $name
	 *        module名称
	 * @param array $config
	 *        配置数组
	 * @param boolean $replace
	 *        如果module已经存在是否覆盖他 默认值为false不进行覆盖
	 * @return array
	 */
	public function setModules($name, $config, $replace = false) {
		if ($replace || !isset($this->_config['modules'][$name])) {
			$this->_config['modules'][$name] = (array) $config;
		}
		return $this->_config['modules'][$name];
	}

	/**
	 * 获得module配置,$name为空时返回当前module配置
	 * 
	 * @param string $name
	 *        module名称 默认为空
	 * @param boolean $merge
	 *        合并默认值
	 * @return array
	 * @throws WindActionException
	 * @throws WindException
	 */
	public function getModules($name = '') {
		if ($name === '') $name = $this->handlerAdapter->getModule();
		if ($name === 'pattern') $name = $this->handlerAdapter->getDefaultModule();
		$_module = $this->getConfig('modules', $name, array());
		if (!isset($_module['_verified']) || $_module['_verified'] !== true) {
			if (empty($_module) && !empty($this->_config['modules']['pattern'])) {
				$_module = $this->_config['modules']['pattern'];
			}
			$_flag = empty($_module);
			$_module = WindUtility::mergeArray($this->_config['modules']['default'], $_module);
			$_module_str = implode('#', $_module);
			if (strpos($_module_str, '{') !== false) {
				preg_match_all('/{(\w+)}/i', $_module_str, $matches);
				if (!empty($matches[1])) {
					$_replace = array();
					foreach ($matches[1] as $key => $value) {
						if ($value === $this->handlerAdapter->getModuleKey())
							$_replace['{' . $value . '}'] = $this->handlerAdapter->getModule();
						elseif ($value === $this->handlerAdapter->getControllerKey())
							$_replace['{' . $value . '}'] = $this->handlerAdapter->getController();
						elseif ($value === $this->handlerAdapter->getActionKey())
							$_replace['{' . $value . '}'] = $this->handlerAdapter->getAction();
						else
							$_replace['{' . $value . '}'] = $this->request->getGet($value);
					}
					$_module_str = strtr($_module_str, $_replace);
					$_module = array_combine(array_keys($_module), explode('#', $_module_str));
				}
			} elseif ($_flag)
				throw new WindException('Your request was not found on this server.', 404);
			
			$_module['_verified'] = true;
			$this->_config['modules'][$name] = $_module;
		}
		return $_module;
	}

	/**
	 * 手动注册actionFilter
	 * 参数为数组格式：
	 * 
	 * @param array $filters        
	 */
	public function registeActionFilter($filters) {
		$this->resolveActionFilters($filters, true);
	}
	
	/**
	 * 加载actionFilters
	 * 
	 * @param WindAction|array $handler
	 * @param boolean $isRegiste
	 */
	protected function resolveActionFilters($handler, $isRegiste = false) {
		$_filters = $this->getModules();
		if ($isRegiste) {
			$_filters = $handler;
		} else {
			$_filters = isset($_filters['filter']) ? $_filters['filter'] : array();
			if ($handler instanceof WindAction) {
				$_filters = WindUtility::mergeArray($_filters, $handler->getActionFilters());
			}
		}
		if (!$_filters) return;
		
		$chain = WindFactory::createInstance('WindHandlerInterceptorChain');
		if ($chain === null) return;
		
		foreach ($_filters as $value) {
			if (!isset($value['class'])) continue;
			$args = isset($value['args']) ? $value['args'] : array();
			$chain->addInterceptors(WindFactory::createInstance(Wind::import($value['class']), $args));
		}
		$chain->getHandler()->handle();
	}
	
	/**
	 * 创建应用控制器
	 * 
	 * @param array $config
	 * @param WindFactory $factory
	 */
	protected function createApplication($config, $factory) {
		$className = Wind::import($config['class']);
		return new $className($this->request, $this->response, $factory);
	}
	
	/**
	 * @return WindHttpRequest
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * @return WindHttpResponse
	 */
	public function getResponse() {
		return $this->response;
	}
	
	/**
	 * @return WindFactory
	 */
	public function getFactory() {
		return $this->factory;
	}

}
