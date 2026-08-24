---
title: "MySQL 索引不是越多越好：一次慢查询的回放"
slug: "mysql-index-slow-query-replay"
category: "数据与缓存"
tags: ["MySQL", "索引", "SQL"]
date: "2022-04-09"
excerpt: "用 EXPLAIN、数据分布和写入成本回放一次索引选择，理解联合索引为什么没有生效。"
---

# MySQL 索引不是越多越好：一次慢查询的回放

订单列表按 user_id、status 过滤，再按 created_at 倒序。已有三个单列索引，但 EXPLAIN 显示扫描范围很大。新增 user_id、status、created_at 的联合索引后行数下降，但我仍检查了写入成本、索引页大小和历史数据分布。status 选择性低，user_id 必须放前面，排序字段才能减少 filesort。索引设计是读写取舍，上线后要看慢日志和 performance_schema，而不是只看本地 explain。命令：

~~~sql
EXPLAIN SELECT * FROM orders WHERE user_id=42 AND status='paid' ORDER BY created_at DESC LIMIT 20;
~~~
## 为什么本地 explain 会骗人
本地数据只有几万行时，优化器可能选择全表扫描，开发者就以为索引没用；线上数据量和分布完全不同，又可能选择另一个计划。我把线上脱敏后的统计信息带回测试库，分别看 rows、filtered、key_len 和 Extra，而不是只看 type=range。

联合索引上线前还要估算写入成本。订单表每秒写入很多行，新增索引会增加页分裂和 redo 压力。我先在影子表回放写入，再用不可见索引观察计划，确认收益后才切换。查询也不应该长期 select *，减少回表列能进一步降低 IO。

检查清单：确认真实数据分布；看 rows 与 Extra；验证排序是否 filesort；评估写入和存储成本；用慢日志观察线上 p95；为索引变更准备回滚。



## 计划稳定性也要验证
数据量增长后，优化器可能因为统计信息过期选择另一条路径。我把 ANALYZE TABLE 纳入维护窗口，并在发布前保存基线计划。生产变更后用 digest 观察同一类 SQL 的 rows_examined，防止只看一次手工查询。

~~~sql
SELECT DIGEST_TEXT, AVG_ROWS_EXAMINED FROM performance_schema.events_statements_summary_by_digest ORDER BY AVG_ROWS_EXAMINED DESC LIMIT 10;
~~~

如果联合索引只服务一个页面，却让所有写入都变慢，就不值得保留。索引必须和业务访问模式一起评审，页面下线时也要清理。


我还把查询样本和索引变更记录放在同一个变更单里，包含数据量、计划、写入 QPS 和回滚方式。这样半年后再看，不会只剩一句“这个索引当时有效”。数据库性能属于持续变化的系统，索引不是一次性装修。


还要注意索引和排序规则、字符集、隐式类型转换这些细节。一次字符串数字比较就可能让索引失效，代码里传参类型必须和列类型一致。上线前我会用真实参数分布跑多组样本，而不是只拿一个最理想的 user_id 做演示。 


最后还要看删除索引的风险：先确认没有其他查询依赖，再用不可见索引观察一段时间。索引清理也应有回滚窗口和监控，避免为了整洁误删仍在使用的路径。 


在实际维护中，我会把这类问题保留一份最小复现和一份线上证据。最小复现用来验证修复，线上证据用来确认修复没有改变其他路径。发布前再检查日志、指标、回滚和权限四件事，很多看似完成的改动，最后都卡在其中一件。
