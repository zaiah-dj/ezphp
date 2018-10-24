select * from
	(select * from posts where posts_id = 2) as p
	inner join
	(select * from authors) as a
	on p.author_rel = a.authors_id
