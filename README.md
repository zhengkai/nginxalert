nginxalert
==========

监控 nginx log 中的状态码和执行时间，来决定是否报警

安装配置
--------

nginx 需做如下配置

    log_format zhengkai '$msec $request_time $request_length $bytes_sent $status $uri';
    access_log /www/log/royal.access.log zhengkai;

在 `config.inc.php` 里指定 nginx pid 文件的位置

需每分钟跑一次 `client/split.sh` 来不断的将 nginx 截断并上传

接收端会将记录存至 MySQL，表结构在 `server/struct.sql`

另外最好能设置每小时跑一次 `server/history.php`，将历史数据存档

使用
----

目前记录的信息比较多，但如何展示是个问题

overview 页的字段说明：

    request : 请求数
	req. rate : 该种请求数占总请求数的百分比
	req./s : 每秒请求数
	time_cost : 响应时间，单位是毫秒
	in / out : 流量

需要关注是 200 的 req. rate，如果这个值有剧烈变化，说明其他返回的代码的比重变大——很可能就是用户端看到了错误的信息<br />
其次就是 200 的 time cost，如果值升高说明 nginx 的响应能力在下降

history 页的项目说明：
	
	rate : 200 和 304 返回所占的比重（只有这两种被认为是“健康的”，该值越大越好）
	avg_time : 平均响应时间，单位是毫秒
	max_time : 每小时里的最大一次的响应时间，单位是毫秒
	num : 请求数
	transfer : 流量 

JSON API
--------

`overview.json.php` 和 `history.json.php` 为上述两页的更详细 JSON 数据

其实这个工具的目的是给预警系统来读的，可以设定一些阀值来报警（如果 rate 低于 97% 或者 200 的 avg. time_cost > 500ms）很可能就是出问题了。但每个网站都是如此的不同，很难找到一个通用的规则来衡量网站的健康状况，所以那两个人类可读的页面只是是了方便定制正确的阀值

报警
----

未完成
