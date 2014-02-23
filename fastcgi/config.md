# fastcgi配置

我们这里说的fastcgi配置专指nginx对fastcgi的配置，fastcgi本身的配置介绍在[fastcgi 安装](install.md)文中有说明。

## nginx的fastcgi模块提供的命令

### [fastcgi_pass](http://tengine.taobao.org/nginx_docs/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_pass)

这个命令是指定将http代理到哪个fastcgi服务端接口。fastcgi_pass后面是填写fastcgi服务端地址的，这个地址可以是域地址，也可以是Uninx-域套接字。

	fastcgi_pass localhost:9000;


	fastcgi_pass unix:/tmp/fastcgi.socket;

这里的设置需要和fastcgi自身配置的listen_address做相应地对应。

比如上面那个例子，listen_addree就应该这么配置：

	<value name="listen_address">/tmp/fastcgi.socket</value>

### [fastcgi_param](http://tengine.taobao.org/nginx_docs/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_param)

这个命令是设置fastcgi请求中的参数，具体设置的东西可以在$_SERVER中获取到。

比如你想要设置当前的机器环境，可以使用`fastcgi_param ENV test;`来设置。

对于php来说，最少需要设置的变量有：

	fastcgi_param SCRIPT_FILENAME /home/www/scripts/php$fastcgi_script_name;
	fastcgi_param QUERY_STRING    $query_string;

对于POST请求，还需要设置：
	fastcgi_param REQUEST_METHOD  $request_method;
	fastcgi_param CONTENT_TYPE    $content_type;
	fastcgi_param CONTENT_LENGTH  $content_length;

fastcgi_param还可以使用if_not_empty进行设置。意思是如果value非空才进行设置。

	fastcgi_param HTTPS   $https if_not_empty;

### [fastcgi_index](http://tengine.taobao.org/nginx_docs/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_index)

这个命令设置了fastcgi默认使用的脚本。就是当SCRIPT_FILENAME没有命中脚本的时候，使用的就是fastcgi_index设置的脚本。

	以上三个命令能组成最基本的fastcgi设置了

	location / {
	  fastcgi_pass   localhost:9000;
	  fastcgi_index  index.php;
	 
	  fastcgi_param  SCRIPT_FILENAME  /home/www/scripts/php$fastcgi_script_name;
	  fastcgi_param  QUERY_STRING     $query_string;
	  fastcgi_param  REQUEST_METHOD   $request_method;
	  fastcgi_param  CONTENT_TYPE     $content_type;
	  fastcgi_param  CONTENT_LENGTH   $content_length;
	}

### fastcgi_hide_header，fastcgi_ignore_headers，fastcgi_pass_header

### fastcgi_cache

这个命令是开启fastcgi的文件缓存。这个缓存可以将动态的页面存为静态的。以提供为加速或者容灾使用。

