nginxalert
==========

监控 nginx log 中的状态码和执行时间，来决定是否报警

nginx 需做如下配置

    log_format zhengkai '$body_bytes_sent $bytes_sent $connection $msec $request_length $request_time $status';
    access_log /www/log/royal.access.log zhengkai;

在 `config.inc.php` 里指定 nginx pid 文件的位置
