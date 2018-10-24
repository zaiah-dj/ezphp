
select * from

	( select * from posts ) as p
	inner join
	( select * from content where sort_order = 0 ) as c 
	on p.posts_id = c.rel_id;
