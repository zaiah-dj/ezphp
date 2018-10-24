select * from
	( select * from posts ) as p
	inner join
	( select * from content where rel_id = 2 ) as c 
	on p.posts_id = c.rel_id order by sort_order;
